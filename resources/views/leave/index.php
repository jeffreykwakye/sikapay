<?php
/**
 * @var string $title
 * @var bool $isApprover
 * @var array $pendingApplications
 * @var array $myApplications
 * @var array $myBalances
 * @var array $leaveTypes
 * @var callable $h
 * @var object $CsrfToken
 */
$this->title = $title;
?>

<div class="page-header">
    <h3 class="fw-bold mb-3">Leave Approvals</h3>
    <ul class="breadcrumbs mb-3">
        <li class="nav-home"><a href="/dashboard"><i class="icon-home"></i></a></li>
        <li class="separator"><i class="icon-arrow-right"></i></li>
        <li class="nav-item"><a href="/leave">Leave Approvals</a></li>
    </ul>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-warning bubble-shadow-small">
                                            <i class="fas fa-hourglass-half"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Pending Approvals</p>
                                            <h4 class="card-title"><?= $h($pendingCount) ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-action">
                                    <a href="/leave/pending" class="btn btn-sm btn-light btn-block">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-info bubble-shadow-small">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Approved Leaves</p>
                                            <h4 class="card-title"><?= $h(count($approvedApplications ?? [])) ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-action">
                                    <a href="/leave/approved" class="btn btn-sm btn-light btn-block">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-danger bubble-shadow-small">
                                            <i class="fas fa-person-walking-luggage"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Staff On Leave</p>
                                            <h4 class="card-title"><?= $h(count($onLeaveStaff ?? [])) ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-action">
                                    <a href="/leave/on-leave" class="btn btn-sm btn-light btn-block">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card card-stats card-round">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-icon">
                                        <div class="icon-big text-center icon-success bubble-shadow-small">
                                            <i class="fas fa-person-walking"></i>
                                        </div>
                                    </div>
                                    <div class="col col-stats ms-3 ms-sm-0">
                                        <div class="numbers">
                                            <p class="card-category">Returning Soon</p>
                                            <h4 class="card-title"><?= $h(count($returningStaff ?? [])) ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-action">
                                    <a href="/leave/returning" class="btn btn-sm btn-light btn-block">View Details</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if (!$isApprover): ?>
                    <div class="alert alert-info text-center mt-4" role="alert">
                        You do not have permission to view leave management details. Please contact your administrator.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
