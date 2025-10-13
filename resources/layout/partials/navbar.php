<nav class="navbar">
    <a href="/dashboard" class="nav-item">Dashboard</a>
    
    <?php 
    // $isSuperAdmin is available via the master's 'extract' call
    if (isset($isSuperAdmin) && $isSuperAdmin): 
    ?>
        <a href="/tenants" class="nav-item nav-admin">Tenant Management</a>
        <a href="/plans" class="nav-item nav-admin">Plans</a>
    <?php endif; ?>
</nav>