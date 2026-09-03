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

    function cardMatchesSearch(card, query) {
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

    function cardMatchesAvailability(card, availabilityFilter) {
        if (availabilityFilter !== 'today') {
            return true;
        }

        return Number(card.dataset.availableToday || 0) > 0;
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

    function sortCards(cards, sortFilter) {
        const sorted = Array.from(cards);

        if (sortFilter === 'most_booked') {
            sorted.sort(function (left, right) {
                const rightCount = Number(right.dataset.doctorCount || 0);
                const leftCount = Number(left.dataset.doctorCount || 0);

                if (rightCount !== leftCount) {
                    return rightCount - leftCount;
                }

                return (left.dataset.displayTitle || '').localeCompare(right.dataset.displayTitle || '');
            });
        }

        return sorted;
    }

    function initSpecialitySearch() {
        const input = document.getElementById('specialityLiveSearch');
        const grid = document.getElementById('specialityCardsGrid');
        const emptyState = document.getElementById('specialitySearchEmpty');
        const sortFilter = document.getElementById('specialitySortFilter');
        const availabilityFilter = document.getElementById('specialityAvailabilityFilter');
        const clearButton = document.getElementById('specialityClearFilters');
        const applyButton = document.getElementById('specialityApplyFilters');
        const visibleNumber = document.getElementById('specialityVisibleNumber');

        if (!grid) {
            return;
        }

        const cards = grid.querySelectorAll('.js-speciality-card');

        function applyFilter() {
            const query = input ? input.value.trim() : '';
            const sortValue = sortFilter ? sortFilter.value : 'default';
            const availabilityValue = availabilityFilter ? availabilityFilter.value : 'all';
            let matchCount = 0;

            sortCards(cards, sortValue).forEach(function (card) {
                grid.appendChild(card);
            });

            cards.forEach(function (card) {
                const isSearchMatch = cardMatchesSearch(card, query);
                const isAvailabilityMatch = cardMatchesAvailability(card, availabilityValue);
                const isMatch = isSearchMatch && isAvailabilityMatch;
                const titleNode = card.querySelector('.js-speciality-card-title');
                const displayTitle = card.dataset.displayTitle || '';

                card.hidden = !isMatch;
                card.classList.toggle('is-search-match', Boolean(query) && isSearchMatch);

                if (titleNode) {
                    titleNode.innerHTML = highlightTitle(displayTitle, query);
                }

                if (isMatch) {
                    matchCount += 1;
                }
            });

            if (emptyState) {
                emptyState.hidden = matchCount !== 0;
            }

            if (visibleNumber) {
                visibleNumber.textContent = String(matchCount);
            }
        }

        function clearFilters() {
            if (input) {
                input.value = '';
            }

            if (sortFilter) {
                sortFilter.value = 'default';
            }

            if (availabilityFilter) {
                availabilityFilter.value = 'all';
            }

            applyFilter();
        }

        if (input) {
            input.addEventListener('input', applyFilter);
            input.addEventListener('search', applyFilter);
        }

        if (sortFilter) {
            sortFilter.addEventListener('change', applyFilter);
        }

        if (availabilityFilter) {
            availabilityFilter.addEventListener('change', applyFilter);
        }

        if (clearButton) {
            clearButton.addEventListener('click', clearFilters);
        }

        if (applyButton) {
            applyButton.addEventListener('click', applyFilter);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSpecialitySearch);
    } else {
        initSpecialitySearch();
    }
})();
