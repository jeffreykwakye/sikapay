<!-- <footer class="main-footer">
    &copy; <//?= date('Y') ?> SikaPay. All rights reserved. | 
    <small>Tenant: <//?= $tenantName ?? 'N/A' ?></small>
</footer> -->

<footer class="footer">
    <div class="container-fluid d-flex justify-content-between">
        <nav class="pull-left">
            <ul class="nav">
                <li class="nav-item">
                    <small><strong><?= $tenantName ?? 'N/A' ?></strong></small>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="#"> Help </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"> Licenses </a>
                </li> -->
            </ul>
        </nav>
        <div class="copyright">
            &copy; <?= date('Y') ?> SikaPay. All rights reserved.
        </div>
        <div>
            Distributed by
            <a target="_blank" href="https://sikapay.com/">SikaPay</a>.
        </div>
    </div>
</footer>