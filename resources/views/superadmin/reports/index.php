<?php
/**
 * @var string $title
 * @var callable $h
 */
?>

<div class="page-header">
    <h3 class="fw-bold mb-3"><?= $h($title) ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/super/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/super/dashboard">Super Admin</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">System Reports</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Available System Reports</h4>
            </div>
            <div class="card-body">
                <p>This section will contain various system-wide reports for Super Administrators.</p>
                <ul>
                    <li>Tenant Growth Report</li>
                    <li>Subscription Revenue Report</li>
                    <li>User Activity Report</li>
                    <li>System Health & Performance</li>
                </ul>
                <p>Reports will be generated here.</p>
            </div>
        </div>
    </div>
</div>
