<aside class="sidebar">
    <nav class="sidebar-nav">
        <?php 
        // $isSuperAdmin is available via the master's 'extract' call
        if (isset($isSuperAdmin) && $isSuperAdmin): 
        ?>
            <h3>System Tools</h3>
            <a href="/audit-logs">Audit Logs</a>
            <a href="/configs">System Configs</a>
        
        <?php else: ?>
            <h3>Payroll Module</h3>
            <a href="/payroll/new">New Cycle</a>
            <a href="/payroll/history">Cycle History</a>
            
            <h3>HR/Records</h3>
            <a href="/employees">Employee Records</a>
            <a href="/departments">Departments</a>
            
            <h3>Reporting</h3>
            <a href="/reports">Financial Reports</a>
        <?php endif; ?>
    </nav>
</aside>