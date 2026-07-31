@php
    $isEdit = $isEdit ?? isset($doctor);
@endphp

            </div>

            <div class="hm-doctors-admin-modal__footer">
                <div class="hm-doctors-admin-modal__footer-start">
                    <a href="{{ route('modules.doctors-admin.doctors.index') }}" class="btn hm-btn hm-btn--outline dda-btn">
                        {{ __('doctors_directory_admin.cancel') }}
                    </a>
                    @if ($isEdit && ! empty($previewUrl))
                        <a href="{{ $previewUrl }}" class="btn hm-btn hm-btn--outline dda-btn" target="_blank" rel="noopener noreferrer">
                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                            {{ __('doctors_directory_admin.preview_profile') }}
                        </a>
                    @endif
                </div>
                <button type="submit" form="ddaDoctorForm" class="btn hm-btn hm-btn--primary dda-btn dda-btn--submit">
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                    {{ $isEdit ? __('doctors_directory_admin.save') : __('doctors_directory_admin.create_doctor') }}
                </button>
            </div>
