<div class="dda-form-section dda-form-section--assignments">
    <header class="dda-form-section__head">
        <span class="dda-form-section__icon" aria-hidden="true"><i class="bi bi-building"></i></span>
        <div>
            <h2>{{ __('doctors_directory_admin.department_assignments') }}</h2>
            <p>{{ __('doctors_directory_admin.department_assignments_subtitle') }}</p>
        </div>
    </header>

    @if ($doctor->hospitals->count() > 0)
        <div class="dda-assignments-table-wrap">
            <table class="dda-assignments-table">
                <thead>
                    <tr>
                        <th>{{ __('doctors_directory_admin.columns.id') }}</th>
                        <th>{{ __('doctors_directory_admin.columns.department') }}</th>
                        <th class="dda-assignments-table__actions">{{ __('doctors_directory_admin.columns.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($doctor->hospitals as $assignment)
                        <tr>
                            <td>#{{ $assignment->id }}</td>
                            <td>{{ $assignment->outpatientClinic?->localizedName() ?? '—' }}</td>
                            <td class="dda-assignments-table__actions">
                                <form method="POST" action="{{ route('modules.doctors-admin.doctors.assignments.destroy', [$doctor->id, $assignment->id]) }}" onsubmit="return confirm(@json(__('doctors_directory_admin.confirm_remove_assignment')));">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm hm-btn hm-btn--outline dda-btn" title="{{ __('doctors_directory_admin.remove_assignment') }}">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="dda-form-empty">{{ __('doctors_directory_admin.no_doctor_assignments') }}</p>
    @endif

    @if (count($departments) > 0)
        <form method="POST" action="{{ route('modules.doctors-admin.doctors.assignments.store', $doctor->id) }}" class="dda-assignments-form">
            @csrf
            <div class="dda-form-field dda-form-field--grow">
                <label for="assignment_department_id">{{ __('doctors_directory_admin.select_department') }}</label>
                <select id="assignment_department_id" name="department_id" class="dda-form-select @error('department_id') is-invalid @enderror" required>
                    <option value="">{{ __('doctors_directory_admin.select_department') }}</option>
                    @foreach ($departments as $id => $label)
                        <option value="{{ $id }}" @selected((string) old('department_id') === (string) $id)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('department_id')<div class="dda-form-error">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn hm-btn hm-btn--primary dda-btn">
                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                {{ __('doctors_directory_admin.add_doctor_department_assignment') }}
            </button>
        </form>
    @else
        <p class="dda-form-empty">{{ __('doctors_directory_admin.no_valid_departments_for_speciality') }}</p>
    @endif
</div>
