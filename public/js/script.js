document.addEventListener('DOMContentLoaded', () => {

    /* =========================================================
       SIDEBAR
    ========================================================= */

    const openButton = document.getElementById('open-sidebar');
    const closeButton = document.getElementById('close-sidebar');

    const sidebarWrapper = document.getElementById('sidebar-wrapper');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    if (
        !openButton ||
        !closeButton ||
        !sidebarWrapper ||
        !sidebar ||
        !overlay
    ) {
        return;
    }

    let isSidebarOpen = false;

    function openSidebar() {

        if (isSidebarOpen) {
            return;
        }

        isSidebarOpen = true;

        sidebarWrapper.classList.remove('pointer-events-none', 'opacity-0');
        sidebarWrapper.classList.add('pointer-events-auto', 'opacity-100');

        sidebar.classList.remove('-translate-x-full');
        sidebar.classList.add('translate-x-0');

        sidebar.setAttribute('aria-hidden', 'false');

        document.body.classList.add('overflow-hidden');
    }

    function closeSidebar() {

        if (!isSidebarOpen) {

            return;
        }

        isSidebarOpen = false;

        sidebarWrapper.classList.remove('pointer-events-auto', 'opacity-100');
        sidebarWrapper.classList.add('pointer-events-none', 'opacity-0');

        sidebar.classList.remove('translate-x-0');
        sidebar.classList.add('-translate-x-full');

        sidebar.setAttribute('aria-hidden', 'true');

        document.body.classList.remove('overflow-hidden');
    }

    openButton.addEventListener('click', openSidebar);
    closeButton.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeSidebar();
        }
    });

});