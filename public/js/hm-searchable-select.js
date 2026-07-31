(function () {
    function closeAll(except) {
        document.querySelectorAll('[data-hm-searchable-select]').forEach(function (root) {
            if (except && root === except) {
                return;
            }

            var menu = root.querySelector('.hm-searchable-select__menu');
            var trigger = root.querySelector('.hm-searchable-select__trigger');

            if (menu) {
                menu.hidden = true;
            }

            if (trigger) {
                trigger.setAttribute('aria-expanded', 'false');
            }

            root.querySelectorAll('.hm-searchable-select__option--parent').forEach(function (parent) {
                parent.classList.remove('is-submenu-open');
                var parentBtn = parent.querySelector('.hm-searchable-select__option-btn--parent');
                if (parentBtn) {
                    parentBtn.setAttribute('aria-expanded', 'false');
                }
            });
        });
    }

    function preserveQueryParams(url) {
        var target = new URL(url, window.location.origin);
        var params = new URLSearchParams(window.location.search);

        ['name', 'code', 'search'].forEach(function (key) {
            if (params.has(key)) {
                target.searchParams.set(key, params.get(key));
            }
        });

        var allCheckbox = document.querySelector('.hm-clinician-filter-form input[name="all"]');
        if (allCheckbox && allCheckbox.checked) {
            target.searchParams.set('all', '1');
        }

        return target.pathname + target.search;
    }

    function optionSearchText(item) {
        var parts = [];
        var parentBtn = item.querySelector(':scope > .hm-searchable-select__option-btn');

        if (parentBtn) {
            parts.push(parentBtn.textContent.trim().toLowerCase());
        }

        item.querySelectorAll('.hm-searchable-select__sublist .hm-searchable-select__option-btn').forEach(function (button) {
            parts.push(button.textContent.trim().toLowerCase());
        });

        return parts.join(' ');
    }

    function initSearchableSelect(root) {
        var trigger = root.querySelector('.hm-searchable-select__trigger');
        var menu = root.querySelector('.hm-searchable-select__menu');
        var search = root.querySelector('.hm-searchable-select__search');
        var list = root.querySelector('.hm-searchable-select__list');
        var empty = root.querySelector('.hm-searchable-select__empty');
        var valueEl = root.querySelector('.hm-searchable-select__value');
        var navigateOnSelect = root.hasAttribute('data-navigate-on-select');

        if (!trigger || !menu || !list) {
            return;
        }

        function filterOptions(query) {
            var normalized = query.trim().toLowerCase();
            var visible = 0;

            list.querySelectorAll(':scope > .hm-searchable-select__option').forEach(function (item) {
                var haystack = optionSearchText(item);
                var show = normalized === '' || haystack.indexOf(normalized) !== -1;
                item.hidden = !show;

                if (show) {
                    visible += 1;
                }

                if (item.classList.contains('hm-searchable-select__option--parent')) {
                    if (show && normalized !== '') {
                        item.classList.add('is-submenu-open');
                        var parentBtn = item.querySelector('.hm-searchable-select__option-btn--parent');
                        if (parentBtn) {
                            parentBtn.setAttribute('aria-expanded', 'true');
                        }
                    } else if (normalized === '') {
                        item.classList.remove('is-submenu-open');
                        var closedBtn = item.querySelector('.hm-searchable-select__option-btn--parent');
                        if (closedBtn) {
                            closedBtn.setAttribute('aria-expanded', 'false');
                        }
                    }
                }
            });

            if (empty) {
                empty.hidden = visible > 0;
            }
        }

        function positionFlyoutSubmenus() {
            // On the Hospital Services screens (.hm-hs), we keep sub-categories inline
            // inside the same dropdown menu. Fixed flyout positioning can overlap
            // other filter fields and look broken.
            if (root.closest('.hm-hs')) {
                return;
            }

            if (!window.matchMedia('(min-width: 992px)').matches) {
                list.querySelectorAll('.hm-searchable-select__sublist').forEach(function (sublist) {
                    sublist.style.position = '';
                    sublist.style.top = '';
                    sublist.style.left = '';
                    sublist.style.right = '';
                    sublist.style.minWidth = '';
                });
                return;
            }

            list.querySelectorAll('.hm-searchable-select__option--parent.is-submenu-open, .hm-searchable-select__option--parent:hover').forEach(function (parent) {
                var sublist = parent.querySelector('.hm-searchable-select__sublist');
                var parentBtn = parent.querySelector('.hm-searchable-select__option-btn--parent');
                if (!sublist || !parentBtn) {
                    return;
                }

                sublist.style.position = 'fixed';
                sublist.style.minWidth = '15rem';
                var rect = parentBtn.getBoundingClientRect();
                var isRtl = document.documentElement.getAttribute('dir') === 'rtl';

                if (isRtl) {
                    sublist.style.right = (window.innerWidth - rect.left + 8) + 'px';
                    sublist.style.left = 'auto';
                } else {
                    sublist.style.left = (rect.right + 8) + 'px';
                    sublist.style.right = 'auto';
                }

                sublist.style.top = rect.top + 'px';
            });
        }

        list.querySelectorAll('.hm-searchable-select__option--parent').forEach(function (parent) {
            parent.addEventListener('mouseenter', positionFlyoutSubmenus);
        });

        function openMenu() {
            closeAll(root);
            menu.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');

            if (search) {
                search.value = '';
                filterOptions('');
                search.focus();
            }

            var selectedChild = list.querySelector('.hm-searchable-select__option--child.is-selected');
            var selectedParent = list.querySelector('.hm-searchable-select__option--parent.is-selected, .hm-searchable-select__option--parent.is-child-selected');
            var parentToOpen = selectedChild
                ? selectedChild.closest('.hm-searchable-select__option--parent')
                : selectedParent;

            if (parentToOpen) {
                parentToOpen.classList.add('is-submenu-open');
                var parentBtn = parentToOpen.querySelector('.hm-searchable-select__option-btn--parent');
                if (parentBtn) {
                    parentBtn.setAttribute('aria-expanded', 'true');
                }
                positionFlyoutSubmenus();
            }
        }

        trigger.addEventListener('click', function () {
            if (trigger.disabled) {
                return;
            }

            if (menu.hidden) {
                openMenu();
            } else {
                menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');
            }
        });

        if (search) {
            search.addEventListener('input', function () {
                filterOptions(search.value);
            });

            search.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    menu.hidden = true;
                    trigger.setAttribute('aria-expanded', 'false');
                    trigger.focus();
                }
            });
        }

        list.querySelectorAll('.hm-searchable-select__option-btn').forEach(function (button) {
            button.addEventListener('click', function (event) {
                var parentItem = button.closest('.hm-searchable-select__option--parent');
                var isParentToggle = button.classList.contains('hm-searchable-select__option-btn--parent');
                var parentUrl = isParentToggle ? (button.getAttribute('data-url') || '') : '';
                var clickedChevron = event.target.closest('.hm-searchable-select__submenu-chevron');

                // If this parent has children, clicking it should expand the submenu in-place.
                // (Do not redirect to the parent page.) Selecting the child options does navigation.
                if (isParentToggle && parentItem) {
                    var sublist = parentItem.querySelector('.hm-searchable-select__sublist');
                    var parentHasChildren = !!(sublist && sublist.querySelector('.hm-searchable-select__option--child'));

                    if (parentHasChildren) {
                        event.preventDefault();
                        parentItem.classList.toggle('is-submenu-open');
                        button.setAttribute(
                            'aria-expanded',
                            parentItem.classList.contains('is-submenu-open') ? 'true' : 'false'
                        );
                        positionFlyoutSubmenus();
                        return;
                    }
                }

                var label = button.textContent.trim();
                valueEl.textContent = label;

                list.querySelectorAll('.hm-searchable-select__option').forEach(function (item) {
                    item.classList.remove('is-selected');
                    item.removeAttribute('aria-selected');
                });

                list.querySelectorAll('.hm-searchable-select__option--parent').forEach(function (item) {
                    item.classList.remove('is-child-selected');
                });

                var option = button.closest('.hm-searchable-select__option');
                if (option) {
                    option.classList.add('is-selected');
                    option.setAttribute('aria-selected', 'true');

                    var childParent = option.closest('.hm-searchable-select__option--parent');
                    if (option.classList.contains('hm-searchable-select__option--child') && childParent) {
                        childParent.classList.add('is-child-selected');
                    }
                }

                menu.hidden = true;
                trigger.setAttribute('aria-expanded', 'false');

                var hidden = root.querySelector('input[type="hidden"]');
                if (hidden) {
                    hidden.value = button.getAttribute('data-value') || '';
                }

                if (navigateOnSelect && button.getAttribute('data-url')) {
                    window.location.href = preserveQueryParams(button.getAttribute('data-url'));
                }
            });
        });
    }

    document.addEventListener('click', function (event) {
        if (!event.target.closest('[data-hm-searchable-select]')) {
            closeAll(null);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-hm-searchable-select]').forEach(initSearchableSelect);
    });
})();
