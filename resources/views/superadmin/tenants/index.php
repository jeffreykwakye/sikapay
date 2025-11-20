<div class="page-header">
    <h3 class="fw-bold mb-3"><?php echo $h($data['title']); ?></h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home">
            <a href="/">
                <i class="icon-home"></i>
            </a>
        </li>
        <li class="separator">
            <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
            <a href="/">Dashboard</a>
        </li>
        <li class="separator">
            <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
            <a href="#"><?php echo $h($data['title']); ?></a>
        </li>
    </ul>
</div>

<div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
    <div class="ms-md-auto py-2 py-md-0">
        <a href="/tenants/create" class="btn btn-primary btn-round">Create New Tenant</a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title"><?php echo $h($data['title']); ?></h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="multi-filter-select" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Subdomain</th>
                                <th>Status</th>
                                <th>Flow</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Subdomain</th>
                                <th>Status</th>
                                <th>Flow</th>
                                <th>Actions</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php foreach ($data['tenants'] as $tenant): ?>
                            <tr>
                                <td><?php echo $h((string)$tenant['id']); ?></td>
                                <td><?php echo $h($tenant['name']); ?></td>
                                <td><?php echo $h($tenant['subdomain']); ?></td>
                                <td><?php echo $h($tenant['subscription_status']); ?></td>
                                <td><?php echo $h($tenant['payroll_approval_flow']); ?></td>
                                <td>
                                    <a href="/tenants/<?php echo $h((string)$tenant['id']); ?>" class="btn btn-info btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>              
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>