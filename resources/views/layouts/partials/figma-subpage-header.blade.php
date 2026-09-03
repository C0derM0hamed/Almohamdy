@php
    $subtitle = $subtitle ?? '';
    $iconSize = $iconSize ?? 32;
    $crumbs = $crumbs ?? array_values(array_filter([
        ['label' => __('dashboard.modules')],
        !empty($parentLabel) ? ['label' => $parentLabel, 'url' => $parentUrl ?? null] : null,
        ['label' => $title],
    ]));
@endphp
@include('layouts.partials.figma-module-header', [
    'crumbs' => $crumbs,
    'title' => $title,
    'subtitle' => $subtitle,
    'heroIconSrc' => $icon ?? '',
    'heroIconSize' => $iconSize,
])
