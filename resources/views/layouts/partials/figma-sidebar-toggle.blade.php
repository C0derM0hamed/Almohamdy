{{-- Mobile sidebar toggle. Rendered next to the brand/logo (navbar) or at the
     start of the page header so it never floats alone on the far edge. --}}
<button
    type="button"
    class="hm-figma-tools__icon hm-figma-tools__mobile-sidebar"
    data-hm-sidebar-toggle
    aria-controls="hmAppSidebar"
    aria-expanded="false"
    data-label-show="{{ __('dashboard.toggle_sidebar_show') }}"
    data-label-hide="{{ __('dashboard.toggle_sidebar_hide') }}"
    aria-label="{{ __('dashboard.toggle_sidebar_show') }}"
>
    <i class="bi bi-list" aria-hidden="true"></i>
</button>
