(() => {
  const root = document.querySelector('[data-user-permissions]');
  if (!root) return;
  const updateItem = (item, desired) => {
    const box = item.querySelector('[data-decision-toggle]');
    const hidden = item.querySelector('[data-decision-input]');
    const legacyInherited = box.dataset.inherited === '1';
    const decision = desired ? 'allow' : (legacyInherited ? 'deny' : 'inherit');
    hidden.value = decision;
    box.checked = desired;
  };
  const updateCategory = (category) => {
    const boxes = [...category.querySelectorAll('[data-decision-toggle]')];
    const active = boxes.filter(x => x.checked).length;
    const all = category.querySelector('[data-category-select-all]');
    all.checked = active === boxes.length && boxes.length > 0;
    all.indeterminate = active > 0 && active < boxes.length;
    category.querySelector('[data-category-count]').textContent = `${active}/${boxes.length}`;
  };
  const updateTotal = () => root.querySelector('[data-selected-count]').textContent = root.querySelectorAll('[data-decision-toggle]:checked').length;
  root.querySelectorAll('[data-permission-item]').forEach(item => item.querySelector('[data-decision-toggle]').addEventListener('change', e => { updateItem(item, e.target.checked); updateCategory(item.closest('[data-permission-category]')); updateTotal(); }));
  root.querySelectorAll('[data-category-select-all]').forEach(all => all.addEventListener('change', () => { const category = all.closest('[data-permission-category]'); category.querySelectorAll('[data-permission-item]').forEach(item => updateItem(item, all.checked)); updateCategory(category); updateTotal(); }));
  const setCategoryOpen = (category, open) => {
    // Keep the page compact: only one permissions category may be expanded
    // at a time. Closing another category does not recurse because this
    // branch only runs when `open` is true.
    if (open) {
      root.querySelectorAll('[data-permission-category]').forEach(other => {
        if (other !== category) setCategoryOpen(other, false);
      });
    }
    const toggle = category.querySelector('[data-category-toggle]');
    const icon = toggle.querySelector('.bi-chevron-up, .bi-chevron-down');
    toggle.setAttribute('aria-expanded', String(open));
    category.querySelector('[data-category-body]').hidden = !open;
    icon?.classList.toggle('bi-chevron-up', open);
    icon?.classList.toggle('bi-chevron-down', !open);
  };
  root.querySelectorAll('[data-category-toggle]').forEach(toggle => toggle.addEventListener('click', () => {
    const category = toggle.closest('[data-permission-category]');
    setCategoryOpen(category, toggle.getAttribute('aria-expanded') !== 'true');
  }));
  root.querySelector('[data-permission-search]')?.addEventListener('input', e => {
    const term = e.target.value.trim().toLowerCase();
    let firstMatchingCategory = null;
    root.querySelectorAll('[data-permission-category]').forEach(category => {
      let visible = 0;
      category.querySelectorAll('[data-permission-item]').forEach(item => {
        const match = !term || item.dataset.search.includes(term);
        item.hidden = !match;
        if (match) visible++;
      });
      category.hidden = visible === 0;
      if (term && visible && !firstMatchingCategory) firstMatchingCategory = category;
    });
    if (term) {
      // Searching can match several categories, but the same one-open rule
      // still applies; show the first matching category as the useful context.
      root.querySelectorAll('[data-permission-category]').forEach(category => {
        if (category !== firstMatchingCategory) setCategoryOpen(category, false);
      });
      if (firstMatchingCategory) setCategoryOpen(firstMatchingCategory, true);
    }
  });
  root.querySelectorAll('[data-permission-category]').forEach(updateCategory);
})();
