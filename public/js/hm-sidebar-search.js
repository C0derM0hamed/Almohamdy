(function () {
    'use strict';

    var ALIASES = {
        'اتفاقية': ['اتفاقية', 'اتفاق', 'تعهد', 'نموذج'],
        'اتفاقيات': ['اتفاقية', 'اتفاق', 'تعهد', 'نموذج'],
        'نموذج': ['نموذج', 'تعهد', 'استمارة', 'form'],
        'سداد': ['سداد', 'دفع', 'payment'],
        'دفع': ['دفع', 'سداد', 'payment'],
        'agreement': ['agreement', 'undertaking', 'form', 'تعهد'],
        'agreements': ['agreement', 'undertaking', 'form', 'تعهد'],
        'payment': ['payment', 'undertaking', 'سداد'],
        'undertaking': ['undertaking', 'agreement', 'سداد', 'تعهد']
    };

    function normalize(value) {
        return String(value || '')
            .normalize('NFKC')
            .toLowerCase()
            // Make Arabic search forgiving of common keyboard/font variants.
            .replace(/[\u064B-\u065F\u0670\u06D6-\u06ED]/gu, '')
            .replace(/[أإآٱ]/g, 'ا')
            .replace(/[ىی]/g, 'ي')
            .replace(/[ؤۆ]/g, 'و')
            .replace(/[ئې]/g, 'ي')
            .replace(/[ةۃ]/g, 'ه')
            .replace(/[کك]/g, 'ك')
            .replace(/[یي]/g, 'ي')
            .replace(/[ـ]/g, '')
            .replace(/[^\p{L}\p{N}]+/gu, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function compact(value) {
        return normalize(value).replace(/\s+/g, '');
    }

    function stripArabicPrefix(token) {
        return token.replace(/^(?:بال|لل|ال)/, '');
    }

    function variants(token) {
        var normalized = normalize(token);
        var base = stripArabicPrefix(normalized);
        var result = [normalized, base];
        (ALIASES[normalized] || ALIASES[base] || []).forEach(function (alias) {
            result.push(normalize(alias), stripArabicPrefix(normalize(alias)));
        });
        return result.filter(Boolean);
    }

    function matches(query, text) {
        var normalizedQuery = normalize(query);
        var normalizedText = normalize(text);

        if (!normalizedQuery) return true;
        if (normalizedText.indexOf(normalizedQuery) !== -1) return true;
        // Spaces, dashes, colons and other light punctuation should not make
        // an otherwise identical Arabic label fail the search.
        if (compact(normalizedText).indexOf(compact(normalizedQuery)) !== -1) return true;

        var targetTokens = normalizedText.split(/\s+/).filter(Boolean);
        return normalizedQuery.split(/\s+/).filter(Boolean).every(function (queryToken) {
            return variants(queryToken).some(function (variant) {
                return targetTokens.some(function (targetToken) {
                    return targetToken.indexOf(variant) !== -1 || variant.indexOf(targetToken) !== -1;
                });
            });
        });
    }

    function init(root) {
        if (root.dataset.hmSidebarSearchBound === 'true') return;

        var input = root.querySelector('[data-sidebar-search-input]');
        var clear = root.querySelector('[data-sidebar-search-clear]');
        var status = root.querySelector('[data-sidebar-search-status]');
        var menu = document.getElementById('sidebar-menu');
        if (!input || !menu) return;

        var groups = Array.prototype.slice.call(menu.children).filter(function (item) {
            return item.matches('li[data-sidebar-group]');
        });
        var standaloneItems = Array.prototype.slice.call(menu.children).filter(function (item) {
            return item.matches('li[data-sidebar-search-item]');
        });
        var originalGroups = groups.map(function (group) {
            var link = group.querySelector(':scope > a.nav-link');
            var subNav = group.querySelector(':scope > ul.sub-nav');
            return {
                group: group,
                link: link,
                subNav: subNav,
                expanded: link ? link.getAttribute('aria-expanded') : 'false',
                shown: subNav ? subNav.classList.contains('show') : false
            };
        });

        function setStatus(count, searching) {
            if (!status) return;
            status.hidden = !searching || count > 0;
            if (searching && count === 0) status.textContent = root.dataset.noResults || '';
        }

        function restoreGroup(state) {
            state.group.style.display = '';
            state.group.querySelectorAll('[data-sidebar-search-item]').forEach(function (item) {
                item.style.display = '';
            });
            if (state.subNav) {
                state.subNav.classList.toggle('show', state.shown);
                state.subNav.style.display = '';
            }
            if (state.link) state.link.setAttribute('aria-expanded', state.expanded);
        }

        function clearSearch() {
            input.value = '';
            clear.hidden = true;
            originalGroups.forEach(restoreGroup);
            standaloneItems.forEach(function (item) { item.style.display = ''; });
            setStatus(0, false);
        }

        function applySearch() {
            var query = input.value.trim();
            clear.hidden = query === '';

            if (query === '') {
                clearSearch();
                return;
            }

            var resultCount = 0;
            originalGroups.forEach(function (state) {
                var groupTitle = state.link ? (state.link.querySelector('.item-name')?.textContent || '') : '';
                var groupMatch = matches(query, groupTitle);
                var childMatches = 0;

                state.group.querySelectorAll(':scope > ul.sub-nav > li[data-sidebar-search-item]').forEach(function (item) {
                    var itemMatch = groupMatch || matches(query, item.getAttribute('data-sidebar-search') || item.textContent);
                    item.style.display = itemMatch ? '' : 'none';
                    if (itemMatch) childMatches += 1;
                });

                var visible = groupMatch || childMatches > 0;
                state.group.style.display = visible ? '' : 'none';
                if (visible) {
                    resultCount += groupMatch ? Math.max(childMatches, 1) : childMatches;
                    if (state.subNav) {
                        state.subNav.classList.add('show');
                        state.subNav.style.display = '';
                    }
                    if (state.link) state.link.setAttribute('aria-expanded', 'true');
                }
            });

            standaloneItems.forEach(function (item) {
                var visible = matches(query, item.getAttribute('data-sidebar-search') || item.textContent);
                item.style.display = visible ? '' : 'none';
                if (visible) resultCount += 1;
            });

            setStatus(resultCount, true);
        }

        input.addEventListener('input', applySearch);
        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                clearSearch();
            }
        });
        if (clear) clear.addEventListener('click', clearSearch);

        root.dataset.hmSidebarSearchBound = 'true';
    }

    function initAll() {
        document.querySelectorAll('[data-sidebar-search]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
}());
