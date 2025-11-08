document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const sidebar = document.querySelector('.sidebar');
    if (!sidebar) return;

    let activeLink = null;

    // Special handling for Dashboard alias: if on /dashboard, activate link with href="/"
    if (currentPath === '/dashboard') {
        activeLink = sidebar.querySelector('ul.nav a[href="/"]');
    }

    // If not found via alias, or if currentPath is not /dashboard, try exact match
    if (!activeLink) {
        activeLink = sidebar.querySelector(`ul.nav a[href="${currentPath}"]`);
    }

    if (activeLink) {
        // Add 'active' to the direct parent <li> of the active link
        const activeLi = activeLink.closest('li');
        if (activeLi) {
            activeLi.classList.add('active');

            // Check if this is a sub-item within a collapsible menu
            const collapseParent = activeLi.closest('.collapse');
            if (collapseParent) {
                // Add 'show' class to make the collapsible section visible
                collapseParent.classList.add('show');

                // Find the parent nav-item that controls this collapse and make it active
                const navItemParent = collapseParent.closest('.nav-item');
                if (navItemParent) {
                    navItemParent.classList.add('active');
                }
            }
        }
    }
});