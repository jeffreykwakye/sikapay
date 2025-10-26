<?php
// Variables provided by the Controller: $departments, $successMessage, $errorMessage, $flashWarning.
// Helpers: $h, $CsrfToken

$departments = $departments ?? [];

// Helper to escape HTML output
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Department Management</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/dashboard">Dashboard</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Department Management</a></li>
    </ul>
</div>

<div class="page-inner">
    
    <div class="row">
        <div class="col-sm-12 col-xl-12">
            <div class="bg-light rounded p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="mb-0">Department Management</h6>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="icon-plus me-2"></i> Add New Department
                    </button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Available Departments</div> 
                    </div>
                    <div class="card-body">

                        <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= $h($successMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($errorMessage)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= $h($errorMessage) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>


                        <div class="table-responsive">
                            <?php if (empty($departments)): ?>
                            <p class="text-center text-muted">No departments have been created yet.</p>
                            <?php else: ?>
                            <table id="multi-filter-select" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Department Name</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>Department Name</th>
                                        </tr>
                                </tfoot>
                                <tbody>
                                    <?php foreach ($departments as $dept): ?>
                                    <tr>
                                        <td><?= $h($dept['name']) ?></td>
                                        <td class="text-center">
                                            <button 
                                                class="btn btn-sm btn-info me-1" 
                                                title="Edit" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?= $dept['id'] ?>"
                                                data-name="<?= $h($dept['name']) ?>"
                                            >
                                                <i class="icon-pencil"></i>
                                            </button>
                                            
                                            <button 
                                                class="btn btn-sm btn-danger delete-btn" 
                                                title="Delete"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteConfirmModal"
                                                data-id="<?= $dept['id'] ?>"
                                                data-name="<?= $h($dept['name']) ?>"
                                            >
                                                <i class="icon-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                            
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createForm" action="/departments/store" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createModalLabel">Create New Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Department Name (Required)</label>
                        <input type="text" class="form-control" id="create_name" name="name" required maxlength="100" placeholder="e.g., Sales, Marketing">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Department</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="POST"> 
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="editModalLabel">Edit Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Department Name (Required)</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required maxlength="100">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel"><i class="icon-trash me-2"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteForm" method="POST">
                <div class="modal-body text-center">
                    <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                    <p>Are you sure you want to delete the department:</p>
                    <p class="fw-bold text-danger fs-5" id="deleteDepartmentName"></p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Yes, Delete It</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/assets/js/tenants/department-management.js"></script>