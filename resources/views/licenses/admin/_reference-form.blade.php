@php
    $url=static fn($name,$params=[])=>\Illuminate\Support\Facades\Route::has($name)?route($name,$params):'#';
    $record=$item ?? null;
    $resourceRoute='modules.licenses.admin.'.$resource;
    $editing=(bool)$record;
@endphp
@include('licenses.partials.page-header',[
    'title'=>$editing?__('licenses.admin.edit',['item'=>$resourceLabel]):__('licenses.admin.add',['item'=>$resourceLabel]),'subtitle'=>__('licenses.admin.subtitle'),'icon'=>'bi-pencil-square',
    'actions'=>new \Illuminate\Support\HtmlString('<a class="lic-btn" href="'.e($url($resourceRoute.'.index')).'"><i class="bi bi-arrow-left"></i>'.e(__('licenses.back')).'</a>'),
])
@include('licenses.partials.feedback')
<section class="lic-panel"><form method="POST" action="{{ $editing?$url($resourceRoute.'.update',$record->getRouteKey()):$url($resourceRoute.'.store') }}" novalidate>@csrf @if($editing)@method('PUT')@endif
    <div class="lic-form-grid">
        <div class="lic-field"><label for="name_ar">{{ __('licenses.fields.name_ar') }} <span class="lic-required">*</span></label><input id="name_ar" name="name_ar" value="{{ old('name_ar',$record?->name_ar) }}" maxlength="255" required dir="rtl" class="form-control @error('name_ar') is-invalid @enderror">@error('name_ar')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="lic-field"><label for="name_en">{{ __('licenses.fields.name_en') }} <span class="lic-required">*</span></label><input id="name_en" name="name_en" value="{{ old('name_en',$record?->name_en) }}" maxlength="255" required dir="ltr" class="form-control @error('name_en') is-invalid @enderror">@error('name_en')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="lic-field"><label for="ranking">{{ __('licenses.fields.ranking') }}</label><input id="ranking" type="number" name="ranking" min="0" value="{{ old('ranking',$record?->ranking ?? 0) }}" class="form-control @error('ranking') is-invalid @enderror">@error('ranking')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        <div class="lic-field lic-field--span-2"><label class="lic-checkbox" for="publish"><input id="publish" type="checkbox" name="publish" value="1" @checked((bool)old('publish',$record?->publish ?? 1))><span>{{ __('licenses.fields.publish') }}</span></label></div>
    </div>
    <div class="lic-form-actions"><button class="lic-btn lic-btn--primary" type="submit"><i class="bi bi-check-lg"></i>{{ $editing?__('licenses.save_changes'):__('licenses.save') }}</button><a class="lic-btn" href="{{ $url($resourceRoute.'.index') }}">{{ __('licenses.cancel') }}</a></div>
</form></section>
