(() => {
  const root = document.querySelector('[data-direct-permissions]');
  if (!root) return;

  const updateGroup = (group) => {
    const boxes = [...group.querySelectorAll('[data-direct-permission-check]')];
    const selected = boxes.filter((box) => box.checked).length;
    const selectAll = group.querySelector('[data-direct-category-select-all]');
    selectAll.checked = selected === boxes.length && boxes.length > 0;
    selectAll.indeterminate = selected > 0 && selected < boxes.length;
    group.querySelector('[data-direct-category-count]').textContent = `${selected}/${boxes.length}`;
  };

  const updateTotal = () => {
    root.querySelector('[data-direct-selected-count]').textContent = root.querySelectorAll('[data-direct-permission-check]:checked').length;
  };

  const setOpen = (group, open) => {
    // This is a single-open accordion: opening one permission group closes
    // every other group so the selected section stays visible.
    if (open) {
      root.querySelectorAll('[data-direct-category]').forEach((other) => {
        if (other !== group) setOpen(other, false);
      });
    }
    const toggle = group.querySelector('[data-direct-category-toggle]');
    const icon = toggle.querySelector('.bi-chevron-up, .bi-chevron-down');
    toggle.setAttribute('aria-expanded', String(open));
    group.querySelector('[data-direct-category-body]').hidden = !open;
    icon?.classList.toggle('bi-chevron-up', open);
    icon?.classList.toggle('bi-chevron-down', !open);
  };

  root.querySelectorAll('[data-direct-permission-check]').forEach((box) => box.addEventListener('change', () => {
    updateGroup(box.closest('[data-direct-category]'));
    updateTotal();
  }));

  root.querySelectorAll('[data-direct-category-select-all]').forEach((selectAll) => selectAll.addEventListener('change', () => {
    const group = selectAll.closest('[data-direct-category]');
    group.querySelectorAll('[data-direct-permission-check]').forEach((box) => { box.checked = selectAll.checked; });
    updateGroup(group);
    updateTotal();
  }));

  root.querySelectorAll('[data-direct-category-toggle]').forEach((toggle) => toggle.addEventListener('click', () => {
    const group = toggle.closest('[data-direct-category]');
    setOpen(group, toggle.getAttribute('aria-expanded') !== 'true');
  }));

  root.querySelector('[data-direct-permission-search]')?.addEventListener('input', (event) => {
    const term = event.target.value.trim().toLowerCase();
    let firstMatchingGroup = null;
    root.querySelectorAll('[data-direct-category]').forEach((group) => {
      let visible = 0;
      group.querySelectorAll('[data-direct-permission-item]').forEach((item) => {
        const match = !term || item.dataset.search.includes(term);
        item.hidden = !match;
        if (match) visible += 1;
      });
      group.hidden = visible === 0;
      if (term && visible && !firstMatchingGroup) firstMatchingGroup = group;
    });
    if (term) {
      root.querySelectorAll('[data-direct-category]').forEach((group) => {
        if (group !== firstMatchingGroup) setOpen(group, false);
      });
      if (firstMatchingGroup) setOpen(firstMatchingGroup, true);
    }
  });

  root.querySelectorAll('[data-direct-category]').forEach(updateGroup);
  updateTotal();
})();
