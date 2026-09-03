<?php

namespace App\Services\Auth;

use Illuminate\Support\Collection;

class PermissionRegistry
{
    /** @return Collection<int, array<string, mixed>> */
    public function categories(): Collection
    {
        return collect(config('permissions.categories', []))->sortBy('order')->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    public function permissions(): Collection
    {
        return collect($this->definitions())
            ->map(fn (array $permission, string $code): array => $permission + ['code' => $code])
            ->sortBy([['category_order', 'asc'], ['order', 'asc'], ['code', 'asc']])
            ->values();
    }

    public function has(string $code): bool
    {
        return isset($this->definitions()[$code]);
    }

    public function canonical(string $code): string
    {
        $code = trim($code);
        foreach ($this->definitions() as $canonical => $definition) {
            if ($canonical === $code || in_array($code, $definition['legacy_aliases'] ?? [], true)) {
                return $canonical;
            }
        }

        return $code;
    }

    /** @return list<string> */
    public function storageCodes(string $code): array
    {
        $canonical = $this->canonical($code);
        $definition = $this->definitions()[$canonical] ?? [];

        return array_values(array_unique(array_filter([
            $canonical,
            ...($definition['legacy_aliases'] ?? []),
        ])));
    }

    /** @return list<string> */
    public function codesForRoute(?string $routeName): array
    {
        if ($routeName === null || $routeName === '') {
            return [];
        }

        $explicit = $this->permissions()
            ->filter(fn (array $permission): bool => in_array($routeName, $permission['routes'] ?? [], true))
            ->pluck('code')
            ->values()
            ->all();
        if ($explicit !== []) {
            return $explicit;
        }

        foreach (config('permissions.modules', []) as $prefix => $module) {
            if (str_starts_with($routeName, $prefix)) {
                $actions = $module['actions'] ?? ['view'];
                $action = $this->actionForRoute($routeName);
                if (! in_array($action, $actions, true)) {
                    $action = in_array('process', $actions, true) ? 'process' : 'view';
                }

                return [$module['code'].'.'.$action];
            }
        }

        return [];
    }

    /** @return array<string, array<string, mixed>> */
    private function definitions(): array
    {
        $definitions = config('permissions.items', []);
        $actionLabels = ['view' => 'عرض', 'create' => 'إضافة', 'process' => 'معالجة وتعديل', 'delete' => 'حذف', 'export' => 'طباعة وتنزيل'];
        foreach (config('permissions.modules', []) as $module) {
            foreach ($module['actions'] ?? ['view'] as $action) {
                if (! isset($actionLabels[$action])) {
                    continue;
                }
                $actionLabel = $actionLabels[$action];
                $code = $module['code'].'.'.$action;
                $definitions[$code] ??= [
                    'category' => $module['category'], 'category_order' => $module['category_order'],
                    'label' => $actionLabel.' '.$module['label'], 'description' => $module['description'],
                    'icon' => $module['icon'], 'order' => $module['order'] + array_search($action, ['view', 'create', 'process', 'delete', 'export'], true),
                    'routes' => [], 'legacy_aliases' => [],
                ];
            }
        }
        return $definitions;
    }

    private function actionForRoute(string $routeName): string
    {
        if (preg_match('/(destroy|\.delete$)/', $routeName)) return 'delete';
        if (preg_match('/(pdf|print|export|download|document|attachment)/', $routeName)) return 'export';
        if (preg_match('/(create|store$)/', $routeName)) return 'create';
        if (preg_match('/(status|process|reply|approve|activate|confirm|receive|received|refuse|transition|update|edit|publish|close|decision|paid|toggle|action|remind)/', $routeName)) return 'process';
        return 'view';
    }
}
