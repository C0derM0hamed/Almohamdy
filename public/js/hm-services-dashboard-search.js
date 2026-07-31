(function () {
    'use strict';

    function initServicesDashboardSearch() {
        var input = document.getElementById('servicesDashboardSearch');
        var grid = document.getElementById('servicesDashboardGrid');

        if (!input || !grid) {
            return;
        }

        var cards = Array.prototype.slice.call(grid.querySelectorAll('[data-hs-dash-card]'));

        function filterCards() {
            var query = input.value.trim().toLowerCase();

            cards.forEach(function (card) {
                var haystack = card.getAttribute('data-search-text') || '';
                var matches = query === '' || haystack.indexOf(query) !== -1;
                card.hidden = !matches;
            });
        }

        input.addEventListener('input', filterCards);
        input.addEventListener('search', filterCards);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initServicesDashboardSearch);
    } else {
        initServicesDashboardSearch();
    }
})();
