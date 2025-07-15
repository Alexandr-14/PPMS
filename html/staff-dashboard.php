<?php
session_start();
require_once '../php/db_connect.php';

// Check if user is logged in as staff or admin
if (!isset($_SESSION['staff_role']) || ($_SESSION['staff_role'] !== 'Staff' && $_SESSION['staff_role'] !== 'Admin')) {
    header("Location: staff-login.html");
    exit();
}

// Get user info
$user_name = $_SESSION['staff_name'] ?? 'User';
$user_id = $_SESSION['staff_id'] ?? '';
$user_role = $_SESSION['staff_role'] ?? 'Staff';
$is_admin = ($user_role === 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPMS <?php echo $is_admin ? 'Admin' : 'Staff'; ?> Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- QR Code Generator - Primary CDN -->
    <script src="https://unpkg.com/qrcode@1.5.3/build/qrcode.min.js"></script>
    <!-- Local QR Code Fallback -->
    <script src="../js/qrcode-simple.js"></script>
    <!-- PPMS Custom Styles -->
    <link rel="stylesheet" href="../css/ppms-styles/shared/variables.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/shared/typography.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/shared/safe-typography-enhancements.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/shared/components.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/staff/staff-dashboard-refined.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/staff/staff-dashboard-overrides.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/staff/staff-navbar-buttons.css?v=<?php echo time(); ?>">
    <!-- Favicon -->
    <link rel="icon" href="../assets/Icon Web.ico" type="image/x-icon">
</head>
<body>
    <!-- Modern Enhanced Navbar -->
    <nav class="navbar-custom">
        <div class="d-flex align-items-center">
            <div class="navbar-logo me-3">
                <img src="../assets/Icon Web.ico" alt="PPMS Logo" style="height: 40px; width: 40px; border-radius: 8px;">
            </div>
            <div>
                <div class="navbar-brand mb-0">
                    <?php if ($is_admin): ?>
                        PPMS ADMIN DASHBOARD
                    <?php else: ?>
                        PERWIRA PARCEL MANAGEMENT SYSTEM
                    <?php endif; ?>
                </div>
                <div style="font-size: 0.85rem; opacity: 0.8;">
                    <?php if ($is_admin): ?>
                        Administrator Access - Full System Control
                    <?php else: ?>
                        Staff Access - Parcel Management
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <div class="me-4 d-flex align-items-center">
                <!-- User Info -->
                <div class="text-end">
                    <div class="navbar-welcome">Welcome back,</div>
                    <div style="font-weight: 700; font-size: 1.1rem;">
                        <?php if ($is_admin): ?>
                            <i class="fas fa-shield-alt me-1"></i>
                        <?php else: ?>
                            <i class="fas fa-user-tie me-1"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($user_name); ?>
                    </div>
                </div>
            </div>
            <button class="logout-btn" onclick="logout()">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </button>
        </div>
    </nav>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
                
                <?php if ($is_admin): ?>
                <!-- Admin Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 id="totalParcels">0</h4>
                                        <p class="mb-0">Total Parcels</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-box fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 id="pendingParcels">0</h4>
                                        <p class="mb-0">Pending Parcels</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 id="retrievedParcels">0</h4>
                                        <p class="mb-0">Retrieved Parcels</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4 id="totalReceivers">0</h4>
                                        <p class="mb-0">Total Receivers</p>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

        <!-- Modern Tabs -->
        <ul class="nav nav-tabs" id="staffTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="add-parcel-tab" data-bs-toggle="tab" data-bs-target="#add-parcel" type="button" role="tab">
                    <i class="fas fa-plus-circle me-1"></i> Add Parcel
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="parcel-list-tab" data-bs-toggle="tab" data-bs-target="#parcel-list" type="button" role="tab">
                    <i class="fas fa-list me-1"></i> Parcel List
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="qr-generation-tab" data-bs-toggle="tab" data-bs-target="#qr-generation" type="button" role="tab">
                    <i class="fas fa-qrcode me-1"></i> QR Generation
                </button>
            </li>
            <?php if ($is_admin): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                    <i class="fas fa-chart-bar me-1"></i> Admin Reports
                </button>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="staffTabContent">
            <!-- Add Parcel Tab -->
            <div class="tab-pane fade show active" id="add-parcel" role="tabpanel">
                <h4>Add New Parcel</h4>
                <p class="text-muted">Register a new parcel for tracking and delivery management.</p>

                <form id="addParcelForm" class="mt-4" style="background: #f8f9fa; padding: 2rem; border-radius: 1rem; border: 1px solid #e9ecef;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="trackingNumber" class="form-label">
                                    <i class="fas fa-barcode me-2"></i>Tracking Number *
                                </label>
                                <input type="text" class="form-control" id="trackingNumber" required
                                       placeholder="Enter unique tracking number">
                                <div class="form-text">Must be unique for each parcel</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="receiverIC" class="form-label">
                                    <i class="fas fa-id-card me-2"></i>Receiver IC Number *
                                </label>
                                <input type="text" class="form-control" id="receiverIC" required
                                       placeholder="Enter receiver's IC number">
                                <div class="form-text">IC number of the parcel receiver</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="parcelWeight" class="form-label">
                                    <i class="fas fa-weight-hanging"></i>Weight *
                                </label>
                                <div class="custom-weight-input">
                                    <input type="number" class="form-control weight-input" id="parcelWeight" step="0.01" min="0" required
                                           placeholder="0.00">
                                    <span class="weight-unit">kg</span>
                                </div>
                                <div class="form-text">Enter parcel weight in kilograms</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="deliveryLocation" class="form-label">
                                    <i class="fas fa-map-marker-alt me-2"></i>Delivery Location *
                                </label>
                                <input type="text" class="form-control" id="deliveryLocation" required
                                       placeholder="Enter delivery address">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="parcelName" class="form-label">
                                    <i class="fas fa-tag me-2"></i>Parcel Description
                                </label>
                                <input type="text" class="form-control" id="parcelName"
                                       placeholder="Optional parcel description">
                                <div class="form-text">Optional description for the parcel</div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Parcel
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo me-2"></i>Reset Form
                        </button>
                    </div>
                </form>
            </div>

            <!-- Parcel List Tab -->
            <div class="tab-pane fade" id="parcel-list" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">Parcel Management</h4>
                        <p class="text-muted mb-0">View, edit, and manage all parcels in the system.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-sort me-1"></i> Sort By
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="sortParcels('tracking', 'asc')">
                                    <i class="fas fa-sort-alpha-down me-2"></i>Tracking (A-Z)
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="sortParcels('tracking', 'desc')">
                                    <i class="fas fa-sort-alpha-up me-2"></i>Tracking (Z-A)
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="sortParcels('date', 'desc')">
                                    <i class="fas fa-calendar me-2"></i>Date (Newest First)
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="sortParcels('date', 'asc')">
                                    <i class="fas fa-calendar me-2"></i>Date (Oldest First)
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="filterParcels('all')">
                                    <i class="fas fa-list me-2"></i>Show All
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterParcels('Pending')">
                                    <i class="fas fa-clock me-2"></i>Pending Only
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterParcels('Retrieved')">
                                    <i class="fas fa-check me-2"></i>Retrieved Only
                                </a></li>
                            </ul>
                        </div>
                        <button class="btn btn-outline-primary" onclick="refreshParcels()">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                </div>

                <?php if ($is_admin): ?>
                <?php endif; ?>

                <!-- Enhanced Parcel Table with Clean Pagination -->
                <div class="parcel-table-container">
                    <!-- Top Controls Bar -->
                    <div class="table-controls-top">
                        <div class="entries-control">
                            <span class="control-label">Show</span>
                            <select class="entries-select" id="itemsPerPage" onchange="changeItemsPerPage()">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="control-label">entries</span>
                        </div>
                        <div id="parcelInfo" class="entries-info">
                            <!-- Showing X to Y of Z entries -->
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="parcelsTable">
                                <thead class="table-header">
                                    <tr>
                                        <th class="px-4 py-3">Tracking Number</th>
                                        <th class="px-4 py-3">Receiver IC</th>
                                        <th class="px-4 py-3">Receiver Name</th>
                                        <th class="px-4 py-3">Delivery Location</th>
                                        <th class="px-4 py-3">Date & Time</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="parcelsTableBody">
                                    <!-- Dynamic content will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bottom Pagination Bar -->
                    <div class="table-controls-bottom">
                        <div id="paginationInfo" class="pagination-info">
                            <!-- Page info will be displayed here -->
                        </div>
                        <nav aria-label="Parcel pagination" class="pagination-nav">
                            <ul class="pagination-controls" id="paginationControls">
                                <!-- Pagination buttons will be generated here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
            <!-- QR Generation Tab -->
            <div class="tab-pane fade" id="qr-generation" role="tabpanel">
                <h4>QR Code Generation</h4>
                <p class="text-muted">Generate QR codes for parcel tracking and receiver identification.</p>

                <div class="mt-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); padding: 2.5rem; border-radius: 1.5rem; border: 1px solid rgba(106, 27, 154, 0.1); box-shadow: 0 8px 32px rgba(0, 0, 0, 0.06);">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Search and Filter Section -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-search me-2 text-primary"></i>Find Parcel
                                </label>

                                <!-- Search by Tracking Number -->
                                <div class="mb-3">
                                    <input type="text" class="form-control" id="qrSearchInput" placeholder="Enter tracking number..."
                                           style="background: #ffffff !important; color: #2d3748 !important; border: 2px solid #e2e8f0 !important; padding: 0.875rem 1rem !important; font-size: 1rem !important; border-radius: 12px !important;"
                                           oninput="searchParcelsForQR()">
                                    <div class="form-text">Type tracking number to search</div>
                                </div>

                                <!-- Status Filter -->
                                <div class="mb-3">
                                    <select class="form-select" id="qrStatusFilter" onchange="filterParcelsForQR()"
                                            style="background: #ffffff !important; color: #2d3748 !important; border: 2px solid #e2e8f0 !important; padding: 0.875rem 1rem !important; font-size: 1rem !important; border-radius: 12px !important;">
                                        <option value="all">All Parcels</option>
                                        <option value="Pending">Pending Only</option>
                                        <option value="Retrieved">Retrieved Only</option>
                                    </select>
                                    <div class="form-text">Filter by parcel status</div>
                                </div>

                                <!-- Parcel Results -->
                                <div class="mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-list me-2 text-success"></i>Select from Results
                                    </label>
                                    <div id="qrParcelResults" class="border rounded" style="max-height: 300px; overflow-y: auto; background: white;">
                                        <div class="p-3 text-center text-muted">
                                            <i class="fas fa-search fa-2x mb-2"></i>
                                            <p>Search for parcels above</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Parcel Information Display -->
                            <div id="parcelInfoCard" class="mb-4" style="display: none;">
                                <div class="card shadow-sm border-0" style="border-radius: 16px; overflow: hidden; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
                                    <!-- Header with gradient -->
                                    <div class="card-header border-0 text-white position-relative" style="background: linear-gradient(135deg, #6A1B9A 0%, #FF9800 100%); padding: 1.25rem 1.5rem;">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-box-open fa-2x opacity-75" style="color: white !important;"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-1 fw-bold" style="color: white !important;">Selected Parcel</h5>
                                                <small class="opacity-90" style="color: white !important;">Ready for QR Code Generation</small>
                                            </div>
                                        </div>
                                        <!-- Decorative element -->
                                        <div class="position-absolute top-0 end-0 p-3">
                                            <i class="fas fa-qrcode fa-3x opacity-25"></i>
                                        </div>
                                    </div>

                                    <!-- Body with clean layout -->
                                    <div class="card-body" style="padding: 2rem 1.5rem;">
                                        <!-- Tracking Number - Prominent Display -->
                                        <div class="text-center mb-4 p-3 rounded-3" style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); border: 2px dashed #6A1B9A;">
                                            <small class="text-muted d-block mb-1">Tracking Number</small>
                                            <h4 class="mb-0 fw-bold" style="color: #6A1B9A; font-family: 'Courier New', monospace;" id="parcelTrackingDisplay"></h4>
                                        </div>

                                        <!-- Information Grid -->
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <div class="info-box h-100 p-3 rounded-3" style="background: #f8f9fa; border-left: 4px solid #6A1B9A;">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-id-card text-primary me-2"></i>
                                                        <small class="text-muted fw-semibold text-uppercase">Receiver IC</small>
                                                    </div>
                                                    <div id="parcelIC" class="fw-bold text-dark" style="font-size: 1.1rem; font-family: 'Courier New', monospace;"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-box h-100 p-3 rounded-3" style="background: #f8f9fa; border-left: 4px solid #FF9800;">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-user text-warning me-2"></i>
                                                        <small class="text-muted fw-semibold text-uppercase">Receiver Name</small>
                                                    </div>
                                                    <div id="parcelReceiverName" class="fw-bold text-dark" style="font-size: 1.1rem;"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-box h-100 p-3 rounded-3" style="background: #f8f9fa; border-left: 4px solid #43e97b;">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-map-marker-alt text-success me-2"></i>
                                                        <small class="text-muted fw-semibold text-uppercase">Pickup Location</small>
                                                    </div>
                                                    <div id="parcelLocation" class="fw-bold text-dark" style="font-size: 1.1rem;"></div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="info-box h-100 p-3 rounded-3" style="background: #f8f9fa; border-left: 4px solid #dc3545;">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <i class="fas fa-info-circle text-info me-2"></i>
                                                        <small class="text-muted fw-semibold text-uppercase">Status</small>
                                                    </div>
                                                    <div id="parcelStatus" class="fw-bold" style="font-size: 1.1rem;"></div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Instructions -->
                                        <div class="mt-4 p-3 rounded-3 text-center" style="background: linear-gradient(135deg, #e8f5e8 0%, #f0f8ff 100%); border: 1px solid rgba(67, 233, 123, 0.3);">
                                            <div class="d-flex align-items-center justify-content-center mb-2">
                                                <i class="fas fa-arrow-down text-success me-2 fa-lg"></i>
                                                <span class="fw-semibold text-success">Ready for QR Generation</span>
                                            </div>
                                            <small class="text-muted">
                                                Click the "Generate Verification QR Code" button below to create a secure QR code for this parcel.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <button class="btn btn-lg shadow-sm" onclick="generateQR()"
                                        style="background: linear-gradient(135deg, #6A1B9A 0%, #FF9800 100%);
                                               border: none;
                                               border-radius: 16px;
                                               padding: 1rem 2.5rem;
                                               color: white;
                                               font-weight: 600;
                                               transition: all 0.3s ease;
                                               box-shadow: 0 4px 15px rgba(106, 27, 154, 0.3);"
                                        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(106, 27, 154, 0.4)';"
                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(106, 27, 154, 0.3)';">
                                    <i class="fas fa-qrcode me-2 fa-lg"></i>Generate Verification QR Code
                                </button>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-shield-alt me-1"></i>
                                        Secure QR code with encrypted verification data
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="qrCodeContainer" class="text-center" style="display: none;">
                                <div class="card" style="border: 2px solid #6A1B9A; border-radius: 16px; background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);">
                                    <div class="card-header text-center" style="background: linear-gradient(135deg, #6A1B9A 0%, #FF9800 100%); color: white; border-radius: 14px 14px 0 0;">
                                        <h6 class="mb-0"><i class="fas fa-qrcode me-2"></i>Verification QR Code</h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="qrCodeDisplay" class="mb-3" style="background: white; padding: 1.5rem; border-radius: 12px; display: inline-block; border: 2px dashed #e2e8f0;"></div>
                                        <div id="qrCodeInfo" class="mb-3" style="display: none;">
                                            <small class="text-muted">QR Code contains encrypted verification data</small>
                                        </div>

                                        <!-- Enhanced Action Buttons -->
                                        <div class="d-grid gap-2">
                                            <button class="btn btn-success" onclick="downloadQR()" style="border-radius: 10px;">
                                                <i class="fas fa-download me-2"></i>Download QR Image
                                            </button>
                                            <button class="btn btn-info" onclick="emailQR()" style="border-radius: 10px;">
                                                <i class="fas fa-envelope me-2"></i>Email to Receiver
                                            </button>
                                            <button class="btn btn-warning" onclick="printQR()" style="border-radius: 10px;">
                                                <i class="fas fa-print me-2"></i>Print QR Code
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                    <?php if ($is_admin): ?>
                    <!-- Admin Reports Tab -->
                    <div class="tab-pane fade" id="reports" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Admin Reports</h5>
                                <small class="text-muted">Generate reports from retrieval records</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="reportStartDate" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="reportStartDate">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="reportEndDate" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="reportEndDate">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="reportStatus" class="form-label">Status Filter</label>
                                            <select class="form-select" id="reportStatus">
                                                <option value="">All Status</option>
                                                <option value="Retrieved">Retrieved</option>
                                                <option value="Pending">Pending</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="reportStaffID" class="form-label">Staff ID Filter</label>
                                            <input type="text" class="form-control" id="reportStaffID" placeholder="Enter staff ID (optional)">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="reportReceiverIC" class="form-label">Receiver IC Filter</label>
                                            <input type="text" class="form-control" id="reportReceiverIC" placeholder="Enter receiver IC (optional)">
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary" onclick="generateReport()">
                                        <i class="fas fa-file-alt me-2"></i>Generate Report
                                    </button>
                                    <button class="btn btn-success" onclick="exportReport()">
                                        <i class="fas fa-file-excel me-2"></i>Export to Excel
                                    </button>
                                    <button class="btn btn-info" onclick="printReport()">
                                        <i class="fas fa-print me-2"></i>Print Report
                                    </button>
                                </div>
                                <div id="reportResults" class="mt-4" style="display: none;">
                                    <!-- Report results will be displayed here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer-custom">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div class="d-flex align-items-center mb-3">
                        <img src="../assets/Icon Web.ico" alt="PPMS Logo" class="footer-logo">
                        <div>
                            <h5 class="footer-title">Perwira Parcel Management System</h5>
                            <p class="footer-subtitle">Universiti Tun Hussein Onn Malaysia</p>
                        </div>
                    </div>
                    <p class="footer-description">
                        <?php if ($is_admin): ?>
                            Administrator Dashboard - Complete system management and control for efficient parcel operations.
                        <?php else: ?>
                            Staff Dashboard - Professional parcel management tools for streamlined operations and customer service.
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5 class="footer-section-title"><?php echo $is_admin ? 'Admin Support' : 'Staff Support'; ?></h5>
                    <div class="contact-info">
                        <p><i class="fas fa-envelope me-2"></i> <?php echo $is_admin ? 'admin@' : 'staff@'; ?>perwiraparcel.uthm.edu.my</p>
                        <p><i class="fas fa-phone me-2"></i> +60 11-1589 5859</p>
                        <p><i class="fas fa-map-marker-alt me-2"></i> Jalan Desasiswa, Parit Sempadan Laut, 86400 Parit Raja, Johor, Malaysia</p>
                        <p><i class="fas fa-<?php echo $is_admin ? 'shield-alt' : 'user-tie'; ?> me-2"></i> <?php echo $is_admin ? 'Administrator' : 'Staff'; ?> Access Level</p>
                    </div>
                </div>
            </div>

            <!-- Delivery Partners Slider Section -->
            <div class="row mt-4 pt-4" style="border-top: 1px solid rgba(107, 114, 128, 0.1);">
                <div class="col-12">
                    <div class="text-center mb-4">
                        <h6 class="footer-section-title" style="letter-spacing: 1px; text-transform: uppercase; font-size: 0.8rem;">
                            <i class="fas fa-truck me-2"></i>Trusted Delivery Partners
                        </h6>
                        <p class="text-muted small mb-0">Reliable delivery services across Malaysia</p>
                    </div>

                    <!-- Modern Card-Based Carousel -->
                    <div class="modern-carousel-container">
                        <div class="carousel-viewport">
                            <div class="carousel-track" id="modernCarouselTrack">
                                <!-- Partner Cards -->
                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/posMalaysia.png" alt="Pos Malaysia" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>Pos Malaysia</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">Pos Malaysia</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/gdex.png" alt="GDEX" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>GDEX</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">GDEX</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/flashexpress.png" alt="Flash Express" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>Flash Express</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">Flash Express</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/shopeeExpress.jpeg" alt="Shopee Express" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>Shopee Express</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">Shopee Express</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/JNT.webp" alt="J&T Express" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>J&T Express</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">J&T Express</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/dhl.png" alt="DHL" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>DHL</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">DHL</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/fedex.png" alt="FedEx" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>FedEx</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">FedEx</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/ninjavan.png" alt="Ninja Van" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>Ninja Van</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">Ninja Van</h6>
                                        </div>
                                    </div>
                                </div>

                                <!-- Duplicate Set for Infinite Scroll -->
                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/posMalaysia.png" alt="Pos Malaysia" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>Pos Malaysia</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">Pos Malaysia</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/gdex.png" alt="GDEX" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>GDEX</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">GDEX</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/flashexpress.png" alt="Flash Express" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>Flash Express</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">Flash Express</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/shopeeExpress.jpeg" alt="Shopee Express" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>Shopee Express</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">Shopee Express</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/JNT.webp" alt="J&T Express" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>J&T Express</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">J&T Express</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/dhl.png" alt="DHL" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>DHL</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">DHL</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/fedex.png" alt="FedEx" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>FedEx</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">FedEx</h6>
                                        </div>
                                    </div>
                                </div>

                                <div class="partner-card">
                                    <div class="partner-card-inner">
                                        <div class="partner-logo-container">
                                            <img src="../assets/ninjavan.png" alt="Ninja Van" class="partner-logo" onerror="this.outerHTML='<div class=&quot;partner-logo-placeholder&quot;>Ninja Van</div>'">
                                        </div>
                                        <div class="partner-info">
                                            <h6 class="partner-name">Ninja Van</h6>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Arrows -->
                        <button class="carousel-nav carousel-nav-prev" onclick="moveCarousel('prev')" aria-label="Previous partners">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="carousel-nav carousel-nav-next" onclick="moveCarousel('next')" aria-label="Next partners">
                            <i class="fas fa-chevron-right"></i>
                        </button>

                        <!-- Elegant Indicators -->
                        <div class="carousel-indicators">
                            <button class="carousel-indicator active" onclick="goToSlide(0)" aria-label="Go to slide 1"></button>
                            <button class="carousel-indicator" onclick="goToSlide(1)" aria-label="Go to slide 2"></button>
                            <button class="carousel-indicator" onclick="goToSlide(2)" aria-label="Go to slide 3"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Edit Parcel Modal -->
    <div class="modal fade" id="editParcelModal" tabindex="-1" aria-labelledby="editParcelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editParcelModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Parcel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editParcelForm">
                        <input type="hidden" id="editParcelId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editTrackingNumber" class="form-label fw-bold text-dark">
                                        <i class="fas fa-barcode me-2 text-primary"></i>Tracking Number
                                    </label>
                                    <input type="text" class="form-control" id="editTrackingNumber" readonly style="background-color: #f8f9fa;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editReceiverIC" class="form-label fw-bold text-dark">
                                        <i class="fas fa-id-card me-2 text-primary"></i>Receiver IC Number
                                    </label>
                                    <input type="text" class="form-control" id="editReceiverIC" maxlength="12" pattern="\d{12}" title="IC Number must be exactly 12 digits" placeholder="e.g., 123456789012" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editParcelWeight" class="form-label fw-bold text-dark">
                                        <i class="fas fa-weight me-2 text-primary"></i>Weight (kg)
                                    </label>
                                    <input type="number" class="form-control" id="editParcelWeight" step="0.01" min="0.01" placeholder="e.g., 2.50" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editDeliveryLocation" class="form-label fw-bold text-dark">
                                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>Delivery Location
                                    </label>
                                    <input type="text" class="form-control" id="editDeliveryLocation" placeholder="e.g., TSN" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editParcelName" class="form-label fw-bold text-dark">
                                        <i class="fas fa-box me-2 text-primary"></i>Parcel Name/Description
                                    </label>
                                    <input type="text" class="form-control" id="editParcelName" placeholder="e.g., Electronics, Documents">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editStatus" class="form-label fw-bold text-dark">
                                        <i class="fas fa-info-circle me-2 text-primary"></i>Status
                                    </label>
                                    <select class="form-select" id="editStatus">
                                        <option value="Pending">🟡 Pending</option>
                                        <option value="Retrieved">🟢 Retrieved</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateParcel()">
                        <i class="fas fa-save me-2"></i>Update Parcel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Parcel Details Modal -->
    <div class="modal fade" id="viewParcelModal" tabindex="-1" aria-labelledby="viewParcelModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewParcelModalLabel">
                        <i class="fas fa-eye me-2"></i>Parcel Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tracking Number:</label>
                                <p id="viewTrackingNumber" class="form-control-plaintext"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Receiver IC:</label>
                                <p id="viewReceiverIC" class="form-control-plaintext"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Receiver Name:</label>
                                <p id="viewReceiverName" class="form-control-plaintext"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Weight:</label>
                                <p id="viewParcelWeight" class="form-control-plaintext"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Delivery Location:</label>
                                <p id="viewDeliveryLocation" class="form-control-plaintext"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status:</label>
                                <p id="viewStatus" class="form-control-plaintext"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Date Added:</label>
                                <p id="viewDateAdded" class="form-control-plaintext"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Parcel Description:</label>
                                <p id="viewParcelName" class="form-control-plaintext"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">QR Code:</label>
                                <div id="viewQRCode" class="text-center"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="editParcelFromView()">
                        <i class="fas fa-edit me-2"></i>Edit Parcel
                    </button>
                </div>
            </div>
        </div>
    </div>



    <!-- Bootstrap JS -->
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>

    <script>
        // Global variables
        let currentParcelData = null;
        let allParcels = [];
        let filteredParcels = [];
        let currentPage = 1;
        let itemsPerPage = 10;
        const isAdmin = <?php echo $is_admin ? 'true' : 'false'; ?>;
        const userRole = '<?php echo $user_role; ?>';

        // Enhanced QR library loading check with multiple fallbacks
        window.addEventListener('load', function() {
            setTimeout(() => {
                console.log('Checking QR library availability...');

                if (typeof QRCode === 'undefined') {
                    console.warn('Primary QRCode library not loaded, attempting to load fallback...');

                    // Try to load from alternative CDN
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js';
                    script.onload = function() {
                        console.log('Alternative QRCode library loaded successfully');
                    };
                    script.onerror = function() {
                        console.log('Alternative CDN failed, using local fallback');
                        // Fallback is already loaded from qrcode-simple.js
                    };
                    document.head.appendChild(script);
                } else {
                    console.log('QRCode library loaded successfully:', typeof QRCode);
                    // Test if it's our fallback
                    if (window.SimpleQR) {
                        console.log('Using SimpleQR fallback implementation');
                    } else {
                        console.log('Using native QRCode library');
                    }
                }
            }, 500);
        });
        const userName = '<?php echo htmlspecialchars($user_name); ?>';
        const userID = '<?php echo htmlspecialchars($user_id); ?>';

        // Auto-refresh statistics every 30 seconds
        function startAutoRefresh() {
            setInterval(() => {
                if (isAdmin) {
                    loadDashboardStats();
                }
                loadParcels(); // Also refresh parcel list to keep data in sync
            }, 30000); // 30 seconds
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            if (isAdmin) {
                loadDashboardStats();
            }
            loadParcels();
            startAutoRefresh(); // Start auto-refresh

            // Check for login success message (only show once)
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('login') === 'success' && !sessionStorage.getItem('welcomeShown')) {
                const welcomeTitle = 'Welcome ' + (isAdmin ? 'Administrator' : 'Staff Member') + '!';
                const welcomeText = 'Login successful. Welcome to your ' + (isAdmin ? 'admin' : 'staff') + ' dashboard.';

                Swal.fire({
                    icon: 'success',
                    title: welcomeTitle,
                    text: welcomeText,
                    timer: 3000,
                    showConfirmButton: false
                });
                // Mark welcome as shown and clean URL
                sessionStorage.setItem('welcomeShown', 'true');
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });

        // Load dashboard statistics (Admin only)
        function loadDashboardStats() {
            if (!isAdmin) return;

            fetch('../php/admin-get-stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Animate number updates
                        animateNumber('totalParcels', data.totalParcels || 0);
                        animateNumber('pendingParcels', data.pendingParcels || 0);
                        animateNumber('retrievedParcels', data.retrievedParcels || 0);
                        animateNumber('totalReceivers', data.totalReceivers || 0);
                    }
                })
                .catch(error => {
                    console.error('Error loading stats:', error);
                });
        }

        // Animate number counting
        function animateNumber(elementId, targetValue) {
            const element = document.getElementById(elementId);
            if (!element) return;

            const startValue = 0;
            const duration = 1000; // 1 second
            const startTime = performance.now();

            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                // Easing function for smooth animation
                const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                const currentValue = Math.floor(startValue + (targetValue - startValue) * easeOutQuart);

                element.textContent = currentValue;

                if (progress < 1) {
                    requestAnimationFrame(updateNumber);
                } else {
                    element.textContent = targetValue; // Ensure final value is exact
                }
            }

            requestAnimationFrame(updateNumber);
        }

        // Load all parcels
        function loadParcels() {
            fetch('../php/staff-get-parcels.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allParcels = data.parcels;
                        currentPage = 1; // Reset to first page when loading new data
                        displayParcels(allParcels);
                    } else {
                        console.error('Error loading parcels:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading parcels:', error);
                });
        }

        // Display parcels in table with pagination
        function displayParcels(parcels) {
            filteredParcels = parcels;
            const tbody = document.getElementById('parcelsTableBody');
            tbody.innerHTML = '';

            if (parcels.length === 0) {
                tbody.innerHTML =
                    '<tr>' +
                        '<td colspan="7" class="text-center text-muted py-5">' +
                            '<i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.3;"></i><br>' +
                            '<h5 class="text-muted">No Parcels Found</h5>' +
                            '<p class="text-muted">Add a new parcel to get started.</p>' +
                        '</td>' +
                    '</tr>';
                updatePaginationInfo(0, 0, 0);
                updatePaginationControls(0);
                return;
            }

            // Calculate pagination
            const totalItems = parcels.length;
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
            const paginatedParcels = parcels.slice(startIndex, endIndex);

            // Display parcels for current page
            paginatedParcels.forEach(parcel => {
                const row = document.createElement('tr');
                const statusBadge = parcel.status === 'Pending'
                    ? '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>'
                    : '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Retrieved</span>';

                // Format date and time
                const formattedDate = parcel.date ? new Date(parcel.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                const formattedTime = parcel.time ? new Date('1970-01-01T' + parcel.time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : '';
                const timeDisplay = parcel.time ? '<br><small class="text-muted">' + formattedTime + '</small>' : '';

                // Delete button (available for both Staff and Admin)
                const deleteButton = '<button class="btn btn-outline-danger" onclick="deleteParcel(\'' + parcel.TrackingNumber + '\')" title="Delete">' +
                                        '<i class="fas fa-trash"></i>' +
                                      '</button>';

                row.innerHTML =
                    '<td class="px-4 py-3">' +
                        '<span class="fw-bold text-primary">' + parcel.TrackingNumber + '</span>' +
                    '</td>' +
                    '<td class="px-4 py-3">' + formatICNumber(parcel.ICNumber) + '</td>' +
                    '<td class="px-4 py-3">' +
                        '<i class="fas fa-user me-1 text-muted"></i>' +
                        (parcel.receiverName || 'N/A') +
                    '</td>' +
                    '<td class="px-4 py-3">' +
                        '<i class="fas fa-map-marker-alt me-1 text-muted"></i>' +
                        (parcel.deliveryLocation || 'N/A') +
                    '</td>' +
                    '<td class="px-4 py-3">' +
                        '<div>' +
                            formattedDate +
                            timeDisplay +
                        '</div>' +
                    '</td>' +
                    '<td class="px-4 py-3">' + statusBadge + '</td>' +
                    '<td class="px-4 py-3">' +
                        '<div class="btn-group btn-group-sm" role="group">' +
                            '<button class="btn btn-outline-primary" onclick="viewParcel(\'' + parcel.TrackingNumber + '\')" title="View Details">' +
                                '<i class="fas fa-eye"></i>' +
                            '</button>' +
                            '<button class="btn btn-outline-warning" onclick="editParcel(\'' + parcel.TrackingNumber + '\')" title="Edit">' +
                                '<i class="fas fa-edit"></i>' +
                            '</button>' +
                            '<button class="btn btn-outline-info" onclick="generateParcelQR(\'' + parcel.TrackingNumber + '\')" title="Generate QR">' +
                                '<i class="fas fa-qrcode"></i>' +
                            '</button>' +
                            deleteButton +
                        '</div>' +
                    '</td>';
                tbody.appendChild(row);
            });

            // Update pagination info and controls
            updatePaginationInfo(startIndex + 1, endIndex, totalItems);
            updatePaginationControls(totalPages);
        }

        // Update pagination information display
        function updatePaginationInfo(start, end, total) {
            const parcelInfo = document.getElementById('parcelInfo');
            const paginationInfo = document.getElementById('paginationInfo');

            if (total === 0) {
                parcelInfo.textContent = 'No entries to show';
                paginationInfo.textContent = '';
            } else {
                parcelInfo.textContent = `Showing ${start} to ${end} of ${total} entries`;
                paginationInfo.textContent = `Page ${currentPage} of ${Math.ceil(total / itemsPerPage)}`;
            }
        }

        // Update pagination controls with enhanced styling
        function updatePaginationControls(totalPages) {
            const paginationControls = document.getElementById('paginationControls');
            paginationControls.innerHTML = '';

            if (totalPages <= 1) return;

            // Previous button with enhanced styling
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            const prevLink = document.createElement('a');
            prevLink.className = 'page-link';
            prevLink.href = '#';
            prevLink.setAttribute('aria-label', 'Previous');
            prevLink.innerHTML = '<i class="fas fa-chevron-left"></i>';
            if (currentPage > 1) {
                prevLink.onclick = (e) => {
                    e.preventDefault();
                    changePage(currentPage - 1);
                };
            } else {
                prevLink.onclick = (e) => e.preventDefault();
            }
            prevLi.appendChild(prevLink);
            paginationControls.appendChild(prevLi);

            // Page numbers with smart pagination
            const maxVisiblePages = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
            let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

            // Adjust start page if we're near the end
            if (endPage - startPage < maxVisiblePages - 1) {
                startPage = Math.max(1, endPage - maxVisiblePages + 1);
            }

            // First page and ellipsis
            if (startPage > 1) {
                const firstLi = document.createElement('li');
                firstLi.className = 'page-item';
                const firstLink = document.createElement('a');
                firstLink.className = 'page-link';
                firstLink.href = '#';
                firstLink.textContent = '1';
                firstLink.onclick = (e) => {
                    e.preventDefault();
                    changePage(1);
                };
                firstLi.appendChild(firstLink);
                paginationControls.appendChild(firstLi);

                if (startPage > 2) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    const ellipsisSpan = document.createElement('span');
                    ellipsisSpan.className = 'page-link';
                    ellipsisSpan.innerHTML = '<i class="fas fa-ellipsis-h"></i>';
                    ellipsisLi.appendChild(ellipsisSpan);
                    paginationControls.appendChild(ellipsisLi);
                }
            }

            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                const pageLi = document.createElement('li');
                pageLi.className = `page-item ${i === currentPage ? 'active' : ''}`;
                const pageLink = document.createElement('a');
                pageLink.className = 'page-link';
                pageLink.href = '#';
                pageLink.textContent = i;
                pageLink.onclick = (e) => {
                    e.preventDefault();
                    changePage(i);
                };
                pageLi.appendChild(pageLink);
                paginationControls.appendChild(pageLi);
            }

            // Last page and ellipsis
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsisLi = document.createElement('li');
                    ellipsisLi.className = 'page-item disabled';
                    const ellipsisSpan = document.createElement('span');
                    ellipsisSpan.className = 'page-link';
                    ellipsisSpan.innerHTML = '<i class="fas fa-ellipsis-h"></i>';
                    ellipsisLi.appendChild(ellipsisSpan);
                    paginationControls.appendChild(ellipsisLi);
                }

                const lastLi = document.createElement('li');
                lastLi.className = 'page-item';
                const lastLink = document.createElement('a');
                lastLink.className = 'page-link';
                lastLink.href = '#';
                lastLink.textContent = totalPages;
                lastLink.onclick = (e) => {
                    e.preventDefault();
                    changePage(totalPages);
                };
                lastLi.appendChild(lastLink);
                paginationControls.appendChild(lastLi);
            }

            // Next button with enhanced styling
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            const nextLink = document.createElement('a');
            nextLink.className = 'page-link';
            nextLink.href = '#';
            nextLink.setAttribute('aria-label', 'Next');
            nextLink.innerHTML = '<i class="fas fa-chevron-right"></i>';
            if (currentPage < totalPages) {
                nextLink.onclick = (e) => {
                    e.preventDefault();
                    changePage(currentPage + 1);
                };
            } else {
                nextLink.onclick = (e) => e.preventDefault();
            }
            nextLi.appendChild(nextLink);
            paginationControls.appendChild(nextLi);
        }

        // Change page
        function changePage(page) {
            if (page < 1 || page > Math.ceil(filteredParcels.length / itemsPerPage)) return;
            currentPage = page;
            displayParcels(filteredParcels);

            // Scroll to table top to maintain position
            document.getElementById('parcelsTable').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }

        // Change items per page
        function changeItemsPerPage() {
            itemsPerPage = parseInt(document.getElementById('itemsPerPage').value);
            currentPage = 1; // Reset to first page
            displayParcels(filteredParcels);
        }

        // Sort parcels
        function sortParcels(sortBy, order) {
            // Store current scroll position
            const tableContainer = document.querySelector('.table-responsive');
            const scrollTop = tableContainer ? tableContainer.scrollTop : 0;

            let sortedParcels = [...allParcels];

            sortedParcels.sort((a, b) => {
                let valueA, valueB;

                switch(sortBy) {
                    case 'tracking':
                        valueA = a.TrackingNumber.toLowerCase();
                        valueB = b.TrackingNumber.toLowerCase();
                        break;
                    case 'date':
                        valueA = new Date(a.date + ' ' + (a.time || '00:00:00'));
                        valueB = new Date(b.date + ' ' + (b.time || '00:00:00'));
                        break;
                    default:
                        return 0;
                }

                if (order === 'asc') {
                    return valueA > valueB ? 1 : -1;
                } else {
                    return valueA < valueB ? 1 : -1;
                }
            });

            currentPage = 1; // Reset to first page when sorting
            displayParcels(sortedParcels);

            // Restore scroll position after a brief delay
            setTimeout(() => {
                if (tableContainer) {
                    tableContainer.scrollTop = scrollTop;
                }
            }, 100);
        }

        // Filter parcels
        function filterParcels(status = null) {
            // Store current scroll position
            const tableContainer = document.querySelector('.table-responsive');
            const scrollTop = tableContainer ? tableContainer.scrollTop : 0;

            let filtered = allParcels;

            if (status && status !== 'all') {
                filtered = allParcels.filter(parcel => parcel.status === status);
            }

            currentPage = 1; // Reset to first page when filtering
            displayParcels(filtered);

            // Restore scroll position after a brief delay
            setTimeout(() => {
                if (tableContainer) {
                    tableContainer.scrollTop = scrollTop;
                }
            }, 100);
        }

        // Modern sleek logout with enhanced SweetAlert (Staff theme)
        function logout() {
            Swal.fire({
                title: '<div style="margin-bottom: 1.5rem;"><div style="width: 80px; height: 80px; background: linear-gradient(135deg, #6A1B9A 0%, #FF9800 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; box-shadow: 0 8px 32px rgba(106, 27, 154, 0.3); position: relative; overflow: hidden; border: none !important;"><div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%); animation: shimmer 2s infinite;"></div><svg style="width: 32px; height: 32px; z-index: 2; position: relative;" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">Ready to leave?</div></div>',
                html: '<div style="color: #6b7280; font-size: 0.95rem; line-height: 1.5;">You\'ll be securely logged out and redirected to the login page.</div>',
                icon: false,
                showCancelButton: true,
                confirmButtonColor: '#6A1B9A',
                cancelButtonColor: '#e5e7eb',
                confirmButtonText: '<i class="fas fa-sign-out-alt me-2"></i>Yes, logout',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Stay here',
                buttonsStyling: false,
                customClass: {
                    popup: 'modern-logout-popup staff-theme',
                    confirmButton: 'modern-confirm-btn staff-confirm',
                    cancelButton: 'modern-cancel-btn'
                },
                background: '#ffffff',
                backdrop: 'rgba(0, 0, 0, 0.75)',
                allowOutsideClick: false,
                allowEscapeKey: true,
                focusConfirm: false,
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: '<div style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Logging out...</div>',
                        html: '<div style="color: #6b7280; font-size: 0.9rem;">Securing your session</div>',
                        icon: null,
                        iconHtml: '<div style="width: 60px; height: 60px; background: linear-gradient(135deg, #6A1B9A 0%, #FF9800 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; box-shadow: 0 4px 20px rgba(106, 27, 154, 0.4);"><div class="spinner-border text-white" style="width: 1.8rem; height: 1.8rem; border-width: 3px;" role="status"></div></div>',
                        timer: 1200,
                        showConfirmButton: false,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        customClass: {
                            popup: 'modern-loading-popup staff-theme'
                        },
                        background: '#ffffff',
                        backdrop: 'rgba(0, 0, 0, 0.85)'
                    }).then(() => {
                        window.location.href = '../php/logout.php';
                    });
                }
            });
        }

        // Refresh parcels
        function refreshParcels() {
            loadParcels();
            if (isAdmin) {
                loadDashboardStats();
            }
            Swal.fire({
                icon: 'success',
                title: 'Refreshed!',
                text: 'Parcel data has been refreshed.',
                timer: 1500,
                showConfirmButton: false
            });
        }

        // Add new parcel
        document.getElementById('addParcelForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('trackingNumber', document.getElementById('trackingNumber').value);
            formData.append('receiverIC', document.getElementById('receiverIC').value);
            formData.append('weight', document.getElementById('parcelWeight').value);
            formData.append('deliveryLocation', document.getElementById('deliveryLocation').value);
            formData.append('name', document.getElementById('parcelName').value);

            fetch('../php/staff-add-parcel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Parcel added successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    document.getElementById('addParcelForm').reset();
                    loadParcels();
                    if (isAdmin) {
                        loadDashboardStats();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to add parcel.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while adding the parcel.'
                });
            });
        });

        // View parcel details
        function viewParcel(trackingNumber) {
            console.log('viewParcel called with:', trackingNumber);

            const parcel = allParcels.find(p => p.TrackingNumber === trackingNumber);
            if (!parcel) {
                console.error('Parcel not found:', trackingNumber);
                Swal.fire({
                    icon: 'error',
                    title: 'Parcel Not Found',
                    text: 'The requested parcel could not be found.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            console.log('Found parcel:', parcel);

            // Populate modal with parcel details
            document.getElementById('viewTrackingNumber').textContent = parcel.TrackingNumber;
            document.getElementById('viewReceiverIC').textContent = formatICNumber(parcel.ICNumber);
            document.getElementById('viewReceiverName').textContent = parcel.receiverName || 'N/A';
            document.getElementById('viewParcelWeight').textContent = parcel.weight ? (parcel.weight + ' kg') : 'N/A';
            document.getElementById('viewDeliveryLocation').textContent = parcel.deliveryLocation || 'N/A';

            // Enhanced status display with color coding
            const statusElement = document.getElementById('viewStatus');
            statusElement.textContent = parcel.status || 'N/A';
            statusElement.className = 'form-control-plaintext';
            if (parcel.status === 'Pending') {
                statusElement.style.color = '#f6ad55';
                statusElement.style.fontWeight = '600';
            } else if (parcel.status === 'Retrieved') {
                statusElement.style.color = '#48bb78';
                statusElement.style.fontWeight = '600';
            }

            document.getElementById('viewDateAdded').textContent = parcel.date || 'N/A';
            document.getElementById('viewParcelName').textContent = parcel.name || 'Package';

            // Generate QR code for viewing with loading state
            const qrContainer = document.getElementById('viewQRCode');
            qrContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Generating QR Code...</div>';

            setTimeout(() => {
                qrContainer.innerHTML = '';
                try {
                    QRCode.toCanvas(qrContainer, trackingNumber, {
                        width: 150,
                        height: 150,
                        colorDark: '#6A1B9A',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.M
                    }, function (error) {
                        if (error) {
                            console.error('QR Code generation error:', error);
                            qrContainer.innerHTML = '<div class="text-danger"><i class="fas fa-exclamation-triangle"></i> QR Code generation failed</div>';
                        } else {
                            console.log('QR Code generated successfully');
                        }
                    });
                } catch (error) {
                    console.error('QR Code library error:', error);
                    qrContainer.innerHTML = '<div class="text-danger"><i class="fas fa-exclamation-triangle"></i> QR Code library not available</div>';
                }
            }, 300);

            // Show modal with animation and debugging
            console.log('Attempting to show modal...');
            const modalElement = document.getElementById('viewParcelModal');
            console.log('Modal element found:', modalElement);

            if (modalElement) {
                try {
                    const modal = new bootstrap.Modal(modalElement, {
                        backdrop: true,
                        keyboard: true,
                        focus: true
                    });
                    console.log('Modal instance created:', modal);
                    modal.show();
                    console.log('Modal show() called');

                    // Check if modal is actually visible
                    setTimeout(() => {
                        const isVisible = modalElement.classList.contains('show');
                        const computedStyle = window.getComputedStyle(modalElement);
                        console.log('Modal visible:', isVisible);
                        console.log('Modal display:', computedStyle.display);
                        console.log('Modal opacity:', computedStyle.opacity);
                        console.log('Modal z-index:', computedStyle.zIndex);
                    }, 500);
                } catch (error) {
                    console.error('Error showing modal:', error);
                    // Fallback: try to show modal directly
                    modalElement.style.display = 'block';
                    modalElement.classList.add('show');
                    document.body.classList.add('modal-open');

                    // Create backdrop manually if needed
                    if (!document.querySelector('.modal-backdrop')) {
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                    }
                }
            } else {
                console.error('Modal element not found!');
            }
        }

        // Edit parcel
        function editParcel(trackingNumber) {
            const parcel = allParcels.find(p => p.TrackingNumber === trackingNumber);
            if (!parcel) return;

            document.getElementById('editParcelId').value = parcel.TrackingNumber;
            document.getElementById('editTrackingNumber').value = parcel.TrackingNumber;
            document.getElementById('editReceiverIC').value = parcel.ICNumber;
            document.getElementById('editParcelWeight').value = parcel.weight || '';
            document.getElementById('editDeliveryLocation').value = parcel.deliveryLocation || '';
            document.getElementById('editParcelName').value = parcel.name || '';
            document.getElementById('editStatus').value = parcel.status || 'Pending';

            new bootstrap.Modal(document.getElementById('editParcelModal')).show();
        }

        // Edit parcel from view modal
        function editParcelFromView() {
            const trackingNumber = document.getElementById('viewTrackingNumber').textContent;
            bootstrap.Modal.getInstance(document.getElementById('viewParcelModal')).hide();
            setTimeout(() => editParcel(trackingNumber), 300);
        }

        // Update parcel
        function updateParcel() {
            const formData = new FormData();
            formData.append('trackingNumber', document.getElementById('editTrackingNumber').value);
            formData.append('receiverIC', document.getElementById('editReceiverIC').value);
            formData.append('weight', document.getElementById('editParcelWeight').value);
            formData.append('deliveryLocation', document.getElementById('editDeliveryLocation').value);
            formData.append('name', document.getElementById('editParcelName').value);
            formData.append('status', document.getElementById('editStatus').value);

            fetch('../php/staff-update-parcel.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        text: 'Parcel updated successfully.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    bootstrap.Modal.getInstance(document.getElementById('editParcelModal')).hide();
                    loadParcels();
                    if (isAdmin) {
                        loadDashboardStats();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to update parcel.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while updating the parcel.'
                });
            });
        }

        // Delete parcel (Staff and Admin)
        function deleteParcel(trackingNumber) {

            Swal.fire({
                title: 'Are you sure?',
                text: 'Delete parcel ' + trackingNumber + '? This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('trackingNumber', trackingNumber);

                    fetch('../php/admin-delete-parcel.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'Parcel has been deleted.',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadParcels();
                            loadDashboardStats();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'Failed to delete parcel.'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while deleting the parcel.'
                        });
                    });
                }
            });
        }

        // Search parcels for QR generation
        let qrSearchTimeout;
        function searchParcelsForQR() {
            clearTimeout(qrSearchTimeout);
            qrSearchTimeout = setTimeout(() => {
                const searchTerm = document.getElementById('qrSearchInput').value.trim();
                const statusFilter = document.getElementById('qrStatusFilter').value;

                if (searchTerm.length < 2) {
                    document.getElementById('qrParcelResults').innerHTML = `
                        <div class="p-3 text-center text-muted">
                            <i class="fas fa-search fa-2x mb-2"></i>
                            <p>Enter at least 2 characters to search</p>
                        </div>
                    `;
                    return;
                }

                displayQRSearchResults(searchTerm, statusFilter);
            }, 300);
        }

        // Filter parcels for QR generation
        function filterParcelsForQR() {
            const searchTerm = document.getElementById('qrSearchInput').value.trim();
            const statusFilter = document.getElementById('qrStatusFilter').value;

            if (searchTerm.length >= 2) {
                displayQRSearchResults(searchTerm, statusFilter);
            }
        }

        // Display search results for QR generation
        function displayQRSearchResults(searchTerm, statusFilter) {
            const resultsContainer = document.getElementById('qrParcelResults');
            resultsContainer.innerHTML = `
                <div class="p-3 text-center">
                    <i class="fas fa-spinner fa-spin"></i> Searching...
                </div>
            `;

            // Filter parcels based on search term and status
            let filteredParcels = allParcels.filter(parcel => {
                const matchesSearch = parcel.TrackingNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                    parcel.ICNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
                                    (parcel.receiverName && parcel.receiverName.toLowerCase().includes(searchTerm.toLowerCase()));

                const matchesStatus = statusFilter === 'all' || parcel.status === statusFilter;

                return matchesSearch && matchesStatus;
            });

            // Sort by date (newest first)
            filteredParcels.sort((a, b) => new Date(b.date) - new Date(a.date));

            if (filteredParcels.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="p-3 text-center text-muted">
                        <i class="fas fa-search fa-2x mb-2"></i>
                        <p>No parcels found matching your criteria</p>
                        <small>Try different search terms or filter options</small>
                    </div>
                `;
                return;
            }

            // Display results (limit to first 10 for performance)
            const displayParcels = filteredParcels.slice(0, 10);
            let resultsHTML = '';

            displayParcels.forEach(parcel => {
                const statusBadge = parcel.status === 'Pending'
                    ? '<span class="badge bg-warning text-dark">Pending</span>'
                    : '<span class="badge bg-success">Retrieved</span>';

                resultsHTML += `
                    <div class="border-bottom p-3 parcel-result-item"
                         style="cursor: pointer; transition: all 0.2s ease;"
                         data-tracking="${parcel.TrackingNumber}"
                         onmouseover="this.style.backgroundColor='#f8f9fa'"
                         onmouseout="this.style.backgroundColor='white'">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold text-primary">${parcel.TrackingNumber}</div>
                                <div class="text-muted small">IC: ${formatICNumber(parcel.ICNumber)}</div>
                                <div class="text-muted small">Name: ${parcel.receiverName || 'N/A'}</div>
                                <div class="text-muted small">Location: ${parcel.deliveryLocation || 'N/A'}</div>
                            </div>
                            <div class="text-end">
                                ${statusBadge}
                                <div class="text-muted small mt-1">${parcel.date || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="mt-2 text-center">
                            <button class="btn btn-sm btn-primary select-parcel-btn"
                                    data-tracking="${parcel.TrackingNumber}"
                                    onclick="handleParcelSelection('${parcel.TrackingNumber}', this)">
                                <i class="fas fa-check me-1"></i>Select This Parcel
                            </button>
                        </div>
                    </div>
                `;
            });

            if (filteredParcels.length > 10) {
                resultsHTML += `
                    <div class="p-2 text-center text-muted small">
                        Showing first 10 of ${filteredParcels.length} results. Refine search for more specific results.
                    </div>
                `;
            }

            resultsContainer.innerHTML = resultsHTML;
        }

        // Handle parcel selection (wrapper function)
        function handleParcelSelection(trackingNumber, buttonElement) {
            console.log('handleParcelSelection called:', trackingNumber); // Debug log

            // Check for any existing modal backdrops before proceeding
            const existingBackdrops = document.querySelectorAll('.modal-backdrop');
            console.log('Existing modal backdrops before selection:', existingBackdrops.length);

            try {
                const parcelElement = buttonElement.closest('.parcel-result-item');
                selectParcelForQR(trackingNumber, parcelElement);

                // Check for modal backdrops after selection
                setTimeout(() => {
                    const newBackdrops = document.querySelectorAll('.modal-backdrop');
                    console.log('Modal backdrops after selection:', newBackdrops.length);
                    if (newBackdrops.length > 0) {
                        console.log('Unexpected modal backdrop detected! Removing...');
                        newBackdrops.forEach(backdrop => {
                            backdrop.remove();
                        });
                        document.body.classList.remove('modal-open');
                    }
                }, 100);

            } catch (error) {
                console.error('Error in handleParcelSelection:', error);
                alert('Error selecting parcel: ' + error.message);
            }
        }

        // Select parcel for QR generation
        function selectParcelForQR(trackingNumber, element) {
            console.log('selectParcelForQR called with:', trackingNumber, element); // Debug log
            const parcel = allParcels.find(p => p.TrackingNumber === trackingNumber);
            if (!parcel) {
                console.log('Parcel not found:', trackingNumber); // Debug log
                return;
            }
            console.log('Found parcel:', parcel); // Debug log

            // Update parcel info display
            updateParcelInfoForQR(parcel);

            // Highlight selected item - reset all first
            document.querySelectorAll('.parcel-result-item').forEach(item => {
                item.style.backgroundColor = 'white';
                item.style.border = 'none';
                item.style.boxShadow = 'none';
            });

            // Highlight the selected item
            if (element) {
                element.style.backgroundColor = '#e3f2fd';
                element.style.border = '2px solid #2196f3';
                element.style.boxShadow = '0 2px 8px rgba(33, 150, 243, 0.2)';
            }

            // Store selected tracking number
            window.selectedTrackingNumber = trackingNumber;

            // Show success feedback
            console.log('About to show SweetAlert toast...');
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Parcel Selected',
                text: `${trackingNumber} ready for QR generation`,
                showConfirmButton: false,
                timer: 2000,
                backdrop: false,  // Ensure no backdrop
                allowOutsideClick: true
            }).then(() => {
                console.log('SweetAlert toast completed');
                // Double-check for any modal backdrops after SweetAlert
                const backdrops = document.querySelectorAll('.modal-backdrop');
                if (backdrops.length > 0) {
                    console.log('Found modal backdrops after SweetAlert, removing...');
                    backdrops.forEach(backdrop => backdrop.remove());
                    document.body.classList.remove('modal-open');
                }
            });
        }

        // Update parcel information display for QR generation
        function updateParcelInfoForQR(parcel) {
            const parcelInfoCard = document.getElementById('parcelInfoCard');

            // Update tracking number display
            document.getElementById('parcelTrackingDisplay').textContent = parcel.TrackingNumber;

            // Update parcel details
            document.getElementById('parcelIC').textContent = formatICNumber(parcel.ICNumber);
            document.getElementById('parcelReceiverName').textContent = parcel.receiverName || 'N/A';
            document.getElementById('parcelLocation').textContent = parcel.deliveryLocation || 'N/A';

            // Update status with proper styling
            const statusElement = document.getElementById('parcelStatus');
            statusElement.textContent = parcel.status || 'N/A';
            statusElement.className = 'fw-bold';
            statusElement.style.fontSize = '1.1rem';

            if (parcel.status === 'Pending') {
                statusElement.style.color = '#f6ad55';
                statusElement.innerHTML = '<i class="fas fa-clock me-1"></i>Pending';
            } else if (parcel.status === 'Retrieved') {
                statusElement.style.color = '#48bb78';
                statusElement.innerHTML = '<i class="fas fa-check-circle me-1"></i>Retrieved';
            }

            // Show the card with animation
            parcelInfoCard.style.display = 'block';
            parcelInfoCard.style.opacity = '0';
            parcelInfoCard.style.transform = 'translateY(20px)';

            setTimeout(() => {
                parcelInfoCard.style.transition = 'all 0.3s ease';
                parcelInfoCard.style.opacity = '1';
                parcelInfoCard.style.transform = 'translateY(0)';
            }, 50);

            // Scroll to parcel info for better UX
            setTimeout(() => {
                parcelInfoCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 200);
        }

        // Update parcel information display
        function updateParcelInfo() {
            const trackingNumber = document.getElementById('qrTrackingNumber').value;
            const parcelInfoCard = document.getElementById('parcelInfoCard');

            if (!trackingNumber) {
                parcelInfoCard.style.display = 'none';
                document.getElementById('qrCodeContainer').style.display = 'none';
                return;
            }

            const parcel = allParcels.find(p => p.TrackingNumber === trackingNumber);
            if (parcel) {
                document.getElementById('parcelIC').textContent = formatICNumber(parcel.ICNumber);
                document.getElementById('parcelReceiverName').textContent = parcel.receiverName || 'N/A';
                document.getElementById('parcelLocation').textContent = parcel.deliveryLocation || 'N/A';

                const statusElement = document.getElementById('parcelStatus');
                statusElement.textContent = parcel.status || 'N/A';
                statusElement.className = 'fw-bold';
                if (parcel.status === 'Pending') {
                    statusElement.style.color = '#f6ad55';
                } else if (parcel.status === 'Retrieved') {
                    statusElement.style.color = '#48bb78';
                }

                parcelInfoCard.style.display = 'block';
            }
        }

        // Generate enhanced QR code with verification data
        function generateQR() {
            console.log('generateQR called, selectedTrackingNumber:', window.selectedTrackingNumber); // Debug log
            const trackingNumber = window.selectedTrackingNumber;
            if (!trackingNumber) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Parcel Selected',
                    text: 'Please search and select a parcel to generate QR code.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            const parcel = allParcels.find(p => p.TrackingNumber === trackingNumber);
            console.log('Found parcel for QR generation:', parcel); // Debug log
            if (!parcel) {
                console.log('Parcel not found in allParcels array'); // Debug log
                Swal.fire({
                    icon: 'error',
                    title: 'Parcel Not Found',
                    text: 'Selected parcel could not be found.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            // Create verification data object
            const verificationData = {
                tracking: trackingNumber,
                ic: parcel.ICNumber,
                timestamp: new Date().toISOString(),
                location: parcel.deliveryLocation,
                hash: btoa(trackingNumber + parcel.ICNumber + Date.now()) // Simple hash for verification
            };

            const qrContainer = document.getElementById('qrCodeDisplay');
            console.log('QR Container found:', qrContainer); // Debug log
            if (!qrContainer) {
                console.error('QR container not found!');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'QR code container not found on page.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }
            qrContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br><small class="text-muted mt-2">Generating secure QR code...</small></div>';

            // Show loading state
            setTimeout(() => {
                qrContainer.innerHTML = '';

                // Enhanced QR library availability check with auto-retry
                if (typeof QRCode === 'undefined') {
                    console.warn('QRCode library not immediately available, attempting to load...');

                    // Try to load QR library dynamically
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js';
                    script.onload = function() {
                        console.log('QRCode library loaded dynamically, retrying generation...');
                        setTimeout(() => generateQR(), 500);
                    };
                    script.onerror = function() {
                        console.error('Failed to load QRCode library dynamically');
                        qrContainer.innerHTML = '<div class="text-warning text-center p-3"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>QR Library Loading...<br><small>Using fallback implementation</small></div>';

                        // Force use fallback after short delay
                        setTimeout(() => {
                            if (typeof QRCode === 'undefined' && window.SimpleQR) {
                                console.log('Using SimpleQR fallback for generation');
                                // Fallback should be available from qrcode-simple.js
                                setTimeout(() => generateQR(), 200);
                            }
                        }, 1000);
                    };
                    document.head.appendChild(script);
                    return;
                }

                // Log which QR implementation is being used
                if (window.SimpleQR && !window.QRCode.toCanvas.toString().includes('native')) {
                    console.log('Using SimpleQR fallback for QR generation');
                } else {
                    console.log('Using native QRCode library');
                }

                console.log('Generating QR with data:', verificationData); // Debug log
                console.log('QR Container:', qrContainer); // Debug log
                console.log('QRCode function:', QRCode); // Debug log

                // Try to generate QR with verification data
                try {
                    console.log('Attempting QR generation with library:', typeof QRCode);
                    console.log('QRCode.toCanvas available:', typeof QRCode.toCanvas);
                    console.log('Verification data:', verificationData);

                    QRCode.toCanvas(qrContainer, JSON.stringify(verificationData), {
                        width: 220,
                        height: 220,
                        margin: 3,
                        color: {
                            dark: '#6A1B9A',
                            light: '#FFFFFF'
                        },
                        correctLevel: QRCode.CorrectLevel ? QRCode.CorrectLevel.H : 'H'
                    }, function (error) {
                        console.log('QR Generation callback called'); // Debug log
                        console.log('Error:', error); // Debug log

                        if (error) {
                            console.error('QR Generation Error:', error);
                            qrContainer.innerHTML = '<div class="text-danger text-center p-3"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br><small>QR Generation Failed</small></div>';
                            Swal.fire({
                                icon: 'error',
                                title: 'Generation Failed!',
                                text: 'Failed to generate QR code: ' + (error.message || error),
                                confirmButtonColor: '#6A1B9A'
                            });
                        } else {
                            console.log('QR Code generated successfully!'); // Debug log
                            document.getElementById('qrCodeContainer').style.display = 'block';
                            document.getElementById('qrCodeInfo').style.display = 'block';

                        // Save QR to database with enhanced data
                        const canvas = qrContainer.querySelector('canvas');
                        const qrDataURL = canvas.toDataURL('image/png', 1.0);

                        const formData = new FormData();
                        formData.append('trackingNumber', trackingNumber);
                        formData.append('qrCode', qrDataURL);
                        formData.append('verificationData', JSON.stringify(verificationData));

                        fetch('../php/admin-save-qr.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'QR Code Generated!',
                                    text: 'Secure verification QR code generated successfully.',
                                    timer: 2500,
                                    showConfirmButton: false,
                                    confirmButtonColor: '#6A1B9A'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error saving QR:', error);
                        });
                    }
                });
                } catch (error) {
                    console.error('QR Generation Exception:', error);
                    console.error('Error details:', {
                        message: error.message,
                        stack: error.stack,
                        qrLibraryType: typeof QRCode,
                        fallbackAvailable: !!window.SimpleQR
                    });

                    qrContainer.innerHTML = '<div class="text-warning text-center p-3"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>QR Generation Error<br><small>Trying alternative method...</small></div>';

                    // Try with simplified QR generation for fallback
                    setTimeout(() => {
                        try {
                            console.log('Attempting fallback QR generation with tracking number only');
                            QRCode.toCanvas(qrContainer, trackingNumber, {
                                width: 220,
                                height: 220,
                                margin: 3,
                                color: {
                                    dark: '#6A1B9A',
                                    light: '#FFFFFF'
                                }
                            }, function (error) {
                                if (error) {
                                    console.error('Fallback QR generation also failed:', error);
                                    qrContainer.innerHTML = '<div class="text-danger text-center p-3"><i class="fas fa-times-circle fa-2x mb-2"></i><br>QR Generation Failed<br><small>Please try refreshing the page</small></div>';
                                } else {
                                    console.log('Fallback QR generation successful');
                                    document.getElementById('qrCodeContainer').style.display = 'block';
                                    document.getElementById('qrCodeInfo').style.display = 'block';

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'QR Generated!',
                                        text: 'QR code generated using simplified method.',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                }
                            });
                        } catch (fallbackError) {
                            console.error('Fallback method also failed:', fallbackError);
                            qrContainer.innerHTML = '<div class="text-danger text-center p-3"><i class="fas fa-times-circle fa-2x mb-2"></i><br>QR Generation Failed<br><small>Please refresh the page and try again</small></div>';
                        }
                    }, 1000);
                }
            }, 800);
        }

        // Generate QR for specific parcel (from table action)
        function generateParcelQR(trackingNumber) {
            // Switch to QR generation tab
            const qrTab = document.getElementById('qr-generation-tab');
            qrTab.click();

            // Set search input and trigger search
            setTimeout(() => {
                document.getElementById('qrSearchInput').value = trackingNumber;
                searchParcelsForQR();

                // Auto-select the parcel after search results load
                setTimeout(() => {
                    // Find the parcel element and select it
                    const parcelElement = document.querySelector(`[onclick*="${trackingNumber}"]`);
                    selectParcelForQR(trackingNumber, parcelElement);

                    // Auto-generate QR
                    setTimeout(() => {
                        generateQR();
                    }, 500);
                }, 500);
            }, 300);
        }

        // Enhanced download QR code with receiver information
        function downloadQR() {
            // Check for both canvas and image elements
            const canvas = document.querySelector('#qrCodeDisplay canvas');
            const img = document.querySelector('#qrCodeDisplay img');

            if (!canvas && !img) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No QR Code',
                    text: 'Please generate a QR code first.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            const trackingNumber = window.selectedTrackingNumber;
            const parcel = allParcels.find(p => p.TrackingNumber === trackingNumber);

            // Create enhanced image with parcel information
            const enhancedCanvas = document.createElement('canvas');
            const ctx = enhancedCanvas.getContext('2d');

            // Set canvas size (QR + info area)
            enhancedCanvas.width = 400;
            enhancedCanvas.height = 500;

            // White background
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, enhancedCanvas.width, enhancedCanvas.height);

            // Header
            ctx.fillStyle = '#6A1B9A';
            ctx.fillRect(0, 0, enhancedCanvas.width, 60);

            // Header text
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 18px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('Perwira Parcel Verification', enhancedCanvas.width / 2, 35);

            // Draw QR code (handle both canvas and image)
            const qrSize = 220;
            const qrX = (enhancedCanvas.width - qrSize) / 2;
            const qrY = 80;

            if (canvas) {
                ctx.drawImage(canvas, qrX, qrY, qrSize, qrSize);
            } else if (img) {
                ctx.drawImage(img, qrX, qrY, qrSize, qrSize);
            }

            // Parcel information
            ctx.fillStyle = '#2d3748';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'left';

            const infoY = qrY + qrSize + 30;
            const leftMargin = 40;

            ctx.fillText('Tracking Number:', leftMargin, infoY);
            ctx.font = '12px Arial';
            ctx.fillText(trackingNumber, leftMargin + 120, infoY);

            ctx.font = 'bold 14px Arial';
            ctx.fillText('Receiver IC:', leftMargin, infoY + 25);
            ctx.font = '12px Arial';
            ctx.fillText(formatICNumber(parcel.ICNumber), leftMargin + 120, infoY + 25);

            ctx.font = 'bold 14px Arial';
            ctx.fillText('Location:', leftMargin, infoY + 50);
            ctx.font = '12px Arial';
            ctx.fillText(parcel.deliveryLocation || 'N/A', leftMargin + 120, infoY + 50);

            // Instructions
            ctx.fillStyle = '#64748b';
            ctx.font = '10px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('Present this QR code at the parcel counter for verification', enhancedCanvas.width / 2, infoY + 85);
            ctx.fillText('Generated: ' + new Date().toLocaleString(), enhancedCanvas.width / 2, infoY + 100);

            // Download enhanced image
            const link = document.createElement('a');
            link.download = 'PPMS_Verification_' + trackingNumber + '.png';
            link.href = enhancedCanvas.toDataURL('image/png', 1.0);
            link.click();

            Swal.fire({
                icon: 'success',
                title: 'Downloaded!',
                text: 'QR verification image downloaded successfully.',
                timer: 2000,
                showConfirmButton: false,
                confirmButtonColor: '#6A1B9A'
            });
        }

        // Email QR code to receiver
        function emailQR() {
            // Check for both canvas and image elements
            const canvas = document.querySelector('#qrCodeDisplay canvas');
            const img = document.querySelector('#qrCodeDisplay img');

            if (!canvas && !img) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No QR Code',
                    text: 'Please generate a QR code first.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            const trackingNumber = window.selectedTrackingNumber;
            const parcel = allParcels.find(p => p.TrackingNumber === trackingNumber);

            Swal.fire({
                title: 'Email QR Code',
                html: `
                    <div class="text-start">
                        <p><strong>Parcel:</strong> ${trackingNumber}</p>
                        <p><strong>Receiver:</strong> ${parcel.receiverName || 'N/A'}</p>
                        <p><strong>IC Number:</strong> ${formatICNumber(parcel.ICNumber)}</p>
                        <hr>
                        <div class="mb-3">
                            <label for="receiverEmail" class="form-label">Receiver's Email:</label>
                            <input type="email" class="form-control" id="receiverEmail" placeholder="Enter receiver's email address">
                        </div>
                        <div class="mb-3">
                            <label for="emailMessage" class="form-label">Additional Message (Optional):</label>
                            <textarea class="form-control" id="emailMessage" rows="3" placeholder="Add a personal message..."></textarea>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-envelope me-2"></i>Send Email',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#6A1B9A',
                preConfirm: () => {
                    const email = document.getElementById('receiverEmail').value;
                    const message = document.getElementById('emailMessage').value;

                    if (!email) {
                        Swal.showValidationMessage('Please enter receiver\'s email address');
                        return false;
                    }

                    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                        Swal.showValidationMessage('Please enter a valid email address');
                        return false;
                    }

                    return { email, message };
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    sendQREmail(trackingNumber, result.value.email, result.value.message);
                }
            });
        }

        // Send QR email
        function sendQREmail(trackingNumber, email, message) {
            const canvas = document.querySelector('#qrCodeDisplay canvas');
            const img = document.querySelector('#qrCodeDisplay img');

            let qrDataURL;
            if (canvas) {
                qrDataURL = canvas.toDataURL('image/png', 1.0);
            } else if (img) {
                qrDataURL = img.src;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'QR code not found.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            const formData = new FormData();
            formData.append('trackingNumber', trackingNumber);
            formData.append('receiverEmail', email);
            formData.append('qrImage', qrDataURL);
            formData.append('additionalMessage', message);

            Swal.fire({
                title: 'Sending Email...',
                text: 'Please wait while we send the QR code to the receiver.',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch('../php/send-qr-email.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '📧 Email Sent Successfully!',
                        html: `
                            <div class="text-start">
                                <p><i class="fas fa-check-circle text-success me-2"></i><strong>QR verification sent!</strong></p>
                                <p>${data.message}</p>
                                <hr>
                                <p><small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    QR code delivered with pickup instructions.
                                </small></p>
                            </div>
                        `,
                        confirmButtonColor: '#6A1B9A'
                    });
                } else {
                    // Check if it's a recent email duplicate
                    if (data.message && data.message.includes('already sent recently')) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Email Recently Sent',
                            html: `
                                <div class="text-start">
                                    <p><i class="fas fa-clock text-warning me-2"></i><strong>QR email was already sent recently</strong></p>
                                    <p>Please wait 5 minutes before sending another QR email for this parcel.</p>
                                    <hr>
                                    <p><small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        This prevents spam and ensures receivers don't get duplicate emails.
                                    </small></p>
                                </div>
                            `,
                            confirmButtonColor: '#6A1B9A'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Email Failed',
                            text: data.message || 'Failed to send email. Please try again.',
                            confirmButtonColor: '#6A1B9A'
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error sending email:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Email Error',
                    text: 'An error occurred while sending the email.',
                    confirmButtonColor: '#6A1B9A'
                });
            });
        }

        // Print QR code
        function printQR() {
            // Check for both canvas and image elements
            const canvas = document.querySelector('#qrCodeDisplay canvas');
            const img = document.querySelector('#qrCodeDisplay img');

            if (!canvas && !img) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No QR Code',
                    text: 'Please generate a QR code first.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            const trackingNumber = window.selectedTrackingNumber;
            const parcel = allParcels.find(p => p.TrackingNumber === trackingNumber);

            // Create print window
            const printWindow = window.open('', '_blank');
            let qrImageData;

            if (canvas) {
                qrImageData = canvas.toDataURL();
            } else if (img) {
                qrImageData = img.src;
            }

            const currentDateTime = new Date().toLocaleString();

            const printHTML = '<!DOCTYPE html>' +
                '<html>' +
                '<head>' +
                    '<title>PPMS QR Verification - ' + trackingNumber + '</title>' +
                    '<style>' +
                        'body { font-family: Arial, sans-serif; text-align: center; padding: 20px; background: white; }' +
                        '.header { background: #6A1B9A; color: white; padding: 15px; margin-bottom: 20px; border-radius: 8px; }' +
                        '.qr-container { margin: 20px 0; padding: 20px; border: 2px dashed #6A1B9A; border-radius: 12px; }' +
                        '.info-table { margin: 20px auto; border-collapse: collapse; width: 100%; max-width: 400px; }' +
                        '.info-table td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }' +
                        '.info-table td:first-child { font-weight: bold; background: #f8f9fa; width: 40%; }' +
                        '.instructions { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px; font-size: 14px; color: #64748b; }' +
                        '@media print { body { margin: 0; } .no-print { display: none; } }' +
                    '</style>' +
                '</head>' +
                '<body>' +
                    '<div class="header">' +
                        '<h2>Perwira Parcel Management System</h2>' +
                        '<h3>Parcel Verification QR Code</h3>' +
                    '</div>' +
                    '<div class="qr-container">' +
                        '<img src="' + qrImageData + '" alt="QR Code" style="max-width: 250px;">' +
                    '</div>' +
                    '<table class="info-table">' +
                        '<tr><td>Tracking Number:</td><td>' + trackingNumber + '</td></tr>' +
                        '<tr><td>Receiver IC:</td><td>' + formatICNumber(parcel.ICNumber) + '</td></tr>' +
                        '<tr><td>Receiver Name:</td><td>' + (parcel.receiverName || 'N/A') + '</td></tr>' +
                        '<tr><td>Pickup Location:</td><td>' + (parcel.deliveryLocation || 'N/A') + '</td></tr>' +
                        '<tr><td>Status:</td><td>' + (parcel.status || 'N/A') + '</td></tr>' +
                        '<tr><td>Generated:</td><td>' + currentDateTime + '</td></tr>' +
                    '</table>' +
                    '<div class="instructions">' +
                        '<strong>Instructions:</strong><br>' +
                        '1. Present this QR code at Kolej Kediaman Luar Kampus - UTHM<br>' +
                        '2. Staff will scan the code for verification<br>' +
                        '3. QR code is unique to this parcel and receiver' +
                    '</div>' +
                    '<scr' + 'ipt>' +
                        'window.onload = function() {' +
                            'setTimeout(function() { window.print(); }, 500);' +
                        '}' +
                    '</scr' + 'ipt>' +
                '</body>' +
                '</html>';

            printWindow.document.write(printHTML);
            printWindow.document.close();
        }

        // Generate report (Admin only)
        function generateReport() {
            if (!isAdmin) {
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: 'Only administrators can generate reports.'
                });
                return;
            }

            const startDate = document.getElementById('reportStartDate').value;
            const endDate = document.getElementById('reportEndDate').value;
            const status = document.getElementById('reportStatus').value;
            const staffID = document.getElementById('reportStaffID').value;
            const receiverIC = document.getElementById('reportReceiverIC').value;

            const formData = new FormData();
            formData.append('startDate', startDate);
            formData.append('endDate', endDate);
            formData.append('status', status);
            formData.append('staffID', staffID);
            formData.append('receiverIC', receiverIC);

            fetch('../php/admin-generate-report.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayReportResults(data.report);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to generate report.'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while generating the report.'
                });
            });
        }

        // Display report results
        function displayReportResults(reportData) {
            const resultsDiv = document.getElementById('reportResults');

            let html = `
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Report Results</h6>
                        <button class="btn btn-sm btn-outline-primary" onclick="printReport()">
                            <i class="fas fa-print me-1"></i>Print Report
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped" id="reportTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Retrieval ID</th>
                                        <th>Tracking Number</th>
                                        <th>Receiver IC</th>
                                        <th>Receiver Name</th>
                                        <th>Staff ID</th>
                                        <th>Retrieve Date</th>
                                        <th>Retrieve Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;

            reportData.forEach(item => {
                html += `
                    <tr>
                        <td>${item.retrievalID || 'N/A'}</td>
                        <td>${item.TrackingNumber}</td>
                        <td>${formatICNumber(item.ICNumber)}</td>
                        <td>${item.receiverName || 'N/A'}</td>
                        <td>${item.staffID || 'N/A'}</td>
                        <td>${item.retrieveDate || 'N/A'}</td>
                        <td>${item.retrieveTime || 'N/A'}</td>
                        <td>
                            <span class="badge ${item.status === 'Pending' ? 'bg-warning text-dark' : 'bg-success'}">
 the delete button is missing u                                ${item.status}
                            </span>
                        </td>
                    </tr>
                `;
            });

            html += `
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <small class="text-muted">
                                Total Records: ${reportData.length} |
                                Generated on: ${new Date().toLocaleString()} |
                                Generated by: ${userName} (${userID})
                            </small>
                        </div>
                    </div>
                </div>
            `;

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        // Export report to Excel
        function exportReport() {
            if (!isAdmin) {
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: 'Only administrators can export reports.'
                });
                return;
            }

            Swal.fire({
                icon: 'info',
                title: 'Export Feature',
                text: 'Excel export functionality will be implemented soon.',
                timer: 2000,
                showConfirmButton: false
            });
        }

        // Print report
        function printReport() {
            const reportTable = document.getElementById('reportTable');
            if (!reportTable) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Report',
                    text: 'Please generate a report first.'
                });
                return;
            }

            const printWindow = window.open('', '_blank');
            const currentDate = new Date().toLocaleString();
            const reportHTML = reportTable.outerHTML;

            printWindow.document.write(
                '<html>' +
                    '<head>' +
                        '<title>PPMS Admin Report</title>' +
                        '<link href="../css/bootstrap/bootstrap.min.css" rel="stylesheet">' +
                        '<style>' +
                            '@media print { .no-print { display: none !important; } }' +
                            'body { font-size: 12px; }' +
                            '.header { text-align: center; margin-bottom: 20px; }' +
                        '</style>' +
                    '</head>' +
                    '<body>' +
                        '<div class="container">' +
                            '<div class="header">' +
                                '<h3>Perwira Parcel Management System</h3>' +
                                '<h5>Admin Report - Retrieval Records</h5>' +
                                '<p>Generated on: ' + currentDate + '</p>' +
                                '<p>Generated by: ' + userName + ' (' + userID + ')</p>' +
                            '</div>' +
                            reportHTML +
                        '</div>' +
                    '</body>' +
                '</html>'
            );
            printWindow.document.close();
            printWindow.print();
        }

        // Show profile
        function showProfile() {
            const profileTitle = (isAdmin ? 'Administrator' : 'Staff') + ' Profile';
            const roleLabel = isAdmin ? 'Admin' : 'Staff';
            const accessLevel = isAdmin ? 'Full Admin Access' : 'Staff Access';
            const currentTime = new Date().toLocaleString();

            Swal.fire({
                title: profileTitle,
                html: '<div class="text-start">' +
                        '<p><strong>Name:</strong> ' + userName + '</p>' +
                        '<p><strong>' + roleLabel + ' ID:</strong> ' + userID + '</p>' +
                        '<p><strong>Role:</strong> ' + userRole + '</p>' +
                        '<p><strong>Access Level:</strong> ' + accessLevel + '</p>' +
                        '<p><strong>Login Time:</strong> ' + currentTime + '</p>' +
                      '</div>',
                icon: 'info',
                confirmButtonText: 'Close'
            });
        }
    </script>

    <!-- Modern Logout Dialog Styling for Staff -->
    <style>
    /* Modern Logout Popup Styling */
    .modern-logout-popup {
        border-radius: 20px !important;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15) !important;
        border: none !important;
        padding: 2rem !important;
        max-width: 420px !important;
    }

    .modern-loading-popup {
        border-radius: 16px !important;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
        border: none !important;
        padding: 1.5rem !important;
        max-width: 350px !important;
    }

    /* Staff Theme Button Styling */
    .staff-confirm {
        background: linear-gradient(135deg, #6A1B9A 0%, #FF9800 100%) !important;
        border: none !important;
        border-radius: 12px !important;
        padding: 0.75rem 1.5rem !important;
        font-weight: 600 !important;
        font-size: 0.95rem !important;
        color: white !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 4px 15px rgba(106, 27, 154, 0.3) !important;
        margin: 0 0.5rem !important;
    }

    .staff-confirm:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 25px rgba(106, 27, 154, 0.4) !important;
        background: linear-gradient(135deg, #5a1a7a 0%, #e6870a 100%) !important;
    }

    .modern-cancel-btn {
        background: #f9fafb !important;
        border: 2px solid #e5e7eb !important;
        border-radius: 12px !important;
        padding: 0.75rem 1.5rem !important;
        font-weight: 600 !important;
        font-size: 0.95rem !important;
        color: #6b7280 !important;
        transition: all 0.3s ease !important;
        margin: 0 0.5rem !important;
    }

    .modern-cancel-btn:hover {
        background: #f3f4f6 !important;
        border-color: #d1d5db !important;
        color: #374151 !important;
        transform: translateY(-1px) !important;
    }

    /* Staff Theme Specific */
    .staff-theme {
        background: linear-gradient(135deg, rgba(106, 27, 154, 0.02), rgba(255, 152, 0, 0.02)) !important;
    }

    /* SweetAlert2 Overrides */
    .swal2-popup {
        font-family: 'Inter', 'Poppins', sans-serif !important;
        border: none !important;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15) !important;
    }

    .swal2-actions {
        margin-top: 2rem !important;
        gap: 0.5rem !important;
    }

    /* Force remove any borders from SweetAlert icons */
    .swal2-icon {
        border: none !important;
        box-shadow: none !important;
    }

    .swal2-icon-content {
        border: none !important;
    }

    /* Custom icon container overrides */
    .modern-logout-popup .swal2-icon,
    .modern-loading-popup .swal2-icon {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    /* Fix backdrop transparency */
    .swal2-container.swal2-backdrop-show {
        background-color: rgba(0, 0, 0, 0.75) !important;
    }

    /* Ensure popup is fully opaque */
    .modern-logout-popup.staff-theme,
    .modern-loading-popup.staff-theme {
        background: #ffffff !important;
        opacity: 1 !important;
    }

    /* Shimmer Animation for Icon */
    @keyframes shimmer {
        0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
        100% { transform: translateX(100%) translateY(100%) rotate(45deg); }
    }

    /* === CLEAN MODERN CAROUSEL === */
    .footer-section-title {
        color: #1f2937;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .modern-carousel-container {
        position: relative;
        max-width: 100%;
        margin: 0 auto;
        padding: 2rem 0;
        background: linear-gradient(135deg, rgba(106, 27, 154, 0.03) 0%, rgba(255, 152, 0, 0.03) 100%);
        border-radius: 20px;
        overflow: hidden;
    }

    .carousel-viewport {
        overflow: hidden;
        padding: 0 3rem;
        margin: 0 auto;
    }

    .carousel-track {
        display: flex;
        gap: 1.5rem;
        transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        padding: 1rem 0;
    }

    .partner-card {
        flex: 0 0 220px;
        background: #ffffff;
        border-radius: 14px;
        box-shadow: 0 6px 24px rgba(0, 0, 0, 0.06);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        border: 1px solid rgba(0, 0, 0, 0.04);
        overflow: hidden;
        cursor: pointer;
    }

    .partner-card:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.12);
        border-color: rgba(106, 27, 154, 0.1);
    }

    .partner-card-inner {
        padding: 1.5rem 1rem;
        text-align: center;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .partner-logo-container {
        width: 60px;
        height: 60px;
        margin: 0 auto 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(106, 27, 154, 0.05) 0%, rgba(255, 152, 0, 0.05) 100%);
        border-radius: 12px;
        transition: all 0.3s ease;
    }

    .partner-card:hover .partner-logo-container {
        background: linear-gradient(135deg, rgba(106, 27, 154, 0.1) 0%, rgba(255, 152, 0, 0.1) 100%);
        transform: scale(1.05);
    }

    .partner-logo {
        max-width: 45px;
        max-height: 45px;
        object-fit: contain;
        filter: grayscale(30%) brightness(0.95);
        transition: all 0.3s ease;
    }

    .partner-card:hover .partner-logo {
        filter: grayscale(0%) brightness(1.1);
        transform: scale(1.1);
    }

    .partner-logo-placeholder {
        width: 45px;
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, rgba(106, 27, 154, 0.1) 0%, rgba(255, 152, 0, 0.1) 100%);
        border-radius: 10px;
        font-size: 0.65rem;
        font-weight: 600;
        color: rgba(106, 27, 154, 0.8);
        text-align: center;
        line-height: 1.2;
    }

    .partner-info {
        text-align: center;
    }

    .partner-name {
        font-size: 1.1rem;
        font-weight: 800;
        color: #1a202c;
        margin: 0;
        letter-spacing: -0.02em;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        text-align: center;
        line-height: 1.2;
    }

    /* Aesthetic Navigation Arrows */
    .carousel-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        box-shadow: 0 4px 16px rgba(240, 147, 251, 0.3);
        z-index: 10;
        opacity: 0;
        transform: translateY(-50%) scale(0.9);
        color: white;
    }

    .modern-carousel-container:hover .carousel-nav {
        opacity: 1;
        transform: translateY(-50%) scale(1);
    }

    .carousel-nav-prev {
        left: 1rem;
    }

    .carousel-nav-next {
        right: 1rem;
    }

    .carousel-nav:hover {
        background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 6px 24px rgba(245, 87, 108, 0.4);
    }

    .carousel-nav i {
        font-size: 0.9rem;
        transition: transform 0.2s ease;
    }

    .carousel-nav:hover i {
        transform: scale(1.1);
    }

    /* Aesthetic Indicators */
    .carousel-indicators {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1.5rem;
        padding: 0.75rem 1.25rem;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 25px;
        width: fit-content;
        margin-left: auto;
        margin-right: auto;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
        backdrop-filter: blur(10px);
    }

    .carousel-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        border: none;
        background: rgba(106, 27, 154, 0.25);
        cursor: pointer;
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        position: relative;
        overflow: hidden;
    }

    .carousel-indicator::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        border-radius: 50%;
        transform: scale(0);
        transition: transform 0.3s ease;
    }

    .carousel-indicator.active::before,
    .carousel-indicator:hover::before {
        transform: scale(1);
    }

    .carousel-indicator.active {
        background: rgba(255, 255, 255, 0.9);
        transform: scale(1.4);
        box-shadow: 0 2px 8px rgba(106, 27, 154, 0.2);
    }

    .carousel-indicator:hover {
        transform: scale(1.2);
        background: rgba(255, 255, 255, 0.7);
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .partner-card {
            flex: 0 0 200px;
        }

        .carousel-viewport {
            padding: 0 2rem;
        }
    }

    @media (max-width: 768px) {
        .modern-carousel-container {
            padding: 1.5rem 0;
        }

        .carousel-viewport {
            padding: 0 1rem;
        }

        .partner-card {
            flex: 0 0 180px;
        }

        .partner-card-inner {
            padding: 1.25rem 0.75rem;
        }

        .partner-logo-container {
            width: 50px;
            height: 50px;
            margin-bottom: 0.75rem;
        }

        .partner-logo {
            max-width: 38px;
            max-height: 38px;
        }

        .partner-logo-placeholder {
            width: 38px;
            height: 38px;
            font-size: 0.6rem;
        }

        .partner-name {
            font-size: 1rem;
        }

        .partner-description {
            font-size: 0.75rem;
        }

        .carousel-nav {
            width: 36px;
            height: 36px;
        }

        .carousel-nav i {
            font-size: 0.8rem;
        }

        .carousel-indicator {
            width: 6px;
            height: 6px;
        }
    }

    @media (max-width: 480px) {
        .partner-card {
            flex: 0 0 150px;
        }

        .carousel-track {
            gap: 1rem;
        }

        .partner-card-inner {
            padding: 1rem 0.5rem;
        }

        .partner-logo-container {
            width: 45px;
            height: 45px;
        }

        .partner-logo {
            max-width: 32px;
            max-height: 32px;
        }

        .partner-name {
            font-size: 0.95rem;
        }

        .partner-description {
            font-size: 0.7rem;
        }
    }
    </style>

    <script>
    // Modern Carousel with Infinite Scroll
    let currentCarouselIndex = 0;
    let isAutoSliding = true;
    let autoSlideInterval;
    let cardsPerView = 3;
    let isTransitioning = false;
    let originalCardsCount = 8; // Number of unique cards

    function initializeCarousel() {
        const track = document.getElementById('modernCarouselTrack');
        if (!track) return;

        // Calculate cards per view based on screen size
        updateCardsPerView();

        // Start auto-slide
        startAutoSlide();

        // Pause on hover
        const container = document.querySelector('.modern-carousel-container');
        container.addEventListener('mouseenter', () => {
            isAutoSliding = false;
            clearInterval(autoSlideInterval);
        });

        // Resume on mouse leave
        container.addEventListener('mouseleave', () => {
            isAutoSliding = true;
            startAutoSlide();
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            updateCardsPerView();
            updateCarouselPosition();
        });
    }

    function updateCardsPerView() {
        const width = window.innerWidth;
        if (width < 480) {
            cardsPerView = 1;
        } else if (width < 768) {
            cardsPerView = 2;
        } else if (width < 1024) {
            cardsPerView = 2;
        } else {
            cardsPerView = 3;
        }
    }

    function startAutoSlide() {
        if (!isAutoSliding) return;

        autoSlideInterval = setInterval(() => {
            moveCarousel('next');
        }, 4000); // Slower for better viewing
    }

    function moveCarousel(direction) {
        if (isTransitioning) return;

        const track = document.getElementById('modernCarouselTrack');

        if (direction === 'next') {
            currentCarouselIndex++;
            updateCarouselPosition();

            // Check if we've reached the end of original cards
            if (currentCarouselIndex >= originalCardsCount) {
                isTransitioning = true;
                setTimeout(() => {
                    // Reset to beginning without animation
                    currentCarouselIndex = 0;
                    track.style.transition = 'none';
                    updateCarouselPosition(false);

                    // Re-enable transition after a brief moment
                    setTimeout(() => {
                        track.style.transition = 'transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                        isTransitioning = false;
                    }, 50);
                }, 500);
            }
        } else {
            if (currentCarouselIndex <= 0) {
                // Jump to end of original set
                isTransitioning = true;
                currentCarouselIndex = originalCardsCount - 1;
                track.style.transition = 'none';
                updateCarouselPosition(false);

                setTimeout(() => {
                    track.style.transition = 'transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                    isTransitioning = false;
                }, 50);
            } else {
                currentCarouselIndex--;
                updateCarouselPosition();
            }
        }

        updateCarouselIndicators();
    }

    function goToSlide(index) {
        if (isTransitioning) return;

        currentCarouselIndex = index;
        updateCarouselPosition();
        updateCarouselIndicators();

        // Reset auto-slide
        if (isAutoSliding) {
            clearInterval(autoSlideInterval);
            startAutoSlide();
        }
    }

    function updateCarouselPosition(animate = true) {
        const track = document.getElementById('modernCarouselTrack');
        const cards = track.children;

        if (cards.length === 0) return;

        const cardWidth = cards[0].offsetWidth;
        const gap = 24; // 1.5rem gap
        const offset = currentCarouselIndex * (cardWidth + gap);

        if (animate) {
            track.style.transition = 'transform 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
        }
        track.style.transform = `translateX(-${offset}px)`;
    }

    function updateCarouselIndicators() {
        const indicators = document.querySelectorAll('.carousel-indicator');
        const currentPage = Math.floor((currentCarouselIndex % originalCardsCount) / Math.max(1, Math.floor(originalCardsCount / indicators.length)));

        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === currentPage);
        });
    }

    // Initialize carousel when page loads
    document.addEventListener('DOMContentLoaded', initializeCarousel);

    // Function to format IC number with dashes
    function formatICNumber(icNumber) {
        if (!icNumber || icNumber.length !== 12) {
            return icNumber; // Return as-is if not 12 digits
        }

        // Format as 123456-78-9012
        return icNumber.substring(0, 6) + '-' + icNumber.substring(6, 8) + '-' + icNumber.substring(8, 12);
    }
    </script>
</body>
</html>
