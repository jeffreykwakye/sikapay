<?php
$this->title = $title;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Statutory Reports</h4>
                </div>
                <div class="card-body">
                    <p>Select a report to download.</p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">PAYE Report</h5>
                                    <p class="card-text">Monthly PAYE tax deductions for all employees.</p>
                                    <a href="/reports/paye/pdf" class="btn btn-primary">Download PDF</a>
                                    <a href="/reports/paye/excel" class="btn btn-primary">Download Excel</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">SSNIT Report</h5>
                                    <p class="card-text">Monthly SSNIT contributions for all employees.</p>
                                    <a href="/reports/ssnit/pdf" class="btn btn-primary">Download PDF</a>
                                    <a href="/reports/ssnit/excel" class="btn btn-primary">Download Excel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
