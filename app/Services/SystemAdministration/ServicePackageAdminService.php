<?php

namespace App\Services\SystemAdministration;

use App\Models\ServicePackage;
use App\Repositories\SystemAdministration\ServicePackageAdminRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ServicePackageAdminService
{
    public function __construct(
        private readonly ServicePackageAdminRepository $repository,
    ) {}

    /**
     * @return array{total: int, published: int, unpublished: int}
     */
    public function dashboardSummary(): array
    {
        return $this->repository->dashboardCounts($this->companyGroupId());
    }

    public function listPaginated(string $search, ?int $sectionId, ?string $publish): LengthAwarePaginator
    {
        $perPage = (int) config('hm.system_administration.per_page', 15);

        return $this->repository->paginateFiltered(
            $search,
            $sectionId,
            $publish,
            $this->companyGroupId(),
            $perPage,
        );
    }

    public function findForEdit(int $id): ?ServicePackage
    {
        return $this->repository->findForEdit($id, $this->companyGroupId());
    }

    /**
     * @return array<int, string>
     */
    public function sectionOptions(): array
    {
        $options = [];
        $labels = config('hm.hospital_services.section_nav_labels', []);

        if (! is_array($labels) || $labels === []) {
            return $options;
        }

        $locale = app()->getLocale() === 'ar' ? 'ar' : 'en';
        $order = config('hm.hospital_services.section_nav_order', []);
        $children = config('hm.hospital_services.section_nav_children', []);

        $ids = [];

        if (is_array($order)) {
            foreach ($order as $id) {
                $ids[] = (int) $id;

                if (is_array($children) && isset($children[$id]) && is_array($children[$id])) {
                    foreach ($children[$id] as $childId) {
                        $ids[] = (int) $childId;
                    }
                }
            }
        }

        foreach (array_keys($labels) as $id) {
            $ids[] = (int) $id;
        }

        foreach (array_unique($ids) as $id) {
            $entry = $labels[$id] ?? null;

            if (! is_array($entry)) {
                continue;
            }

            $label = trim((string) ($entry[$locale] ?? $entry['ar'] ?? $entry['en'] ?? ''));

            if ($label !== '') {
                $options[$id] = $label;
            }
        }

        return $options;
    }

    /** @param array<string, mixed> $attributes @param array<int, UploadedFile> $files */
    public function create(array $attributes, array $files = []): ServicePackage
    {
        $attributes['companies_groups_id'] = $this->companyGroupId();
        $attributes['created_by'] = (int) session('hr_user_id', 0);
        $attributes['updated_by'] = (int) session('hr_user_id', 0);
        $attributes['publish'] = array_key_exists('publish', $attributes) ? (int) (bool) $attributes['publish'] : 1;
        $package = $this->repository->create($attributes);
        $this->uploadAttachments($package, $files);

        return $package;
    }

    /** @param array<string, mixed> $attributes @param array<int, UploadedFile> $files */
    public function update(ServicePackage $package, array $attributes, array $files = []): ServicePackage
    {
        $attributes['updated_by'] = (int) session('hr_user_id', 0);
        $attributes['publish'] = array_key_exists('publish', $attributes) ? (int) (bool) $attributes['publish'] : 0;

        $updated = $this->repository->update($package, $attributes);
        $this->uploadAttachments($updated, $files);

        return $updated;
    }

    public function togglePublish(ServicePackage $package): ServicePackage
    {
        return $this->repository->togglePublish(
            $package,
            (int) session('hr_user_id', 0),
        );
    }

    public function delete(ServicePackage $package): void
    {
        foreach ($package->attachments()->get() as $attachment) {
            $this->deleteStoredAttachment((string) $attachment->file_name);
        }
        $this->repository->delete($package);
    }

    /** @return \Illuminate\Support\Collection<int, \App\Models\ServicePackageAttachment> */
    public function attachments(ServicePackage $package)
    {
        return $package->attachments()->get();
    }

    /** @param array<int, UploadedFile> $files */
    public function uploadAttachments(ServicePackage $package, array $files): void
    {
        if (! Schema::hasTable('service_packages_attachments')) {
            return;
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }
            $path = $file->store('service-packages', 'public');
            DB::table('service_packages_attachments')->insert(array_intersect_key([
                'service_packages_id' => $package->id,
                'file_name' => $path,
                'created_by' => (int) session('hr_user_id', 0),
                'created_at' => now(),
            ], array_flip(Schema::getColumnListing('service_packages_attachments'))));
        }
    }

    public function deleteAttachment(ServicePackage $package, int $attachmentId): void
    {
        abort_unless(Schema::hasTable('service_packages_attachments'), 404);
        $attachment = DB::table('service_packages_attachments')
            ->where('id', $attachmentId)
            ->where('service_packages_id', $package->id)
            ->first();
        abort_if($attachment === null, 404);
        $this->deleteStoredAttachment((string) ($attachment->file_name ?? ''));
        DB::table('service_packages_attachments')->where('id', $attachmentId)->delete();
    }

    public function downloadAttachment(ServicePackage $package, int $attachmentId): array
    {
        abort_unless(Schema::hasTable('service_packages_attachments'), 404);
        $attachment = DB::table('service_packages_attachments')
            ->where('id', $attachmentId)
            ->where('service_packages_id', $package->id)
            ->first();
        abort_if($attachment === null, 404);
        $path = trim((string) ($attachment->file_name ?? ''));
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            return [Storage::disk('public')->path($path), basename($path)];
        }
        $legacy = public_path('files/'.basename($path));
        abort_unless($path !== '' && is_file($legacy), 404);

        return [$legacy, basename($legacy)];
    }

    public function import(int $sectionId, UploadedFile $file): int
    {
        abort_unless(Schema::hasTable('services_sections') && DB::table('services_sections')->where('id', $sectionId)->exists(), 422, 'القسم غير موجود.');
        $count = 0;
        foreach ($this->spreadsheetRows($file) as $row) {
            $code = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[2] ?? $row[1] ?? ''));
            if ($code === '' && $name === '') {
                continue;
            }
            if (strtolower($code) === 'code' || in_array(mb_strtolower($name), ['الاسم', 'name'], true)) {
                continue;
            }
            $this->create([
                'service_id' => $sectionId,
                'code1' => $code,
                'name_ar' => $name,
                'name_en' => $name,
                'notice_ar' => (string) ($row[3] ?? ''),
                'notice_en' => (string) ($row[3] ?? ''),
                'price' => (string) ($row[1] ?? ''),
                'notice1_ar' => (string) ($row[4] ?? ''),
                'notice1_en' => (string) ($row[4] ?? ''),
                'service_details' => (string) ($row[5] ?? ''),
                'consultation_discount' => (string) ($row[6] ?? ''),
                'lab_x_rays_discount' => (string) ($row[7] ?? ''),
                'operations_hypnosis_discount' => (string) ($row[8] ?? ''),
                'delivery_discount' => (string) ($row[9] ?? ''),
                'publish' => 1,
            ]);
            $count++;
        }

        return $count;
    }

    private function companyGroupId(): ?int
    {
        $groupId = (int) session('companies_groups_id', 0);

        return $groupId > 0 ? $groupId : null;
    }

    private function deleteStoredAttachment(string $path): void
    {
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** @return list<array<int, string>> */
    private function spreadsheetRows(UploadedFile $file): array
    {
        $path = (string) $file->getRealPath();
        if (strtolower($file->getClientOriginalExtension()) !== 'xlsx') {
            $handle = fopen($path, 'rb');
            abort_if($handle === false, 422, 'تعذر قراءة ملف الاستيراد.');
            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = array_map(static fn ($value): string => trim((string) $value), $row);
            }
            fclose($handle);

            return $rows;
        }

        $zip = new \ZipArchive;
        abort_if($zip->open($path) !== true, 422, 'ملف XLSX غير صالح.');
        $shared = [];
        if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
            $node = @simplexml_load_string($xml);
            if ($node !== false) {
                $node->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                foreach ($node->xpath('//a:si') ?: [] as $item) {
                    $shared[] = trim(implode('', array_map('strval', $item->xpath('.//a:t') ?: [])));
                }
            }
        }
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        abort_if($sheetXml === false, 422, 'لا توجد ورقة بيانات في ملف XLSX.');
        $sheet = @simplexml_load_string($sheetXml);
        abort_if($sheet === false, 422, 'تعذر تحليل ملف XLSX.');
        $sheet->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        foreach ($sheet->xpath('//a:sheetData/a:row') ?: [] as $rowNode) {
            $values = [];
            foreach ($rowNode->xpath('./a:c') ?: [] as $cell) {
                $value = (string) ($cell->v ?? '');
                if ((string) ($cell['t'] ?? '') === 's') {
                    $value = $shared[(int) $value] ?? '';
                } elseif ((string) ($cell['t'] ?? '') === 'inlineStr') {
                    $value = trim(implode('', array_map('strval', $cell->xpath('.//a:t') ?: [])));
                }
                $values[] = trim($value);
            }
            $rows[] = $values;
        }

        return $rows;
    }
}
