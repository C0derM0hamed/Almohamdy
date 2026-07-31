(function () {
    'use strict';

    var modalEl = document.getElementById('inqStatusModal');
    if (!modalEl || typeof bootstrap === 'undefined') {
        return;
    }

    var form = document.getElementById('inqStatusForm');
    var statusSelect = document.getElementById('inq_status_id');
    var departmentWrap = document.getElementById('inqStatusDepartmentWrap');
    var assignmentWrap = document.getElementById('inqStatusAssignmentWrap');
    var employeeWrap = document.getElementById('inqStatusEmployeeWrap');
    var departmentSelect = document.getElementById('inq_department_id');
    var employeeSelect = document.getElementById('inq_employee_id');
    var notesField = document.getElementById('inq_notes');
    var subtitleEl = document.getElementById('inqStatusModalSubtitle');
    var errorBox = document.getElementById('inqStatusFormError');
    var submitBtn = document.getElementById('inqStatusSubmitBtn');

    var forwardStatusId = parseInt(modalEl.getAttribute('data-forward-status-id') || '999999', 10);
    var usersUrlTemplate = modalEl.getAttribute('data-users-url') || '';
    var labels = {
        loading: modalEl.getAttribute('data-label-loading') || 'Loading…',
        empty: modalEl.getAttribute('data-label-empty') || 'No employees',
        error: modalEl.getAttribute('data-label-error') || 'Unable to update status.',
        employeePlaceholder: modalEl.getAttribute('data-label-employee-placeholder') || 'Select an employee',
    };

    function ensureModalInBody() {
        // Page transition transform on .hm-page-root breaks fixed centering.
        if (modalEl.parentElement !== document.body) {
            document.body.appendChild(modalEl);
        }
    }

    ensureModalInBody();

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    function selectedAssignmentType() {
        var checked = form.querySelector('input[name="assignment_type"]:checked');
        return checked ? checked.value : 'department';
    }

    function setError(message) {
        if (!errorBox) {
            return;
        }
        if (!message) {
            errorBox.classList.add('d-none');
            errorBox.textContent = '';
            return;
        }
        errorBox.textContent = message;
        errorBox.classList.remove('d-none');
    }

    function toggleForwardFields() {
        var statusId = parseInt(statusSelect.value || '0', 10);
        var isForward = statusId === forwardStatusId;

        if (departmentWrap) {
            departmentWrap.classList.toggle('d-none', !isForward);
        }
        if (assignmentWrap) {
            assignmentWrap.classList.toggle('d-none', !isForward);
        }

        if (!isForward) {
            if (departmentSelect) {
                departmentSelect.value = '';
            }
            if (employeeSelect) {
                employeeSelect.innerHTML = '';
                employeeSelect.value = '';
            }
            if (employeeWrap) {
                employeeWrap.classList.add('d-none');
            }
            return;
        }

        toggleEmployeeField();
    }

    function toggleEmployeeField() {
        var isEmployee = selectedAssignmentType() === 'employee';
        if (employeeWrap) {
            employeeWrap.classList.toggle('d-none', !isEmployee);
        }
        if (!isEmployee && employeeSelect) {
            employeeSelect.value = '';
        }
        if (isEmployee && departmentSelect && departmentSelect.value) {
            loadEmployees(departmentSelect.value);
        }
    }

    function loadEmployees(departmentId) {
        if (!employeeSelect || !usersUrlTemplate) {
            return;
        }

        employeeSelect.disabled = true;
        employeeSelect.innerHTML = '<option value="">' + labels.loading + '</option>';

        var url = usersUrlTemplate.replace('__DEPARTMENT__', encodeURIComponent(departmentId));

        fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('load failed');
                }
                return response.json();
            })
            .then(function (data) {
                var users = (data && data.users) || [];
                employeeSelect.innerHTML = '';

                var placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = users.length ? labels.employeePlaceholder : labels.empty;
                employeeSelect.appendChild(placeholder);

                users.forEach(function (user) {
                    var option = document.createElement('option');
                    option.value = String(user.id);
                    option.textContent = user.name;
                    employeeSelect.appendChild(option);
                });

                employeeSelect.disabled = users.length === 0;
            })
            .catch(function () {
                employeeSelect.innerHTML = '';
                var option = document.createElement('option');
                option.value = '';
                option.textContent = labels.empty;
                employeeSelect.appendChild(option);
                employeeSelect.disabled = true;
            });
    }

    function openForButton(button) {
        var url = button.getAttribute('data-inq-status-url');
        if (!url || !form) {
            return;
        }

        form.setAttribute('action', url);
        setError('');
        if (statusSelect) {
            statusSelect.value = '';
        }
        if (notesField) {
            notesField.value = '';
        }
        if (departmentSelect) {
            departmentSelect.value = '';
        }

        var departmentRadio = form.querySelector('input[name="assignment_type"][value="department"]');
        if (departmentRadio) {
            departmentRadio.checked = true;
        }

        if (subtitleEl) {
            subtitleEl.textContent = button.getAttribute('data-inq-status-subtitle') || '';
        }

        toggleForwardFields();
        ensureModalInBody();
        modal.show();
    }

    document.addEventListener(
        'click',
        function (event) {
            var button = event.target.closest('[data-inq-status-modal]');
            if (!button) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            openForButton(button);
        },
        true
    );

    if (statusSelect) {
        statusSelect.addEventListener('change', toggleForwardFields);
    }

    if (departmentSelect) {
        departmentSelect.addEventListener('change', function () {
            if (selectedAssignmentType() === 'employee' && departmentSelect.value) {
                loadEmployees(departmentSelect.value);
            }
        });
    }

    form.querySelectorAll('input[name="assignment_type"]').forEach(function (input) {
        input.addEventListener('change', toggleEmployeeField);
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        setError('');

        var statusId = parseInt(statusSelect.value || '0', 10);
        if (!statusId) {
            setError(labels.error);
            return;
        }

        if (statusId === forwardStatusId) {
            if (!departmentSelect || !departmentSelect.value) {
                setError(modalEl.getAttribute('data-label-department-required') || labels.error);
                return;
            }
            if (selectedAssignmentType() === 'employee' && (!employeeSelect || !employeeSelect.value)) {
                setError(modalEl.getAttribute('data-label-employee-required') || labels.error);
                return;
            }
        }

        var formData = new FormData(form);
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        fetch(form.getAttribute('action'), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
            credentials: 'same-origin',
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    return { ok: response.ok, status: response.status, data: data };
                }).catch(function () {
                    return { ok: response.ok, status: response.status, data: null };
                });
            })
            .then(function (result) {
                if (result.ok && result.data && result.data.ok) {
                    modal.hide();
                    window.location.reload();
                    return;
                }

                var message = labels.error;
                if (result.data && result.data.message) {
                    message = result.data.message;
                } else if (result.data && result.data.errors) {
                    var firstKey = Object.keys(result.data.errors)[0];
                    if (firstKey && result.data.errors[firstKey][0]) {
                        message = result.data.errors[firstKey][0];
                    }
                }
                setError(message);
            })
            .catch(function () {
                setError(labels.error);
            })
            .finally(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                }
            });
    });
})();
