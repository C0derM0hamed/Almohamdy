<?php

namespace App\Services\Publications;

use App\Services\Auth\PermissionService;
use App\Support\ProtectedFileDownload;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PublicationService
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function authorizeScope(): void
    {
        abort_unless((int) session('hr_branch_id', 0) > 0 && (int) session('companies_groups_id', 0) > 0, 403);
    }

    public function list(array $filters): LengthAwarePaginator
    {
        $this->authorizeScope();
        $query = DB::table('new_post as p')
            ->leftJoin('post_type as t', 't.id', '=', 'p.post_type_id')
            ->leftJoin('branches as b', 'b.id', '=', 'p.branch_id')
            ->where('p.companies_groups_id', $this->companyId())
            ->select('p.*', 't.name_ar as type_name_ar', 'b.name_ar as branch_name_ar')
            ->orderByDesc('p.id');
        if (! $this->permissions->isAdmin()) $query->where('p.branch_id', $this->branchId());
        return $query->when($filters['type_id'] > 0, fn ($q) => $q->where('p.post_type_id', $filters['type_id']))
            ->when($filters['search'] !== '', fn ($q) => $q->where(function ($inner) use ($filters): void { $inner->where('p.subject_ar', 'like', '%'.$filters['search'].'%')->orWhere('p.subject_en', 'like', '%'.$filters['search'].'%'); }))
            ->paginate(15)->withQueryString();
    }

    public function types(): \Illuminate\Support\Collection
    {
        $this->authorizeScope();
        return DB::table('post_type')->where('publish', 1)->orderBy('name_ar')->get();
    }

    public function branches(): \Illuminate\Support\Collection
    {
        $this->authorizeScope();
        $query = DB::table('branches')
            ->where('companies_groups_id', $this->companyId())
            ->when(Schema::hasColumn('branches', 'publish'), fn ($query) => $query->where('publish', 1))
            ->orderBy('name_ar');
        if (! $this->permissions->isAdmin()) $query->where('id', $this->branchId());
        return $query->get();
    }

    public function create(array $data, ?UploadedFile $file): int
    {
        $this->authorizeScope();
        $validBranches = $this->branches()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $branchIds = array_values(array_unique(array_map('intval', $data['branch_ids'])));
        abort_unless($branchIds !== [] && array_diff($branchIds, $validBranches) === [], 403);
        abort_unless($this->types()->contains('id', (int) $data['post_type_id']), 422);
        $path = $file?->store('publications', 'public') ?: '';
        $lastId = 0;
        foreach ($branchIds as $branchId) {
            $lastId = (int) DB::table('new_post')->insertGetId([
                'branch_id' => $branchId, 'post_type_id' => (int) $data['post_type_id'],
                'subject_ar' => trim($data['subject_ar']), 'subject_en' => trim($data['subject_en'] ?? ''),
                'post_ar' => trim($data['post_ar']), 'post_en' => trim($data['post_en'] ?? ''),
                'uploaded_file' => $path, 'companies_groups_id' => $this->companyId(), 'created_by' => (int) session('hr_user_id', 0),
            ]);
        }
        return $lastId;
    }

    public function find(int $id): ?object
    {
        $this->authorizeScope();
        $query = DB::table('new_post as p')->leftJoin('post_type as t', 't.id', '=', 'p.post_type_id')->leftJoin('branches as b', 'b.id', '=', 'p.branch_id')->where('p.id', $id)->where('p.companies_groups_id', $this->companyId())->select('p.*', 't.name_ar as type_name_ar', 'b.name_ar as branch_name_ar');
        if (! $this->permissions->isAdmin()) $query->where('p.branch_id', $this->branchId());
        return $query->first();
    }

    public function download(int $id): mixed
    {
        $post = $this->find($id);
        abort_if($post === null, 404);
        return app(ProtectedFileDownload::class)->download($post->uploaded_file, 'publication-'.$id, []);
    }

    private function branchId(): int { return (int) session('hr_branch_id', 0); }
    private function companyId(): int { return (int) session('companies_groups_id', 0); }
}
