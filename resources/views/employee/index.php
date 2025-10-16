<?php 
// resources/views/employee/index.php

// Assume $employees is passed from the controller, and $this->auth->can() is available

$this->title = $title; // 'Employee Directory'
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3">Employee Directory</h1>
    <?php if ($this->auth->can('employee:create')): ?>
        <a href="/employees/create" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add New Employee
        </a>
    <?php endif; ?>
</div>

<?php if (empty($employees)): ?>
    <div class="alert alert-info" role="alert">
        There are no employees added yet for this tenant. 
        <?php if ($this->auth->can('employee:create')): ?>
            Start by adding the first one!
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Hired Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?= htmlspecialchars($employee['employee_id']) ?></td>
                            <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                            <td><?= htmlspecialchars($employee['email']) ?></td>
                              <td><?= htmlspecialchars($employee['position_title'] ?? 'N/A') ?></td>
                            <td>
                                <span class="badge 
                                    <?= $employee['employment_status'] === 'Active' ? 'bg-success' : 'bg-warning' ?>">
                                    <?= htmlspecialchars($employee['employment_status'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td><?= date('Y-m-d', strtotime($employee['hire_date'])) ?></td>
                            <td>
                                <?php if ($this->auth->can('employee:read_all')): ?>
                                    <a href="/employees/<?= $employee['user_id'] ?>" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                <?php endif; ?>
                                <?php if ($this->auth->can('employee:update')): ?>
                                    <a href="/employees/<?= $employee['user_id'] ?>/edit" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                <?php endif; ?>
                                </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>