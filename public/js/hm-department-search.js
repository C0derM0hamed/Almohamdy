(function () {
    'use strict';

    function escapeHtml(value) {
        return (value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function normalizeEnglish(value) {
        return (value || '').trim().toLowerCase();
    }

    function cardMatches(card, query) {
        if (!query) {
            return true;
        }

        const nameAr = (card.dataset.nameAr || '').trim();
        const nameEn = (card.dataset.nameEn || '').trim();
        const needle = query.trim();
        const needleLower = normalizeEnglish(needle);

        return (
            (nameAr && nameAr.includes(needle))
            || (nameEn && normalizeEnglish(nameEn).includes(needleLower))
        );
    }

    function highlightTitle(title, query) {
        const safeTitle = escapeHtml(title);

        if (!query) {
            return safeTitle;
        }

        const needle = query.trim();

        if (!needle) {
            return safeTitle;
        }

        const arIndex = title.indexOf(needle);

        if (arIndex !== -1) {
            const before = escapeHtml(title.slice(0, arIndex));
            const match = escapeHtml(title.slice(arIndex, arIndex + needle.length));
            const after = escapeHtml(title.slice(arIndex + needle.length));

            return before + '<mark class="hm-search-highlight">' + match + '</mark>' + after;
        }

        const lowerTitle = title.toLowerCase();
        const lowerNeedle = needle.toLowerCase();
        const enIndex = lowerTitle.indexOf(lowerNeedle);

        if (enIndex !== -1) {
            const before = escapeHtml(title.slice(0, enIndex));
            const match = escapeHtml(title.slice(enIndex, enIndex + needle.length));
            const after = escapeHtml(title.slice(enIndex + needle.length));

            return before + '<mark class="hm-search-highlight">' + match + '</mark>' + after;
        }

        return safeTitle;
    }

    function initDepartmentSearch() {
        const input = document.getElementById('departmentLiveSearch');
        const grid = document.getElementById('departmentCardsGrid');
        const emptyState = document.getElementById('departmentSearchEmpty');

        if (!input || !grid) {
            return;
        }

        const cards = grid.querySelectorAll('.js-department-card');

        function applyFilter() {
            const query = input.value.trim();
            let visibleCount = 0;

            cards.forEach(function (card) {
                const isMatch = cardMatches(card, query);
                const label = card.querySelector('.dd-department-card__title')
                    || card.querySelector('.hm-opd-bar__label');
                const displayTitle = card.dataset.displayTitle || (label ? label.textContent : '');

                card.hidden = !isMatch;
                card.classList.toggle('is-search-match', Boolean(query) && isMatch);

                if (label) {
                    label.innerHTML = highlightTitle(displayTitle, query);
                }

                if (isMatch) {
                    visibleCount += 1;
                }
            });

            if (emptyState) {
                emptyState.hidden = !(query && visibleCount === 0);
            }
        }

        input.addEventListener('input', applyFilter);
        input.addEventListener('search', applyFilter);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDepartmentSearch);
    } else {
        initDepartmentSearch();
    }
})();
