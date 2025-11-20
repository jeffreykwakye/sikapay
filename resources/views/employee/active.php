<?php 
/**
 * @var string $title
 * @var array $employees An array of all employees for the current tenant.
 * @var callable $h Helper function for HTML escaping.
 */

$this->title = $title;

// Fallback for helper if not provided by the master layout
if (!isset($h)) {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Active Employees</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/dashboard">Dashboard</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Employees</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Active</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Active Employees</h4>
                    <?php if ($this->auth->can('employee:create')): ?>
                        <a href="/employees/create" class="btn btn-primary">
                            <i class="icon-plus"></i> Add New Employee
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($employees)): ?>
                    <div class="alert alert-info text-center" role="alert">
                        <p class="mb-0">No active employees found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table id="basic-datatables" class="display table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Hire Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $employee): ?>
                                    <tr>
                                        <td><?= $h($employee['employee_id']) ?></td>
                                        <td><?= $h($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                        <td><?= $h($employee['email']) ?></td>
                                        <td><?= $h($employee['position_title'] ?? 'N/A') ?></td>
                                        <td>
                                            <span class="badge <?= $employee['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                                <?= $employee['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($employee['hire_date'])) ?></td>
                                        <td class="text-center">
                                            <?php if ($this->auth->can('employee:read_all')): ?>
                                                <a href="/employees/<?= $employee['user_id'] ?>" class="btn btn-sm btn-info" title="View Profile"><i class="icon-eye"></i></a>
                                            <?php endif; ?>
                                            <?php if ($this->auth->can('employee:update')): ?>
                                                <a href="/employees/<?= $employee['user_id'] ?>/edit" class="btn btn-sm btn-warning" title="Edit"><i class="icon-pencil"></i></a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>