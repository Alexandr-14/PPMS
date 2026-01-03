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
    <!-- SheetJS for Excel Export -->
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <!-- QR Code Generator - Primary CDN -->
    <script src="https://unpkg.com/qrcode@1.5.3/build/qrcode.min.js"></script>
    <!-- Local QR Code Fallback -->
    <script src="../js/qrcode-simple.js"></script>
    <!-- PPMS QR Code Configuration -->
    <script src="../js/qr-config.js"></script>
    <!-- PPMS Custom Styles -->
    <link rel="stylesheet" href="../css/ppms-styles/shared/variables.css">
    <link rel="stylesheet" href="../css/ppms-styles/shared/typography.css">
    <link rel="stylesheet" href="../css/ppms-styles/shared/safe-typography-enhancements.css">
    <link rel="stylesheet" href="../css/ppms-styles/shared/components.css">
    <link rel="stylesheet" href="../css/ppms-styles/staff/staff-dashboard-refined.css">
    <link rel="stylesheet" href="../css/ppms-styles/staff/staff-dashboard-overrides.css">
    <link rel="stylesheet" href="../css/ppms-styles/staff/staff-navbar-buttons.css">
    <!-- Mobile Responsive Styles -->
    <link rel="stylesheet" href="../css/ppms-styles/shared/mobile-responsive.css">
    <!-- Favicon -->
    <link rel="icon" href="../assets/Icon Web.ico" type="image/x-icon">

    <style>
        /* QR Action Buttons - Staff Theme */
        .btn-qr-staff {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            color: white;
        }

        .btn-qr-staff-download {
            background: #9C27B0 !important;
        }

        .btn-qr-staff-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(156, 39, 176, 0.3);
            background: #7B1FA2 !important;
        }

        .btn-qr-staff-enlarge {
            background: #FF9800 !important;
        }

        .btn-qr-staff-enlarge:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.3);
            background: #F57C00 !important;
        }

        /* ===== ADMIN REPORT MODULE STYLES ===== */

        /* Purple Gradient Badge */
        .bg-purple-gradient {
            background: linear-gradient(135deg, #6A1B9A 0%, #9C27B0 100%);
            color: white;
        }

        .bg-purple {
            background-color: #6A1B9A !important;
            color: white;
        }

        .bg-purple-light {
            background: linear-gradient(135deg, rgba(106, 27, 154, 0.85) 0%, rgba(156, 39, 176, 0.85) 100%);
            border-bottom: 2px solid #6A1B9A;
            color: white !important;
        }

        .bg-purple-light h6 {
            color: white !important;
        }

        /* Report Header */
        .report-header {
            padding: 1rem;
            background: linear-gradient(135deg, rgba(106, 27, 154, 0.05) 0%, rgba(255, 152, 0, 0.05) 100%);
            border-radius: 12px;
            border-left: 4px solid #6A1B9A;
        }

        .report-header h4 {
            color: #1f2937 !important;
            font-weight: 700;
        }

        .report-header p {
            color: #6b7280 !important;
        }

        .report-badge .badge {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
        }

        /* Quick Filter Buttons */
        .quick-filters {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .btn-outline-purple {
            color: #6A1B9A;
            border-color: #6A1B9A;
        }

        .btn-outline-purple:hover, .btn-outline-purple:focus, .btn-outline-purple.active {
            background-color: #6A1B9A;
            border-color: #6A1B9A;
            color: white;
        }

        .btn-purple {
            background: linear-gradient(135deg, #6A1B9A 0%, #9C27B0 100%);
            border: none;
            color: white;
        }

        .btn-purple:hover {
            background: linear-gradient(135deg, #5c1786 0%, #8a1f9e 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(106, 27, 154, 0.3);
        }

        .btn-orange {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            border: none;
            color: white;
        }

        .btn-orange:hover {
            background: linear-gradient(135deg, #e68a00 0%, #d66a00 100%);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
        }

        /* Report Filter Card */
        .report-filter-card {
            border: none;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            overflow: hidden;
        }

        .report-filter-card .card-header {
            font-weight: 600;
        }

        /* Report Statistics Cards */
        .report-stat-card {
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .report-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .report-stat-card .card-body {
            padding: 1.5rem;
        }

        .report-stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-total {
            background: linear-gradient(135deg, #6A1B9A 0%, #9C27B0 100%);
            color: white;
        }

        .stat-retrieved {
            background: linear-gradient(135deg, #43e97b 0%, #38d9a9 100%);
            color: white;
        }

        .stat-pending {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            color: white;
        }

        .stat-rate {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* Report Table */
        .table-purple {
            background: linear-gradient(135deg, #6A1B9A 0%, #9C27B0 100%);
            color: white;
        }

        .table-purple th {
            font-weight: 600;
            padding: 1rem 0.75rem;
            border: none;
            white-space: nowrap;
        }

        #reportTable tbody tr:hover {
            background-color: rgba(106, 27, 154, 0.08);
        }

        #reportTable td {
            vertical-align: middle;
            padding: 0.875rem 0.75rem;
        }

        /* Status badges in report */
        .badge-retrieved {
            background: linear-gradient(135deg, #43e97b 0%, #38d9a9 100%);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .badge-pending {
            background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-weight: 500;
        }

        /* Print styles */
        @media print {
            .no-print, .navbar-custom, .quick-filters, .report-filter-card, .btn-group {
                display: none !important;
            }
            .report-stat-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }

        /* SweetAlert Toast Styling - Solid White Background, No Backdrop */
        .swal2-container.swal2-top-end.swal2-backdrop-show,
        .swal2-container.swal2-top-end {
            background: transparent !important;
        }

        .swal2-popup.swal2-toast {
            background: #ffffff !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15) !important;
            border-radius: 10px !important;
        }

        .swal2-popup.swal2-toast .swal2-title {
            color: #333 !important;
        }

    </style>
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
                        Perwira Parcel Management System - Admin
                    <?php else: ?>
                        Perwira Parcel Management System - Staff
                    <?php endif; ?>
                </div>
                <div class="navbar-subtitle" style="font-size: 0.85rem; opacity: 0.8;">
                    <?php if ($is_admin): ?>
                        Admin Access - Parcel Management
                    <?php else: ?>
                        Staff Access - Parcel Management
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="navbar-user-name">
                <?php echo $is_admin ? 'Admin' : htmlspecialchars($user_name); ?>
            </div>
            <button type="button" class="logout-btn" onclick="logout()" aria-label="Logout">
                <i class="fas fa-sign-out-alt me-2"></i><span class="logout-text">Logout</span>
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
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="parcel-history-tab" data-bs-toggle="tab" data-bs-target="#parcel-history" type="button" role="tab">
                    <i class="fas fa-history me-1"></i> Parcel History
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
                                <div class="form-text">Tracking number of the parcel</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="receiverIC" class="form-label">
                                    <i class="fas fa-id-card me-2"></i>Receiver Matric Number *
                                </label>
                                <input type="text" class="form-control" id="receiverIC" required
                                       placeholder="Enter receiver's Matric number (8 digits)">
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
                                <div class="form-text">Enter parcel weight</div>
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
                        <h4 class="mb-1">Active Parcels</h4>
                        <p class="text-muted mb-0">View, edit, and manage pending parcels in the system.</p>
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
                    <!-- Controls Row: Search (left) + Sort/Refresh (right) -->
                    <div class="d-flex flex-wrap align-items-center mb-3 gap-3">
                        <div class="input-group" style="width: 100%; max-width: 450px;">
                            <input type="text" id="parcelListSearchInput" class="form-control" placeholder="Find a parcel..." style="border: none; border-radius: 50px; background: white; font-size: 0.95rem; padding: 12px 20px 12px 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.15)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'" onfocus="this.style.boxShadow='0 4px 16px rgba(106, 27, 154, 0.2)'; this.style.outline='none'" onblur="this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                            <span class="input-group-text" style="border: none; background: transparent; padding-left: 0; margin-left: -40px; z-index: 10;">
                                <i class="fas fa-search" style="color: #6a1b9a; font-size: 1.1rem;"></i>
                            </span>
                        </div>
                        <div class="d-flex gap-2 ms-auto flex-wrap justify-content-end mt-2 mt-md-0">
                            <div class="dropdown">
                                <button class="btn btn-outline-success dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                    <i class="fas fa-sort me-1"></i> Sort By
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="sortParcels('tracking', 'asc')"><i class="fas fa-sort-alpha-down me-2"></i>Tracking (A-Z)</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="sortParcels('tracking', 'desc')"><i class="fas fa-sort-alpha-up me-2"></i>Tracking (Z-A)</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="sortParcels('date', 'desc')"><i class="fas fa-calendar me-2"></i>Date (Newest First)</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="sortParcels('date', 'asc')"><i class="fas fa-calendar me-2"></i>Date (Oldest First)</a></li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-outline-primary" onclick="refreshParcels()"><i class="fas fa-sync-alt me-1"></i> Refresh</button>
                        </div>
                    </div>
                    <div id="parcelListNoResults" class="text-muted small mb-2 d-none">No parcels match your search</div>

                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="parcelsTable">
                                <thead class="table-header">
                                    <tr>
                                        <th class="px-4 py-3">Tracking Number</th>
                                        <th class="px-4 py-3">Receiver Matric</th>
                                        <th class="px-4 py-3">Location</th>
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
                                                        <small class="text-muted fw-semibold text-uppercase">Receiver Matric</small>
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
                                            <button type="button" class="btn-qr-staff btn-qr-staff-download" onclick="downloadQR()">
                                                <i class="fas fa-download me-2"></i>Download QR Image
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parcel History Tab -->
            <div class="tab-pane fade" id="parcel-history" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-1">Parcel History</h4>
                        <p class="text-muted mb-0">View all retrieved parcels and historical records.</p>
                    </div>
                </div>

                <!-- Parcel History Table -->
                <div class="parcel-table-container">
                    <!-- Top Controls Bar -->
                    <div class="table-controls-top">
                        <div class="entries-control">
                            <span class="control-label">Show</span>
                            <select class="entries-select" id="historyItemsPerPage" onchange="changeHistoryItemsPerPage()">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="control-label">entries</span>
                        </div>
                        <div id="historyInfo" class="entries-info">
                            <!-- Showing X to Y of Z entries -->
                        </div>
                    </div>

                    <!-- Controls Row: Search (left) + Sort/Refresh (right) -->
                    <div class="d-flex flex-wrap align-items-center mb-3 gap-3">
                        <div class="input-group" style="width: 100%; max-width: 450px;">
                            <input type="text" id="historySearchInput" class="form-control" placeholder="Find a parcel..." style="border: none; border-radius: 50px; background: white; font-size: 0.95rem; padding: 12px 20px 12px 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.15)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'" onfocus="this.style.boxShadow='0 4px 16px rgba(106, 27, 154, 0.2)'; this.style.outline='none'" onblur="this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                            <span class="input-group-text" style="border: none; background: transparent; padding-left: 0; margin-left: -40px; z-index: 10;">
                                <i class="fas fa-search" style="color: #6a1b9a; font-size: 1.1rem;"></i>
                            </span>
                        </div>
                        <div class="d-flex gap-2 ms-auto flex-wrap justify-content-end mt-2 mt-md-0">
                            <div class="dropdown">
                                <button class="btn btn-outline-success dropdown-toggle" type="button" id="historySort" data-bs-toggle="dropdown">
                                    <i class="fas fa-sort me-1"></i> Sort By
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="sortParcelHistory('tracking', 'asc')"><i class="fas fa-sort-alpha-down me-2"></i>Tracking (A-Z)</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="sortParcelHistory('tracking', 'desc')"><i class="fas fa-sort-alpha-up me-2"></i>Tracking (Z-A)</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="sortParcelHistory('date', 'desc')"><i class="fas fa-calendar me-2"></i>Date (Newest First)</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="sortParcelHistory('date', 'asc')"><i class="fas fa-calendar me-2"></i>Date (Oldest First)</a></li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-outline-primary" onclick="refreshParcelHistory()"><i class="fas fa-sync-alt me-1"></i> Refresh</button>
                            <button type="button" class="btn btn-outline-success" onclick="printParcelHistory()"><i class="fas fa-print me-1"></i> Print</button>
                        </div>
                    </div>
                    <div id="historyNoResults" class="text-muted small mb-2 d-none">No parcels match your search</div>

                    <!-- Table Container -->
                    <div class="table-wrapper">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="historyTable">
                                <thead class="table-header">
                                    <tr>
                                        <th class="px-4 py-3">Tracking Number</th>
                                        <th class="px-4 py-3">Receiver Matric</th>
                                        <th class="px-4 py-3">Receiver Name</th>

                                        <th class="px-4 py-3">Location</th>
                                        <th class="px-4 py-3">Date & Time</th>
                                        <th class="px-4 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="historyTableBody">
                                    <!-- Dynamic content will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bottom Pagination Bar -->
                    <div class="table-controls-bottom">
                        <div id="historyPaginationInfo" class="pagination-info">
                            <!-- Page info will be displayed here -->
                        </div>
                        <nav aria-label="History pagination" class="pagination-nav">
                            <ul class="pagination-controls" id="historyPaginationControls">
                                <!-- Pagination buttons will be generated here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>

                    <?php if ($is_admin): ?>
                    <!-- Admin Reports Tab -->
                    <div class="tab-pane fade" id="reports" role="tabpanel">
                        <!-- Report Header -->
                        <div class="report-header mb-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1"><i class="fas fa-chart-bar me-2"></i>Admin Reports</h4>
                                    <p class="text-muted mb-0">Generate and export retrieval records reports</p>
                                </div>
                                <div class="report-badge">
                                    <span class="badge bg-purple-gradient">
                                        <i class="fas fa-user-shield me-1"></i>Admin Access
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Date Filters -->
                        <div class="quick-filters mb-4">
                            <label class="form-label fw-semibold"><i class="fas fa-clock me-2"></i>Quick Filters:</label>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-purple btn-sm" onclick="setQuickFilter('today')">
                                    <i class="fas fa-calendar-day me-1"></i>Today
                                </button>
                                <button type="button" class="btn btn-outline-purple btn-sm" onclick="setQuickFilter('week')">
                                    <i class="fas fa-calendar-week me-1"></i>This Week
                                </button>
                                <button type="button" class="btn btn-outline-purple btn-sm" onclick="setQuickFilter('month')">
                                    <i class="fas fa-calendar-alt me-1"></i>This Month
                                </button>
                                <button type="button" class="btn btn-outline-purple btn-sm" onclick="setQuickFilter('year')">
                                    <i class="fas fa-calendar me-1"></i>This Year
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickFilter('all')">
                                    <i class="fas fa-infinity me-1"></i>All Time
                                </button>
                            </div>
                        </div>

                        <!-- Filter Card -->
                        <div class="card report-filter-card mb-4">
                            <div class="card-header bg-purple-light">
                                <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Advanced Filters</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label for="reportStartDate" class="form-label">Start Date</label>
                                        <input type="date" class="form-control" id="reportStartDate">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="reportEndDate" class="form-label">End Date</label>
                                        <input type="date" class="form-control" id="reportEndDate">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="reportStatus" class="form-label">Status</label>
                                        <select class="form-select" id="reportStatus">
                                            <option value="">All Status</option>
                                            <option value="Retrieved">Retrieved</option>
                                            <option value="Pending">Pending</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="reportReceiverIC" class="form-label">Receiver Matric</label>
                                        <input type="text" class="form-control" id="reportReceiverIC" placeholder="Optional">
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex gap-2 mt-4 flex-wrap">
                                    <button type="button" class="btn btn-purple" onclick="generateReport()">
                                        <i class="fas fa-search me-2"></i>Generate Report
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="exportToExcel()" id="btnExportExcel" disabled>
                                        <i class="fas fa-file-excel me-2"></i>Export to Excel
                                    </button>
                                    <button type="button" class="btn btn-orange" onclick="exportToCSV()" id="btnExportCSV" disabled>
                                        <i class="fas fa-file-csv me-2"></i>Export to CSV
                                    </button>
                                    <button type="button" class="btn btn-info" onclick="printReport()" id="btnPrintReport" disabled>
                                        <i class="fas fa-print me-2"></i>Print Report
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="clearFilters()">
                                        <i class="fas fa-times me-2"></i>Clear Filters
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Report Results -->
                        <div id="reportResults" class="card" style="display: none;">
                            <div class="card-header bg-purple-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><i class="fas fa-table me-2"></i>Report Results</h6>
                                <span class="badge bg-purple" id="resultCount">0 records</span>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0" id="reportTable">
                                        <thead class="table-purple">
                                            <tr>
                                                <th class="text-center" style="width: 50px;">#</th>
                                                <th class="text-center" style="width: 100px;">Retrieval ID</th>
                                                <th>Tracking Number</th>
                                                <th>Receiver</th>
                                                <th>Matric Number</th>
                                                <th><i class="fas fa-sort-down me-1"></i>Timestamp</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reportTableBody">
                                            <!-- Report data will be inserted here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted" id="reportMeta">
                                        Generated on: <span id="reportGeneratedDate">-</span> |
                                        Generated by: <span id="reportGeneratedBy">-</span>
                                    </small>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-purple" onclick="printReport()">
                                            <i class="fas fa-print me-1"></i>Print
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="exportToExcel()">
                                            <i class="fas fa-file-excel me-1"></i>Excel
                                        </button>
                                    </div>
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

                                <!-- Duplicate cards for infinite loop -->
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
                            </div>
                        </div>

                        <!-- Navigation Arrows -->
                        <button type="button" class="carousel-nav carousel-nav-prev" onclick="moveCarousel('prev')" aria-label="Previous partners">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" class="carousel-nav carousel-nav-next" onclick="moveCarousel('next')" aria-label="Next partners">
                            <i class="fas fa-chevron-right"></i>
                        </button>

                        <!-- Elegant Indicators -->
                        <div class="carousel-indicators">
                            <button type="button" class="carousel-indicator active" onclick="goToSlide(0)" aria-label="Go to slide 1"></button>
                            <button type="button" class="carousel-indicator" onclick="goToSlide(1)" aria-label="Go to slide 2"></button>
                            <button type="button" class="carousel-indicator" onclick="goToSlide(2)" aria-label="Go to slide 3"></button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Copyright Notice -->
            <div class="row mt-4 pt-4" style="border-top: 1px solid rgba(107, 114, 128, 0.1);">
                <div class="col-12 text-center">
                    <p class="text-muted small mb-0">&copy; 2026 Perwira Parcel Management System. All rights reserved.</p>
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
                                        <i class="fas fa-id-card me-2 text-primary"></i>Receiver Matric Number
                                    </label>
                                    <input type="text" class="form-control" id="editReceiverIC" maxlength="8" pattern="[A-Z]{2}[0-9]{6}" title="Matric Number must be 2 letters + 6 digits (e.g., CI230010)" placeholder="e.g., CI230010" required>
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
                                <label class="form-label fw-bold">Receiver Matric:</label>
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
                loadParcelHistory(); // Also refresh parcel history
            }, 30000); // 30 seconds
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            if (isAdmin) {
                loadDashboardStats();
            }
            loadParcels();
            loadParcelHistory(); // Load parcel history
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

            fetch('../php/admin-get-stats.php', {
                credentials: 'include'
            })
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
            fetch('../php/staff-get-parcels.php', {
                credentials: 'include'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allParcels = data.parcels;
                        currentPage = 1; // Reset to first page when loading new data
                        // Show only Pending in Parcel List tab by default
                        displayParcels(allParcels.filter(p => (p.status || '').toLowerCase() === 'pending'));
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
                row.dataset.tracking = parcel.TrackingNumber || '';
                row.dataset.matric = parcel.MatricNumber || '';
                row.dataset.receiverName = parcel.receiverName || '';
                row.dataset.location = (parcel.deliveryLocation || '').toString();
                const statusBadge = parcel.status === 'Pending'
                    ? '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>'
                    : '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Retrieved</span>';

                // Format date and time
                const formattedDate = parcel.date ? new Date(parcel.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                const formattedTime = parcel.time ? new Date('1970-01-01T' + parcel.time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : '';
                const timeDisplay = parcel.time ? '<br><small class="text-muted">' + formattedTime + '</small>' : '';

                // Shorten delivery location to first line only
                const locationLines = (parcel.deliveryLocation || 'N/A').split(',');
                const shortLocation = locationLines.length > 0 ? locationLines[locationLines.length - 1].trim() : 'N/A';

                // Delete button (available for both Staff and Admin)
                const deleteButton = '<button type="button" class="btn btn-outline-danger" onclick="deleteParcel(\'' + parcel.TrackingNumber + '\')" title="Delete">' +
                                        '<i class="fas fa-trash"></i>' +
                                      '</button>';

                row.innerHTML =
                    '<td class="px-4 py-3">' +
                        '<span class="fw-bold text-primary">' + parcel.TrackingNumber + '</span>' +
                    '</td>' +
                    '<td class="px-4 py-3">' + formatICNumber(parcel.MatricNumber) + '</td>' +
                    '<td class="px-4 py-3">' +
                        '<i class="fas fa-map-marker-alt me-1 text-muted"></i>' +
                        '<small>' + shortLocation + '</small>' +
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
                            '<button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #6a1b9a, #8e24aa); color: white; border: none;" onclick="viewParcel(\'' + parcel.TrackingNumber + '\')" title="View Details">' +
                                '<i class="fas fa-eye"></i>' +
                            '</button>' +
                            '<button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #ff6b35, #ff8c42); color: white; border: none;" onclick="editParcel(\'' + parcel.TrackingNumber + '\')" title="Edit">' +
                                '<i class="fas fa-edit"></i>' +
                            '</button>' +
                            '<button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #17a2b8, #20c997); color: white; border: none;" onclick="generateParcelQR(\'' + parcel.TrackingNumber + '\')" title="Generate QR">' +
                                '<i class="fas fa-qrcode"></i>' +
                            '</button>' +
                            deleteButton +
                        '</div>' +
                    '</td>';
                tbody.appendChild(row);
            });

            // Re-apply client-side search if input has value
            try {
                const _si = document.getElementById('parcelListSearchInput');
                if (_si && _si.value.trim()) {
                    applyStaffListSearch();
                }
            } catch (e) { /* no-op */ }


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

        // --- Client-side Search (Staff) ---
        function normalizeStr(v){ return (v ?? '').toString().toLowerCase().trim(); }
        function matchesQuery(parcel, q){
            const query = normalizeStr(q);
            if (!query) return true;
            return [parcel.TrackingNumber, parcel.MatricNumber, parcel.receiverName, parcel.deliveryLocation, parcel.name]
                .some(val => normalizeStr(val).includes(query));
        }

        // Debounce timer for search
        let searchDebounceTimer = null;
        function basePendingParcels() { return (allParcels || []).filter(p => normalizeStr(p.status) === 'pending'); }
        function baseRetrievedHistory() { return (allParcelHistory || []); }

        function applyStaffListSearch(){
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                const input = document.getElementById('parcelListSearchInput');
                const noResults = document.getElementById('parcelListNoResults');
                if (!input) return;
                const q = input.value || '';
                const base = basePendingParcels();
                const result = q.trim() ? base.filter(p => matchesQuery(p, q)) : base;
                currentPage = 1;
                displayParcels(result);
                if (noResults) noResults.classList.toggle('d-none', result.length !== 0);
            }, 300);
        }

        function applyStaffHistorySearch(){
            clearTimeout(searchDebounceTimer);
            searchDebounceTimer = setTimeout(() => {
                const input = document.getElementById('historySearchInput');
                const noResults = document.getElementById('historyNoResults');
                if (!input) return;
                const q = input.value || '';
                const base = baseRetrievedHistory();
                const result = q.trim() ? base.filter(p => matchesQuery(p, q)) : base;
                currentHistoryPage = 1;
                displayParcelHistory(result);
                if (noResults) noResults.classList.toggle('d-none', result.length !== 0);
            }, 300);
        }

        document.addEventListener('DOMContentLoaded', function(){
            const listInput = document.getElementById('parcelListSearchInput');
            if (listInput){
                listInput.addEventListener('input', applyStaffListSearch);
            }
            const histInput = document.getElementById('historySearchInput');
            if (histInput){
                histInput.addEventListener('input', applyStaffHistorySearch);
            }

            // Reset/clear search when switching tabs and enforce base status on each tab
            const staffTabs = document.getElementById('staffTabs');
            if (staffTabs){
                staffTabs.addEventListener('shown.bs.tab', function(ev){
                    const target = ev.target && ev.target.getAttribute('data-bs-target');
                    if (target === '#parcel-list'){
                        const other = document.getElementById('historySearchInput');
                        if (other){ other.value=''; }
                        const noRes = document.getElementById('historyNoResults');
                        if (noRes) noRes.classList.add('d-none');
                        displayParcels(basePendingParcels());
                    } else if (target === '#parcel-history'){
                        const other = document.getElementById('parcelListSearchInput');
                        if (other){ other.value=''; }
                        const noRes = document.getElementById('parcelListNoResults');
                        if (noRes) noRes.classList.add('d-none');
                        displayParcelHistory(baseRetrievedHistory());
                    }
                });
            }
        });


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
                body: formData,
                credentials: 'include'
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
        async function viewParcel(trackingNumber) {
            console.log('viewParcel called with:', trackingNumber);

            const findLocalParcel = () => {
                return (allParcels || []).find(p => p.TrackingNumber === trackingNumber)
                    || (filteredParcelHistory || []).find(p => p.TrackingNumber === trackingNumber)
                    || (allParcelHistory || []).find(p => p.TrackingNumber === trackingNumber);
            };

            let parcel = findLocalParcel();

            // If parcel isn't in the active list (common for history), or details are incomplete,
            // fetch a full record from the server.
            const needsFetch = !parcel
                || parcel.deliveryLocation === undefined
                || parcel.weight === undefined
                || parcel.status === undefined
                || parcel.qrGenerated === undefined;

            if (needsFetch) {
                try {
                    const response = await fetch(`../php/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(trackingNumber)}`);
                    const data = await response.json();
                    if (data && data.success && data.parcel) {
                        parcel = data.parcel;
                    }
                } catch (e) {
                    console.warn('Failed to fetch parcel details:', e);
                }
            }

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
            document.getElementById('viewReceiverIC').textContent = formatICNumber(parcel.MatricNumber);
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

            // Check if QR has been generated by staff
            const qrContainer = document.getElementById('viewQRCode');

            if (!parcel.qrGenerated) {
                qrContainer.innerHTML = '<div class="text-center text-warning p-3"><i class="fas fa-hourglass-half fa-2x mb-2"></i><br><strong>QR Code Not Yet Generated</strong><br><small>Click "Generate QR" button to create the verification QR code</small></div>';
                console.log('⏳ QR code not yet generated for parcel:', trackingNumber);
            } else {
                // Generate scannable QR code for parcel view
                qrContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading QR Code...</div>';

                try {
                    // Build QR payload in scannable format
                    const qrPayload = "PPMS|" +
                                     trackingNumber + "|" +
                                     parcel.MatricNumber + "|" +
                                     (parcel.receiverName || 'N/A') + "|" +
                                     (parcel.deliveryLocation || 'N/A') + "|" +
                                     (parcel.status || 'Pending');

                    console.log('QR Payload:', qrPayload);

                    // Generate QR code URL using api.qrserver.com
                    const encodedPayload = encodeURIComponent(qrPayload);
                    const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodedPayload}`;

                    console.log('QR Code URL:', qrCodeUrl);

                    // Display the QR code image
                    qrContainer.innerHTML = `<img src="${qrCodeUrl}" alt="QR Code" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">`;

                    console.log('✅ Scannable QR code generated successfully!');

                } catch (error) {
                    console.error('QR Generation Exception:', error);
                    qrContainer.innerHTML = '<div class="text-danger text-center p-3"><i class="fas fa-times-circle fa-2x mb-2"></i><br>QR Generation Failed<br><small>Please try again</small></div>';
                }
            }

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
            document.getElementById('editReceiverIC').value = parcel.MatricNumber;
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
                body: formData,
                credentials: 'include'
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
                        body: formData,
                        credentials: 'include'
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

        // ===== PARCEL HISTORY FUNCTIONS =====
        let allParcelHistory = [];
        let filteredParcelHistory = [];
        let currentHistoryPage = 1;
        let historyItemsPerPage = 10;

        // Load parcel history (retrieved parcels only)
        function loadParcelHistory() {
            fetch('../php/staff-get-parcel-history.php', {
                credentials: 'include'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allParcelHistory = data.parcels;
                        currentHistoryPage = 1;
                        // Show all statuses in Parcel History tab by default
                        displayParcelHistory(allParcelHistory);
                    } else {
                        console.error('Error loading parcel history:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error loading parcel history:', error);
                });
        }

        // Display parcel history in table with pagination
        function displayParcelHistory(parcels) {
            filteredParcelHistory = parcels;
            const tbody = document.getElementById('historyTableBody');
            tbody.innerHTML = '';

            if (parcels.length === 0) {
                tbody.innerHTML =
                    '<tr>' +
                        '<td colspan="6" class="text-center text-muted py-5">' +
                            '<i class="fas fa-history fa-3x mb-3" style="opacity: 0.3;"></i><br>' +
                            '<h5 class="text-muted">No History Found</h5>' +
                            '<p class="text-muted">Retrieved parcels will appear here.</p>' +
                        '</td>' +
                    '</tr>';
                updateHistoryPaginationInfo(0, 0, 0);
                updateHistoryPaginationControls(0);
                return;
            }

            // Calculate pagination
            const totalItems = parcels.length;
            const totalPages = Math.ceil(totalItems / historyItemsPerPage);
            const startIndex = (currentHistoryPage - 1) * historyItemsPerPage;
            const endIndex = Math.min(startIndex + historyItemsPerPage, totalItems);
            const paginatedParcels = parcels.slice(startIndex, endIndex);

            // Display parcels for current page
            paginatedParcels.forEach(parcel => {
                const row = document.createElement('tr');
                row.dataset.tracking = parcel.TrackingNumber || '';
                row.dataset.matric = parcel.MatricNumber || '';
                row.dataset.receiverName = parcel.receiverName || '';
                row.dataset.location = (parcel.deliveryLocation || '').toString();

                // Format date and time
                const formattedDate = parcel.date ? new Date(parcel.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'N/A';
                const formattedTime = parcel.time ? new Date('1970-01-01T' + parcel.time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true }) : '';
                const timeDisplay = parcel.time ? '<br><small class="text-muted">' + formattedTime + '</small>' : '';

                // Shorten delivery location to first line only
                const locationLines = (parcel.deliveryLocation || 'N/A').split(',');
                const shortLocation = locationLines.length > 0 ? locationLines[locationLines.length - 1].trim() : 'N/A';

                row.innerHTML =
                    '<td class="px-4 py-3">' +
                        '<span class="fw-bold text-primary">' + parcel.TrackingNumber + '</span>' +
                    '</td>' +
                    '<td class="px-4 py-3">' + formatICNumber(parcel.MatricNumber) + '</td>' +
                    '<td class="px-4 py-3">' + (parcel.receiverName || 'N/A') + '</td>' +
                    '<td class="px-4 py-3">' +
                        '<i class="fas fa-map-marker-alt me-1 text-muted"></i>' +
                        '<small>' + shortLocation + '</small>' +
                    '</td>' +
                    '<td class="px-4 py-3">' +
                        '<div>' +
                            formattedDate +
                            timeDisplay +
                        '</div>' +
                    '</td>' +
                    '<td class="px-4 py-3">' +
                        '<div class="d-flex gap-2 flex-wrap">' +
                            '<button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #6a1b9a, #8e24aa); color: white; border: none;" onclick="viewParcel(\'' + parcel.TrackingNumber + '\')" title="View Details">' +
                                '<i class="fas fa-eye"></i>' +
                            '</button>' +
                            '<button type="button" class="btn btn-sm" style="background: linear-gradient(135deg, #00695c, #009688); color: white; border: none;" onclick="downloadHistoryQRStaff(\'' + parcel.TrackingNumber + '\')" title="Download QR">' +
                                '<i class="fas fa-download"></i>' +
                            '</button>' +
                        '</div>' +
                    '</td>';
                tbody.appendChild(row);
            });

            // Re-apply client-side search if input has value
            try {
                const _hi = document.getElementById('historySearchInput');
                if (_hi && _hi.value.trim()) {
                    applyStaffHistorySearch();
                }
            } catch (e) { /* no-op */ }

            // Update pagination info and controls
            updateHistoryPaginationInfo(startIndex + 1, endIndex, totalItems);
            updateHistoryPaginationControls(totalPages);
        }

        // Update history pagination info
        function updateHistoryPaginationInfo(start, end, total) {
            const infoElement = document.getElementById('historyInfo');
            if (total === 0) {
                infoElement.textContent = 'Showing 0 entries';
            } else {
                infoElement.textContent = `Showing ${start} to ${end} of ${total} entries`;
            }
        }

        // Update history pagination controls
        function updateHistoryPaginationControls(totalPages) {
            const controlsContainer = document.getElementById('historyPaginationControls');
            controlsContainer.innerHTML = '';

            if (totalPages <= 1) return;

            // Previous button
            const prevBtn = document.createElement('li');
            prevBtn.className = 'page-item ' + (currentHistoryPage === 1 ? 'disabled' : '');
            prevBtn.innerHTML = '<a class="page-link" href="#" onclick="previousHistoryPage(event)">Previous</a>';
            controlsContainer.appendChild(prevBtn);

            // Page numbers
            for (let i = 1; i <= totalPages; i++) {
                const pageBtn = document.createElement('li');
                pageBtn.className = 'page-item ' + (i === currentHistoryPage ? 'active' : '');
                pageBtn.innerHTML = '<a class="page-link" href="#" onclick="goToHistoryPage(' + i + ', event)">' + i + '</a>';
                controlsContainer.appendChild(pageBtn);
            }

            // Next button
            const nextBtn = document.createElement('li');
            nextBtn.className = 'page-item ' + (currentHistoryPage === totalPages ? 'disabled' : '');
            nextBtn.innerHTML = '<a class="page-link" href="#" onclick="nextHistoryPage(event)">Next</a>';
            controlsContainer.appendChild(nextBtn);
        }

        // Pagination functions for history
        function previousHistoryPage(event) {
            event.preventDefault();
            if (currentHistoryPage > 1) {
                currentHistoryPage--;
                displayParcelHistory(filteredParcelHistory);
            }
        }

        function nextHistoryPage(event) {
            event.preventDefault();
            const totalPages = Math.ceil(filteredParcelHistory.length / historyItemsPerPage);
            if (currentHistoryPage < totalPages) {
                currentHistoryPage++;
                displayParcelHistory(filteredParcelHistory);
            }
        }

        function goToHistoryPage(page, event) {
            event.preventDefault();
            currentHistoryPage = page;
            displayParcelHistory(filteredParcelHistory);
        }

        // Change items per page for history
        function changeHistoryItemsPerPage() {
            historyItemsPerPage = parseInt(document.getElementById('historyItemsPerPage').value);
            currentHistoryPage = 1;
            displayParcelHistory(filteredParcelHistory);
        }

        // Refresh parcel history
        function refreshParcelHistory() {
            loadParcelHistory();
            Swal.fire({
                icon: 'success',
                title: 'Refreshed!',
                text: 'Parcel history has been refreshed.',
                timer: 1500,
                showConfirmButton: false
            });
        }

        // Sort parcel history
        function sortParcelHistory(field, order) {
            // Store current scroll position
            const tableContainer = document.querySelector('.table-responsive');
            const scrollTop = tableContainer ? tableContainer.scrollTop : 0;

            if (field === 'tracking') {
                filteredParcelHistory.sort((a, b) => {
                    const aVal = a.TrackingNumber.toLowerCase();
                    const bVal = b.TrackingNumber.toLowerCase();
                    return order === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
                });
            } else if (field === 'date') {
                filteredParcelHistory.sort((a, b) => {
                    const aDate = new Date(a.date + ' ' + a.time);
                    const bDate = new Date(b.date + ' ' + b.time);
                    return order === 'asc' ? aDate - bDate : bDate - aDate;
                });
            }
            currentHistoryPage = 1;
            displayParcelHistory(filteredParcelHistory);

            // Restore scroll position after a brief delay
            setTimeout(() => {
                if (tableContainer) {
                    tableContainer.scrollTop = scrollTop;
                }
            }, 100);
        }

        // Print Parcel History function
        function printParcelHistory() {
            const printWindow = window.open('', '_blank');
            const historyData = filteredParcelHistory;
            
            let tableRows = '';
            historyData.forEach((parcel, index) => {
                tableRows += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${parcel.TrackingNumber}</td>
                        <td>${parcel.MatricNumber}</td>
                        <td>${parcel.receiverName || 'N/A'}</td>
                        <td>${parcel.location}</td>
                        <td>${parcel.date} ${parcel.time}</td>
                    </tr>
                `;
            });
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Parcel History Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .header h1 { color: #6a1b9a; margin: 0; }
                        .header p { margin: 5px 0; color: #666; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                        th { background-color: #6a1b9a; color: white; font-weight: 600; }
                        tr:nth-child(even) { background-color: #f9f9f9; }
                        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
                        @media print {
                            body { margin: 0; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Perwira Parcel Management System</h1>
                        <p>Parcel History Report</p>
                        <p>Generated: ${new Date().toLocaleString()}</p>
                        <p>Total Records: ${historyData.length}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tracking Number</th>
                                <th>Receiver Matric</th>
                                <th>Receiver Name</th>
                                <th>Location</th>
                                <th>Date & Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                        </tbody>
                    </table>
                    <div class="footer">
                        <p>&copy; 2026 Perwira Parcel Management System. All rights reserved.</p>
                    </div>
                    <script>
                        window.onload = function() {
                            window.print();
                        }
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
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
                                    parcel.MatricNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
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
                                <div class="text-muted small">Matric: ${formatICNumber(parcel.MatricNumber)}</div>
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
            document.getElementById('parcelIC').textContent = formatICNumber(parcel.MatricNumber);
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
                document.getElementById('parcelIC').textContent = formatICNumber(parcel.MatricNumber);
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

            // Create verification data object (matching backend format)
            const verificationData = {
                tracking: trackingNumber,
                matric: parcel.MatricNumber,
                timestamp: new Date().toISOString(),
                location: parcel.deliveryLocation,
                signature: 'pending',  // Will be generated by backend with HMAC-SHA256
                version: '1.0'
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

                // Generate scannable QR code using API
                try {
                    console.log('Generating scannable QR code for:', trackingNumber);

                    // Build QR payload in scannable format
                    const qrPayload = "PPMS|" +
                                     trackingNumber + "|" +
                                     parcel.MatricNumber + "|" +
                                     (parcel.receiverName || 'N/A') + "|" +
                                     (parcel.deliveryLocation || 'N/A') + "|" +
                                     (parcel.status || 'Pending');

                    console.log('QR Payload:', qrPayload);

                    // Generate QR code URL using api.qrserver.com
                    const encodedPayload = encodeURIComponent(qrPayload);
                    const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodedPayload}`;

                    console.log('QR Code URL:', qrCodeUrl);

                    // Display the QR code image
                    qrContainer.innerHTML = `<img src="${qrCodeUrl}" alt="QR Code" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">`;

                    document.getElementById('qrCodeContainer').style.display = 'block';
                    document.getElementById('qrCodeInfo').style.display = 'block';

                    console.log('✅ Scannable QR code generated successfully!');

                    // Save QR code to database
                    saveQRCodeToDatabase(trackingNumber, qrCodeUrl, verificationData);

                    Swal.fire({
                        icon: 'success',
                        title: 'QR Generated!',
                        text: 'Scannable QR code generated successfully. You can now scan it with your phone camera!',
                        timer: 2000,
                        showConfirmButton: false
                    });

                } catch (error) {
                    console.error('QR Generation Exception:', error);
                    qrContainer.innerHTML = '<div class="text-danger text-center p-3"><i class="fas fa-times-circle fa-2x mb-2"></i><br>QR Generation Failed<br><small>Please try again</small></div>';

                    Swal.fire({
                        icon: 'error',
                        title: 'Generation Failed!',
                        text: 'Failed to generate QR code: ' + error.message,
                        confirmButtonColor: '#6A1B9A'
                    });
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

        // Save QR code to database
        function saveQRCodeToDatabase(trackingNumber, qrCodeUrl, verificationData) {
            console.log('Saving QR code to database for:', trackingNumber);

            // Convert image URL to canvas and then to base64
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function() {
                const canvas = document.createElement('canvas');
                canvas.width = img.width;
                canvas.height = img.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);

                const qrBase64 = canvas.toDataURL('image/png');

                // Keep a local copy for immediate download (even before server save finishes)
                window.lastGeneratedQrBase64 = qrBase64;
                window.lastGeneratedQrTracking = trackingNumber;

                // Send to backend to save
                const formData = new FormData();
                formData.append('trackingNumber', trackingNumber);
                formData.append('qrCode', qrBase64);
                formData.append('verificationData', JSON.stringify(verificationData));

                fetch('../php/admin-save-qr.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('✅ QR code saved to database successfully');
                        // Update the parcel data to reflect QR generation
                        const parcel = allParcels.find(p => p.TrackingNumber === trackingNumber);
                        if (parcel) {
                            parcel.qrGenerated = true;
                            parcel.QR = data.qrPath;
                        }
                        // Refresh parcel list to ensure UI is updated
                        loadParcels();
                    } else {
                        console.error('Failed to save QR code:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error saving QR code:', error);
                });
            };
            img.onerror = function() {
                console.error('Failed to load QR image for saving');
            };
            img.src = qrCodeUrl;
        }

        // Download QR code (direct download, no popup page)
        function downloadQR() {
            const trackingNumber = window.selectedTrackingNumber;

            // Check for both canvas and image elements
            const canvas = document.querySelector('#qrCodeDisplay canvas');
            const img = document.querySelector('#qrCodeDisplay img');

            if (!trackingNumber) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Parcel Selected',
                    text: 'Please search and select a parcel first.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            if (!canvas && !img) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No QR Code',
                    text: 'Please generate a QR code first.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            // Prefer downloading the locally-rendered QR (most reliable across browsers)
            if (window.lastGeneratedQrBase64 && window.lastGeneratedQrTracking === trackingNumber) {
                const link = document.createElement('a');
                link.href = window.lastGeneratedQrBase64;
                link.download = `PPMS_Verification_${trackingNumber}.png`;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                return;
            }

            // Try to convert the displayed image to base64 for a reliable download
            if (img && img.src) {
                const tempImg = new Image();
                tempImg.crossOrigin = 'anonymous';
                tempImg.onload = function() {
                    try {
                        const tmpCanvas = document.createElement('canvas');
                        tmpCanvas.width = tempImg.width;
                        tmpCanvas.height = tempImg.height;
                        const ctx = tmpCanvas.getContext('2d');
                        ctx.drawImage(tempImg, 0, 0);
                        const base64 = tmpCanvas.toDataURL('image/png');

                        window.lastGeneratedQrBase64 = base64;
                        window.lastGeneratedQrTracking = trackingNumber;

                        const link = document.createElement('a');
                        link.href = base64;
                        link.download = `PPMS_Verification_${trackingNumber}.png`;
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } catch (e) {
                        console.warn('QR base64 conversion failed, falling back to server download:', e);
                        downloadQrFile(trackingNumber);
                    }
                };
                tempImg.onerror = function() {
                    downloadQrFile(trackingNumber);
                };
                tempImg.src = img.src;
                return;
            }

            // Fallback: download the server-saved QR file
            downloadQrFile(trackingNumber);
        }

        function downloadQrFile(trackingNumber) {
            const url = `../php/download-qr.php?trackingNumber=${encodeURIComponent(trackingNumber)}`;
            const link = document.createElement('a');
            link.href = url;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
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
                    '<style>'+
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
                        '<tr><td>Receiver Matric:</td><td>' + formatICNumber(parcel.MatricNumber) + '</td></tr>' +
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

        // Enlarge QR code in modal - Sleek Modal
        function enlargeQR() {
            let canvas = document.querySelector('#qrCodeDisplay canvas');
            let img = document.querySelector('#qrCodeDisplay img');

            if (!canvas && !img) {
                console.warn('QR code not found, waiting for it to load...');
                // Wait a moment for QR to load
                setTimeout(() => {
                    canvas = document.querySelector('#qrCodeDisplay canvas');
                    img = document.querySelector('#qrCodeDisplay img');
                    if (!canvas && !img) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'No QR Code',
                            text: 'Please generate a QR code first.',
                            confirmButtonColor: '#6A1B9A'
                        });
                        return;
                    }
                    enlargeQRWithData(canvas, img);
                }, 500);
                return;
            }

            enlargeQRWithData(canvas, img);
        }

        function enlargeQRWithData(canvas, img) {

            let qrImageData;
            if (canvas) {
                qrImageData = canvas.toDataURL();
            } else if (img) {
                qrImageData = img.src;
                // Upgrade to higher resolution for better quality
                if (qrImageData.includes('api.qrserver.com')) {
                    qrImageData = qrImageData.replace(/size=\d+x\d+/, 'size=600x600');
                }
            }

            // Create sleek modal with enlarged QR code
            Swal.fire({
                html: `
                    <div style="text-align: center;">
                        <img src="${qrImageData}"
                             style="max-width: 90%; max-height: 80vh; height: auto; border: 3px solid white; border-radius: 8px; box-shadow: 0 8px 32px rgba(0,0,0,0.3);"
                             alt="Enlarged QR Code">
                        <p style="color: white; margin-top: 20px; font-size: 14px;">Click outside or press ESC to close</p>
                    </div>
                `,
                background: 'transparent',
                showConfirmButton: false,
                allowOutsideClick: true,
                allowEscapeKey: true,
                didOpen: (modal) => {
                    // Create dark overlay with backdrop blur
                    const backdrop = document.querySelector('.swal2-container');
                    if (backdrop) {
                        backdrop.style.background = 'rgba(0, 0, 0, 0.85)';
                        backdrop.style.backdropFilter = 'blur(10px)';
                    }

                    // Create close button with rotation animation
                    const closeBtn = document.createElement('button');
                    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                    closeBtn.style.cssText = `
                        position: fixed;
                        top: 30px;
                        right: 30px;
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        background: white;
                        border: none;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 24px;
                        color: #1f2937;
                        z-index: 10000;
                        transition: all 0.3s ease;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    `;
                    closeBtn.onmouseover = () => {
                        closeBtn.style.transform = 'scale(1.1) rotate(90deg)';
                        closeBtn.style.background = '#f0f0f0';
                    };
                    closeBtn.onmouseout = () => {
                        closeBtn.style.transform = 'scale(1) rotate(0deg)';
                        closeBtn.style.background = 'white';
                    };
                    closeBtn.onclick = () => Swal.close();
                    document.body.appendChild(closeBtn);
                },
                willClose: () => {
                    const closeBtn = document.querySelector('button[style*="position: fixed"]');
                    if (closeBtn) closeBtn.remove();
                }
            });
        }

        // Download QR code from parcel history (Staff)
        function downloadHistoryQRStaff(trackingNumber) {
            console.log('Downloading QR for tracking:', trackingNumber);

            fetch(`../php/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(trackingNumber)}`, {
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (!data || !data.success || !data.parcel) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not fetch parcel data. Please try again.',
                        confirmButtonColor: '#6A1B9A'
                    });
                    return;
                }

                if (!data.parcel.qrGenerated) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'QR Not Generated',
                        text: 'This parcel does not have a QR code yet. Generate it first.',
                        confirmButtonColor: '#6A1B9A'
                    });
                    return;
                }

                if (!data.parcel.qrExists) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'QR File Missing',
                        text: 'QR code record exists, but the image file is missing. Please regenerate the QR code.',
                        confirmButtonColor: '#6A1B9A'
                    });
                    return;
                }

                downloadQrFile(trackingNumber);
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to download QR code.',
                    confirmButtonColor: '#6A1B9A'
                });
            });
        }

        // Enlarge QR code from parcel history (Staff)
        function enlargeHistoryQRStaff(trackingNumber) {
            console.log('Enlarging QR for tracking:', trackingNumber);

            // Fetch parcel data with QR
            fetch(`../php/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(trackingNumber)}`, {
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.success && data.parcel) {
                    const parcel = data.parcel;

                    // Build QR payload
                    const qrPayload = "PPMS|" +
                                     trackingNumber + "|" +
                                     parcel.MatricNumber + "|" +
                                     (parcel.receiverName || 'N/A') + "|" +
                                     (parcel.deliveryLocation || 'N/A') + "|" +
                                     (parcel.status || 'Pending');

                    // Generate QR code URL with higher resolution
                    const encodedPayload = encodeURIComponent(qrPayload);
                    const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=600x600&data=${encodedPayload}`;

                    // Show enlarged QR in modal
                    Swal.fire({
                        html: `
                            <div style="text-align: center;">
                                <img src="${qrCodeUrl}"
                                     style="max-width: 90%; max-height: 80vh; height: auto; border: 3px solid white; border-radius: 8px; box-shadow: 0 8px 32px rgba(0,0,0,0.3);"
                                     alt="Enlarged QR Code">
                                <p style="color: white; margin-top: 20px; font-size: 14px;">Click outside or press ESC to close</p>
                            </div>
                        `,
                        background: 'transparent',
                        showConfirmButton: false,
                        allowOutsideClick: true,
                        allowEscapeKey: true,
                        customClass: {
                            popup: 'enlarged-qr-popup'
                        },
                        didOpen: (modal) => {
                            const backdrop = document.querySelector('.swal2-container');
                            if (backdrop) {
                                backdrop.style.background = 'rgba(0, 0, 0, 0.9)';
                                backdrop.style.backdropFilter = 'blur(5px)';
                            }
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Could not fetch parcel data. Please try again.',
                        confirmButtonColor: '#6A1B9A'
                    });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to enlarge QR code.',
                    confirmButtonColor: '#6A1B9A'
                });
            });
        }

        // ========== ADMIN REPORT MODULE ==========

        // Store current report data globally for export
        let currentReportData = [];

        // Set quick date filter
        function setQuickFilter(period) {
            const today = new Date();
            let startDate, endDate;

            switch(period) {
                case 'today':
                    startDate = endDate = formatDateForInput(today);
                    break;
                case 'week':
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay()); // Start of week (Sunday)
                    startDate = formatDateForInput(weekStart);
                    endDate = formatDateForInput(today);
                    break;
                case 'month':
                    startDate = formatDateForInput(new Date(today.getFullYear(), today.getMonth(), 1));
                    endDate = formatDateForInput(today);
                    break;
                case 'year':
                    startDate = formatDateForInput(new Date(today.getFullYear(), 0, 1));
                    endDate = formatDateForInput(today);
                    break;
                case 'all':
                    startDate = '';
                    endDate = '';
                    break;
            }

            document.getElementById('reportStartDate').value = startDate;
            document.getElementById('reportEndDate').value = endDate;

            // Visual feedback
            document.querySelectorAll('.quick-filters .btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        // Format date for input field (YYYY-MM-DD)
        function formatDateForInput(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Clear all filters
        function clearFilters() {
            document.getElementById('reportStartDate').value = '';
            document.getElementById('reportEndDate').value = '';
            document.getElementById('reportStatus').value = '';
            document.getElementById('reportReceiverIC').value = '';
            document.querySelectorAll('.quick-filters .btn').forEach(btn => btn.classList.remove('active'));
        }

        // Generate report (Admin only)
        function generateReport() {
            if (!isAdmin) {
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: 'Only administrators can generate reports.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            const startDate = document.getElementById('reportStartDate').value;
            const endDate = document.getElementById('reportEndDate').value;
            const status = document.getElementById('reportStatus').value;
            const receiverIC = document.getElementById('reportReceiverIC').value;

            // Show loading
            Swal.fire({
                title: 'Generating Report...',
                html: '<i class="fas fa-spinner fa-spin fa-2x"></i>',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            const formData = new FormData();
            formData.append('startDate', startDate);
            formData.append('endDate', endDate);
            formData.append('status', status);
            formData.append('receiverIC', receiverIC);

            fetch('../php/admin-generate-report.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    currentReportData = data.report;
                    displayReportResults(data.report);
                    enableExportButtons(true);

                    // Success toast
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Report generated: ${data.report.length} records found`,
                        showConfirmButton: false,
                        timer: 3000,
                        background: '#fff',
                        showClass: { popup: 'swal2-show' },
                        hideClass: { popup: 'swal2-hide' }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to generate report.',
                        confirmButtonColor: '#6A1B9A'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while generating the report.',
                    confirmButtonColor: '#6A1B9A'
                });
            });
        }

        // Enable/disable export buttons
        function enableExportButtons(enable) {
            document.getElementById('btnExportExcel').disabled = !enable;
            document.getElementById('btnExportCSV').disabled = !enable;
            document.getElementById('btnPrintReport').disabled = !enable;
        }

        // Display report results in new format
        function displayReportResults(reportData) {
            const resultsDiv = document.getElementById('reportResults');
            const tableBody = document.getElementById('reportTableBody');

            // Update result count
            document.getElementById('resultCount').textContent = reportData.length + ' records';

            // Update generated info
            document.getElementById('reportGeneratedDate').textContent = new Date().toLocaleString();
            document.getElementById('reportGeneratedBy').textContent = userName + ' (' + userID + ')';

            // Clear and populate table
            tableBody.innerHTML = '';

            if (reportData.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted mb-0">No records found matching your criteria</p>
                        </td>
                    </tr>
                `;
            } else {
                reportData.forEach((item, index) => {
                    const statusClass = item.status === 'Pending' ? 'badge-pending' : 'badge-retrieved';
                    // Combine date and time into timestamp
                    const timestamp = (item.retrieveDate && item.retrieveTime)
                        ? `${item.retrieveDate} ${item.retrieveTime}`
                        : (item.retrieveDate || item.retrieveTime || 'N/A');
                    tableBody.innerHTML += `
                        <tr>
                            <td class="text-center">${index + 1}</td>
                            <td class="text-center"><strong>${item.retrievalID || 'N/A'}</strong></td>
                            <td><code>${item.TrackingNumber}</code></td>
                            <td>${item.receiverName || 'N/A'}</td>
                            <td>${formatICNumber(item.MatricNumber)}</td>
                            <td>${timestamp}</td>
                            <td><span class="${statusClass}">${item.status}</span></td>
                        </tr>
                    `;
                });
            }

            resultsDiv.style.display = 'block';

            // Scroll to results
            resultsDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Export to Excel (XLSX format using SheetJS)
        function exportToExcel() {
            if (!isAdmin || currentReportData.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Data',
                    text: 'Please generate a report first.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            // Prepare data for Excel
            const excelData = currentReportData.map((item, index) => {
                const timestamp = (item.retrieveDate && item.retrieveTime)
                    ? `${item.retrieveDate} ${item.retrieveTime}`
                    : (item.retrieveDate || item.retrieveTime || 'N/A');
                return {
                    'No': index + 1,
                    'Retrieval ID': item.retrievalID || 'N/A',
                    'Tracking Number': item.TrackingNumber,
                    'Receiver Name': item.receiverName || 'N/A',
                    'Matric Number': item.MatricNumber,
                    'Timestamp': timestamp,
                    'Status': item.status
                };
            });

            // Create workbook and worksheet
            const ws = XLSX.utils.json_to_sheet(excelData);
            const wb = XLSX.utils.book_new();

            // Set column widths
            ws['!cols'] = [
                { wch: 5 },  // No
                { wch: 12 }, // Retrieval ID
                { wch: 20 }, // Tracking Number
                { wch: 25 }, // Receiver Name
                { wch: 15 }, // Matric Number
                { wch: 20 }, // Timestamp
                { wch: 10 }  // Status
            ];

            XLSX.utils.book_append_sheet(wb, ws, 'Retrieval Report');

            // Generate filename with date
            const today = new Date();
            const filename = `PPMS_Report_${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}.xlsx`;

            // Download
            XLSX.writeFile(wb, filename);

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Excel file downloaded!',
                showConfirmButton: false,
                timer: 2000,
                background: '#fff',
                showClass: { popup: 'swal2-show' },
                hideClass: { popup: 'swal2-hide' }
            });
        }

        // Export to CSV
        function exportToCSV() {
            if (!isAdmin || currentReportData.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Data',
                    text: 'Please generate a report first.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            // Prepare CSV content
            const headers = ['No', 'Retrieval ID', 'Tracking Number', 'Receiver Name', 'Matric Number', 'Timestamp', 'Status'];
            let csvContent = headers.join(',') + '\n';

            currentReportData.forEach((item, index) => {
                const timestamp = (item.retrieveDate && item.retrieveTime)
                    ? `${item.retrieveDate} ${item.retrieveTime}`
                    : (item.retrieveDate || item.retrieveTime || 'N/A');
                const row = [
                    index + 1,
                    item.retrievalID || 'N/A',
                    item.TrackingNumber,
                    `"${(item.receiverName || 'N/A').replace(/"/g, '""')}"`, // Handle quotes in names
                    item.MatricNumber,
                    timestamp,
                    item.status
                ];
                csvContent += row.join(',') + '\n';
            });

            // Create download
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            const today = new Date();
            const filename = `PPMS_Report_${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}.csv`;

            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'CSV file downloaded!',
                showConfirmButton: false,
                timer: 2000,
                background: '#fff',
                showClass: { popup: 'swal2-show' },
                hideClass: { popup: 'swal2-hide' }
            });
        }

        // Print report with professional layout
        function printReport() {
            if (currentReportData.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Report',
                    text: 'Please generate a report first.',
                    confirmButtonColor: '#6A1B9A'
                });
                return;
            }

            const printWindow = window.open('', '_blank');
            const currentDate = new Date().toLocaleString();

            // Calculate stats for print
            const total = currentReportData.length;
            const retrieved = currentReportData.filter(item => item.status === 'Retrieved').length;
            const pending = currentReportData.filter(item => item.status === 'Pending').length;
            const rate = total > 0 ? ((retrieved / total) * 100).toFixed(1) : 0;

            // Build table rows
            let tableRows = '';
            currentReportData.forEach((item, index) => {
                const timestamp = (item.retrieveDate && item.retrieveTime)
                    ? `${item.retrieveDate} ${item.retrieveTime}`
                    : (item.retrieveDate || item.retrieveTime || 'N/A');
                tableRows += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td class="text-center">${item.retrievalID || 'N/A'}</td>
                        <td>${item.TrackingNumber}</td>
                        <td>${item.receiverName || 'N/A'}</td>
                        <td>${item.MatricNumber}</td>
                        <td>${timestamp}</td>
                        <td class="${item.status === 'Retrieved' ? 'status-retrieved' : 'status-pending'}">${item.status}</td>
                    </tr>
                `;
            });

            // Get filter info
            const startDate = document.getElementById('reportStartDate').value || 'All';
            const endDate = document.getElementById('reportEndDate').value || 'All';
            const statusFilter = document.getElementById('reportStatus').value || 'All';

            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>PPMS Admin Report</title>
                    <link rel="icon" href="../assets/Icon Web.ico" type="image/x-icon">
                    <style>
                        /* Page setup for landscape printing */
                        @page {
                            size: landscape;
                            margin: 15mm;
                        }

                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; color: #333; }

                        .header { text-align: center; margin-bottom: 25px; border-bottom: 3px solid #6A1B9A; padding-bottom: 15px; }
                        .header h1 { color: #6A1B9A; font-size: 22px; margin-bottom: 5px; }
                        .header h2 { color: #666; font-size: 14px; font-weight: normal; }
                        .header .logo { width: 50px; height: 50px; margin-bottom: 8px; }

                        .meta-info { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 11px; color: #666; }

                        .stats-row { display: flex; justify-content: space-around; margin-bottom: 20px; }
                        .stat-box { text-align: center; padding: 12px 25px; border-radius: 6px; }
                        .stat-box.total { background: #f3e5f5; border: 2px solid #6A1B9A; }
                        .stat-box.retrieved { background: #e8f5e9; border: 2px solid #43e97b; }
                        .stat-box.pending { background: #fff3e0; border: 2px solid #FF9800; }
                        .stat-box.rate { background: #ede7f6; border: 2px solid #667eea; }
                        .stat-box h3 { font-size: 20px; margin-bottom: 3px; }
                        .stat-box p { font-size: 10px; color: #666; }

                        table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 20px; }

                        /* Screen styles for table header */
                        th { background: #6A1B9A; color: white; padding: 10px 8px; text-align: left; font-weight: 600; }
                        th.text-center { text-align: center; }

                        td { padding: 7px 8px; border-bottom: 1px solid #ddd; }
                        td.text-center { text-align: center; }
                        tr:nth-child(odd) { background: #ffffff; }
                        tr:nth-child(even) { background: #f8f8f8; }

                        .status-retrieved { color: #2e7d32; font-weight: 600; }
                        .status-pending { color: #ef6c00; font-weight: 600; }

                        .footer { text-align: center; margin-top: 25px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 10px; color: #999; }
                        .filter-info { background: #f5f5f5; padding: 8px 12px; border-radius: 4px; margin-bottom: 15px; font-size: 10px; }
                        .sort-icon { font-size: 9px; margin-right: 3px; }

                        /* Signature block - hidden on screen, visible on print */
                        .signature-block {
                            display: none;
                        }

                        /* Print-specific styles */
                        @media print {
                            body { padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

                            /* Ink-saving table header: white background with border */
                            th {
                                background: #ffffff !important;
                                color: #333 !important;
                                border-bottom: 2px solid #6A1B9A !important;
                                border-top: 1px solid #333 !important;
                            }

                            /* Subtle zebra striping for print */
                            tr:nth-child(even) { background: #f5f5f5 !important; }

                            /* Hide filter info box on print */
                            .filter-info { display: none !important; }

                            /* Hide stats row on print to save space/ink */
                            .stats-row { display: none !important; }

                            .stat-box { padding: 8px 15px; }

                            /* Ensure table header repeats on multiple pages */
                            thead { display: table-header-group; }
                            tbody { display: table-row-group; }
                            tr { page-break-inside: avoid; }

                            /* Show signature block on print */
                            .signature-block {
                                display: flex !important;
                                justify-content: space-between;
                                margin-top: 60px;
                                padding: 0 40px;
                                page-break-inside: avoid;
                            }

                            .signature-box {
                                text-align: center;
                                width: 250px;
                            }

                            .signature-line {
                                border-top: 1px solid #333;
                                margin-top: 60px;
                                padding-top: 8px;
                            }

                            .signature-label {
                                font-size: 11px;
                                font-weight: 600;
                                color: #333;
                            }

                            .signature-title {
                                font-size: 9px;
                                color: #666;
                                margin-top: 3px;
                            }

                            /* Ensure footer stays at bottom */
                            .footer {
                                margin-top: 20px;
                                page-break-inside: avoid;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <img src="../assets/Icon Web.ico" alt="PPMS Logo" class="logo">
                        <h1>Perwira Parcel Management System</h1>
                        <h2>Admin Report - Parcel Retrieval Records</h2>
                    </div>

                    <div class="meta-info">
                        <div><strong>Generated:</strong> ${currentDate}</div>
                        <div><strong>Generated by:</strong> ${userName} (${userID})</div>
                    </div>

                    <div class="filter-info">
                        <strong>Filters Applied:</strong>
                        Date Range: ${startDate} to ${endDate} |
                        Status: ${statusFilter}
                    </div>

                    <div class="stats-row">
                        <div class="stat-box total">
                            <h3>${total}</h3>
                            <p>Total Records</p>
                        </div>
                        <div class="stat-box retrieved">
                            <h3>${retrieved}</h3>
                            <p>Retrieved</p>
                        </div>
                        <div class="stat-box pending">
                            <h3>${pending}</h3>
                            <p>Pending</p>
                        </div>
                        <div class="stat-box rate">
                            <h3>${rate}%</h3>
                            <p>Retrieval Rate</p>
                        </div>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 35px;">#</th>
                                <th class="text-center" style="width: 80px;">Retrieval ID</th>
                                <th>Tracking Number</th>
                                <th>Receiver</th>
                                <th>Matric No</th>
                                <th><span class="sort-icon">▼</span>Timestamp</th>
                                <th style="width: 70px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${tableRows}
                        </tbody>
                    </table>

                    <!-- Signature Block (Print Only) -->
                    <div class="signature-block">
                        <div class="signature-box">
                            <div class="signature-line">
                                <div class="signature-label">Prepared By</div>
                                <div class="signature-title">Staff / Administrator</div>
                            </div>
                        </div>
                        <div class="signature-box">
                            <div class="signature-line">
                                <div class="signature-label">Verified By</div>
                                <div class="signature-title">Supervisor / Management</div>
                            </div>
                        </div>
                    </div>

                    <div class="footer">
                        <p>Perwira Parcel Management System (PPMS) - Kolej Kediaman Perwira, UTHM</p>
                        <p>This report was auto-generated. For queries, contact the system administrator.</p>
                    </div>

                    <scr` + `ipt>
                        window.onload = function() { setTimeout(function() { window.print(); }, 300); }
                    </scr` + `ipt>
                </body>
                </html>
            `);
            printWindow.document.close();
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

    /* === CLEAN MODERN CAROUSEL WITH CSS SCROLL SNAP === */
    .modern-carousel-container {
        position: relative;
        max-width: 100%;
        margin: 0 auto;
        padding: 1.25rem 0.75rem;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(245, 250, 255, 0.95) 100%);
        border-radius: 16px;
        border: 1px solid rgba(106, 27, 154, 0.15);
        overflow: visible;
        box-shadow: 0 4px 20px rgba(106, 27, 154, 0.1);
    }

    .carousel-viewport {
        overflow: hidden;
        padding: 0 0.5rem;
        margin: 0 auto;
        position: relative;
    }

    .carousel-track {
        display: flex;
        gap: 0.75rem;
        padding: 0.5rem 0;
        width: max-content;
        /* Animation removed - carousel is now static */
    }

    

    /* Keyframes removed - carousel doesn't auto-scroll */

    .partner-card {
        flex: 0 0 120px;
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        transition: all 0.25s ease;
        border: 1px solid rgba(226, 232, 240, 0.8);
        overflow: hidden;
        cursor: pointer;
    }

    .partner-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        border-color: rgba(106, 27, 154, 0.3);
        filter: grayscale(0%);
    }

    .partner-card {
        filter: grayscale(100%);
    }

    .partner-card-inner {
        padding: 0.875rem 0.5rem;
        text-align: center;
        height: 100%;
        min-height: 85px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .partner-logo-container {
        width: 44px;
        height: 44px;
        margin: 0 auto 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 8px;
        padding: 6px;
        transition: all 0.25s ease;
    }

    .partner-card:hover .partner-logo-container {
        background: linear-gradient(135deg, rgba(106, 27, 154, 0.1) 0%, rgba(255, 152, 0, 0.1) 100%);
        transform: scale(1.05);
    }

    .partner-logo {
        max-width: 30px;
        max-height: 30px;
        object-fit: contain;
        filter: none;
        transition: all 0.25s ease;
    }

    .partner-card:hover .partner-logo {
        filter: brightness(1.05);
        transform: scale(1.08);
    }

    .partner-logo-placeholder {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 6px;
        font-size: 0.5rem;
        font-weight: 600;
        color: white;
        text-align: center;
        line-height: 1.1;
    }

    .partner-info {
        text-align: center;
    }

    .partner-name {
        font-size: 0.7rem;
        font-weight: 600;
        color: #374151;
        margin: 0;
        text-align: center;
        line-height: 1.3;
    }

    /* Navigation Arrows */
    .carousel-nav {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(106, 27, 154, 0.8);
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10;
        transition: all 0.3s ease;
        font-size: 1.2rem;
    }

    .carousel-nav:hover {
        background: rgba(106, 27, 154, 1);
        transform: translateY(-50%) scale(1.1);
        box-shadow: 0 4px 12px rgba(106, 27, 154, 0.4);
    }

    .carousel-nav-prev {
        left: 0.5rem;
    }

    .carousel-nav-next {
        right: 0.5rem;
    }

    /* Indicators - Dots */
    .carousel-indicators {
        display: none;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 1rem;
        padding: 0 1rem;
    }

    .carousel-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: rgba(106, 27, 154, 0.3);
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
        padding: 0;
    }

    .carousel-indicator.active {
        background: rgba(106, 27, 154, 1);
        width: 24px;
        border-radius: 4px;
    }

    .carousel-indicator:hover {
        background: rgba(106, 27, 154, 0.6);
    }

    /* Carousel Responsive Design */
    @media (max-width: 768px) {
        .modern-carousel-container {
            padding: 0.875rem 0.5rem;
            border-radius: 10px;
        }

        .carousel-viewport {
            padding: 0 0.25rem;
        }

        .carousel-track {
            gap: 0.5rem;
        }

        .partner-card {
            flex: 0 0 100px;
            min-width: 100px;
        }

        .partner-card-inner {
            padding: 0.5rem 0.375rem;
            min-height: 65px;
        }

        .partner-logo-container {
            width: 32px;
            height: 32px;
            margin-bottom: 0.375rem;
            border-radius: 6px;
            padding: 4px;
        }

        .partner-logo {
            max-width: 22px;
            max-height: 22px;
        }

        .partner-logo-placeholder {
            width: 22px;
            height: 22px;
            font-size: 0.4rem;
            border-radius: 4px;
        }

        .partner-name {
            font-size: 0.55rem;
            line-height: 1.2;
        }

        .partner-description {
            display: none;
        }

        .carousel-nav {
            width: 36px;
            height: 36px;
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        .partner-card {
            flex: 0 0 90px;
            min-width: 90px;
        }

        .carousel-track {
            gap: 0.375rem;
        }

        .partner-card-inner {
            padding: 0.4rem 0.25rem;
            min-height: 58px;
        }

        .partner-logo-container {
            width: 28px;
            height: 28px;
            margin-bottom: 0.25rem;
        }

        .partner-logo {
            max-width: 18px;
            max-height: 18px;
        }

        .partner-name {
            font-size: 0.5rem;
        }

        .carousel-nav {
            width: 32px;
            height: 32px;
            font-size: 0.9rem;
        }

        .carousel-indicators {
            margin-top: 0.75rem;
            gap: 0.375rem;
        }

        .carousel-indicator {
            width: 6px;
            height: 6px;
        }

        .carousel-indicator.active {
            width: 20px;
        }
    }
    </style>

    <script>
    // CSS Scroll-Snap Carousel with Navigation
    let currentCarouselIndex = 0;
    let isAutoSliding = false; // Carousel is now static by default
    let autoSlideInterval;
    let cardsPerView = 3;
    let originalCardsCount = 8; // Number of unique cards

    // Infinite carousel state
    let carouselIsInfiniteReady = false;
    let carouselBaseOffset = 0;
    let carouselBaseWidth = 0;
    let carouselCardWithGap = 0;
    let carouselScrollNormalizeLock = false;

    function setupInfiniteCarousel() {
        const viewport = document.querySelector('.carousel-viewport');
        const track = document.getElementById('modernCarouselTrack');
        if (!viewport || !track) return;

        const originals = Array.from(track.querySelectorAll('.partner-card'));
        if (originals.length === 0) return;

        // Avoid double-initializing
        if (track.dataset.infiniteReady === '1') return;

        // Measure base width before cloning
        carouselBaseWidth = track.scrollWidth;

        // Clone a small buffer on both ends (enough for smooth looping)
        const cloneCount = Math.min(3, originals.length);
        const headClones = originals.slice(0, cloneCount).map(node => node.cloneNode(true));
        const tailClones = originals.slice(-cloneCount).map(node => node.cloneNode(true));

        tailClones.forEach(clone => {
            clone.setAttribute('aria-hidden', 'true');
            clone.classList.add('is-clone');
            track.insertBefore(clone, track.firstChild);
        });
        headClones.forEach(clone => {
            clone.setAttribute('aria-hidden', 'true');
            clone.classList.add('is-clone');
            track.appendChild(clone);
        });

        // After clones inserted, compute base offset and card step
        requestAnimationFrame(() => {
            const cards = track.querySelectorAll('.partner-card');
            const gap = parseInt(window.getComputedStyle(track).gap) || 0;
            const firstCard = cards[0];
            carouselCardWithGap = (firstCard ? firstCard.offsetWidth : 0) + gap;

            // First original card is after the prepended clones
            carouselBaseOffset = cards[cloneCount] ? cards[cloneCount].offsetLeft : 0;
            viewport.scrollLeft = carouselBaseOffset;
            carouselIsInfiniteReady = true;
            track.dataset.infiniteReady = '1';
            updateCarouselIndicators();
        });
    }

    function initializeCarousel() {
        const viewport = document.querySelector('.carousel-viewport');
        const track = document.getElementById('modernCarouselTrack');
        if (!viewport || !track) return;

        // Calculate cards per view based on screen size
        updateCardsPerView();

        // Auto-slide is disabled - carousel is static by default
        // Users can navigate using arrow buttons and touch/mouse

        // Pause on hover (not needed since auto-slide is disabled)
        const container = document.querySelector('.modern-carousel-container');
        
        // Remove auto-slide resume logic since carousel is static
        container.addEventListener('mouseleave', () => {
            // No auto-slide to resume
        });

        // Track scroll position for indicators
        viewport.addEventListener('scroll', updateCarouselIndicators);

        // Handle window resize
        window.addEventListener('resize', () => {
            updateCardsPerView();
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
        const viewport = document.querySelector('.carousel-viewport');
        const cards = document.querySelectorAll('.partner-card');

        if (!viewport || cards.length === 0) return;

        const cardWidth = cards[0].offsetWidth;
        const gap = parseInt(window.getComputedStyle(document.querySelector('.carousel-track')).gap);
        const cardWithGap = cardWidth + gap;
        const currentScroll = viewport.scrollLeft;

        if (direction === 'next') {
            currentCarouselIndex = (currentCarouselIndex + 1 + originalCardsCount) % originalCardsCount;
        } else {
            currentCarouselIndex = (currentCarouselIndex - 1 + originalCardsCount) % originalCardsCount;
        }

        const targetScroll = (carouselIsInfiniteReady ? carouselBaseOffset : 0) + (currentCarouselIndex * cardWithGap);
        viewport.scrollTo({
            left: targetScroll,
            behavior: 'smooth'
        });

        updateCarouselIndicators();
    }

    function goToSlide(index) {
        const viewport = document.querySelector('.carousel-viewport');
        const cards = document.querySelectorAll('.partner-card');

        if (!viewport || cards.length === 0) return;

        currentCarouselIndex = index;
        const cardWidth = cards[0].offsetWidth;
        const gap = parseInt(window.getComputedStyle(document.querySelector('.carousel-track')).gap);
        const cardWithGap = cardWidth + gap;
        const targetScroll = (carouselIsInfiniteReady ? carouselBaseOffset : 0) + (currentCarouselIndex * cardWithGap);

        viewport.scrollTo({
            left: targetScroll,
            behavior: 'smooth'
        });

        updateCarouselIndicators();

        // Reset auto-slide
        if (isAutoSliding) {
            clearInterval(autoSlideInterval);
            startAutoSlide();
        }
    }

    function updateCarouselIndicators() {
        const viewport = document.querySelector('.carousel-viewport');
        const cards = document.querySelectorAll('.partner-card');
        const indicators = document.querySelectorAll('.carousel-indicator');

        if (!viewport || cards.length === 0 || indicators.length === 0) return;

        const cardWidth = cards[0].offsetWidth;
        const gap = parseInt(window.getComputedStyle(document.querySelector('.carousel-track')).gap);
        const cardWithGap = cardWidth + gap;
        // Normalize scroll position for infinite looping
        if (carouselIsInfiniteReady && carouselBaseWidth > 0 && !carouselScrollNormalizeLock) {
            const min = carouselBaseOffset - 2;
            const max = carouselBaseOffset + carouselBaseWidth + 2;
            if (viewport.scrollLeft < min) {
                carouselScrollNormalizeLock = true;
                viewport.scrollLeft = viewport.scrollLeft + carouselBaseWidth;
                requestAnimationFrame(() => { carouselScrollNormalizeLock = false; });
            } else if (viewport.scrollLeft > max) {
                carouselScrollNormalizeLock = true;
                viewport.scrollLeft = viewport.scrollLeft - carouselBaseWidth;
                requestAnimationFrame(() => { carouselScrollNormalizeLock = false; });
            }
        }

        const scrollPosition = carouselIsInfiniteReady ? (viewport.scrollLeft - carouselBaseOffset) : viewport.scrollLeft;

        // Calculate which slide is currently visible (within the base region)
        const visibleIndex = Math.round(scrollPosition / cardWithGap);
        currentCarouselIndex = ((visibleIndex % originalCardsCount) + originalCardsCount) % originalCardsCount;

        // Update active indicator
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === currentCarouselIndex);
        });
    }

    // Carousel Drag Functionality
    function initializeCarouselDrag() {
        const viewport = document.querySelector('.carousel-viewport');
        if (!viewport) return;

        let isDown = false;
        let startX;
        let scrollLeft;

        viewport.addEventListener('mousedown', (e) => {
            isDown = true;
            viewport.classList.add('active');
            viewport.style.cursor = 'grabbing';
            startX = e.pageX - viewport.offsetLeft;
            scrollLeft = viewport.scrollLeft;
        });

        viewport.addEventListener('mouseleave', () => {
            isDown = false;
            viewport.classList.remove('active');
            viewport.style.cursor = 'grab';
        });

        viewport.addEventListener('mouseup', () => {
            isDown = false;
            viewport.classList.remove('active');
            viewport.style.cursor = 'grab';
        });

        viewport.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - viewport.offsetLeft;
            const walk = (x - startX) * 2;
            viewport.scrollLeft = scrollLeft - walk;
        });

        // Touch support for mobile
        let touchStartX = 0;
        let touchScrollLeft = 0;

        viewport.addEventListener('touchstart', (e) => {
            touchStartX = e.touches[0].pageX - viewport.offsetLeft;
            touchScrollLeft = viewport.scrollLeft;
        }, { passive: true });

        viewport.addEventListener('touchmove', (e) => {
            const x = e.touches[0].pageX - viewport.offsetLeft;
            const walk = (x - touchStartX) * 2;
            viewport.scrollLeft = touchScrollLeft - walk;
        }, { passive: true });

        viewport.style.cursor = 'grab';
    }

    // Initialize carousel when page loads
    document.addEventListener('DOMContentLoaded', () => {
        initializeCarousel();
        initializeCarouselDrag();
        setupInfiniteCarousel();
    });

    // Function to format Matric number (8 digits, no formatting needed)
    function formatICNumber(matricNumber) {
        if (!matricNumber || matricNumber.length !== 8) {
            return matricNumber; // Return as-is if not 8 digits
        }

        // Matric numbers are displayed as-is (8 digits)
        return matricNumber;
    }

    </script>
</body>
</html>