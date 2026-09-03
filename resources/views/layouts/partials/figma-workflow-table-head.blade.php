@php($tableCount = method_exists($items, 'total') ? $items->total() : count($items))
<div class="wf-table-head">
    <div class="wf-table-heading"><h2>{{ $title }}</h2><span>{{ $tableCount }}</span></div>
    <div class="wf-table-tools" aria-label="{{ __('technical_failures.table_tools') }}">
        <button type="button" data-wf-sort title="{{ __('technical_failures.sort') }}"><i class="bi bi-arrow-down-up"></i></button>
        <button type="button" data-wf-export title="{{ __('technical_failures.export') }}"><i class="bi bi-download"></i></button>
        <button type="button" data-wf-refresh title="{{ __('technical_failures.refresh') }}"><i class="bi bi-arrow-clockwise"></i></button>
    </div>
</div>
@once
@push('scripts')
<script>
document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-wf-sort],[data-wf-export],[data-wf-refresh]');
    if (!button) return;
    const panel = button.closest('.wf-table-panel');
    const table = panel && panel.querySelector('table');
    if (button.matches('[data-wf-refresh]')) { window.location.reload(); return; }
    if (!table) return;
    if (button.matches('[data-wf-sort]')) {
        const body = table.tBodies[0];
        const rows = Array.from(body.rows).filter(row => row.cells.length > 1);
        const descending = button.dataset.direction !== 'desc';
        rows.sort((a, b) => a.cells[0].innerText.trim().localeCompare(b.cells[0].innerText.trim(), undefined, {numeric: true}) * (descending ? -1 : 1));
        rows.forEach(row => body.appendChild(row));
        button.dataset.direction = descending ? 'desc' : 'asc';
        return;
    }
    const csv = Array.from(table.rows).map(row => Array.from(row.cells).slice(0, -1).map(cell => '"' + cell.innerText.trim().replaceAll('"', '""') + '"').join(',')).join('\n');
    const link = document.createElement('a');
    link.href = URL.createObjectURL(new Blob(['\ufeff' + csv], {type: 'text/csv;charset=utf-8'}));
    link.download = (document.title || 'records') + '.csv';
    link.click(); URL.revokeObjectURL(link.href);
});
</script>
@endpush
@endonce
