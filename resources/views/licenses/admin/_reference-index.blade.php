@php
    $url=static fn($name,$params=[])=>\Illuminate\Support\Facades\Route::has($name)?route($name,$params):'#';
    $items=$items ?? collect();
    $resourceRoute='modules.licenses.admin.'.$resource;
@endphp
@include('licenses.partials.page-header',[
    'title'=>$resourceLabel,'subtitle'=>__('licenses.admin.subtitle'),'icon'=>$resourceIcon ?? 'bi-list-ul',
    'actions'=>new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e($url('modules.licenses.admin.index')).'"><i class="bi bi-arrow-left"></i>'.e(__('licenses.back')).'</a><a class="lic-btn lic-btn--primary" href="'.e($url($resourceRoute.'.create')).'"><i class="bi bi-plus-lg"></i>'.e(__('licenses.admin.add',['item'=>$resourceLabel])).'</a>'),
])
@include('licenses.partials.feedback')
<section class="lic-panel">
    <div class="lic-table-wrap"><table class="lic-table"><thead><tr><th>{{ __('licenses.fields.name_ar') }}</th><th>{{ __('licenses.fields.name_en') }}</th><th>{{ __('licenses.fields.ranking') }}</th><th>{{ __('licenses.fields.publish') }}</th><th>{{ __('licenses.actions') }}</th></tr></thead><tbody>
    @forelse($items as $item)
        <tr><td>{{ $item->name_ar }}</td><td>{{ $item->name_en ?: '—' }}</td><td>{{ (int)($item->ranking ?? 0) }}</td><td><span class="lic-status {{ (int)$item->publish===1?'lic-status--active':'lic-status--expired' }}">{{ (int)$item->publish===1?__('licenses.enabled'):__('licenses.disabled') }}</span></td><td><div class="lic-table__actions"><a class="lic-btn lic-btn--sm" href="{{ $url($resourceRoute.'.edit',$item->getRouteKey()) }}"><i class="bi bi-pencil"></i>{{ __('licenses.edit') }}</a><form method="POST" action="{{ $url($resourceRoute.'.publish',$item->getRouteKey()) }}">@csrf @method('PATCH')<input type="hidden" name="publish" value="{{ (int)$item->publish===1?0:1 }}"><button class="lic-btn lic-btn--sm" type="submit">{{ __('licenses.admin.toggle') }}</button></form></div></td></tr>
    @empty<tr><td colspan="5" class="lic-empty">{{ __('licenses.admin.empty') }}</td></tr>@endforelse
    </tbody></table></div>
    @if(method_exists($items,'links') && $items->total()>0)<div class="lic-pagination">{{ $items->withQueryString()->links('pagination.hm') }}</div>@endif
</section>
