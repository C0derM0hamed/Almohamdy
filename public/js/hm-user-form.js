(function () {
    'use strict';

    function initBranchPicker(root) {
        if (root.dataset.bound === 'true') return;

        var toggle = root.querySelector('[data-branch-picker-toggle]');
        var menu = root.querySelector('[data-branch-picker-menu]');
        var summary = root.querySelector('[data-branch-picker-summary]');
        var values = root.querySelector('[data-branch-picker-values]');
        var options = Array.prototype.slice.call(root.querySelectorAll('[data-branch-picker-option]'));
        var form = root.closest('form');
        var primary = form ? form.querySelector('input[name="branch_id"]') : null;
        var selectedTemplate = root.dataset.selectedTemplate || ':count selected';
        var placeholder = root.dataset.placeholder || 'Select';

        if (!toggle || !menu || !summary || !values) return;

        function selectedOptions() {
            return options.filter(function (option) { return option.checked; });
        }

        function sync() {
            var selected = selectedOptions();
            values.innerHTML = '';
            selected.forEach(function (option) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'branch_ids[]';
                input.value = option.value;
                values.appendChild(input);
            });

            if (primary && selected[0]) primary.value = selected[0].value;
            summary.textContent = selected.length
                ? selectedTemplate.replace(':count', String(selected.length))
                : placeholder;
            toggle.classList.toggle('is-selected', selected.length > 0);
        }

        function close() {
            menu.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            root.classList.remove('is-open');
        }

        toggle.addEventListener('click', function () {
            if (toggle.disabled) return;
            var open = menu.hidden;
            menu.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            root.classList.toggle('is-open', open);
            if (open) {
                var first = menu.querySelector('input:not(:disabled)');
                if (first) first.focus();
            }
        });

        options.forEach(function (option) {
            option.addEventListener('change', sync);
        });

        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) close();
        });

        toggle.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') close();
            if (event.key === 'ArrowDown' && menu.hidden) {
                event.preventDefault();
                toggle.click();
            }
        });

        menu.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                close();
                toggle.focus();
            }
        });

        root.dataset.bound = 'true';
        sync();
    }

    function init() {
        document.querySelectorAll('[data-branch-picker]').forEach(initBranchPicker);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
