(function () {
    'use strict';

    function initServicesDashboardSearch() {
        var input = document.getElementById('servicesDashboardSearch');
        var grid = document.getElementById('servicesDashboardGrid');
        var button = document.getElementById('serviceLocationsSearchBtn')
            || document.querySelector('.hm-fm .fm-btn--search');
        var reset = document.querySelector('[data-hm-dashboard-reset="' + (input ? input.id : '') + '"]');
        var count = document.querySelector('[data-hm-dashboard-count-for="' + (grid ? grid.id : '') + '"]');

        if (!input || !grid) {
            return;
        }

        var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-hs-dash-card]'));

        function filterCards() {
            var query = input.value.trim().toLowerCase();
            var visibleCount = 0;

            cards.forEach(function (card) {
                var haystack = card.getAttribute('data-search-text') || '';
                var matches = query === '' || haystack.indexOf(query) !== -1;
                card.hidden = !matches;
                if (matches) {
                    visibleCount += 1;
                }
            });

            if (count) {
                count.textContent = visibleCount;
            }
        }

        input.addEventListener('input', filterCards);
        input.addEventListener('search', filterCards);
        if (button) {
            button.addEventListener('click', filterCards);
        }
        if (reset) {
            reset.addEventListener('click', function () {
                input.value = '';
                filterCards();
                input.focus();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initServicesDashboardSearch);
    } else {
        initServicesDashboardSearch();
    }
})();
