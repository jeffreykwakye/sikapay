<?php
// Ensure the necessary variables are extracted
if (!isset($positions) || !isset($departments)) {
    // Should be caught by the Controller, but a safety measure
    throw new Exception("Required data (positions and departments) not provided to the view.");
}

// Helper to escape HTML output
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Position Manangement</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/dashboard">Dashboard</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Position Management</a></li>
    </ul>
</div>

<div class="page-inner">
    
    <div class="row">
        <div class="col-sm-12 col-xl-12">
            <div class="bg-light rounded p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="mb-0">Position Management</h6>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="icon-plus me-2"></i> Add New Position
                    </button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Available Positions</div> 
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
                            <?php if (empty($positions)): ?>
                            <p class="text-center text-muted">No positions have been created yet.</p>
                            <?php else: ?>
                            <table id="multi-filter-select" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Position Title</th>
                                        <th>Department</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>Position Title</th>
                                        <th>Department</th>
                                        <!-- <th class="text-center">Actions</th> -->
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <?php foreach ($positions as $pos): ?>
                                    <tr>
                                        <td><?= $h($pos['title']) ?></td>
                                        <td>
                                            <?= $pos['department_name'] ? $h($pos['department_name']) : '<span class="text-muted">None</span>' ?>
                                        </td>
                                        <td class="text-center">
                                            <button 
                                                class="btn btn-sm btn-info me-1" 
                                                title="Edit" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?= $pos['id'] ?>"
                                                data-title="<?= $h($pos['title']) ?>"
                                                data-department-id="<?= $pos['department_id'] ?? 0 ?>"
                                            >
                                                <i class="icon-pencil"></i>
                                            </button>
                                            
                                            <button 
                                                class="btn btn-sm btn-danger delete-btn" 
                                                title="Delete"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteConfirmModal"
                                                data-id="<?= $pos['id'] ?>"
                                                data-title="<?= $h($pos['title']) ?>"
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


    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="createForm" action="/positions/store" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="createModalLabel">Create New Position</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                        
                        <div class="mb-3">
                            <label for="create_title" class="form-label">Position Title (Required)</label>
                            <input type="text" class="form-control" id="create_title" name="title" required maxlength="255">
                        </div>

                        <div class="mb-3">
                            <label for="create_department_id" class="form-label">Department (Optional)</label>
                            <select class="form-select" id="create_department_id" name="department_id">
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= $h($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create Position</button>
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
                        <h5 class="modal-title" id="editModalLabel">Edit Position</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= $CsrfToken::getToken() ?>">
                        
                        <div class="mb-3">
                            <label for="edit_title" class="form-label">Position Title (Required)</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required maxlength="255">
                        </div>

                        <div class="mb-3">
                            <label for="edit_department_id" class="form-label">Department (Optional)</label>
                            <select class="form-select" id="edit_department_id" name="department_id">
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= $h($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
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
                        <p>Are you sure you want to delete the position:</p>
                        <p class="fw-bold text-danger fs-5" id="deletePositionTitle"></p>
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
</div>

<script src="/assets/js/tenants/position-management.js"></script>