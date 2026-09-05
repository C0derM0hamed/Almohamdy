(function () {
    'use strict';

    // Bootstrap appends its backdrop to <body>. Keeping page modals inside the
    // main layout's stacking context can place that backdrop above the dialog.
    // Move only this module's modals beside the backdrop before initializing.
    document.querySelectorAll('.lic-departments-modal, .lic-operation-modal, .lic-quick-view-modal').forEach(function (modal) {
        if (modal.parentElement !== document.body) document.body.appendChild(modal);
    });

    var departmentsModal = document.getElementById('licenseDepartmentsModal');
    if (departmentsModal) {
        departmentsModal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            var list = departmentsModal.querySelector('[data-lic-departments-list]');
            if (!trigger || !list) return;
            var names = [];
            try { names = JSON.parse(trigger.dataset.licDepartments || '[]'); } catch (error) { names = []; }
            list.replaceChildren();
            names.forEach(function (name) {
                var chip = document.createElement('span');
                chip.className = 'lic-chip';
                chip.textContent = name;
                list.appendChild(chip);
            });
        });
    }

    function bindQuickViewModal(modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;
            if (!trigger) return;
            var details = {};
            try { details = JSON.parse(trigger.dataset.licensePreview || '{}'); } catch (error) { details = {}; }
            modal.querySelectorAll('[data-license-preview-field]').forEach(function (field) {
                var key = field.dataset.licensePreviewField;
                field.textContent = details[key] || '—';
                if (key === 'status') {
                    field.className = 'lic-status lic-status--' + (details.status_key || 'unknown');
                }
            });
            var openLink = modal.querySelector('[data-license-preview-open]');
            if (openLink) openLink.href = details.url || '#';
            var previewForm = modal.querySelector('[data-license-preview-form]');
            if (previewForm) {
                previewForm.setAttribute('action', details.action || '#');
                previewForm.reset();
            }
            var departments = Array.isArray(details.departments) ? details.departments : [];
            var list = modal.querySelector('[data-license-preview-departments]');
            if (!list) return;
            list.classList.toggle('lic-chip-list--compact', departments.length > 1);
            list.replaceChildren();
            if (!departments.length) {
                list.textContent = '—';
                return;
            }
            departments.slice(0, 1).forEach(function (name) {
                var chip = document.createElement('span');
                chip.className = 'lic-chip';
                chip.textContent = name;
                list.appendChild(chip);
            });
            if (departments.length > 1) {
                var more = document.createElement('button');
                more.type = 'button';
                more.className = 'lic-chip lic-chip--more';
                more.textContent = '+' + (departments.length - 1);
                more.setAttribute('aria-label', '+' + (departments.length - 1));
                more.addEventListener('click', function () {
                    list.classList.remove('lic-chip-list--compact');
                    list.replaceChildren();
                    departments.forEach(function (name) {
                        var chip = document.createElement('span');
                        chip.className = 'lic-chip';
                        chip.textContent = name;
                        list.appendChild(chip);
                    });
                });
                list.appendChild(more);
            }
        });
        var quickViewOpenLink = modal.querySelector('[data-license-preview-open]');
        if (quickViewOpenLink) {
            quickViewOpenLink.addEventListener('click', function (event) {
                var destination = quickViewOpenLink.href;
                if (!destination || destination.endsWith('#')) return;
                event.preventDefault();
                if (window.bootstrap && bootstrap.Modal) {
                    modal.addEventListener('hidden.bs.modal', function () {
                        window.location.assign(destination);
                    }, { once: true });
                    bootstrap.Modal.getOrCreateInstance(modal).hide();
                } else {
                    window.location.assign(destination);
                }
            });
        }
    }
    document.querySelectorAll('.lic-quick-view-modal').forEach(bindQuickViewModal);

    var tabLinks = Array.from(document.querySelectorAll('[data-license-tab]'));
    var tabPanels = Array.from(document.querySelectorAll('[data-license-panel]'));
    function activatePanel(id, updateHash) {
        if (!tabPanels.length) return;
        if (!tabPanels.some(function (panel) { return panel.id === id; })) id = 'timeline';
        tabLinks.forEach(function (link) {
            var active = link.getAttribute('href') === '#' + id;
            link.classList.toggle('is-active', active);
            link.setAttribute('aria-selected', active ? 'true' : 'false');
            link.setAttribute('tabindex', active ? '0' : '-1');
        });
        tabPanels.forEach(function (panel) { panel.hidden = panel.id !== id; });
        if (updateHash && history.replaceState) history.replaceState(null, '', '#' + id);
    }
    function openPanel(id) {
        if (tabPanels.length) activatePanel(id, true);
        var panel = document.getElementById(id);
        if (panel) panel.scrollIntoView({ block: 'start' });
    }
    if (tabLinks.length && tabPanels.length) {
        tabLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();
                activatePanel(link.getAttribute('href').slice(1), true);
            });
        });
        var initialPanel = document.querySelector('[data-license-initial-panel]');
        activatePanel((initialPanel && initialPanel.dataset.licenseInitialPanel) || location.hash.slice(1) || 'timeline', false);
    }
    document.querySelectorAll('[data-license-open-panel]').forEach(function (trigger) {
        trigger.addEventListener('click', function () {
            openPanel(trigger.dataset.licenseOpenPanel);
        });
    });
    var initialTarget = document.querySelector('[data-license-initial-panel]');
    if (initialTarget && !tabPanels.length) openPanel(initialTarget.dataset.licenseInitialPanel);

    document.querySelectorAll('[data-license-operation-form]').forEach(function (form) {
        var modal = document.getElementById(form.dataset.licenseOperationForm);
        var body = modal && modal.querySelector('.modal-body');
        if (body) body.appendChild(form);
    });

    var operation = document.querySelector('[data-license-open-operation]');
    if (operation && window.bootstrap && bootstrap.Modal) {
        var target = document.getElementById(operation.dataset.licenseOpenOperation);
        if (target) bootstrap.Modal.getOrCreateInstance(target).show();
    }
})();
