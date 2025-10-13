<?php
// app/routes.php (REVISED)
// Returns an array of routes in the format: [METHOD, URI, HANDLER]

return [
    // Root Route
    ['GET', '/', ['HomeController', 'index']],
    
    // Login Routes
    ['GET', '/login', ['LoginController', 'show']],
    ['POST', '/attempt-login', ['LoginController', 'attempt']],
    ['GET', '/logout', ['LoginController', 'logout']],

    // Placeholder Dashboard Route
    ['GET', '/dashboard', ['DashboardController', 'index']],

    // Tenant Management Routes (Super Admin)
    ['GET', '/tenants', ['TenantController', 'index']],
    ['GET', '/tenants/create', ['TenantController', 'create']],
    ['POST', '/tenants', ['TenantController', 'store']],

    // Scope Test Route
    ['GET', '/test-scope', ['TestController', 'index']],
];