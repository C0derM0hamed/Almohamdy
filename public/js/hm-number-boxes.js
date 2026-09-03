(function () {
    'use strict';

    var NUMBER_NAME_PATTERN = /(^|[_-])(id|idno|number|mobile|phone|price|amount|quantity|count|year|month|day|days|code|identity|passport|otp|pin)([_-]|$)/i;
    var observerBound = false;

    function isFilterContext(input) {
        var form = input.closest('form');
        return Boolean(
            (form && form.method.toLowerCase() === 'get')
            || input.closest('[data-filter-form], .filter-form, .filters, .filter-card, .hm-filter-card, .hm-filter-panel, .hm-filters')
        );
    }

    function isCandidate(input) {
        if (
            !(input instanceof HTMLInputElement)
            || input.closest('[data-digit-input], [data-auto-digit-input], .hm-digit-input__value')
            || isFilterContext(input)
        ) {
            return false;
        }

        if (input.disabled || input.readOnly || input.dataset.numberBoxes === 'false') {
            return false;
        }

        var type = (input.type || '').toLowerCase();

        if (
            type === 'number'
            || input.dataset.numberBoxes === 'true'
            || input.inputMode === 'numeric'
            || input.inputMode === 'tel'
        ) {
            return true;
        }

        return (type === 'text' || type === 'tel') && NUMBER_NAME_PATTERN.test(input.name || '');
    }

    function getBoxCount(input) {
        var name = (input.name || '').toLowerCase();
        if (name.indexOf('mobile') !== -1 || name.indexOf('phone') !== -1) return 10;

        var configured = Number(input.dataset.numberLength || input.getAttribute('maxlength'));

        if (Number.isFinite(configured) && configured > 0) {
            return Math.min(Math.max(configured, 1), 20);
        }

        var max = input.getAttribute('max');
        if (max !== null && max !== '' && /^\d+(?:\.\d+)?$/.test(max)) {
            return Math.min(Math.max(String(max).replace(/\D/g, '').length, 1), 20);
        }

        if (name.indexOf('month') !== -1) return 2;
        if (name.indexOf('year') !== -1) return 4;
        return 12;
    }

    function isMobileInput(input) {
        var name = (input.name || '').toLowerCase();
        return name.indexOf('mobile') !== -1 || name.indexOf('phone') !== -1;
    }

    function getLabel(input) {
        var label = input.labels && input.labels[0];
        var text = label ? label.textContent.trim() : '';
        return text || input.getAttribute('aria-label') || input.getAttribute('placeholder') || input.name || 'رقم';
    }

    function syncGroup(group) {
        var source = group.querySelector('.hm-digit-input__value');
        var boxes = Array.prototype.slice.call(group.querySelectorAll('[data-digit-box]'));
        if (!source || !boxes.length) return;

        var value = Array.from(String(source.value || '')).slice(0, boxes.length);
        boxes.forEach(function (box, index) {
            box.value = value[index] || '';
            box.disabled = source.disabled;
        });
    }

    function bindGroup(group) {
        if (group.dataset.hmNumberBoxesBound === 'true') {
            syncGroup(group);
            return;
        }

        var source = group.querySelector('.hm-digit-input__value');
        var boxes = Array.prototype.slice.call(group.querySelectorAll('[data-digit-box]'));
        if (!source || !boxes.length) return;

        function syncSource() {
            source.value = boxes.map(function (box) { return box.value; }).join('');
            source.setCustomValidity('');
        }

        source.addEventListener('hm:digit-sync', function () {
            syncGroup(group);
        });

        if (window.MutationObserver) {
            new MutationObserver(function () {
                syncGroup(group);
            }).observe(source, {attributes: true, attributeFilter: ['disabled']});
        }

        boxes.forEach(function (box, index) {
            box.addEventListener('input', function () {
                var value = Array.from(this.value || '').filter(function (character) {
                    return /[0-9]/.test(character);
                }).slice(-1)[0] || '';
                this.value = value;
                syncSource();
                if (value && boxes[index + 1]) boxes[index + 1].focus();
            });

            box.addEventListener('keydown', function (event) {
                if (event.key === 'Backspace' && !this.value && boxes[index - 1]) {
                    boxes[index - 1].value = '';
                    boxes[index - 1].focus();
                    syncSource();
                } else if (event.key === 'ArrowLeft' && boxes[index - 1]) {
                    event.preventDefault();
                    boxes[index - 1].focus();
                } else if (event.key === 'ArrowRight' && boxes[index + 1]) {
                    event.preventDefault();
                    boxes[index + 1].focus();
                }
            });

            box.addEventListener('paste', function (event) {
                event.preventDefault();
                var pasted = (event.clipboardData || window.clipboardData)?.getData('text') || '';
                var characters = Array.from(pasted.trim()).filter(function (character) {
                    return /[0-9]/.test(character);
                }).slice(0, boxes.length - index);
                if (!characters.length) return;

                characters.forEach(function (character, offset) {
                    boxes[index + offset].value = character;
                });
                syncSource();
                boxes[Math.min(index + characters.length, boxes.length - 1)].focus();
            });
        });

        group.dataset.hmNumberBoxesBound = 'true';
        syncGroup(group);
    }

    function createGroup(input) {
        var group = document.createElement('div');
        var boxesContainer = document.createElement('div');
        var count = getBoxCount(input);
        var label = getLabel(input);

        group.className = 'hm-digit-input';
        group.dataset.autoDigitInput = 'true';
        group.dataset.digitMax = String(count);

        input.classList.add('hm-digit-input__value');
        if (isMobileInput(input)) {
            input.maxLength = 10;
            input.pattern = '05[0-9]{8}';
            input.inputMode = 'numeric';
        }
        if (input.required) {
            input.dataset.digitRequired = 'true';
            input.required = false;
        }
        input.setAttribute('tabindex', '-1');
        input.setAttribute('aria-hidden', 'true');
        input.parentNode.insertBefore(group, input);
        group.appendChild(input);

        boxesContainer.className = 'hm-digit-input__boxes';
        boxesContainer.setAttribute('role', 'group');
        boxesContainer.setAttribute('aria-label', label);
        boxesContainer.setAttribute('dir', 'ltr');

        for (var index = 0; index < count; index += 1) {
            var box = document.createElement('input');
            box.type = 'text';
            box.className = 'hm-digit-input__box';
            box.maxLength = 1;
            box.inputMode = 'numeric';
            box.autocomplete = 'off';
            box.setAttribute('aria-label', label + ' ' + (index + 1));
            box.dataset.digitBox = 'true';
            boxesContainer.appendChild(box);
        }

        group.appendChild(boxesContainer);
        return group;
    }

    function bindValidation(form) {
        if (form.dataset.hmNumberValidationBound === 'true') return;

        form.addEventListener('submit', function (event) {
            var invalidBox = null;

            form.querySelectorAll('[data-auto-digit-input]').forEach(function (group) {
                var source = group.querySelector('.hm-digit-input__value');
                var firstBox = group.querySelector('[data-digit-box]');
                var isRequired = source && (source.required || source.dataset.digitRequired === 'true');
                var isMobile = source && isMobileInput(source);
                var hasInvalidMobile = isMobile && source.value && !/^05[0-9]{8}$/.test(source.value);
                if (source) source.setCustomValidity('');
                if (source && firstBox && !source.disabled && hasInvalidMobile) {
                    source.setCustomValidity('يجب أن يبدأ رقم الجوال بـ 05 ويتكون من 10 أرقام.');
                    invalidBox = invalidBox || firstBox;
                } else if (source && firstBox && !source.disabled && isRequired && !source.value) {
                    source.setCustomValidity('أدخل القيمة المطلوبة.');
                    invalidBox = invalidBox || firstBox;
                }
            });

            if (invalidBox) {
                event.preventDefault();
                invalidBox.focus();
            }
        });
        form.dataset.hmNumberValidationBound = 'true';
    }

    function bindDatePicker(input) {
        if (input.dataset.hmDatePickerBound === 'true') return;

        input.addEventListener('click', function () {
            if (typeof this.showPicker !== 'function') return;

            try {
                this.showPicker();
            } catch (error) {
                // The browser can reject showPicker when the click is not user initiated.
            }
        });

        input.dataset.hmDatePickerBound = 'true';
    }

    function init() {
        document.querySelectorAll('input[type="date"], input[type="datetime-local"], input[type="month"], input[type="week"]')
            .forEach(bindDatePicker);

        document.querySelectorAll('input').forEach(function (input) {
            if (!isCandidate(input)) return;
            var group = createGroup(input);
            bindGroup(group);
        });

        document.querySelectorAll('[data-auto-digit-input]').forEach(function (group) {
            bindGroup(group);
            var form = group.closest('form');
            if (form) bindValidation(form);
        });

        if (!observerBound && window.MutationObserver && document.body) {
            new MutationObserver(function (records) {
                var hasAddedNodes = records.some(function (record) {
                    return record.addedNodes && record.addedNodes.length > 0;
                });
                if (hasAddedNodes) init();
            }).observe(document.body, {childList: true, subtree: true});
            observerBound = true;
        }
    }

    document.addEventListener('hm:page-loaded', init);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
