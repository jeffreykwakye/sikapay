<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title><?= $h($title ?? 'SikaPay Platform') ?></title>
        <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
        <link rel="icon" href="/assets/images/tenant_logos/1/Icon Fill.svg" type="image/svg+xml" />
        <!-- Fonts and icons -->
        <script src="/assets/js/plugin/webfont/webfont.min.js"></script>
        <script src="/assets/js/fontloader-config.js"></script>

        <!-- CSS Files -->
        <link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
        <link rel="stylesheet" href="/assets/css/plugins.min.css" />
        <link rel="stylesheet" href="/assets/css/kaiadmin.min.css" />
        <link rel="stylesheet" href="/assets/css/custom.css" /> <!-- NEW: Custom styles for the project -->

        <!-- CSS Just for demo purpose, don't include it in your project -->
        <link rel="stylesheet" href="/assets/css/demo.css" />
    </head>
    <body class="<?= (isset($subscriptionStatus) && $subscriptionStatus === 'past_due') ? 'has-subscription-notice' : '' ?>">
        <?php if (isset($subscriptionStatus) && $subscriptionStatus === 'past_due'): ?>
            <div class="subscription-notice">
                Your subscription is past due. Please <a href="/subscription">renew your plan</a> to restore full functionality.
            </div>
        <?php endif; ?>
        <div class="wrapper">
            <!-- Sidebar -->
            <?php require __DIR__ . '/partials/sidebar.php'; ?>
            <!-- End Sidebar -->

            <div class="main-panel">
                <div class="main-header">
                    <div class="main-header-logo">
                        <!-- Logo Header -->
                        <?php require __DIR__ . '/partials/header.php'; ?>
                        <!-- End Logo Header -->
                    </div>
                    <!-- Navbar Header -->
                    <?php require __DIR__ . '/partials/navbar.php'; ?>
                    <!-- End Navbar -->
                </div>

                <div class="container">
                    <div class="page-inner">
                        <?php 
                        // This is where the page-specific view fragment is loaded
                        if (isset($__content_file)) {
                            require $__content_file;
                        }
                        ?>
                    </div>
                </div>
                <?php require __DIR__ . '/partials/footer.php'; ?>
            </div>

             

            <!-- Custom template | don't include it in your project! -->
            <!-- <div class="custom-template">
                <div class="title">Settings</div>
                    <div class="custom-content">
                        <div class="switcher">
                            <div class="switch-block">
                                <h4>Logo Header</h4>
                                <div class="btnSwitch">
                                    <button
                                        type="button"
                                        class="selected changeLogoHeaderColor"
                                        data-color="dark">
                                    </button>
                                    <button
                                        type="button"
                                        class="selected changeLogoHeaderColor"
                                        data-color="blue"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="purple"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="light-blue"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="green"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="orange"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="red"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="white"
                                        ></button>
                                    <br />
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="dark2"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="blue2"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="purple2"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="light-blue2"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="green2"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="orange2"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeLogoHeaderColor"
                                        data-color="red2"
                                        ></button>
                                </div>
                            </div>
                            <div class="switch-block">
                                <h4>Navbar Header</h4>
                                <div class="btnSwitch">
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="dark"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="blue"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="purple"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="light-blue"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="green"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="orange"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="red"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="white"
                                    ></button>
                                    <br />
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="dark2"
                                    ></button>
                                    <button
                                    type="button"
                                    class="selected changeTopBarColor"
                                    data-color="blue2"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="purple2"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="light-blue2"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="green2"
                                    ></button>
                                    <button
                                    type="button"
                                    class="changeTopBarColor"
                                    data-color="orange2"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeTopBarColor"
                                        data-color="red2"
                                        ></button>
                                </div>
                            </div>
                            <div class="switch-block">
                                <h4>Sidebar</h4>
                                <div class="btnSwitch">
                                    <button
                                        type="button"
                                        class="selected changeSideBarColor"
                                        data-color="white"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeSideBarColor"
                                        data-color="dark"
                                        ></button>
                                    <button
                                        type="button"
                                        class="changeSideBarColor"
                                        data-color="dark2"
                                        ></button>
                                </div>
                            </div>
                        </div>
                    </div>
                <div class="custom-toggle">
                    <i class="icon-settings"></i>
                </div>
            </div> -->
            <!-- End Custom template -->
        </div>
        <!--   Core JS Files   -->
        <script src="/assets/js/core/jquery-3.7.1.min.js"></script>
        <script src="/assets/js/core/popper.min.js"></script>
        <script src="/assets/js/core/bootstrap.min.js"></script>

        <!-- jQuery Scrollbar -->
        <script src="/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

        <!-- Chart JS -->
        <script src="/assets/js/plugin/chart.js/chart.min.js"></script>

        <!-- jQuery Sparkline -->
        <script src="/assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

        <!-- Chart Circle -->
        <script src="/assets/js/plugin/chart-circle/circles.min.js"></script>

        <!-- Datatables -->
        <script src="/assets/js/plugin/datatables/datatables.min.js"></script>

        <!-- Bootstrap Notify -->
        <script src="/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

        <!-- jQuery Vector Maps -->
        <script src="/assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
        <script src="/assets/js/plugin/jsvectormap/world.js"></script>

        <!-- Google Maps Plugin -->
        <script src="/assets/js/plugin/gmaps/gmaps.js"></script>

        <!-- Sweet Alert -->
        <script src="/assets/js/plugin/sweetalert/sweetalert.min.js"></script>

        <!-- Kaiadmin JS -->
        <script src="/assets/js/kaiadmin.min.js"></script>

        <!-- Kaiadmin DEMO methods, don't include it in your project! -->
        <script src="/assets/js/setting-demo2.js"></script>
        <script src="/assets/js/basic-datatable.js"></script>
        <script src="/assets/js/multi-select-datatable.js"></script>
        <script src="/assets/js/tenants/dashboard.js"></script>
        <script src="/assets/js/sidebar-active.js"></script>
    </body>
</html>
