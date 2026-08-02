<form method="get" class="card card-body border-0 shadow-sm mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-3"><label class="form-label" for="from">من تاريخ</label><input class="form-control" id="from" name="from" type="date" value="{{ request('from') }}"></div>
        <div class="col-md-3"><label class="form-label" for="to">إلى تاريخ</label><input class="form-control" id="to" name="to" type="date" value="{{ request('to') }}"></div>
        <div class="col-md-3"><label class="form-label" for="search">بحث</label><input class="form-control" id="search" name="search" value="{{ request('search') }}"></div>
        @if(isset($filterTypes))<div class="col-md-2"><label class="form-label" for="type">النوع</label><select class="form-select" id="type" name="type"><option value="">الكل</option>@foreach($filterTypes as $type)<option value="{{ $type->id }}" @selected((string)request('type') === (string)$type->id)>{{ $type->name_ar }}</option>@endforeach</select></div>@endif
        @if(isset($filterDecisions))<div class="col-md-2"><label class="form-label" for="status">الحالة</label><select class="form-select" id="status" name="status"><option value="">الكل</option><option value="pending" @selected(request('status')==='pending')>معلق</option>@foreach($filterDecisions as $decision)<option value="{{ $decision->id }}" @selected((string)request('status') === (string)$decision->id)>{{ $decision->name_ar }}</option>@endforeach</select></div>@endif
        <div class="col-md-auto"><button class="btn btn-dark">بحث</button> <a class="btn btn-outline-secondary" href="{{ url()->current() }}">استعادة</a></div>
    </div>
</form>
