<?php
/**
 * @var string $title
 * @var array $leaveTypes
 * @var callable $h
 * @var object $CsrfToken
 */
$this->title = $title;
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Manage Leave Types</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/leave">Leave Management</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Leave Types</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Leave Types</h4>
                    <button id="add-leave-type-btn" class="btn btn-primary btn-round ms-auto">
                        <i class="fa fa-plus"></i>
                        Add New Leave Type
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="leave-types-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Default Days</th>
                                <th>Accrued</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveTypes as $type): ?>
                            <tr>
                                <td><?= $h($type['name']) ?></td>
                                <td><?= $h($type['default_days']) ?></td>
                                <td><?= $type['is_accrued'] ? 'Yes' : 'No' ?></td>
                                <td>
                                    <span class="badge <?= $type['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                        <?= $type['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="form-button-action">
                                        <button type="button" data-bs-toggle="tooltip" title="Edit" class="btn btn-link btn-primary btn-lg edit-leave-type-btn"
                                            data-id="<?= $h($type['id']) ?>" 
                                            data-name="<?= $h($type['name']) ?>"
                                            data-default_days="<?= $h($type['default_days']) ?>"
                                            data-is_accrued="<?= $type['is_accrued'] ? '1' : '0' ?>"
                                            data-is_active="<?= $type['is_active'] ? '1' : '0' ?>">
                                            <i class="fa fa-edit"></i>
                                        </button>
                                        <form action="/leave/types/delete/<?= $type['id'] ?>" method="POST" class="d-inline">
                                            <?= $CsrfToken::field() ?>
                                            <button type="submit" data-bs-toggle="tooltip" title="Delete" class="btn btn-link btn-danger" onclick="return confirm('Are you sure you want to delete this leave type?')">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
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

<!-- Add/Edit Leave Type Modal -->
<div class="modal fade" id="leaveTypeModal" tabindex="-1" aria-labelledby="leaveTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="leaveTypeForm" action="" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="leaveTypeModalLabel">Add Leave Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= $CsrfToken::field() ?>
                    <input type="hidden" id="leaveTypeId" name="id">
                    <div class="mb-3">
                        <label for="name" class="form-label">Leave Type Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="default_days" class="form-label">Default Days per Year</label>
                        <input type="number" class="form-control" id="default_days" name="default_days" required min="0">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="is_accrued" name="is_accrued">
                        <label class="form-check-label" for="is_accrued">
                            Is this leave type accrued over time?
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="is_active" name="is_active" checked>
                        <label class="form-check-label" for="is_active">
                            Is this leave type active?
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="/assets/js/leave/types.js"></script>
