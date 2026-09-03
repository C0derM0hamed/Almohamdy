(function () {
    'use strict';

    function init() {
        var modal = document.querySelector('[data-medical-agreement-timeline-modal]');
        if (!modal) return;
        if (modal.dataset.timelineBound === 'true') return;

        // Keep old server/browser-rendered rows compatible during a rolling
        // deployment: a legacy clock link is promoted to the same AJAX popup
        // trigger instead of navigating to the full details page.
        document.querySelectorAll('a[href]').forEach(function (link) {
            if (!link.querySelector('.bi-clock-history')) return;
            var url;
            try { url = new URL(link.href, window.location.href); } catch (error) { return; }
            if (!/^\/modules\/medical-agreements\/[^/]+\/\d+\/?$/.test(url.pathname)) return;
            link.dataset.medicalAgreementTimeline = '';
            link.dataset.timelineUrl = url.pathname.replace(/\/$/, '') + '/timeline';
            link.dataset.noTransition = 'true';
        });

        var dialog = modal.querySelector('.hm-agreement-timeline-dialog');
        var state = modal.querySelector('[data-timeline-state]');
        var stateText = modal.querySelector('[data-timeline-state-text]');
        var scroll = modal.querySelector('[data-timeline-scroll]');
        var events = modal.querySelector('[data-timeline-events]');
        var lastTrigger = null;
        var requestId = 0;
        var dateFormatter = new Intl.DateTimeFormat(document.documentElement.lang === 'en' ? 'en-GB' : 'ar-SA', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });

        function setText(selector, value) {
            var node = modal.querySelector(selector);
            if (node) node.textContent = value === null || value === undefined || value === '' ? '—' : String(value);
        }

        function formatDate(value) {
            if (!value) return '—';
            var normalized = String(value).replace(' ', 'T');
            var date = new Date(normalized);
            if (Number.isNaN(date.getTime())) return String(value);
            return dateFormatter.format(date);
        }

        function statusClass(status) {
            return ['completed', 'current', 'pending', 'rejected'].indexOf(status) !== -1 ? status : 'pending';
        }

        function renderMeta(meta) {
            var fragment = document.createDocumentFragment();
            (Array.isArray(meta) ? meta : []).forEach(function (item) {
                if (!item || !item.value) return;
                var wrapper = document.createElement('div');
                wrapper.className = 'hm-agreement-timeline-event-meta-item';
                var label = document.createElement('span');
                label.textContent = item.label || 'تفاصيل';
                var value = document.createElement('strong');
                value.textContent = item.value;
                wrapper.append(label, value);
                fragment.appendChild(wrapper);
            });
            return fragment;
        }

        function renderEvents(payload) {
            events.replaceChildren();
            var list = Array.isArray(payload.events) ? payload.events : [];
            list.forEach(function (item, index) {
                var li = document.createElement('li');
                li.className = 'hm-agreement-timeline-event is-' + statusClass(item.status);

                var marker = document.createElement('span');
                marker.className = 'hm-agreement-timeline-event-marker';
                marker.setAttribute('aria-hidden', 'true');
                var markerIcon = document.createElement('i');
                markerIcon.className = 'bi ' + (item.icon || 'bi-circle');
                marker.appendChild(markerIcon);

                var card = document.createElement('article');
                card.className = 'hm-agreement-timeline-event-card';
                var top = document.createElement('div');
                top.className = 'hm-agreement-timeline-event-top';
                var title = document.createElement('h4');
                title.textContent = item.title || 'مرحلة الاتفاقية';
                var badge = document.createElement('span');
                badge.className = 'hm-agreement-timeline-event-badge';
                badge.textContent = item.status_label || 'قيد الانتظار';
                top.append(title, badge);
                var description = document.createElement('p');
                description.textContent = item.description || '';
                var date = document.createElement('time');
                date.className = 'hm-agreement-timeline-event-date';
                date.setAttribute('dir', 'ltr');
                date.textContent = formatDate(item.date);
                card.append(top, description, date);
                var meta = renderMeta(item.meta);
                if (meta.childNodes.length) {
                    var metaWrap = document.createElement('div');
                    metaWrap.className = 'hm-agreement-timeline-event-meta';
                    metaWrap.appendChild(meta);
                    card.appendChild(metaWrap);
                }
                li.append(marker, card);
                events.appendChild(li);
            });
            setText('[data-timeline-count]', list.length + ' ' + (list.length === 1 ? 'مرحلة' : 'مراحل'));
        }

        function showState(kind, message) {
            state.dataset.timelineState = kind;
            stateText.textContent = message;
            state.hidden = false;
            scroll.hidden = true;
        }

        function showData(payload) {
            var agreement = payload.agreement || {};
            setText('[data-timeline-title]', 'اتفاقية #' + (agreement.id || '—'));
            setText('[data-timeline-subtitle]', agreement.patient_name || 'بيانات الاتفاقية');
            setText('[data-timeline-id]', '#' + (agreement.id || '—'));
            setText('[data-timeline-patient]', agreement.patient_name);
            setText('[data-timeline-file]', agreement.file_number);
            setText('[data-timeline-status]', payload.status && payload.status.label);
            renderEvents(payload);
            state.hidden = true;
            scroll.hidden = false;
            var detail = modal.querySelector('[data-timeline-detail]');
            var pdf = modal.querySelector('[data-timeline-pdf]');
            if (detail) detail.href = payload.detail_url || '#';
            if (pdf) pdf.href = payload.pdf_url || '#';
        }

        function close() {
            requestId += 1;
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('hm-agreement-timeline-open');
            window.setTimeout(function () {
                if (!modal.classList.contains('is-open')) modal.hidden = true;
            }, 180);
            if (lastTrigger) lastTrigger.focus();
        }

        function open(trigger) {
            lastTrigger = trigger;
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('hm-agreement-timeline-open');
            setText('[data-timeline-title]', 'الخط الزمني للاتفاقية');
            setText('[data-timeline-subtitle]', 'جارٍ تحميل مراحل الاتفاقية...');
            setText('[data-timeline-id]', '—');
            setText('[data-timeline-patient]', '—');
            setText('[data-timeline-file]', '—');
            setText('[data-timeline-status]', '—');
            setText('[data-timeline-count]', '0 مرحلة');
            showState('loading', 'جارٍ تحميل الخط الزمني...');
            events.replaceChildren();
            window.requestAnimationFrame(function () {
                modal.classList.add('is-open');
                var closeButton = modal.querySelector('[data-timeline-close]');
                if (closeButton) closeButton.focus();
            });

            var currentRequest = ++requestId;
            fetch(trigger.dataset.timelineUrl, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) {
                    if (!response.ok) throw new Error('timeline-request-failed');
                    return response.json();
                })
                .then(function (payload) {
                    if (currentRequest !== requestId || modal.hidden) return;
                    showData(payload);
                })
                .catch(function () {
                    if (currentRequest !== requestId || modal.hidden) return;
                    showState('error', 'تعذر تحميل الخط الزمني. حاول مرة أخرى.');
                });
        }

        document.querySelectorAll('[data-medical-agreement-timeline]').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                open(trigger);
            });
        });
        modal.querySelectorAll('[data-timeline-close]').forEach(function (button) {
            button.addEventListener('click', close);
        });
        modal.addEventListener('click', function (event) {
            if (event.target === modal) close();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.hidden) close();
        });
        dialog.addEventListener('keydown', function (event) {
            if (event.key !== 'Tab') return;
            var focusable = dialog.querySelectorAll('a[href], button:not([disabled])');
            if (!focusable.length) return;
            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        });
        modal.dataset.timelineBound = 'true';
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
    document.addEventListener('hm:page-loaded', init);
})();
