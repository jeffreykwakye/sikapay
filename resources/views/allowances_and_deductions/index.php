<?php
/**
 * @var string $title
 * @var array $elements An array of all custom payroll elements for the tenant.
 * @var callable $h Helper function for HTML escaping.
 * @var object $CsrfToken Class with static method getToken().
 */

$this->title = 'Manage Payroll Elements';

// Fallback for helper if not provided by the master layout
if (!isset($h)) {
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Manage Payroll Elements</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/dashboard">Dashboard</a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="#">Payroll Elements</a></li>
    </ul>
</div>

<div class="page-inner">
    
    <div class="row">
        <div class="col-sm-12 col-xl-12">
            <div class="bg-light rounded p-4">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="mb-0">Custom Allowances & Deductions</h6>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="icon-plus me-2"></i> Add New Element
                    </button>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Defined Payroll Elements</div> 
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
                            <?php if (empty($elements)): ?>
                            <p class="text-center text-muted">No custom payroll elements have been defined yet.</p>
                            <?php else: ?>
                            <table id="multi-filter-select" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Amount Type</th>
                                        <th>Default Amount</th>
                                        <th>Calculation Base</th>
                                        <th>Taxable</th>
                                        <th>SSNIT Chargeable</th>
                                        <th>Recurring</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tfoot>
                                    <tr>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Amount Type</th>
                                        <th>Default Amount</th>
                                        <th>Calculation Base</th>
                                        <th>Taxable</th>
                                        <th>SSNIT Chargeable</th>
                                        <th>Recurring</th>
                                        <!-- <th class="text-center">Actions</th> -->
                                    </tr>
                                </tfoot>
                                <tbody>
                                    <?php foreach ($elements as $element): ?>
                                    <tr>
                                        <td><?= $h($element['name']) ?></td>
                                        <td><?= $h(ucfirst($element['category'])) ?></td>
                                        <td><?= $h(ucfirst($element['amount_type'])) ?></td>
                                        <td><?= $h(number_format((float)$element['default_amount'], 2)) ?></td>
                                        <td><?= $h($element['calculation_base'] ?? 'N/A') ?></td>
                                        <td><?= $element['is_taxable'] ? 'Yes' : 'No' ?></td>
                                        <td><?= $element['is_ssnit_chargeable'] ? 'Yes' : 'No' ?></td>
                                        <td><?= $element['is_recurring'] ? 'Yes' : 'No' ?></td>
                                        <td class="text-center">
                                            <button 
                                                class="btn btn-sm btn-info me-1" 
                                                title="Edit" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal"
                                                data-id="<?= $element['id'] ?>"
                                                data-name="<?= $h($element['name']) ?>"
                                                data-category="<?= $h($element['category']) ?>"
                                                data-amount-type="<?= $h($element['amount_type']) ?>"
                                                data-default-amount="<?= $h($element['default_amount']) ?>"
                                                data-calculation-base="<?= $h($element['calculation_base']) ?>"
                                                data-is-taxable="<?= $h($element['is_taxable']) ?>"
                                                data-is-ssnit-chargeable="<?= $h($element['is_ssnit_chargeable']) ?>"
                                                data-is-recurring="<?= $h($element['is_recurring']) ?>"
                                                data-description="<?= $h($element['description']) ?>"
                                            >
                                                <i class="icon-pencil"></i>
                                            </button>
                                            
                                            <button 
                                                class="btn btn-sm btn-danger delete-btn" 
                                                title="Delete"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteConfirmModal"
                                                data-id="<?= $element['id'] ?>"
                                                data-name="<?= $h($element['name']) ?>"
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

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="createForm" action="/payroll-elements" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="createModalLabel">Add New Payroll Element</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= $CsrfToken::field() ?>
                    
                    <div class="mb-3">
                        <label for="create_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="create_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_category" class="form-label">Category</label>
                        <select class="form-select" id="create_category" name="category" required>
                            <option value="allowance">Allowance</option>
                            <option value="deduction">Deduction</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="create_amount_type" class="form-label">Amount Type</label>
                        <select class="form-select" id="create_amount_type" name="amount_type" required>
                            <option value="fixed">Fixed</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="create_default_amount" class="form-label">Default Amount</label>
                        <input type="number" class="form-control" id="create_default_amount" name="default_amount" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="create_calculation_base" class="form-label">Calculation Base (for Percentage)</label>
                        <select class="form-select" id="create_calculation_base" name="calculation_base">
                            <option value="">N/A (Fixed Amount)</option>
                            <option value="basic_salary">Basic Salary</option>
                            <option value="gross_salary">Gross Salary</option>
                            <option value="net_salary">Net Salary</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="create_is_taxable" name="is_taxable" value="1" checked>
                        <label class="form-check-label" for="create_is_taxable">Is Taxable?</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="create_is_ssnit_chargeable" name="is_ssnit_chargeable" value="1" checked>
                        <label class="form-check-label" for="create_is_ssnit_chargeable">Is SSNIT Chargeable?</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="create_is_recurring" name="is_recurring" value="1">
                        <label class="form-check-label" for="create_is_recurring">Is Recurring?</label>
                    </div>
                    <div class="mb-3">
                        <label for="create_description" class="form-label">Description</label>
                        <textarea class="form-control" id="create_description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Element</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="POST">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="editModalLabel">Edit Payroll Element</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?= $CsrfToken::field() ?>
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category" class="form-label">Category</label>
                        <select class="form-select" id="edit_category" name="category" required>
                            <option value="allowance">Allowance</option>
                            <option value="deduction">Deduction</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_amount_type" class="form-label">Amount Type</label>
                        <select class="form-select" id="edit_amount_type" name="amount_type" required>
                            <option value="fixed">Fixed</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_default_amount" class="form-label">Default Amount</label>
                        <input type="number" class="form-control" id="edit_default_amount" name="default_amount" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_calculation_base" class="form-label">Calculation Base (for Percentage)</label>
                        <select class="form-select" id="edit_calculation_base" name="calculation_base">
                            <option value="">N/A (Fixed Amount)</option>
                            <option value="basic_salary">Basic Salary</option>
                            <option value="gross_salary">Gross Salary</option>
                            <option value="net_salary">Net Salary</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_taxable" name="is_taxable" value="1">
                        <label class="form-check-label" for="edit_is_taxable">Is Taxable?</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_ssnit_chargeable" name="is_ssnit_chargeable" value="1">
                        <label class="form-check-label" for="edit_is_ssnit_chargeable">Is SSNIT Chargeable?</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_is_recurring" name="is_recurring" value="1">
                        <label class="form-check-label" for="edit_is_recurring">Is Recurring?</label>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
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

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmModalLabel"><i class="icon-trash me-2"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="deleteForm" method="POST">
                <div class="modal-body text-center">
                    <?= $CsrfToken::field() ?>
                    <p>Are you sure you want to delete the payroll element:</p>
                    <p class="fw-bold text-danger fs-5" id="deleteElementName"></p>
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

<?php //$this->start('page_specific_scripts') ?>
<script src="/assets/js/payroll-elements/management.js"></script>
<?php //$this->end() ?>