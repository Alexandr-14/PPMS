<?php
session_start();
require_once '../api/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['receiver_matric']) || empty($_SESSION['receiver_matric'])) {
    header("Location: receiver-login.html");
    exit();
}

$receiverIC = $_SESSION['receiver_matric'];
$receiverName = $_SESSION['receiver_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PPMS Receiver Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="../css/bootstrap/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- PPMS Custom Styles -->
    <link rel="stylesheet" href="../css/ppms-styles/shared/variables.css">
    <link rel="stylesheet" href="../css/ppms-styles/shared/typography.css">
    <link rel="stylesheet" href="../css/ppms-styles/shared/safe-typography-enhancements.css">
    <link rel="stylesheet" href="../css/ppms-styles/shared/components.css">
    <link rel="stylesheet" href="../css/ppms-styles/receiver/receiver-dashboard.css">
    <link rel="stylesheet" href="../css/ppms-styles/receiver/receiver-dashboard-overrides.css">
    <link rel="stylesheet" href="../css/ppms-styles/receiver/receiver-notifications.css">
    <link rel="stylesheet" href="../css/ppms-styles/receiver/receiver-navbar-buttons.css">
    <!-- Mobile Responsive Styles -->
    <link rel="stylesheet" href="../css/ppms-styles/shared/mobile-responsive.css">
    <!-- Favicon -->
    <link rel="icon" href="../assets/Icon Web.ico" type="image/x-icon">
    <style>
        /* Row hover effect for parcel tables (Receiver) - Same as Staff */
        #historyTable tbody tr {
            transition: background-color 0.2s ease, box-shadow 0.2s ease;
        }
        #historyTable tbody tr:hover {
            background-color: rgba(106, 27, 154, 0.06); /* purple tint - same as staff */
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            position: relative;
            z-index: 1;
        }

        /* Hide native browser clear (X) for our custom clear button */
        #receiverHistorySearchInput::-ms-clear, #receiverHistorySearchInput::-ms-reveal { display: none; width:0; height:0; }
        #receiverHistorySearchInput::-webkit-search-cancel-button { -webkit-appearance: none; }

        /* Override CSS for search input - remove borders */
        #receiverHistorySearchInput {
            border: none !important;
            border-radius: 50px !important;
            background: white !important;
            padding: 12px 20px 12px 20px !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        }

        #receiverHistorySearchInput:focus {
            border: none !important;
            box-shadow: 0 4px 16px rgba(25, 135, 84, 0.2) !important;
            outline: none !important;
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
                <div class="navbar-brand mb-0">Perwira Parcel Management System</div>
                <div class="navbar-subtitle" style="font-size: 0.85rem; opacity: 0.8;">Receiver Access - Parcel Tracking</div>
            </div>
        </div>
        <div class="d-flex align-items-center gap-3">
            <!-- Notification Bell with Dropdown (Receiver only) -->
            <div class="dropdown">
                    <button class="notification-bell-btn btn" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php
                        // Count unread notifications
                        $unreadStmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notification WHERE MatricNumber = ? AND isRead = 0");
                        $unreadStmt->bind_param("s", $receiverIC);
                        $unreadStmt->execute();
                        $unreadResult = $unreadStmt->get_result();
                        $unreadCount = $unreadResult->fetch_assoc()['unread_count'];

                        if ($unreadCount > 0):
                        ?>
                            <span class="notification-badge"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </button>

                    <!-- Notification Dropdown -->
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown" aria-labelledby="notificationDropdown">
                        <div class="notification-dropdown-header">
                            <h6 class="mb-0">Notifications</h6>
                            <?php if ($unreadCount > 0): ?>
                                <button class="btn btn-sm btn-link text-success p-0" onclick="markAllAsRead()">
                                    Mark all read
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="notification-dropdown-body">
                            <?php
                            // Fetch recent 5 notifications for dropdown
                            $recentStmt = $conn->prepare("SELECT * FROM notification WHERE MatricNumber = ? ORDER BY sentTimestamp DESC LIMIT 5");
                            $recentStmt->bind_param("s", $receiverIC);
                            $recentStmt->execute();
                            $recentResult = $recentStmt->get_result();

                            if ($recentResult->num_rows > 0):
                                while ($notification = $recentResult->fetch_assoc()):
                                    $isUnread = !$notification['isRead'];
                                    $notificationType = $notification['notificationType'];
                                    $iconClass = '';

                                    switch($notificationType) {
                                        case 'delivery':
                                            $iconClass = 'fas fa-truck text-success';
                                            break;
                                        case 'pickup':
                                            $iconClass = 'fas fa-check-circle text-success';
                                            break;
                                        case 'arrival':
                                            $iconClass = 'fas fa-box text-info';
                                            break;
                                        default:
                                            $iconClass = 'fas fa-info-circle text-primary';
                                    }
                            ?>
                                <div class="notification-dropdown-item <?php echo $isUnread ? 'unread' : ''; ?>"
                                     data-notification-id="<?php echo $notification['notificationID']; ?>">
                                    <div class="notification-icon">
                                        <i class="<?php echo $iconClass; ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <div class="notification-message">
                                            <?php echo htmlspecialchars(substr($notification['messageContent'], 0, 60)) . (strlen($notification['messageContent']) > 60 ? '...' : ''); ?>
                                        </div>
                                        <div class="notification-time">
                                            <?php echo date('M d, h:i A', strtotime($notification['sentTimestamp'])); ?>
                                        </div>
                                    </div>
                                    <div class="notification-dropdown-actions">
                                        <?php if ($isUnread): ?>
                                            <button class="btn btn-sm btn-link text-success p-0"
                                                    onclick="markNotificationAsRead(<?php echo $notification['notificationID']; ?>)"
                                                    title="Mark as read">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <div class="notification-unread-dot"></div>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-link text-danger p-0"
                                                onclick="deleteNotification(<?php echo $notification['notificationID']; ?>)"
                                                title="Delete notification">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php
                                endwhile;
                            else:
                            ?>
                                <div class="notification-dropdown-empty">
                                    <i class="fas fa-bell-slash text-muted"></i>
                                    <p class="mb-0">No notifications yet</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="notification-dropdown-footer">
                            <button type="button" class="btn btn-sm btn-success w-100" onclick="showAllNotifications()">
                                <i class="fas fa-list me-1"></i> View All Notifications
                            </button>
                        </div>
                    </div>
                </div>

                <div class="navbar-user-name">
                    <?php echo htmlspecialchars($receiverName); ?>
                </div>

                <button type="button" class="logout-btn" onclick="logout()" aria-label="Logout">
                    <i class="fas fa-sign-out-alt me-2"></i><span class="logout-text">Logout</span>
                </button>
            </div>
        </nav>

        <!-- Dashboard Container -->
        <div class="dashboard-container">
            <!-- Simple Tabs -->
            <ul class="nav nav-tabs sticky-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tracking-tab" data-bs-toggle="tab" data-bs-target="#tracking" type="button" role="tab">
                    <svg class="me-1" style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Track Parcel
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                    <svg class="me-1" style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="3.27,6.96 12,12.01 20.73,6.96" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="22.08" x2="12" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg> Parcel Details
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link position-relative" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
                    <svg class="me-1" style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Notifications
                    <?php if ($unreadCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                            <?php echo $unreadCount; ?>
                        </span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                    <svg class="me-1" style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 3v5h5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 7v5l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Parcel History
                </button>
            </li>

        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="myTabContent">
            <!-- Tracking Tab -->
            <div class="tab-pane fade show active" id="tracking" role="tabpanel">
                <h4 class="text-engaging">Track Your Parcel</h4>
                <p class="welcome-text">Enter your tracking number to see the status of your parcel.</p>

                <form method="post" action="../api/track-parcel.php" class="mt-4" style="background: #f8f9fa; padding: 2rem; border-radius: 1rem; border: 1px solid #e9ecef;">
                    <div class="mb-3">
                        <label for="trackingNumber" class="form-label" style="font-weight: 700 !important; color: #2d3748 !important; font-size: 1rem !important; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 1rem !important;">
                             Tracking Number
                        </label>
                        <input type="text" class="form-control" id="trackingNumber" name="trackingNumber" required
                               placeholder="Enter your tracking number"
                               style="background: #ffffff !important; color: #2d3748 !important; border: 2px solid #e2e8f0 !important; padding: 0.75rem 1rem !important; font-size: 1rem !important;">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Track a Parcel
                    </button>
                </form>

                <?php if (isset($_GET['tracking_result'])): ?>
                    <?php if ($_GET['tracking_result'] == 'not_found'): ?>
                        <div class="alert alert-warning mt-3">
                            <svg class="me-2" style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Parcel not found. Please check your tracking number.
                        </div>
                    <?php elseif ($_GET['tracking_result'] == 'unauthorized'): ?>
                        <div class="alert alert-danger mt-3">
                            <svg class="me-2" style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><rect x="3" y="11" width="18" height="11" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="16" r="1" stroke="currentColor" stroke-width="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4" stroke="currentColor" stroke-width="2"/></svg>
                            You can only track parcels assigned to you.
                        </div>
                    <?php elseif ($_GET['tracking_result'] == 'error'): ?>
                        <div class="alert alert-danger mt-3">
                            <svg class="me-2" style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="15" y1="9" x2="9" y2="15" stroke="currentColor" stroke-width="2"/><line x1="9" y1="9" x2="15" y2="15" stroke="currentColor" stroke-width="2"/></svg>
                            An error occurred. Please try again.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Details Tab -->
            <div class="tab-pane fade" id="details" role="tabpanel">
                <div class="mb-4">
                    <h4 class="mb-0">Parcel Details</h4>
                </div>

                <?php if (isset($_SESSION['tracked_parcel'])): ?>
                    <?php $parcel = $_SESSION['tracked_parcel']; ?>

                    <!-- Modern Parcel Details Card -->
                    <div class="parcel-details-container">
                        <!-- Header Section with Tracking Number -->
                        <div class="parcel-header">
                            <div class="tracking-badge">
                                <i class="fas fa-barcode me-2"></i>
                                <span class="tracking-label">Tracking Number</span>
                                <span class="tracking-number"><?php echo htmlspecialchars($parcel['TrackingNumber']); ?></span>
                            </div>

                            <!-- Status Badge -->
                            <div class="status-container" id="parcelStatusBadge" data-status="<?php echo htmlspecialchars($parcel['status']); ?>">
                                <?php if ($parcel['status'] == 'Pending'): ?>
                                    <div class="status-badge status-pending">
                                        <svg style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><polyline points="12,6 12,12 16,14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <span>Pending Pickup</span>
                                    </div>
                                <?php elseif ($parcel['status'] == 'Retrieved'): ?>
                                    <div class="status-badge status-retrieved">
                                        <svg style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="22,4 12,14.01 9,11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        <span>Retrieved</span>
                                    </div>
                                <?php else: ?>
                                    <div class="status-badge status-other">
                                        <svg style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12" y2="12" stroke="currentColor" stroke-width="2"/><line x1="12" y1="8" x2="12.01" y2="8" stroke="currentColor" stroke-width="2"/></svg>
                                        <span><?php echo htmlspecialchars($parcel['status']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Modern Two-Column Layout -->
                        <div class="parcel-details-grid-modern">
                            <!-- Left Column: Package Details Card -->
                            <div class="package-details-card">
                                <div class="package-details-header">
                                    <h3 class="package-details-title">Package Details</h3>
                                </div>

                                <!-- Date & Weight Row -->
                                <div class="details-row">
                                    <div class="detail-item-modern">
                                        <div class="detail-item-icon">
                                            <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/><line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/></svg>
                                        </div>
                                        <div class="detail-item-content">
                                            <div class="detail-item-label">Received Date</div>
                                            <div class="detail-item-value">
                                                <?php echo date('F d, Y', strtotime($parcel['date'])); ?>
                                                <?php if (!empty($parcel['time'])): ?>
                                                    <div class="detail-item-subvalue">at <?php echo date('h:i A', strtotime($parcel['time'])); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="detail-item-modern">
                                        <div class="detail-item-icon">
                                            <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none"><path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 17l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        </div>
                                        <div class="detail-item-content">
                                            <div class="detail-item-label">Package Weight</div>
                                            <div class="detail-item-value"><?php echo htmlspecialchars($parcel['weight']); ?> kg</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Storage Rack -->
                                <div class="details-row">
                                    <div class="detail-item-modern full-width">
                                        <div class="detail-item-icon">
                                            <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/></svg>
                                        </div>
                                        <div class="detail-item-content">
                                            <div class="detail-item-label">Storage Rack</div>
                                            <div class="detail-item-value"><?php echo htmlspecialchars($parcel['deliveryLocation'] ?? 'Not specified'); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Receiver Matric Number (Prominent) -->
                                <div class="details-row">
                                    <div class="detail-item-modern full-width">
                                        <div class="detail-item-icon">
                                            <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
                                        </div>
                                        <div class="detail-item-content">
                                            <div class="detail-item-label">Receiver Matric Number</div>
                                            <div class="detail-item-value-prominent" id="receiverICDisplay"><?php echo htmlspecialchars($parcel['MatricNumber']); ?></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Package Description (if available) -->
                                <?php if (!empty($parcel['name'])): ?>
                                <div class="details-row">
                                    <div class="detail-item-modern full-width">
                                        <div class="detail-item-icon">
                                            <svg style="width: 18px; height: 18px;" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" stroke="currentColor" stroke-width="2"/><polyline points="3.27,6.96 12,12.01 20.73,6.96" stroke="currentColor" stroke-width="2"/><line x1="12" y1="22.08" x2="12" y2="12" stroke="currentColor" stroke-width="2"/></svg>
                                        </div>
                                        <div class="detail-item-content">
                                            <div class="detail-item-label">Package Description</div>
                                            <div class="detail-item-value"><?php echo htmlspecialchars($parcel['name']); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Right Column: QR Code Card -->
                            <div class="qr-card-modern">
                                <div class="qr-card-header">
                                    <h3 class="qr-card-title">QR Code</h3>
                                </div>
                                <div class="qr-code-container-modern" id="parcelQRCode"></div>
                                <small class="qr-code-note">QR Code contains encrypted verification data</small>
                                <div class="qr-buttons-container">
                                    <button type="button" class="btn-qr btn-qr-download" onclick="downloadReceiverQR()">
                                        <i class="fas fa-download me-2"></i>Download QR Image
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Action Alert (updated in real-time) -->
                        <div id="parcelStatusAlert" data-status="<?php echo htmlspecialchars($parcel['status']); ?>">
                            <?php if ($parcel['status'] == 'Pending'): ?>
                                <div class="action-alert">
                                    <div class="alert-icon">
                                        <i class="fas fa-hand-holding-box"></i>
                                    </div>
                                    <div class="alert-content">
                                        <div class="alert-title text-friendly">Ready for Pickup!</div>
                                        <div class="alert-message welcome-text">Your parcel is ready for collection. Please visit the parcel center with your IC and this tracking number.</div>
                                    </div>
                                </div>
                            <?php elseif ($parcel['status'] == 'Retrieved'): ?>
                                <div class="action-alert success-alert">
                                    <div class="alert-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="alert-content">
                                        <div class="alert-title">Package Retrieved</div>
                                        <div class="alert-message">This parcel has been successfully collected. Thank you for using our service!</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-search text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">No Parcel Details</h5>
                        <p class="text-muted mb-4">Track a parcel to view its details here.</p>
                        <button class="btn btn-primary" onclick="document.getElementById('tracking-tab').click()">
                            <i class="fas fa-search me-1"></i> Track a Parcel
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- History Tab -->
            <div class="tab-pane fade" id="history" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="text-engaging mb-1">Parcel History</h4>
                        <p class="welcome-text mb-0">View and manage all your parcel records.</p>
                    </div>
                </div>

                <?php
                $stmt = $conn->prepare("
                    SELECT
                        p.*,
                        r.name as receiverName
                    FROM parcel p
                    LEFT JOIN receiver r ON p.MatricNumber = r.MatricNumber
                    WHERE p.MatricNumber = ?
                    ORDER BY p.date DESC, p.time DESC
                ");
                $stmt->bind_param("s", $receiverIC);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0):
                ?>
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card stats-card border-primary">
                                <div class="card-body py-3">
                                    <i class="fas fa-boxes text-primary stats-icon"></i>
                                    <h5 class="text-primary stats-number" id="totalParcels"><?php echo $result->num_rows; ?></h5>
                                    <small class="stats-label">Total Parcels</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stats-card border-warning">
                                <div class="card-body py-3">
                                    <i class="fas fa-clock text-warning stats-icon"></i>
                                    <h5 class="text-warning stats-number" id="pendingParcels">0</h5>
                                    <small class="stats-label">Pending</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stats-card border-success">
                                <div class="card-body py-3">
                                    <i class="fas fa-check-circle text-success stats-icon"></i>
                                    <h5 class="text-success stats-number" id="retrievedParcels">0</h5>
                                    <small class="stats-label">Retrieved</small>
                                </div>
                            </div>
                        </div>
                    </div>

                        <!-- Controls Row: Search (left) + Sort/Refresh (right) -->
                        <div class="d-flex flex-wrap align-items-center mb-3 gap-3">
                            <div class="input-group" style="width: 100%; max-width: 450px;">
                                <input type="text" id="receiverHistorySearchInput" class="form-control" placeholder="Find a parcel..." style="border: none; border-radius: 50px; background: white; font-size: 0.95rem; padding: 12px 20px 12px 20px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.15)'" onmouseout="this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'" onfocus="this.style.boxShadow='0 4px 16px rgba(25, 135, 84, 0.2)'; this.style.outline='none'" onblur="this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)'">
                                <span class="input-group-text" style="border: none; background: transparent; padding-left: 0; margin-left: -40px; z-index: 10;">
                                    <i class="fas fa-search" style="color: #43e97b; font-size: 1.1rem;"></i>
                                </span>
                            </div>
                            <div class="d-flex gap-2 ms-auto flex-wrap justify-content-end mt-2 mt-md-0">
                                <div class="dropdown">
                                    <button class="btn btn-outline-success dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                        <i class="fas fa-sort me-1"></i> Sort By
                                    </button>
                                    <ul class="dropdown-menu ppms-sort-menu">
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="sortHistory('tracking', 'asc')">
                                                <i class="fas fa-sort-alpha-down me-2"></i>Tracking (A-Z)
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="sortHistory('tracking', 'desc')">
                                                <i class="fas fa-sort-alpha-up me-2"></i>Tracking (Z-A)
                                            </button>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="sortHistory('date', 'desc')">
                                                <i class="fas fa-calendar me-2"></i>Date (Newest First)
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item" onclick="sortHistory('date', 'asc')">
                                                <i class="fas fa-calendar me-2"></i>Date (Oldest First)
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                                <button class="btn btn-outline-success" onclick="refreshHistory()">
                                    <i class="fas fa-sync-alt me-1"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div id="receiverHistoryNoResults" class="text-muted small mb-3 d-none">No parcels match your search</div>

                        <div class="card border-0 shadow-sm table-card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="historyTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-3 py-2">Tracking Number</th>
                                        <th class="px-3 py-2">Date & Time</th>
                                        <th class="px-3 py-2">Weight</th>
                                        <th class="px-3 py-2">Storage Rack</th>
                                        <th class="px-3 py-2">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Reset result pointer
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()):
                                    ?>
                                    <tr data-status="<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>" data-date="<?php echo htmlspecialchars($row['date'], ENT_QUOTES); ?>" data-tracking="<?php echo htmlspecialchars($row['TrackingNumber'], ENT_QUOTES); ?>" data-matric="<?php echo htmlspecialchars($row['MatricNumber'], ENT_QUOTES); ?>" data-receiver-name="<?php echo htmlspecialchars($row['receiverName'] ?? 'N/A', ENT_QUOTES); ?>" data-location="<?php echo htmlspecialchars($row['deliveryLocation'] ?? 'N/A', ENT_QUOTES); ?>">
                                            <td class="px-3 py-2">
                                                <span class="fw-bold text-primary"><?php echo htmlspecialchars($row['TrackingNumber'], ENT_QUOTES); ?></span>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div>
                                                    <?php echo date('M d, Y', strtotime($row['date'])); ?>
                                                    <?php if (!empty($row['time'])): ?>
                                                        <br><small class="text-muted"><?php echo date('h:i A', strtotime($row['time'])); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <!-- Status column removed -->
                                            <td class="px-3 py-2">
                                                <i class="fas fa-weight-hanging me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($row['weight'], ENT_QUOTES); ?> kg
                                            </td>
                                            <td class="px-3 py-2">
                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($row['deliveryLocation'] ?? 'Not specified', ENT_QUOTES); ?>
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="ppms-history-actions">
                                                    <button type="button" class="ppms-action-btn ppms-action-view-receiver eye-button"
                                                            data-tracking="<?php echo htmlspecialchars($row['TrackingNumber']); ?>"
                                                            data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                                            data-ic="<?php echo htmlspecialchars($row['MatricNumber']); ?>"
                                                            data-receiver-name="<?php echo htmlspecialchars($row['receiverName'] ?? 'N/A'); ?>"
                                                            data-weight="<?php echo htmlspecialchars($row['weight'] ?? 'N/A'); ?>"
                                                            data-location="<?php echo htmlspecialchars($row['deliveryLocation'] ?? 'N/A'); ?>"
                                                            data-date="<?php echo htmlspecialchars($row['date']); ?>"
                                                            data-time="<?php echo htmlspecialchars($row['time']); ?>"
                                                            data-name="<?php echo htmlspecialchars($row['name'] ?? 'Package'); ?>"
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="ppms-action-btn ppms-action-download-receiver" 
                                                            data-tracking="<?php echo htmlspecialchars($row['TrackingNumber']); ?>"
                                                            onclick="downloadHistoryQR('<?php echo htmlspecialchars($row['TrackingNumber']); ?>')"
                                                            title="Download QR">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5 class="text-muted">No Parcel History</h5>
                        <p class="text-muted mb-4">You don't have any parcels in your history yet.</p>
                        <button class="btn btn-primary" onclick="document.getElementById('tracking-tab').click()">
                            <i class="fas fa-search me-1"></i> Track Your First Parcel
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Notifications Tab -->
            <div class="tab-pane fade" id="notifications" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="text-engaging mb-1">Notifications</h4>
                        <p class="welcome-text mb-0">Stay updated with your parcel activities and delivery status.</p>
                    </div>
                    <?php if ($unreadCount > 0): ?>
                        <button class="btn btn-sm btn-outline-success" onclick="markAllAsRead()">
                            <i class="fas fa-check-double me-1"></i> Mark All Read
                        </button>
                    <?php endif; ?>
                </div>

                <div class="notification-list" id="notificationList">
                    <?php
                    // Fetch first 10 notifications for the current receiver (pagination)
                    $allNotificationStmt = $conn->prepare("SELECT * FROM notification WHERE MatricNumber = ? ORDER BY sentTimestamp DESC LIMIT 10");
                    $allNotificationStmt->bind_param("s", $receiverIC);
                    $allNotificationStmt->execute();
                    $allNotificationResult = $allNotificationStmt->get_result();

                    // Also get total count for pagination
                    $countStmt = $conn->prepare("SELECT COUNT(*) as total FROM notification WHERE MatricNumber = ?");
                    $countStmt->bind_param("s", $receiverIC);
                    $countStmt->execute();
                    $countResult = $countStmt->get_result();
                    $totalNotifications = $countResult->fetch_assoc()['total'];

                    if ($allNotificationResult->num_rows > 0):
                        while ($notification = $allNotificationResult->fetch_assoc()):
                            $isUnread = !$notification['isRead'];
                            $notificationType = $notification['notificationType'];
                            $iconClass = '';
                            $bgClass = '';

                            switch($notificationType) {
                                case 'delivery':
                                    $iconClass = 'fas fa-truck text-success';
                                    $bgClass = 'notification-delivery';
                                    break;
                                case 'pickup':
                                    $iconClass = 'fas fa-check-circle text-success';
                                    $bgClass = 'notification-pickup';
                                    break;
                                case 'arrival':
                                    $iconClass = 'fas fa-box text-info';
                                    $bgClass = 'notification-arrival';
                                    break;
                                default:
                                    $iconClass = 'fas fa-info-circle text-primary';
                                    $bgClass = 'notification-info';
                            }
                    ?>
                        <div class="notification-item <?php echo $bgClass; ?> <?php echo $isUnread ? 'unread' : ''; ?>"
                             data-notification-id="<?php echo $notification['notificationID']; ?>">
                            <div class="notification-icon-wrapper">
                                <i class="<?php echo $iconClass; ?>"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-message-header">
                                    <?php if ($isUnread): ?>
                                        <span class="notification-unread-dot"></span>
                                    <?php endif; ?>
                                    <div class="notification-message">
                                        <?php echo htmlspecialchars($notification['messageContent'], ENT_QUOTES); ?>
                                    </div>
                                </div>
                                <div class="notification-meta">
                                    <span class="notification-time">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo date('M d, Y h:i A', strtotime($notification['sentTimestamp'])); ?>
                                    </span>
                                    <?php if ($notification['TrackingNumber']): ?>
                                        <span class="notification-tracking">
                                            <i class="fas fa-barcode me-1"></i>
                                            <?php echo htmlspecialchars($notification['TrackingNumber']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="notification-actions">
                                <div class="notification-buttons">
                                    <?php if ($isUnread): ?>
                                        <button class="btn btn-sm btn-icon-action mark-read-btn"
                                                onclick="markSingleNotificationAsRead(<?php echo $notification['notificationID']; ?>)"
                                                title="Mark as read">
                                            <i class="fas fa-circle"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-icon-action read-status"
                                                disabled
                                                title="Already read">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-icon-action delete-notification-btn"
                                            onclick="deleteNotification(<?php echo $notification['notificationID']; ?>)"
                                            title="Delete notification">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php
                        endwhile;
                    else:
                    ?>
                        <div class="notification-empty">
                            <div class="empty-notification-icon">
                                <i class="fas fa-bell-slash"></i>
                            </div>
                            <h5>No notifications yet</h5>
                            <p>You'll see updates about your parcels here when they arrive.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Load More Button -->
                    <?php if ($totalNotifications > 10): ?>
                        <div class="notification-load-more-container">
                            <button class="btn btn-outline-success btn-sm w-100" onclick="loadMoreNotifications()" id="loadMoreBtn">
                                <i class="fas fa-chevron-down me-2"></i>Load More Notifications
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


        </div>
    </div>

    <!-- Modern Enhanced Footer -->
    <footer class="footer-custom">

        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 mb-md-0">
                    <div class="d-flex align-items-center mb-3">
                        <img src="../assets/Icon Web.ico" alt="PPMS Logo" class="footer-logo">
                        <div>
                            <h5 class="footer-title">Perwira Parcel Management System</h5>
                            <p class="footer-subtitle">Universiti Tun Hussein Onn Malaysia</p>
                        </div>
                    </div>
                    <p class="footer-description">
                        Your trusted partner in parcel management and delivery services.
                        We provide efficient and secure parcel management for UTHM students and staff.
                    </p>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h5 class="footer-section-title">Contact Information</h5>
                    <div class="contact-info">
                        <div class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            info@perwiraparcel.uthm.edu.my
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            +60 11-1589 5859
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            Parit Raja, Johor, Malaysia
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-clock me-2"></i>
                            Mon-Sun: 8:00 AM - 8:00 PM
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-12 mt-4 mt-lg-0">
                    <h5 class="footer-section-title">Location</h5>
                    <div class="ratio ratio-4x3 ppms-footer-map d-none d-md-block">
                        <iframe title="PPMS Location Map" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3987.7123755011366!2d103.09752854533595!3d1.8616741603266367!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31d05c1ddc3a2039%3A0x6e5816f50fb38689!2sKolej%20Kediaman%20Luar%20Kampus%20-%20UTHM!5e0!3m2!1sen!2smy!4v1767879404889!5m2!1sen!2smy" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                    <a class="btn btn-outline-primary w-100 mt-2 d-md-none" href="https://www.google.com/maps?q=Kolej%20Kediaman%20Luar%20Kampus%20-%20UTHM" target="_blank" rel="noopener noreferrer" aria-label="Open PPMS location in Google Maps">
                        <i class="fas fa-map-marked-alt me-2"></i>Open in Google Maps
                    </a>
                </div>
            </div>

            <!-- Delivery Partners Slider Section -->
            <div class="row mt-4 pt-4 delivery-partners" style="border-top: 1px solid rgba(107, 114, 128, 0.1);">
                <div class="col-12">
                    <div class="text-center mb-4">
                        <h6 class="footer-section-title">
                            <i class="fas fa-truck me-2"></i>Trusted Delivery Partners
                        </h6>
                        <p class="text-muted small mb-0">Reliable delivery services across Malaysia</p>
                    </div>

                    <!-- Modern Card-Based Carousel -->
                    <div class="modern-carousel-container">
                        <div class="carousel-viewport">
                            <div class="carousel-track" id="modernCarouselTrack">
                                <!-- Partner Cards - First Set -->
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
                        <button class="carousel-nav carousel-nav-prev" onclick="moveCarousel('prev')" aria-label="Previous partners" style="display: none;">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="carousel-nav carousel-nav-next" onclick="moveCarousel('next')" aria-label="Next partners" style="display: none;">
                            <i class="fas fa-chevron-right"></i>
                        </button>

                        <!-- Elegant Indicators -->
                        <div class="carousel-indicators" style="display: none;">
                            <button class="carousel-indicator active" onclick="goToSlide(0)" aria-label="Go to slide 1"></button>
                            <button class="carousel-indicator" onclick="goToSlide(1)" aria-label="Go to slide 2"></button>
                            <button class="carousel-indicator" onclick="goToSlide(2)" aria-label="Go to slide 3"></button>
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
                                <label class="form-label fw-bold">Status:</label>
                                <p id="viewStatus" class="form-control-plaintext"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Receiver Matric:</label>
                                <p id="viewReceiverIC" class="form-control-plaintext"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Receiver Name:</label>
                                <p id="viewReceiverName" class="form-control-plaintext"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Weight:</label>
                                <p id="viewParcelWeight" class="form-control-plaintext"></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Storage Rack:</label>
                                <p id="viewDeliveryLocation" class="form-control-plaintext"></p>
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
                                <label class="form-label fw-bold">Time Added:</label>
                                <p id="viewTimeAdded" class="form-control-plaintext"></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
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
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- QR Code Generator - Primary CDN -->
    <script src="https://unpkg.com/qrcode@1.5.3/build/qrcode.min.js"></script>
    <!-- Local QR Code Fallback -->
    <script src="../js/qrcode-simple.js"></script>
    <!-- PPMS QR Code Configuration -->
    <script src="../js/qr-config.js"></script>

    <script>
    // Eye Button Handler - Show Parcel Details Modal
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Starting eye button setup...');

        // Find all eye buttons
        const eyeButtons = document.querySelectorAll('.eye-button');
        console.log('👁️ Found eye buttons:', eyeButtons.length);

        // Add click event to each eye button
        eyeButtons.forEach(function(button, index) {
            console.log('🔧 Setting up button', index + 1);

            button.addEventListener('click', function() {
                console.log('👁️ Eye button clicked!');

                // Get data from button attributes
                const tracking = this.getAttribute('data-tracking');
                const status = this.getAttribute('data-status');
                const ic = this.getAttribute('data-ic');
                const receiverName = this.getAttribute('data-receiver-name');
                const weight = this.getAttribute('data-weight');
                const location = this.getAttribute('data-location');
                const date = this.getAttribute('data-date');
                const time = this.getAttribute('data-time');
                const name = this.getAttribute('data-name');

                console.log('📦 Parcel data:', {tracking, status, ic, receiverName, weight, location, date, time, name});

                // Fetch verification data from server
                fetch(`../api/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(tracking)}`, {
                    credentials: 'include'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.parcel) {
                            // Store verification data globally for QR generation
                            if (data.parcel.verificationData) {
                                window.parcelVerificationData = data.parcel.verificationData;
                                console.log('✅ Verification data loaded for QR code');
                            } else {
                                console.warn('⚠️ No verification data available');
                                window.parcelVerificationData = null;
                            }
                        }
                        // Show parcel details modal
                        showParcelDetailsModal({
                            TrackingNumber: tracking,
                            status: status,
                            ic: ic,
                            receiverName: receiverName,
                            weight: weight,
                            deliveryLocation: location,
                            date: date,
                            time: time,
                            name: name,
                            qrGenerated: data.success ? data.parcel.qrGenerated : false,
                            verificationData: data.success ? data.parcel.verificationData : null
                        });
                    })
                    .catch(error => {
                        console.error('Error fetching verification data:', error);
                        // Show modal anyway
                        showParcelDetailsModal({
                            TrackingNumber: tracking,
                            status: status,
                            ic: ic,
                            receiverName: receiverName,
                            weight: weight,
                            deliveryLocation: location,
                            date: date,
                            time: time,
                            name: name,
                            qrGenerated: false,
                            verificationData: null
                        });
                    });
            });
        });
    });

    // Function to show parcel details modal
    function showParcelDetailsModal(parcel) {
        console.log('Showing parcel modal for:', parcel.TrackingNumber);

        // Populate modal with parcel details
        document.getElementById('viewTrackingNumber').textContent = parcel.TrackingNumber || 'N/A';

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

        // Receiver information
        document.getElementById('viewReceiverIC').textContent = parcel.ic || 'N/A';
        document.getElementById('viewReceiverName').textContent = parcel.receiverName || 'N/A';

        document.getElementById('viewParcelWeight').textContent = parcel.weight ? (parcel.weight + ' kg') : 'N/A';
        document.getElementById('viewDeliveryLocation').textContent = parcel.deliveryLocation || 'N/A';
        document.getElementById('viewDateAdded').textContent = parcel.date || 'N/A';
        document.getElementById('viewTimeAdded').textContent = parcel.time ? formatTime(parcel.time) : 'N/A';
        document.getElementById('viewParcelName').textContent = parcel.name || 'Package';

        // Generate QR code for viewing with loading state
        const qrContainer = document.getElementById('viewQRCode');

        // Check if QR has been generated by staff
        if (!parcel.qrGenerated) {
            qrContainer.innerHTML = '<div class="text-center text-warning p-3"><i class="fas fa-hourglass-half fa-2x mb-2"></i><br><strong>QR Code Not Yet Generated</strong><br><small>Staff will generate the QR code for this parcel</small></div>';
            console.log('⏳ QR code not yet generated by staff for parcel:', parcel.TrackingNumber);
            return;
        }

        qrContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Generating QR Code...</div>';

        setTimeout(() => {
            qrContainer.innerHTML = '';

            // Check if QRCode library is available
            if (typeof QRCode === 'undefined') {
                console.warn('QRCode library not immediately available, attempting to load...');

                // Try to load QR library dynamically
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js';
                script.onload = function() {
                    console.log('QRCode library loaded dynamically, retrying generation...');
                    setTimeout(() => generateReceiverQR(qrContainer, parcel), 500);
                };
                script.onerror = function() {
                    console.log('CDN failed, using local fallback');
                    qrContainer.innerHTML = '<div class="text-center text-warning"><i class="fas fa-exclamation-circle"></i> QR Code unavailable</div>';
                };
                document.head.appendChild(script);
                return;
            }

            generateReceiverQR(qrContainer, parcel);
        }, 300);

        // Helper function to generate scannable QR code
        function generateReceiverQR(container, parcelData) {
            try {
                // Build QR payload in scannable format
                const qrPayload = "PPMS|" +
                                 parcelData.TrackingNumber + "|" +
                                 parcelData.MatricNumber + "|" +
                                 (parcelData.receiverName || 'N/A') + "|" +
                                 (parcelData.deliveryLocation || 'N/A') + "|" +
                                 (parcelData.status || 'Pending');

                console.log('QR Payload:', qrPayload);

                // Generate QR code URL using api.qrserver.com
                const encodedPayload = encodeURIComponent(qrPayload);
                const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodedPayload}`;

                console.log('QR Code URL:', qrCodeUrl);

                // Display the QR code image
                container.innerHTML = `<img src="${qrCodeUrl}" alt="QR Code" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">`;

                console.log('✅ Scannable QR code generated successfully!');

            } catch (error) {
                console.error('❌ QR Code generation error:', error);
                container.innerHTML = '<div class="text-center text-danger"><i class="fas fa-exclamation-triangle"></i> QR Code generation failed</div>';
            }
        }

        // Generate scannable QR code for parcel details card (after modal is shown)
        setTimeout(() => {
            const parcelQRContainer = document.getElementById('parcelQRCode');
            if (parcelQRContainer) {
                // Check if QR has been generated by staff
                if (!parcel.qrGenerated) {
                    parcelQRContainer.innerHTML = '<div class="text-center text-warning p-2"><i class="fas fa-hourglass-half"></i><br><small>QR not yet generated</small></div>';
                    console.log('⏳ QR code not yet generated by staff');
                    return;
                }

                try {
                    // Build QR payload in scannable format
                    const qrPayload = "PPMS|" +
                                     parcel.TrackingNumber + "|" +
                                     parcel.MatricNumber + "|" +
                                     (parcel.receiverName || 'N/A') + "|" +
                                     (parcel.deliveryLocation || 'N/A') + "|" +
                                     (parcel.status || 'Pending');

                    // Generate QR code URL using api.qrserver.com
                    const encodedPayload = encodeURIComponent(qrPayload);
                    const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodedPayload}`;

                    // Display the QR code image
                    parcelQRContainer.innerHTML = `<img src="${qrCodeUrl}" alt="QR Code" style="max-width: 100%; height: auto; border-radius: 8px;">`;

                    console.log('✅ Parcel details QR generated successfully');
                } catch (error) {
                    console.error('Parcel details QR generation error:', error);
                    parcelQRContainer.innerHTML = '<div class="text-muted small">QR Code unavailable</div>';
                }
            }
        }, 500);

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
            } catch (error) {
                console.error('Error showing modal:', error);
            }
        } else {
            console.error('Modal element not found!');
        }
    }

    // Inline handler for eye buttons to ensure clicks work after dynamic updates
    function handleEyeButton(buttonEl) {
        if (!buttonEl) return;

        const tracking = buttonEl.getAttribute('data-tracking');
        const status = buttonEl.getAttribute('data-status');
        const ic = buttonEl.getAttribute('data-ic');
        const receiverName = buttonEl.getAttribute('data-receiver-name');
        const weight = buttonEl.getAttribute('data-weight');
        const location = buttonEl.getAttribute('data-location');
        const date = buttonEl.getAttribute('data-date');
        const time = buttonEl.getAttribute('data-time');
        const name = buttonEl.getAttribute('data-name');

        fetch(`../api/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(tracking)}`, {
            credentials: 'include'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.parcel) {
                    if (data.parcel.verificationData) {
                        window.parcelVerificationData = data.parcel.verificationData;
                    } else {
                        window.parcelVerificationData = null;
                    }
                }
                showParcelDetailsModal({
                    TrackingNumber: tracking,
                    status: status,
                    ic: ic,
                    receiverName: receiverName,
                    weight: weight,
                    deliveryLocation: location,
                    date: date,
                    time: time,
                    name: name,
                    qrGenerated: data.success ? data.parcel.qrGenerated : false,
                    verificationData: data.success ? data.parcel.verificationData : null
                });
            })
            .catch(() => {
                showParcelDetailsModal({
                    TrackingNumber: tracking,
                    status: status,
                    ic: ic,
                    receiverName: receiverName,
                    weight: weight,
                    deliveryLocation: location,
                    date: date,
                    time: time,
                    name: name,
                    qrGenerated: false,
                    verificationData: null
                });
            });
    }

    // Helper function to format time
    function formatTime(timeString) {
        if (!timeString) return 'N/A';
        try {
            const date = new Date('1970-01-01T' + timeString);
            return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
        } catch (e) {
            return timeString;
        }
    }

    // Consolidated DOMContentLoaded event listener for better performance
    document.addEventListener('DOMContentLoaded', function() {
        console.log('PPMS Dashboard loaded successfully');
        console.log('showParcelModal function available:', typeof window.showParcelModal);

        // Test if eye buttons exist
        const eyeButtons = document.querySelectorAll('button[onclick*="showParcelModal"]');
        console.log('Found eye buttons:', eyeButtons.length);

        // Load real-time statistics
        loadReceiverStats();

        // Auto-refresh statistics every 30 seconds
        setInterval(loadReceiverStats, 30000);

        // 1. Force input field styling on page load
        const trackingInput = document.getElementById('trackingNumber');
        if (trackingInput) {
            // Force white background and dark text
            trackingInput.style.backgroundColor = '#ffffff';
            trackingInput.style.color = '#2d3748';
            trackingInput.style.border = '2px solid #e2e8f0';

            // Add event listeners to maintain styling
            trackingInput.addEventListener('focus', function() {
                this.style.backgroundColor = '#ffffff';
                this.style.color = '#2d3748';
                this.style.borderColor = '#43e97b';
                this.style.boxShadow = '0 0 0 3px rgba(67, 233, 123, 0.15)';
            });

            trackingInput.addEventListener('blur', function() {
                this.style.backgroundColor = '#ffffff';
                this.style.color = '#2d3748';
                this.style.border = '2px solid #e2e8f0';
            });

            trackingInput.addEventListener('input', function() {
                this.style.backgroundColor = '#ffffff';
                this.style.color = '#2d3748';
            });
        }

        // 2. Generate scannable QR on Parcel Details tab
        (function generateDetailsTabQR() {
            const detailsQR = document.getElementById('parcelQRCode');
            const trackingEl = document.querySelector('.tracking-number');
            if (!detailsQR || !trackingEl) return;

            const tracking = trackingEl.textContent.trim();
            detailsQR.innerHTML = '<div class="text-muted small">Loading QR...</div>';

            fetch(`../api/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(tracking)}`, {
                credentials: 'include'
            })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success && data.parcel) {
                        const parcel = data.parcel;

                        // Check if QR has been generated by staff
                        if (!parcel.qrGenerated) {
                            detailsQR.innerHTML = '<div class="text-center text-warning p-2"><i class="fas fa-hourglass-half"></i><br><small>QR not yet generated by staff</small></div>';
                            console.log('⏳ Details tab: QR code not yet generated by staff');
                            return;
                        }

                        // Build QR payload in scannable format
                        const qrPayload = "PPMS|" +
                                         tracking + "|" +
                                         parcel.MatricNumber + "|" +
                                         (parcel.receiverName || 'N/A') + "|" +
                                         (parcel.deliveryLocation || 'N/A') + "|" +
                                         (parcel.status || 'Pending');

                        // Generate QR code URL using api.qrserver.com
                        const encodedPayload = encodeURIComponent(qrPayload);
                        const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodedPayload}`;

                        // Display the QR code image
                        detailsQR.innerHTML = `<img src="${qrCodeUrl}" alt="QR Code" style="max-width: 100%; height: auto; border-radius: 8px;">`;

                        console.log('✅ Details tab: scannable QR generated successfully');
                    } else {
                        console.warn('⚠️ Details tab: could not fetch parcel data');
                        detailsQR.innerHTML = '<div class="text-muted small">QR Code unavailable</div>';
                    }
                })
                .catch(err => {
                    console.error('Details tab QR fetch error:', err);
                    detailsQR.innerHTML = '<div class="text-muted small">QR Code unavailable</div>';
                });
        })();

        // 3. Mark individual notification as read when clicked
        // 2. Mark individual notification as read when clicked
        document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.notificationId;

                // Mark as read visually
                this.classList.remove('unread');
                const indicator = this.querySelector('.notification-unread-indicator');
                if (indicator) {
                    indicator.remove();
                }

                // Update badge count
                updateNotificationBadges();

                // Send AJAX request to mark as read
                fetch('../api/mark-notifications-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        action: 'mark_single_read',
                        notificationId: notificationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error('Failed to mark notification as read');
                    }
                })
                .catch(error => {
                    console.error('Error marking notification as read:', error);
                });
            });
        });

        // 4. Enhanced form submission with loading state
        const trackForm = document.querySelector('form[action="../api/track-parcel.php"]');
        if (trackForm) {
            trackForm.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;

                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Tracking...';
                submitBtn.disabled = true;

                // Re-enable button after a delay (in case of redirect issues)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            });
        }

        // 5. Force Font Awesome to load properly
        setTimeout(function() {
            // Check if Font Awesome is loaded
            const testIcon = document.createElement('i');
            testIcon.className = 'fas fa-test';
            testIcon.style.position = 'absolute';
            testIcon.style.left = '-9999px';
            document.body.appendChild(testIcon);

            const iconStyle = window.getComputedStyle(testIcon, ':before');
            if (!iconStyle.content || iconStyle.content === 'none') {
                // Font Awesome not loaded, load it manually
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css';
                document.head.appendChild(link);
            } else {
                console.log('Font Awesome loaded successfully');
            }

            document.body.removeChild(testIcon);
        }, 100);
    });

    // Modern sleek logout with enhanced SweetAlert
    function logout() {
        Swal.fire({
            title: '<div style="margin-bottom: 1.5rem;"><div style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #43e97b 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3); position: relative; overflow: hidden; border: none !important;"><div style="position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%); animation: shimmer 2s infinite;"></div><svg style="width: 32px; height: 32px; z-index: 2; position: relative;" viewBox="0 0 24 24" fill="none"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg></div><div style="font-size: 1.5rem; font-weight: 700; color: #1f2937;">Ready to leave?</div></div>',
            html: '<div style="color: #6b7280; font-size: 0.95rem; line-height: 1.5;">You\'ll be securely logged out and redirected to the login page.</div>',
            icon: false,
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#e5e7eb',
            confirmButtonText: '<i class="fas fa-sign-out-alt me-2"></i>Yes, logout',
            cancelButtonText: '<i class="fas fa-times me-2"></i>Stay here',
            buttonsStyling: false,
            customClass: {
                popup: 'modern-logout-popup',
                confirmButton: 'modern-confirm-btn',
                cancelButton: 'modern-cancel-btn'
            },
            background: '#ffffff',
            backdrop: 'rgba(0, 0, 0, 0.4)',
            allowOutsideClick: false,
            allowEscapeKey: true,
            focusConfirm: false,
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '<div style="margin-bottom: 1rem;"><div style="width: 60px; height: 60px; background: linear-gradient(135deg, #667eea 0%, #43e97b 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; box-shadow: 0 4px 20px rgba(102, 126, 234, 0.4); border: none !important;"><div class="spinner-border text-white" style="width: 1.8rem; height: 1.8rem; border-width: 3px;" role="status"></div></div><div style="font-size: 1.25rem; font-weight: 600; color: #1f2937;">Logging out...</div></div>',
                    html: '<div style="color: #6b7280; font-size: 0.9rem;">Securing your session</div>',
                    icon: false,
                    timer: 1200,
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    customClass: {
                        popup: 'modern-loading-popup'
                    },
                    background: '#ffffff',
                    backdrop: 'rgba(0, 0, 0, 0.6)'
                }).then(() => {
                    window.location.href = '../api/logout.php';
                });
            }
        });
    }

    // Login success notification
    if (window.location.search.includes('login=success')) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'Welcome back!',
            text: 'Login successful',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        // Clean URL
        setTimeout(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        }, 1000);
    }

    // Auto-switch to details tab if tracking was successful
    if (window.location.search.includes('tracking_success=1')) {
        setTimeout(() => {
            const detailsTab = new bootstrap.Tab(document.getElementById('details-tab'));
            detailsTab.show();

            // Show success notification
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Parcel Found!',
                text: 'Displaying parcel details',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        }, 100);

        // Clean URL
        setTimeout(() => {
            window.history.replaceState({}, document.title, window.location.pathname);
        }, 2000);
    }

    // QR Code generation is now handled in the consolidated DOMContentLoaded listener above

    // Print function removed as per user request



    // View parcel details
    function viewParcelDetails(trackingNumber) {
        // Switch to tracking tab and fill the tracking number
        const trackingTab = new bootstrap.Tab(document.getElementById('tracking-tab'));
        trackingTab.show();

        setTimeout(() => {
            const trackingInput = document.getElementById('trackingNumber');
            if (trackingInput) {
                trackingInput.value = trackingNumber;
                trackingInput.focus();
            }
        }, 100);
    }



    // Simple test function - we'll add QR later once basic modal works
    function testEyeButton() {
        alert('Eye button is working!');
    }

    // Missing functions that are called by onclick events
    function sortHistory(field, order) {
        console.log('Sorting history by', field, order);
        // Simple sorting implementation
        const table = document.getElementById('historyTable');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            let aVal, bVal;
            if (field === 'date') {
                aVal = new Date(a.dataset.date);
                bVal = new Date(b.dataset.date);
            } else if (field === 'tracking') {
                aVal = (a.dataset.tracking || '').toLowerCase();
                bVal = (b.dataset.tracking || '').toLowerCase();
                return order === 'asc' ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
            }

            if (order === 'asc') {
                return aVal - bVal;
            } else {
                return bVal - aVal;
            }
        });

        // Clear and re-append sorted rows
        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));

        // Close the dropdown after sorting
        const sortDropdown = document.getElementById('sortDropdown');
        if (sortDropdown) {
            const dropdownInstance = bootstrap.Dropdown.getInstance(sortDropdown);
            if (dropdownInstance) {
                dropdownInstance.hide();
            }
        }
    }

    function filterHistory(status) {
        console.log('Filtering history by', status);
        currentStatusFilter = status; // Update the global status filter
        applyReceiverHistorySearch(); // Re-apply search with new filter

        // Close the dropdown after filtering
        const sortDropdown = document.getElementById('sortDropdown');
        if (sortDropdown) {
            const dropdownInstance = bootstrap.Dropdown.getInstance(sortDropdown);
            if (dropdownInstance) {
                dropdownInstance.hide();
            }
        }
    }

    function refreshHistory() {
        console.log('Refreshing history');

        // Show loading toast
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Refreshing...',
            showConfirmButton: false,
            timer: 1500
        });

        // Fetch fresh history data via AJAX
        fetch('../api/receiver-get-history.php', {
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Refresh response:', data);
            if (data.success) {
                // Update table with fresh data
                const table = document.getElementById('historyTable');
                if (table) {
                    const tbody = table.querySelector('tbody');
                    tbody.innerHTML = '';

                    // Rebuild table rows
                    data.parcels.forEach(parcel => {
                        try {
                            const row = document.createElement('tr');

                            const statusBadge = parcel.status === 'Pending'
                                ? '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>'
                                : parcel.status === 'Retrieved'
                                ? '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Retrieved</span>'
                                : `<span class="badge bg-secondary">${parcel.status}</span>`;

                            const dateTime = new Date(parcel.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric'});
                            const timeStr = parcel.time ? new Date('2000-01-01 ' + parcel.time).toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'}) : '';

                            row.innerHTML = `
                                <td class="px-4 py-3"><span class="fw-bold text-primary">${parcel.TrackingNumber}</span></td>
                                <td class="px-4 py-3">
                                    <div>${dateTime}${timeStr ? '<br><small class="text-muted">' + timeStr + '</small>' : ''}</div>
                                </td>
                                <td class="px-4 py-3">${statusBadge}</td>
                                <td class="px-4 py-3"><i class="fas fa-weight-hanging me-1 text-muted"></i>${parcel.weight} kg</td>
                                <td class="px-4 py-3"><i class="fas fa-map-marker-alt me-1 text-muted"></i>${parcel.deliveryLocation || 'Not specified'}</td>
                                <td class="px-4 py-3">
                                    <div class="ppms-history-actions">
                                        <button type="button" class="ppms-action-btn ppms-action-view-receiver eye-button"
                                                data-tracking="${parcel.TrackingNumber}"
                                                data-status="${parcel.status}"
                                                data-ic="${parcel.MatricNumber}"
                                                data-receiver-name="${parcel.receiverName || 'N/A'}"
                                                data-weight="${parcel.weight || 'N/A'}"
                                                data-location="${parcel.deliveryLocation || 'N/A'}"
                                                data-date="${parcel.date}"
                                                data-time="${parcel.time}"
                                                data-name="${parcel.name || ''}"
                                                onclick="handleEyeButton(this)"
                                                title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="ppms-action-btn ppms-action-download-receiver" onclick="downloadHistoryQR('${parcel.TrackingNumber}')" title="Download QR">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </div>
                                </td>
                            `;

                            // Set data attributes AFTER innerHTML to preserve them
                            row.setAttribute('data-status', parcel.status);
                            row.setAttribute('data-date', parcel.date);
                            row.setAttribute('data-tracking', parcel.TrackingNumber);
                            row.setAttribute('data-matric', parcel.MatricNumber);
                            row.setAttribute('data-receiver-name', parcel.receiverName || 'N/A');
                            row.setAttribute('data-location', parcel.deliveryLocation || 'N/A');

                            tbody.appendChild(row);
                        } catch(rowError) {
                            console.error('Error creating row for parcel:', parcel, rowError);
                        }
                    });
                }

                // Update statistics
                try { updateStatistics(); } catch(e){ console.error('Error updating statistics:', e); }

                // Re-attach event listeners to new eye buttons
                try {
                    const eyeButtons = document.querySelectorAll('.eye-button');
                    eyeButtons.forEach(function(button) {
                        button.addEventListener('click', function() {
                            const tracking = this.getAttribute('data-tracking');
                            const status = this.getAttribute('data-status');
                            const ic = this.getAttribute('data-ic');
                            const receiverName = this.getAttribute('data-receiver-name');
                            const weight = this.getAttribute('data-weight');
                            const location = this.getAttribute('data-location');
                            const date = this.getAttribute('data-date');
                            const time = this.getAttribute('data-time');
                            const name = this.getAttribute('data-name');

                            // Fetch verification data from server
                            fetch(`../api/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(tracking)}`, {
                                credentials: 'include'
                            })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success && data.parcel) {
                                        if (data.parcel.verificationData) {
                                            window.parcelVerificationData = data.parcel.verificationData;
                                        } else {
                                            window.parcelVerificationData = null;
                                        }
                                    }
                                    // Show parcel details modal
                                    showParcelDetailsModal({
                                        TrackingNumber: tracking,
                                        status: status,
                                        ic: ic,
                                        receiverName: receiverName,
                                        weight: weight,
                                        deliveryLocation: location,
                                        date: date,
                                        time: time,
                                        name: name,
                                        qrGenerated: data.success ? data.parcel.qrGenerated : false,
                                        verificationData: data.success ? data.parcel.verificationData : null
                                    });
                                })
                                .catch(error => {
                                    console.error('Error fetching verification data:', error);
                                    // Show modal anyway
                                    showParcelDetailsModal({
                                        TrackingNumber: tracking,
                                        status: status,
                                        ic: ic,
                                        receiverName: receiverName,
                                        weight: weight,
                                        deliveryLocation: location,
                                        date: date,
                                        time: time,
                                        name: name,
                                        qrGenerated: false,
                                        verificationData: null
                                    });
                                });
                        });
                    });
                } catch(e) { console.error('Error attaching eye button listeners:', e); }

                // Reset search and filter
                const searchInput = document.getElementById('receiverHistorySearchInput');
                if (searchInput) searchInput.value = '';
                currentStatusFilter = 'all';

                // Show success message
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Refreshed!',
                    text: 'Parcel history has been refreshed.',
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                throw new Error(data.message || 'Failed to refresh');
            }
        })
        .catch(error => {
            console.error('Error refreshing history:', error);
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Error!',
                text: 'Failed to refresh. Please try again.',
                showConfirmButton: false,
                timer: 1500
            });
        });
    }

    // Print Receiver History Function
    function printReceiverHistory() {
        const printWindow = window.open('', '_blank');
        const historyData = filteredHistory; // Use the filtered history data
        
        let tableRows = '';
        historyData.forEach((parcel, index) => {
            // Format status for display
            let statusText = parcel.status;
            if (parcel.status === 'Arrived') statusText = 'Arrived';
            else if (parcel.status === 'Collected') statusText = 'Collected';
            else if (parcel.status === 'Overdue') statusText = 'Overdue';
            
            tableRows += `<tr>
                <td style="text-align: center;">${index + 1}</td>
                <td>${parcel.TrackingNumber}</td>
                <td>${parcel.MatricNumber}</td>
                <td>${parcel.receiverName || 'N/A'}</td>
                <td style="text-align: center;">${statusText}</td>
                <td>${parcel.deliveryLocation || 'N/A'}</td>
                <td style="text-align: center;">${parcel.date}</td>
            </tr>`;
        });
        
        const currentDate = new Date().toLocaleString('en-MY', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Parcel History Report</title>
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { 
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                        padding: 30px;
                        color: #333;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        border-bottom: 3px solid #28a745;
                        padding-bottom: 20px;
                    }
                    .header h1 {
                        color: #28a745;
                        font-size: 28px;
                        margin-bottom: 10px;
                        font-weight: 700;
                    }
                    .header .subtitle {
                        color: #666;
                        font-size: 14px;
                        margin-bottom: 5px;
                    }
                    .header .date {
                        color: #999;
                        font-size: 12px;
                        font-style: italic;
                    }
                    .info {
                        margin-bottom: 20px;
                        padding: 15px;
                        background-color: #f8f9fa;
                        border-left: 4px solid #28a745;
                        border-radius: 4px;
                    }
                    .info p {
                        margin: 5px 0;
                        font-size: 14px;
                    }
                    .info strong {
                        color: #28a745;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    thead {
                        background-color: #28a745;
                        color: white;
                    }
                    th {
                        padding: 14px 12px;
                        text-align: left;
                        font-weight: 600;
                        font-size: 13px;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                    td {
                        padding: 12px;
                        border-bottom: 1px solid #e9ecef;
                        font-size: 13px;
                    }
                    tbody tr:hover {
                        background-color: #f8f9fa;
                    }
                    tbody tr:nth-child(even) {
                        background-color: #fafafa;
                    }
                    .footer {
                        margin-top: 40px;
                        text-align: center;
                        font-size: 12px;
                        color: #999;
                        border-top: 2px solid #e9ecef;
                        padding-top: 20px;
                    }
                    @media print {
                        body { padding: 20px; }
                        .header { page-break-after: avoid; }
                        table { page-break-inside: auto; }
                        tr { page-break-inside: avoid; page-break-after: auto; }
                        thead { display: table-header-group; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>PERWIRA PARCEL MANAGEMENT SYSTEM</h1>
                    <div class="subtitle">Parcel History Report - Receiver Dashboard</div>
                    <div class="date">Generated on: ${currentDate}</div>
                </div>
                
                <div class="info">
                    <p><strong>Total Records:</strong> ${historyData.length}</p>
                    <p><strong>Report Type:</strong> Complete Parcel History</p>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px; text-align: center;">#</th>
                            <th style="width: 140px;">Tracking Number</th>
                            <th style="width: 120px;">Matric Number</th>
                            <th>Receiver Name</th>
                            <th style="width: 100px; text-align: center;">Status</th>
                            <th>Delivery Location</th>
                            <th style="width: 110px; text-align: center;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                </table>
                
                <div class="footer">
                    <p>&copy; 2025 Perwira Parcel Management System (PPMS)</p>
                    <p>Universiti Tun Hussein Onn Malaysia (UTHM)</p>
                </div>
                
                <script>
                    window.onload = function() {
                        window.print();
                    };
                <\/script>
            </body>
            </html>
        `);
        
        printWindow.document.close();
    }



    // Notification panel functions
    function markAllAsRead() {
        Swal.fire({
            title: 'Mark all as read?',
            text: 'This will mark all notifications as read.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#43e97b',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, mark all',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX call to mark all notifications as read
                fetch('../api/mark-notifications-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({ action: 'mark_all_read' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove unread indicators from notification panel
                        document.querySelectorAll('.notification-item.unread').forEach(item => {
                            item.classList.remove('unread');
                        });
                        document.querySelectorAll('.notification-unread-indicator').forEach(indicator => {
                            indicator.remove();
                        });

                        // Remove unread indicators from dropdown
                        document.querySelectorAll('.notification-dropdown-item.unread').forEach(item => {
                            item.classList.remove('unread');
                        });
                        document.querySelectorAll('.notification-unread-dot').forEach(dot => {
                            dot.remove();
                        });

                        // Remove notification badge
                        const badge = document.querySelector('.notification-badge');
                        if (badge) {
                            badge.remove();
                        }

                        // Remove tab notification badge
                        const tabBadge = document.querySelector('.tab-notification-badge');
                        if (tabBadge) {
                            tabBadge.remove();
                        }

                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'All notifications marked as read',
                            showConfirmButton: false,
                            timer: 2000
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        });
    }

    function loadMoreNotifications() {
        const notificationList = document.getElementById('notificationList');
        const loadMoreBtn = document.getElementById('loadMoreBtn');

        if (!loadMoreBtn) return;

        // Get current offset (number of notifications already loaded)
        const currentCount = document.querySelectorAll('.notification-item').length;

        // Show loading state
        loadMoreBtn.disabled = true;
        loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';

        // Fetch more notifications
        fetch('../api/load-more-notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            credentials: 'include',
            body: 'offset=' + currentCount
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications.length > 0) {
                // Build HTML for new notifications
                let html = '';
                data.notifications.forEach(notification => {
                    const isUnread = notification.isRead === 0;
                    const bgClass = isUnread ? 'bg-light' : '';

                    // Determine icon based on notification type
                    let iconClass = 'fas fa-bell';
                    if (notification.notificationType === 'arrival') {
                        iconClass = 'fas fa-box';
                    } else if (notification.notificationType === 'delivery') {
                        iconClass = 'fas fa-check-circle';
                    } else if (notification.notificationType === 'pickup' || notification.notificationType === 'parcel_retrieved') {
                        iconClass = 'fas fa-hand-holding-box';
                    } else if (notification.notificationType === 'qr_generated') {
                        iconClass = 'fas fa-qrcode';
                    }

                    html += `
                        <div class="notification-item ${bgClass} ${isUnread ? 'unread' : ''}" data-notification-id="${notification.notificationID}">
                            <div class="notification-icon-wrapper">
                                <i class="${iconClass}"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-message-header">
                                    ${isUnread ? '<span class="notification-unread-dot"></span>' : ''}
                                    <div class="notification-message">
                                        ${notification.messageContent}
                                    </div>
                                </div>
                                <div class="notification-meta">
                                    <span class="notification-time">
                                        <i class="fas fa-clock me-1"></i>
                                        ${new Date(notification.sentTimestamp).toLocaleDateString('en-US', {month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit'})}</span>
                                    ${notification.TrackingNumber ? `<span class="notification-tracking"><i class="fas fa-barcode me-1"></i>${notification.TrackingNumber}</span>` : ''}
                                </div>
                            </div>
                            <div class="notification-actions">
                                <div class="notification-buttons">
                                    ${isUnread ? `
                                        <button class="btn btn-sm btn-icon-action mark-read-btn"
                                                onclick="markSingleNotificationAsRead(${notification.notificationID})"
                                                title="Mark as read">
                                            <i class="fas fa-circle"></i>
                                        </button>
                                    ` : `
                                        <button class="btn btn-sm btn-icon-action read-status"
                                                disabled
                                                title="Already read">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    `}
                                    <button class="btn btn-sm btn-icon-action delete-notification-btn"
                                            onclick="deleteNotification(${notification.notificationID})"
                                            title="Delete notification">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });

                // Insert before load more button
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = html;
                while (tempDiv.firstChild) {
                    notificationList.insertBefore(tempDiv.firstChild, loadMoreBtn.parentElement);
                }

                // Update or remove load more button
                if (data.hasMore) {
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down me-2"></i>Load More Notifications';
                } else {
                    loadMoreBtn.parentElement.remove();
                }

                // Re-attach click handlers to new notifications
                attachNotificationHandlers();

            } else {
                loadMoreBtn.parentElement.remove();
            }
        })
        .catch(error => {
            console.error('Error loading more notifications:', error);
            loadMoreBtn.disabled = false;
            loadMoreBtn.innerHTML = '<i class="fas fa-chevron-down me-2"></i>Load More Notifications';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load more notifications'
            });
        });
    }

    function attachNotificationHandlers() {
        // Attach click handlers to newly loaded notifications
        document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.closest('.btn-link')) return; // Don't trigger on button clicks
                const notificationId = this.dataset.notificationId;
                markNotificationAsRead(notificationId);
            });
        });
    }

    // Notification click handling is now in the consolidated DOMContentLoaded listener above

    // Load real-time receiver statistics
    function loadReceiverStats() {
        fetch('../api/receiver-get-stats.php', {
            credentials: 'include'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animate number updates
                    animateNumber('totalParcels', data.totalParcels || 0);
                    animateNumber('pendingParcels', data.pendingParcels || 0);
                    animateNumber('retrievedParcels', data.retrievedParcels || 0);
                } else {
                    console.error('Error loading receiver stats:', data.message);
                }
            })
            .catch(error => {
                console.error('Error loading receiver stats:', error);
            });
    }

    // Animate number changes
    function animateNumber(elementId, targetValue) {
        const element = document.getElementById(elementId);
        if (!element) return;

        const currentValue = parseInt(element.textContent) || 0;
        const increment = targetValue > currentValue ? 1 : -1;
        const duration = 500; // 500ms animation
        const steps = Math.abs(targetValue - currentValue);
        const stepDuration = steps > 0 ? duration / steps : 0;

        if (steps === 0) return; // No change needed

        let current = currentValue;
        const timer = setInterval(() => {
            current += increment;
            element.textContent = current;

            if (current === targetValue) {
                clearInterval(timer);
            }
        }, stepDuration);
    }

    // Update statistics (fallback for filtering)
    function updateStatistics() {
        const table = document.getElementById('historyTable');
        if (!table) return;

        const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
        let pendingCount = 0;
        let retrievedCount = 0;

        visibleRows.forEach(row => {
            const status = row.dataset.status;
            if (status === 'Pending') {
                pendingCount++;
            } else if (status === 'Retrieved') {
                retrievedCount++;
            }
        });

        const totalElement = document.getElementById('totalParcels');
        const pendingElement = document.getElementById('pendingParcels');
        const retrievedElement = document.getElementById('retrievedParcels');

        if (totalElement) totalElement.textContent = visibleRows.length;
        if (pendingElement) pendingElement.textContent = pendingCount;
        if (retrievedElement) retrievedElement.textContent = retrievedCount;
    }

    // Debounce timer for receiver search
    let receiverSearchDebounceTimer = null;
    let currentStatusFilter = 'all'; // Track current status filter

    // --- Client-side Search (Receiver History) ---
    function applyReceiverHistorySearch(){
        clearTimeout(receiverSearchDebounceTimer);
        receiverSearchDebounceTimer = setTimeout(() => {
            const input = document.getElementById('receiverHistorySearchInput');
            const noResults = document.getElementById('receiverHistoryNoResults');
            const table = document.getElementById('historyTable');
            if (!input || !table) return;

            const q = (input.value || '').toLowerCase().trim();

            const rows = Array.from(table.querySelectorAll('tbody tr'));
            let anyVisible = false;
            rows.forEach(row => {
                const tracking = (row.dataset.tracking || '').toLowerCase();
                const matric = (row.dataset.matric || '').toLowerCase();
                const receiverName = (row.dataset['receiver-name'] || '').toLowerCase();
                const location = (row.dataset.location || '').toLowerCase();
                const name = (row.querySelector('[data-name]')?.getAttribute('data-name') || '').toLowerCase();
                const status = row.dataset.status || '';

                // Check if matches search query
                const matchesSearch = !q || [tracking, matric, receiverName, location, name].some(v => v.includes(q));

                // Check if matches status filter
                const matchesStatus = currentStatusFilter === 'all' || status === currentStatusFilter;

                // Show row only if it matches both search and status filter
                const shouldShow = matchesSearch && matchesStatus;
                row.style.display = shouldShow ? '' : 'none';
                if (shouldShow) anyVisible = true;
            });

            // Update stats and no-results message
            try { updateStatistics(); } catch(e){}
            if (noResults) noResults.classList.toggle('d-none', anyVisible);
        }, 300);
    }

    document.addEventListener('DOMContentLoaded', function(){
        const si = document.getElementById('receiverHistorySearchInput');
        if (si){ si.addEventListener('input', applyReceiverHistorySearch); }

        const historyTabBtn = document.getElementById('history-tab');
        if (historyTabBtn){
            historyTabBtn.addEventListener('shown.bs.tab', function(){
                const inp = document.getElementById('receiverHistorySearchInput');
                if (inp) inp.value = '';
                const noRes = document.getElementById('receiverHistoryNoResults');
                if (noRes) noRes.classList.add('d-none');
                try { filterHistory('all'); } catch(e){}
            });
        }

        // If history is already the active tab on load, default to showing all statuses
        const historyPane = document.getElementById('history');
        if (historyPane && historyPane.classList.contains('active')){
            try { filterHistory('all'); } catch(e){}
        }
    });

    // Form submission handling is now in the consolidated DOMContentLoaded listener above

    // Mark individual notification as read from dropdown
    function markNotificationAsRead(notificationId) {
        // Mark as read visually
        const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
        if (notificationItem) {
            notificationItem.classList.remove('unread');
            const dot = notificationItem.querySelector('.notification-unread-dot');
            if (dot) {
                dot.remove();
            }
        }

        // Update badge count
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            let count = parseInt(badge.textContent) - 1;
            if (count <= 0) {
                badge.remove();
            } else {
                badge.textContent = count;
            }
        }

        // Update tab badge count
        const tabBadge = document.querySelector('.tab-notification-badge');
        if (tabBadge) {
            let count = parseInt(tabBadge.textContent) - 1;
            if (count <= 0) {
                tabBadge.remove();
            } else {
                tabBadge.textContent = count;
            }
        }

        // AJAX call to mark as read in database
        fetch('../api/mark-notifications-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                action: 'mark_single_read',
                notificationId: notificationId
            })
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Mark single notification as read from main panel
    function markSingleNotificationAsRead(notificationId) {
        // Mark as read visually in main panel
        const notificationItem = document.querySelector(`.notification-item[data-notification-id="${notificationId}"]`);
        if (notificationItem) {
            notificationItem.classList.remove('unread');

            // Replace button with "Read" status
            const actionsDiv = notificationItem.querySelector('.notification-actions');
            if (actionsDiv) {
                actionsDiv.innerHTML = `
                    <span class="text-muted small">
                        <i class="fas fa-check-circle me-1"></i>Read
                    </span>
                `;
            }
        }

        // Also update dropdown if exists
        const dropdownItem = document.querySelector(`.notification-dropdown-item[data-notification-id="${notificationId}"]`);
        if (dropdownItem) {
            dropdownItem.classList.remove('unread');
            const dot = dropdownItem.querySelector('.notification-unread-dot');
            if (dot) {
                dot.remove();
            }
            const actions = dropdownItem.querySelector('.notification-dropdown-actions');
            if (actions) {
                actions.innerHTML = '';
            }
        }

        // Update badge counts
        updateNotificationBadges();

        // AJAX call to mark as read in database
        fetch('../api/mark-notifications-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            credentials: 'include',
            body: JSON.stringify({
                action: 'mark_single_read',
                notificationId: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success toast
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Marked as read',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Helper function to update notification badges
    function updateNotificationBadges() {
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            let count = parseInt(badge.textContent) - 1;
            if (count <= 0) {
                badge.remove();
            } else {
                badge.textContent = count;
            }
        }

        const tabBadge = document.querySelector('.tab-notification-badge');
        if (tabBadge) {
            let count = parseInt(tabBadge.textContent) - 1;
            if (count <= 0) {
                tabBadge.remove();
            } else {
                tabBadge.textContent = count;
            }
        }
    }

    // Delete notification function
    function deleteNotification(notificationId) {
        Swal.fire({
            title: 'Delete Notification?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // AJAX call to delete notification
                fetch('../api/delete-notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        notificationId: notificationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove notification from DOM
                        const notificationItem = document.querySelector(`.notification-item[data-notification-id="${notificationId}"]`);
                        if (notificationItem) {
                            notificationItem.style.transition = 'all 0.3s ease';
                            notificationItem.style.opacity = '0';
                            notificationItem.style.transform = 'translateX(100%)';
                            setTimeout(() => {
                                notificationItem.remove();
                            }, 300);
                        }

                        // Remove from dropdown if exists
                        const dropdownItem = document.querySelector(`.notification-dropdown-item[data-notification-id="${notificationId}"]`);
                        if (dropdownItem) {
                            dropdownItem.remove();
                        }

                        // Update badge counts if it was unread
                        if (notificationItem && notificationItem.classList.contains('unread')) {
                            updateNotificationBadges();
                        }

                        // Show success message
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Notification deleted',
                            showConfirmButton: false,
                            timer: 2000
                        });

                        // Check if no notifications left and try to load more if available
                        setTimeout(() => {
                            const remainingNotifications = document.querySelectorAll('.notification-item');
                            const loadMoreBtn = document.getElementById('loadMoreBtn');

                            if (remainingNotifications.length === 0) {
                                // Show empty state
                                const notificationList = document.querySelector('.notification-list');
                                if (notificationList) {
                                    notificationList.innerHTML = `
                                        <div class="notification-empty">
                                            <div class="empty-notification-icon">
                                                <i class="fas fa-bell-slash"></i>
                                            </div>
                                            <h5>No notifications yet</h5>
                                            <p>You'll see updates about your parcels here when they arrive.</p>
                                        </div>
                                    `;
                                }
                            } else if (loadMoreBtn && remainingNotifications.length < 5) {
                                // Auto-load more if we have few notifications left and more are available
                                loadMoreNotifications();
                            }
                        }, 400);

                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete notification'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Network error occurred'
                    });
                });
            }
        });
    }

    // Show all notifications (switch to notifications tab)
    function showAllNotifications() {
        // Close dropdown
        const dropdown = bootstrap.Dropdown.getInstance(document.getElementById('notificationDropdown'));
        if (dropdown) {
            dropdown.hide();
        }

        // Switch to notifications tab
        const notificationsTab = new bootstrap.Tab(document.getElementById('notifications-tab'));
        notificationsTab.show();

        // Show toast notification
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'All Notifications',
            text: 'Switched to notifications tab',
            showConfirmButton: false,
            timer: 2000
        });
    }

    // Font Awesome loading is now handled in the consolidated DOMContentLoaded listener above
    </script>

    <!-- External CSS files now handle all styling -->


    <script>
    // CSS Scroll-Snap Carousel with Navigation
    let currentCarouselIndex = 0;
    let isAutoSliding = false; // Carousel is static by default
    let autoSlideInterval;
    let cardsPerView = 3;
    let originalCardsCount = 0; // Set dynamically from DOM

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

        originalCardsCount = originals.length;

        if (track.dataset.infiniteReady === '1') return;

        carouselBaseWidth = track.scrollWidth;

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

        requestAnimationFrame(() => {
            const cards = track.querySelectorAll('.partner-card');
            const gap = parseInt(window.getComputedStyle(track).gap) || 0;
            const firstCard = cards[0];
            carouselCardWithGap = (firstCard ? firstCard.offsetWidth : 0) + gap;
            carouselBaseOffset = cards[cloneCount] ? cards[cloneCount].offsetLeft : 0;
            viewport.scrollLeft = carouselBaseOffset;
            carouselIsInfiniteReady = true;
            track.dataset.infiniteReady = '1';
            updateCarouselIndicators();
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
            const walk = (x - startX) * 2; // Scroll speed multiplier
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

        // Set initial cursor style
        viewport.style.cursor = 'grab';
    }

    // Drag functionality is initialized in the consolidated DOMContentLoaded listener

    // Function to format Matric number (8 digits, no formatting needed)
    function formatICNumber(matricNumber) {
        if (!matricNumber || matricNumber.length !== 8) {
            return matricNumber; // Return as-is if not 8 digits
        }

        // Matric numbers are displayed as-is (8 digits)
        return matricNumber;
    }

    // Format Matric numbers on page load
    document.addEventListener('DOMContentLoaded', function() {
        const matricDisplay = document.getElementById('receiverICDisplay');
        if (matricDisplay) {
            const matricNumber = matricDisplay.textContent.trim();
            matricDisplay.textContent = formatICNumber(matricNumber);
        }

    });

    // Enlarge QR code for receiver - Sleek Modal
    function enlargeReceiverQR() {
        const qrContainer = document.getElementById('parcelQRCode');
        if (!qrContainer) {
            Swal.fire({
                icon: 'error',
                title: 'QR Container Not Found',
                text: 'QR code container is not available.',
                confirmButtonColor: '#43e97b'
            });
            console.error('QR Container not found');
            return;
        }

        let img = qrContainer.querySelector('img');

        // If no image found, try to wait a moment for it to load
        if (!img) {
            console.warn('QR image not found, waiting for it to load...');
            setTimeout(() => {
                img = qrContainer.querySelector('img');
                if (!img) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'No QR Code',
                        text: 'QR code is not available for this parcel. Please wait for it to load.',
                        confirmButtonColor: '#43e97b'
                    });
                    return;
                }
                enlargeQRWithImage(img);
            }, 500);
            return;
        }

        enlargeQRWithImage(img);
    }

    function enlargeQRWithImage(img) {

        // Get higher resolution QR code for enlargement
        let qrImageData = img.src;

        // If using api.qrserver.com, upgrade to larger size for better quality
        if (qrImageData.includes('api.qrserver.com')) {
            qrImageData = qrImageData.replace(/size=\d+x\d+/, 'size=600x600');
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
            customClass: {
                popup: 'enlarged-qr-popup'
            },
            didOpen: (modal) => {
                // Create dark overlay
                const backdrop = document.querySelector('.swal2-container');
                if (backdrop) {
                    backdrop.style.background = 'rgba(0, 0, 0, 0.9)';
                    backdrop.style.backdropFilter = 'blur(5px)';
                }

                // Style the popup
                modal.style.background = 'transparent';
                modal.style.boxShadow = 'none';

                // Create close button
                const closeBtn = document.createElement('button');
                closeBtn.innerHTML = '<i class="fas fa-times"></i>';
                closeBtn.setAttribute('aria-label', 'Close');
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
                    closeBtn.style.background = '#f87171';
                    closeBtn.style.color = 'white';
                };
                closeBtn.onmouseout = () => {
                    closeBtn.style.transform = 'scale(1) rotate(0deg)';
                    closeBtn.style.background = 'white';
                    closeBtn.style.color = '#1f2937';
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

    // Download QR code for receiver (direct download, no popup page)
    function downloadReceiverQR() {
        const trackingNumber = document.querySelector('.tracking-number')?.textContent.trim();
        if (!trackingNumber) {
            Swal.fire({
                icon: 'warning',
                title: 'No Parcel Selected',
                text: 'Please track a parcel first.',
                confirmButtonColor: '#43e97b'
            });
            return;
        }

        fetch(`../api/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(trackingNumber)}`, {
            credentials: 'include'
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success || !data.parcel) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not fetch QR data. Please try again.',
                    confirmButtonColor: '#43e97b'
                });
                return;
            }

            if (!data.parcel.qrGenerated) {
                Swal.fire({
                    icon: 'warning',
                    title: 'QR Not Available',
                    text: 'QR code has not been generated by staff yet.',
                    confirmButtonColor: '#43e97b'
                });
                return;
            }

            if (!data.parcel.qrExists) {
                Swal.fire({
                    icon: 'warning',
                    title: 'QR File Missing',
                    text: 'QR code exists, but the image file is missing. Please contact staff to regenerate it.',
                    confirmButtonColor: '#43e97b'
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
                confirmButtonColor: '#43e97b'
            });
        });
    }

    function downloadQrFile(trackingNumber) {
        const url = `../api/download-qr.php?trackingNumber=${encodeURIComponent(trackingNumber)}`;
        const link = document.createElement('a');
        link.href = url;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    // Print QR code for receiver - Opens print window
    function printReceiverQR() {
        const qrContainer = document.getElementById('parcelQRCode');
        if (!qrContainer) {
            Swal.fire({
                icon: 'warning',
                title: 'QR Container Not Found',
                text: 'QR code container is not available.',
                confirmButtonColor: '#43e97b'
            });
            return;
        }

        const img = qrContainer.querySelector('img');

        if (!img) {
            Swal.fire({
                icon: 'warning',
                title: 'No QR Code',
                text: 'QR code is not available for this parcel.',
                confirmButtonColor: '#43e97b'
            });
            return;
        }

        // Get tracking number
        const trackingNumberElement = document.querySelector('.tracking-number');
        const trackingNumber = trackingNumberElement ? trackingNumberElement.textContent.trim() : 'QR_Code';

        // Store the image source
        const qrImageSrc = img.src;

        // Create print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Print QR Code - ${trackingNumber}</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        text-align: center;
                        padding: 20px;
                        background: white;
                        margin: 0;
                    }
                    .header {
                        background: linear-gradient(135deg, #43e97b 0%, #38d9a9 100%);
                        color: white;
                        padding: 15px;
                        margin-bottom: 20px;
                        border-radius: 8px;
                    }
                    .qr-container {
                        margin: 20px 0;
                        padding: 20px;
                        border: 2px dashed #43e97b;
                        border-radius: 12px;
                    }
                    .qr-container img {
                        max-width: 250px;
                        border: 2px solid #e5e7eb;
                        border-radius: 8px;
                    }
                    .info {
                        margin: 20px 0;
                        padding: 15px;
                        background: #f8fafc;
                        border-radius: 8px;
                    }
                    .instructions {
                        background: #f8f9fa;
                        padding: 15px;
                        border-radius: 8px;
                        margin-top: 20px;
                        font-size: 14px;
                        color: #64748b;
                    }
                    @media print {
                        body {
                            margin: 0;
                        }
                        .no-print {
                            display: none;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h2>Perwira Parcel Management System</h2>
                    <h3>Parcel Verification QR Code</h3>
                </div>
                <div class="qr-container">
                    <img src="${qrImageSrc}" alt="QR Code">
                </div>
                <div class="info">
                    <p><strong>Tracking Number:</strong> ${trackingNumber}</p>
                    <p><strong>Generated:</strong> ${new Date().toLocaleString()}</p>
                </div>
                <div class="instructions">
                    <strong>Instructions:</strong><br>
                    1. Present this QR code at the parcel counter for verification<br>
                    2. Staff will scan the code to confirm parcel details<br>
                    3. QR code is unique to this parcel and receiver
                </div>
                <script>
                    window.onload = function() {
                        setTimeout(function() { window.print(); }, 500);
                    }
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }

    // Download QR code from parcel history
    function downloadHistoryQR(trackingNumber) {
        console.log('Downloading QR for tracking:', trackingNumber);

        fetch(`../api/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(trackingNumber)}`, {
            credentials: 'include'
        })
        .then(r => r.json())
        .then(data => {
            if (!data || !data.success || !data.parcel) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not fetch parcel data. Please try again.',
                    confirmButtonColor: '#43e97b'
                });
                return;
            }

            if (!data.parcel.qrGenerated) {
                Swal.fire({
                    icon: 'warning',
                    title: 'QR Not Available',
                    text: 'QR code has not been generated by staff yet.',
                    confirmButtonColor: '#43e97b'
                });
                return;
            }

            if (!data.parcel.qrExists) {
                Swal.fire({
                    icon: 'warning',
                    title: 'QR File Missing',
                    text: 'QR code exists, but the image file is missing. Please contact staff to regenerate it.',
                    confirmButtonColor: '#43e97b'
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
                confirmButtonColor: '#43e97b'
            });
        });
    }

    // Enlarge QR code from parcel history
    function enlargeHistoryQR(trackingNumber) {
        console.log('Enlarging QR for tracking:', trackingNumber);

        // Fetch parcel data with QR
        fetch(`../api/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(trackingNumber)}`, {
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
                    confirmButtonColor: '#43e97b'
                });
            }
        })
        .catch(err => {
            console.error('Error:', err);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to enlarge QR code.',
                confirmButtonColor: '#43e97b'
            });
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

        if (!originalCardsCount) {
            originalCardsCount = cards.length;
        }

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

        if (!viewport || cards.length === 0) return;

        if (!originalCardsCount) {
            originalCardsCount = cards.length;
        }

        const cardWidth = cards[0].offsetWidth;
        const gap = parseInt(window.getComputedStyle(document.querySelector('.carousel-track')).gap);
        const cardWithGap = cardWidth + gap;
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

        const visibleIndex = Math.round(scrollPosition / cardWithGap);
        currentCarouselIndex = ((visibleIndex % originalCardsCount) + originalCardsCount) % originalCardsCount;

        // Update active indicator (if present)
        if (indicators.length > 0) {
            indicators.forEach((indicator, index) => {
                indicator.classList.toggle('active', index === currentCarouselIndex);
            });
        }
    }

    function applyTrackedParcelStatus(nextStatus) {
        const badgeContainer = document.getElementById('parcelStatusBadge');
        const alertContainer = document.getElementById('parcelStatusAlert');
        if (!badgeContainer || !alertContainer) return;

        const currentStatus = badgeContainer.getAttribute('data-status') || '';
        if (currentStatus === nextStatus) return;

        badgeContainer.setAttribute('data-status', nextStatus);
        alertContainer.setAttribute('data-status', nextStatus);

        if (nextStatus === 'Pending') {
            badgeContainer.innerHTML = `
                <div class="status-badge status-pending">
                    <svg style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><polyline points="12,6 12,12 16,14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Pending Pickup</span>
                </div>
            `;
            alertContainer.innerHTML = `
                <div class="action-alert">
                    <div class="alert-icon"><i class="fas fa-hand-holding-box"></i></div>
                    <div class="alert-content">
                        <div class="alert-title text-friendly">Ready for Pickup!</div>
                        <div class="alert-message welcome-text">Your parcel is ready for collection. Please visit the parcel center with your IC and this tracking number.</div>
                    </div>
                </div>
            `;
        } else if (nextStatus === 'Retrieved') {
            badgeContainer.innerHTML = `
                <div class="status-badge status-retrieved">
                    <svg style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="22,4 12,14.01 9,11.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Retrieved</span>
                </div>
            `;
            alertContainer.innerHTML = `
                <div class="action-alert success-alert">
                    <div class="alert-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="alert-content">
                        <div class="alert-title">Package Retrieved</div>
                        <div class="alert-message">This parcel has been successfully collected. Thank you for using our service!</div>
                    </div>
                </div>
            `;
        } else {
            badgeContainer.innerHTML = `
                <div class="status-badge status-other">
                    <svg style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><line x1="12" y1="16" x2="12" y2="12" stroke="currentColor" stroke-width="2"/><line x1="12" y1="8" x2="12.01" y2="8" stroke="currentColor" stroke-width="2"/></svg>
                    <span>${nextStatus || 'Unknown'}</span>
                </div>
            `;
            alertContainer.innerHTML = '';
        }
    }

    function startTrackedParcelRealtime() {
        const trackingNumber = document.querySelector('.tracking-number')?.textContent?.trim();
        const badgeContainer = document.getElementById('parcelStatusBadge');
        const alertContainer = document.getElementById('parcelStatusAlert');
        if (!trackingNumber || !badgeContainer || !alertContainer) return;

        if (window.__ppmsTrackedParcelPoller) return;

        const poll = () => {
            fetch(`../api/get-parcel-with-qr.php?trackingNumber=${encodeURIComponent(trackingNumber)}`, {
                credentials: 'include'
            })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success && data.parcel && data.parcel.status) {
                        applyTrackedParcelStatus(data.parcel.status);
                    }
                })
                .catch(() => {});
        };

        poll();
        window.__ppmsTrackedParcelPoller = setInterval(poll, 5000);
    }

    // Initialize carousel and real-time tracked parcel status when page loads
    document.addEventListener('DOMContentLoaded', () => {
        initializeCarousel();
        initializeCarouselDrag();
        setupInfiniteCarousel();
        startTrackedParcelRealtime();
    });

    </script>
</body>
</html>
