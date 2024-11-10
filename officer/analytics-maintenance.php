<?php
session_start();
include '../officer/header.php';
include '../includes/db_connection.php';
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">

<div class="body-wrapper">
    <div class="container-fluid">
        <?php
        // Including sidebar if it exists
        $sidebar_path = '../officer/sidebar.php';
        if (file_exists($sidebar_path)) {
            include $sidebar_path;
        } else {
            echo "<!-- Sidebar not found at $sidebar_path -->";
        }
        ?>
        <div class="card card-body py-3">
            <div class="row align-items-center">
                <div class="col-12">
                    <div class="d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-4 mb-sm-0 card-title">Analytics</h4>
                        <nav aria-label="breadcrumb" class="ms-auto">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item d-flex align-items-center">
                                    <a class="text-muted text-decoration-none d-flex" href="../officer/home.php">
                                        <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                    </a>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <h5 class="border-bottom py-2 px-4 mb-4">Maintenance</h5>

        <div class="body-wrapper m-0">
            <div class="container-fluid p-0">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card bg-secondary-subtle overflow-hidden shadow-none">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-9">
                                    <div>
                                        <span class="text-dark-light fw-semibold">Customers</span>
                                        <div class="hstack gap-6">
                                            <h5 class="card-title fw-semibold mb-0 fs-7">14,872</h5>
                                            <span class="fs-11 text-dark-light fw-semibold">+6.4%</span>
                                        </div>
                                    </div>
                                    <span class="round-48 d-flex align-items-center justify-content-center bg-white rounded">
                                        <iconify-icon icon="solar:pie-chart-3-line-duotone" class="text-secondary fs-6"></iconify-icon>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card bg-danger-subtle overflow-hidden shadow-none">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-15">
                                    <div>
                                        <span class="text-dark-light fw-semibold fs-12">Projects</span>
                                        <div class="hstack gap-6">
                                            <h5 class="card-title fw-semibold mb-0 fs-7">78,298</h5>
                                            <span class="fs-11 text-dark-light fw-semibold">-12%</span>
                                        </div>
                                    </div>
                                    <span class="round-48 d-flex align-items-center justify-content-center bg-white rounded">
                                        <iconify-icon icon="solar:layers-linear" class="text-danger fs-6"></iconify-icon>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title fw-semibold">Your Performance</h5>
                                <p class="card-subtitle mb-0 lh-base">Last check on 25 February</p>
                                <div class="row mt-4">
                                    <div class="col-md-6">
                                        <div class="vstack gap-9 mt-2">
                                            <div class="hstack align-items-center gap-3">
                                                <div class="d-flex align-items-center justify-content-center round-48 bg-primary-subtle flex-shrink-0">
                                                    <iconify-icon icon="solar:shop-2-linear" class="fs-7 text-primary"></iconify-icon>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 text-nowrap">64 new orders</h6>
                                                    <span>Processing</span>
                                                </div>
                                            </div>
                                            <div class="hstack align-items-center gap-3">
                                                <div class="d-flex align-items-center justify-content-center round-48 bg-danger-subtle">
                                                    <iconify-icon icon="solar:filters-outline" class="fs-7 text-danger"></iconify-icon>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">4 orders</h6>
                                                    <span>On hold</span>
                                                </div>
                                            </div>
                                            <div class="hstack align-items-center gap-3">
                                                <div class="d-flex align-items-center justify-content-center round-48 bg-secondary-subtle">
                                                    <iconify-icon icon="solar:pills-3-linear" class="fs-7 text-secondary"></iconify-icon>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">12 orders</h6>
                                                    <span>Delivered</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="text-center mt-sm-n7">
                                            <div id="your-performance"></div>
                                            <h2 class="fs-8">275</h2>
                                            <p class="mb-0">Learn insights on how to manage all aspects of your startup.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start justify-content-between">
                                            <div>
                                                <h5 class="card-title fw-semibold">Customers</h5>
                                                <p class="card-subtitle mb-0">Last 7 days</p>
                                            </div>
                                            <span class="fs-11 text-success fw-semibold lh-lg">+26.5%</span>
                                        </div>
                                        <div class="py-4 my-1">
                                            <div id="customers-area"></div>
                                        </div>
                                        <div class="d-flex flex-column align-items-center gap-2 w-100 mt-3">
                                            <div class="d-flex align-items-center gap-2 w-100">
                                                <span class="d-block flex-shrink-0 round-8 bg-primary rounded-circle"></span>
                                                <h6 class="fs-3 fw-normal text-muted mb-0">April 07 - April 14</h6>
                                                <h6 class="fs-3 fw-normal mb-0 ms-auto text-muted">6,380</h6>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 w-100">
                                                <span class="d-block flex-shrink-0 round-8 bg-light rounded-circle"></span>
                                                <h6 class="fs-3 fw-normal text-muted mb-0">Last Week</h6>
                                                <h6 class="fs-3 fw-normal mb-0 ms-auto text-muted">4,298</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title fw-semibold">Sales Overview</h5>
                                        <p class="card-subtitle mb-1">Last 7 days</p>
                                        <div class="position-relative labels-chart">
                                            <span class="fs-11 label-1">0%</span>
                                            <span class="fs-11 label-2">25%</span>
                                            <span class="fs-11 label-3">50%</span>
                                            <span class="fs-11 label-4">75%</span>
                                            <div id="sales-overview"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional sections, transaction table, etc., as needed -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/vendor.min.js"></script>
<script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/libs/simplebar/dist/simplebar.min.js"></script>
<script src="../assets/js/theme/app.init.js"></script>
<script src="../assets/js/theme/theme.js"></script>
<script src="../assets/js/theme/app.min.js"></script>
<script src="../assets/js/theme/sidebarmenu-default.js"></script>
<script src="https://cdn.jsdelivr.net/npm/iconify-icon@1.0.8/dist/iconify-icon.min.js"></script>
<script src="../assets/libs/owl.carousel/dist/owl.carousel.min.js"></script>
<script src="../assets/js/apps/productDetail.js"></script>
</body>
</html>
