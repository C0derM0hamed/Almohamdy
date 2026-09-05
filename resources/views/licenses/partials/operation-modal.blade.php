<div class="modal fade lic-operation-modal" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable {{ $size ?? '' }} modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-6" id="{{ $id }}Title"><i class="bi {{ $icon }}" aria-hidden="true"></i>{{ $title }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('licenses.close') }}"></button>
            </div>
            <div class="modal-body"></div>
        </div>
    </div>
</div>
