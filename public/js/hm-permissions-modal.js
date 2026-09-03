(() => {
  // Permission details inside the user-list modal behave as a single-open
  // accordion. Keeping groups from shrinking also ensures the opened list
  // uses the modal's scroll area instead of collapsing into thin strips.
  document.querySelectorAll('.hm-permissions-tree').forEach((tree) => {
    const groups = [...tree.querySelectorAll(':scope > details.hm-permissions-tree__group')];

    groups.forEach((group) => {
      const summary = group.querySelector(':scope > summary');
      const syncExpandedState = () => summary?.setAttribute('aria-expanded', String(group.open));

      syncExpandedState();
      group.addEventListener('toggle', () => {
        syncExpandedState();
        if (!group.open) return;

        groups.forEach((other) => {
          if (other !== group && other.open) other.open = false;
        });
      });
    });
  });
})();
