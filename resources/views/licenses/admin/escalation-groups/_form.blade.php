@php $record=$group ?? null;$editing=(bool)$record; @endphp
<div class="lic-form-grid">
    <div class="lic-field lic-field--span-2"><label for="name">{{ __('licenses.admin.group_name') }} <span class="lic-required">*</span></label><input id="name" name="name" value="{{ old('name',$record?->name) }}" required maxlength="255" class="form-control @error('name') is-invalid @enderror">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
    <div class="lic-field lic-field--span-2"><label class="lic-checkbox" for="publish"><input id="publish" type="checkbox" name="publish" value="1" @checked((bool)old('publish',$record?->publish ?? 1))><span>{{ __('licenses.fields.publish') }}</span></label></div>
</div>
