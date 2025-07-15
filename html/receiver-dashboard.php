<?php
session_start();
require_once '../php/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['receiver_ic']) || empty($_SESSION['receiver_ic'])) {
    header("Location: receiver-login.html");
    exit();
}

$receiverIC = $_SESSION['receiver_ic'];
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
    <link rel="stylesheet" href="../css/ppms-styles/shared/variables.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/shared/typography.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/shared/safe-typography-enhancements.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/shared/components.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/receiver/receiver-dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/receiver/receiver-dashboard-overrides.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/receiver/receiver-notifications.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../css/ppms-styles/receiver/receiver-navbar-buttons.css?v=<?php echo time(); ?>">
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
                <div class="navbar-brand mb-0">PERWIRA PARCEL MANAGEMENT SYSTEM</div>
                <div style="font-size: 0.85rem; opacity: 0.8;">Universiti Tun Hussein Onn Malaysia</div>
            </div>
        </div>
        <div class="d-flex align-items-center">
            <div class="me-4 d-flex align-items-center">
                <!-- Notification Bell with Dropdown -->
                <div class="dropdown me-3">
                    <button class="notification-bell-btn btn" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php
                        // Count unread notifications
                        $unreadStmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM Notification WHERE ICNumber = ? AND isRead = 0");
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
                            $recentStmt = $conn->prepare("SELECT * FROM Notification WHERE ICNumber = ? ORDER BY sentTimestamp DESC LIMIT 5");
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
                            <a href="#" class="btn btn-sm btn-success w-100" onclick="showAllNotifications()">
                                <i class="fas fa-list me-1"></i> View All Notifications
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Info -->
                <div class="text-end">
                    <div class="navbar-welcome">Welcome back,</div>
                    <div style="font-weight: 700; font-size: 1.1rem;"><?php echo htmlspecialchars($receiverName); ?></div>
                </div>
            </div>
            <button class="logout-btn" onclick="logout()">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </button>
        </div>
    </nav>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <!-- Simple Tabs -->
        <ul class="nav nav-tabs" id="myTab" role="tablist">
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
                
                <form method="post" action="../php/track-parcel.php" class="mt-4" style="background: #f8f9fa; padding: 2rem; border-radius: 1rem; border: 1px solid #e9ecef;">
                    <div class="mb-3">
                        <label for="trackingNumber" class="form-label" style="font-weight: 700 !important; color: #2d3748 !important; font-size: 1rem !important; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 1rem !important;">
                             Tracking Number
                        </label>
                        <input type="text" class="form-control" id="trackingNumber" name="trackingNumber" required
                               placeholder="Enter your tracking number"
                               style="background: #ffffff !important; color: #2d3748 !important; border: 2px solid #e2e8f0 !important; padding: 0.75rem 1rem !important; font-size: 1rem !important;">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <svg class="me-1" style="width: 16px; height: 16px; display: inline-block;" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/><path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> Track Parcel
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
                            <div class="status-container">
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

                        <!-- Main Details Grid -->
                        <div class="parcel-details-grid">
                            <!-- Date & Time Card -->
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none"><rect x="3" y="4" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2"/><line x1="16" y1="2" x2="16" y2="6" stroke="currentColor" stroke-width="2"/><line x1="8" y1="2" x2="8" y2="6" stroke="currentColor" stroke-width="2"/><line x1="3" y1="10" x2="21" y2="10" stroke="currentColor" stroke-width="2"/></svg>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Received Date</div>
                                    <div class="detail-value">
                                        <?php echo date('F d, Y', strtotime($parcel['date'])); ?>
                                        <?php if (!empty($parcel['time'])): ?>
                                            <div class="detail-subvalue">at <?php echo date('h:i A', strtotime($parcel['time'])); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Weight Card -->
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none"><path d="M12 2L2 7l10 5 10-5-10-5z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 17l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 12l10 5 10-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Package Weight</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($parcel['weight']); ?> kg</div>
                                </div>
                            </div>

                            <!-- Location Card -->
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="10" r="3" stroke="currentColor" stroke-width="2"/></svg>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Delivery Location</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($parcel['deliveryLocation'] ?? 'Not specified'); ?></div>
                                </div>
                            </div>

                            <!-- Receiver Card -->
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2"/></svg>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Receiver IC Number</div>
                                    <div class="detail-value" id="receiverICDisplay"><?php echo htmlspecialchars($parcel['ICNumber']); ?></div>
                                </div>
                            </div>

                            <!-- Parcel Name Card (if available) -->
                            <?php if (!empty($parcel['name'])): ?>
                            <div class="detail-card">
                                <div class="detail-icon">
                                    <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" stroke="currentColor" stroke-width="2"/><polyline points="3.27,6.96 12,12.01 20.73,6.96" stroke="currentColor" stroke-width="2"/><line x1="12" y1="22.08" x2="12" y2="12" stroke="currentColor" stroke-width="2"/></svg>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">Package Description</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($parcel['name']); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- QR Code Card -->
                            <div class="detail-card qr-card">
                                <div class="detail-icon">
                                    <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="5" height="5" stroke="currentColor" stroke-width="2"/><rect x="3" y="16" width="5" height="5" stroke="currentColor" stroke-width="2"/><rect x="16" y="3" width="5" height="5" stroke="currentColor" stroke-width="2"/><rect x="11" y="11" width="2" height="2" stroke="currentColor" stroke-width="2"/><rect x="13" y="13" width="2" height="2" stroke="currentColor" stroke-width="2"/><rect x="11" y="16" width="2" height="2" stroke="currentColor" stroke-width="2"/><rect x="16" y="11" width="2" height="2" stroke="currentColor" stroke-width="2"/><rect x="18" y="16" width="2" height="2" stroke="currentColor" stroke-width="2"/><rect x="16" y="18" width="2" height="2" stroke="currentColor" stroke-width="2"/></svg>
                                </div>
                                <div class="detail-content">
                                    <div class="detail-label">QR Code</div>
                                    <div class="qr-code-container" id="parcelQRCode"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Alert -->
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
                    <div class="d-flex gap-2">
                        <div class="dropdown">
                            <button class="btn btn-outline-success dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-sort me-1"></i> Sort By
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="sortHistory('date', 'desc')">
                                    <i class="fas fa-calendar me-2"></i>Date (Newest First)
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="sortHistory('date', 'asc')">
                                    <i class="fas fa-calendar me-2"></i>Date (Oldest First)
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="filterHistory('all')">
                                    <i class="fas fa-list me-2"></i>Show All
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterHistory('Pending')">
                                    <i class="fas fa-clock me-2"></i>Pending Only
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterHistory('Retrieved')">
                                    <i class="fas fa-check me-2"></i>Retrieved Only
                                </a></li>
                            </ul>
                        </div>
                        <button class="btn btn-outline-success" onclick="refreshHistory()">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                    </div>
                </div>

                <?php
                $stmt = $conn->prepare("SELECT * FROM Parcel WHERE ICNumber = ? ORDER BY date DESC, time DESC");
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

                    <div class="card border-0 shadow-sm">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="historyTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4 py-3">Tracking Number</th>
                                        <th class="px-4 py-3">Date & Time</th>
                                        <th class="px-4 py-3">Status</th>
                                        <th class="px-4 py-3">Weight</th>
                                        <th class="px-4 py-3">Location</th>
                                        <th class="px-4 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Reset result pointer
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()):
                                    ?>
                                    <tr data-status="<?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>" data-date="<?php echo htmlspecialchars($row['date'], ENT_QUOTES); ?>">
                                            <td class="px-4 py-3">
                                                <span class="fw-bold text-primary"><?php echo htmlspecialchars($row['TrackingNumber'], ENT_QUOTES); ?></span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div>
                                                    <?php echo date('M d, Y', strtotime($row['date'])); ?>
                                                    <?php if (!empty($row['time'])): ?>
                                                        <br><small class="text-muted"><?php echo date('h:i A', strtotime($row['time'])); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php if ($row['status'] == 'Pending'): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-clock me-1"></i>Pending
                                                    </span>
                                                <?php elseif ($row['status'] == 'Retrieved'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i>Retrieved
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <i class="fas fa-weight-hanging me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($row['weight'], ENT_QUOTES); ?> kg
                                            </td>
                                            <td class="px-4 py-3">
                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($row['deliveryLocation'] ?? 'Not specified', ENT_QUOTES); ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <button class="btn btn-sm btn-outline-primary eye-button"
                                                        data-tracking="<?php echo htmlspecialchars($row['TrackingNumber']); ?>"
                                                        data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                                        data-ic="<?php echo htmlspecialchars($row['ICNumber']); ?>"
                                                        data-weight="<?php echo htmlspecialchars($row['weight'] ?? 'N/A'); ?>"
                                                        data-location="<?php echo htmlspecialchars($row['deliveryLocation'] ?? 'N/A'); ?>"
                                                        data-date="<?php echo htmlspecialchars($row['date']); ?>"
                                                        data-time="<?php echo htmlspecialchars($row['time']); ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
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

                <div class="notification-list">
                    <?php
                    // Fetch all notifications for the current receiver
                    $allNotificationStmt = $conn->prepare("SELECT * FROM Notification WHERE ICNumber = ? ORDER BY sentTimestamp DESC");
                    $allNotificationStmt->bind_param("s", $receiverIC);
                    $allNotificationStmt->execute();
                    $allNotificationResult = $allNotificationStmt->get_result();

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
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($notification['messageContent'], ENT_QUOTES); ?>
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
                                        <button class="btn btn-sm btn-outline-success mark-read-btn"
                                                onclick="markSingleNotificationAsRead(<?php echo $notification['notificationID']; ?>)"
                                                title="Mark as read">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small read-status">
                                            <i class="fas fa-check-circle me-1"></i>Read
                                        </span>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-outline-danger delete-notification-btn"
                                            onclick="deleteNotification(<?php echo $notification['notificationID']; ?>)"
                                            title="Delete notification">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php if ($isUnread): ?>
                                    <div class="notification-unread-indicator"></div>
                                <?php endif; ?>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Modern Enhanced Footer -->
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
                        Your trusted partner in parcel management and delivery services.
                        We provide efficient and secure parcel management for UTHM students and staff.
                    </p>
                </div>
                <div class="col-md-6">
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

    <!-- Bootstrap JS -->
    <script src="../js/bootstrap/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>

    <script>
    // SIMPLE Eye Button Handler - Step by Step
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
                const weight = this.getAttribute('data-weight');
                const location = this.getAttribute('data-location');
                const date = this.getAttribute('data-date');
                const time = this.getAttribute('data-time');

                console.log('📦 Parcel data:', {tracking, status, ic, weight, location, date, time});

                // Show simple alert first to test
                alert('Eye button works!\nTracking: ' + tracking + '\nStatus: ' + status);

                // TODO: Add modal later
            });
        });
    });

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

        // 2. Generate QR Code for parcel details (with error handling)
        const qrContainer = document.getElementById('parcelQRCode');
        if (qrContainer) {
            // Get tracking number from the page
            const trackingElement = document.querySelector('.tracking-number');
            if (trackingElement) {
                const trackingNumber = trackingElement.textContent.trim();

                // Check if QRCode library is available
                if (typeof QRCode !== 'undefined') {
                    try {
                        // Generate QR code
                        QRCode.toCanvas(qrContainer, trackingNumber, {
                            width: 120,
                            height: 120,
                            color: {
                                dark: '#1f2937',
                                light: '#ffffff'
                            },
                            margin: 2
                        }, function (error) {
                            if (error) {
                                console.error('QR Code generation error:', error);
                                qrContainer.innerHTML = '<div class="text-muted small">QR Code unavailable</div>';
                            }
                        });
                    } catch (error) {
                        console.error('QR Code library error:', error);
                        qrContainer.innerHTML = '<div class="text-muted small">QR Code unavailable</div>';
                    }
                } else {
                    console.warn('QRCode library not loaded');
                    qrContainer.innerHTML = '<div class="text-muted small">QR Code loading...</div>';

                    // Try to load QR code after a delay
                    setTimeout(() => {
                        if (typeof QRCode !== 'undefined') {
                            QRCode.toCanvas(qrContainer, trackingNumber, {
                                width: 120,
                                height: 120,
                                color: {
                                    dark: '#1f2937',
                                    light: '#ffffff'
                                },
                                margin: 2
                            }, function (error) {
                                if (error) {
                                    console.error('QR Code generation error (delayed):', error);
                                    qrContainer.innerHTML = '<div class="text-muted small">QR Code unavailable</div>';
                                }
                            });
                        } else {
                            qrContainer.innerHTML = '<div class="text-muted small">QR Code unavailable</div>';
                        }
                    }, 1000);
                }
            }
        }

        // 3. Mark individual notification as read when clicked
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
                fetch('../php/mark-notifications-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'mark_single_read',
                        notification_id: notificationId
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
        const trackForm = document.querySelector('form[action="../php/track-parcel.php"]');
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
                    window.location.href = '../php/logout.php';
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

    // History sorting function
    function sortHistory(column, order) {
        const table = document.getElementById('historyTable');
        if (!table) return;

        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));

        rows.sort((a, b) => {
            let aVal, bVal;

            if (column === 'date') {
                aVal = new Date(a.dataset.date);
                bVal = new Date(b.dataset.date);
            }

            if (order === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });

        tbody.innerHTML = '';
        rows.forEach(row => tbody.appendChild(row));

        // Update button text
        document.getElementById('sortDropdown').innerHTML =
            `<i class="fas fa-sort me-1"></i> ${order === 'desc' ? 'Date (Newest)' : 'Date (Oldest)'}`;
    }

    // History filtering function
    function filterHistory(status) {
        const table = document.getElementById('historyTable');
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (status === 'all' || row.dataset.status === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        updateStatistics();

        // Update button text
        const filterText = status === 'all' ? 'Show All' :
                          status === 'Pending' ? 'Pending Only' : 'Retrieved Only';
        document.getElementById('sortDropdown').innerHTML =
            `<i class="fas fa-filter me-1"></i> ${filterText}`;
    }

    // Refresh history
    function refreshHistory() {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Refreshing...';
        btn.disabled = true;

        // Use AJAX to refresh only the parcel history data
        fetch(window.location.href, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Parse the response and extract the history table
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newHistoryTable = doc.querySelector('#historyTable tbody');
            const newStatistics = doc.querySelector('.statistics-cards');

            if (newHistoryTable) {
                // Update the table content
                const currentTable = document.querySelector('#historyTable tbody');
                if (currentTable) {
                    currentTable.innerHTML = newHistoryTable.innerHTML;
                }
            }

            if (newStatistics) {
                // Update statistics
                const currentStats = document.querySelector('.statistics-cards');
                if (currentStats) {
                    currentStats.innerHTML = newStatistics.innerHTML;
                }
            }

            // Also refresh real-time statistics
            loadReceiverStats();

            // Reset button
            btn.innerHTML = originalText;
            btn.disabled = false;

            // Show success message
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = '<i class="fas fa-check-circle me-2"></i>History refreshed successfully!';
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #43e97b 0%, #38d9a9 100%);
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                font-weight: 500;
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        })
        .catch(error => {
            console.error('Error refreshing history:', error);
            // Reset button on error
            btn.innerHTML = originalText;
            btn.disabled = false;

            // Show error message
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Failed to refresh. Please try again.';
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #dc3545;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                font-weight: 500;
            `;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        });
    }

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
    }

    function filterHistory(status) {
        console.log('Filtering history by', status);
        const table = document.getElementById('historyTable');
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (status === 'all' || row.dataset.status === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        updateStatistics();
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

        // Reload the page after a short delay
        setTimeout(() => {
            window.location.reload();
        }, 1500);
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
                fetch('../php/mark-notifications-read.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
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
        // Implementation for loading more notifications
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Loading more notifications...',
            showConfirmButton: false,
            timer: 1500
        });
    }

    // Notification click handling is now in the consolidated DOMContentLoaded listener above

    // Load real-time receiver statistics
    function loadReceiverStats() {
        fetch('../php/receiver-get-stats.php')
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
        fetch('../php/mark-notifications-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
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
        fetch('../php/mark-notifications-read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
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
                fetch('../php/delete-notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
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

                        // Check if no notifications left
                        setTimeout(() => {
                            const remainingNotifications = document.querySelectorAll('.notification-item');
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
        }, 4000);
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

    // Format IC numbers on page load
    document.addEventListener('DOMContentLoaded', function() {
        const icDisplay = document.getElementById('receiverICDisplay');
        if (icDisplay) {
            const icNumber = icDisplay.textContent.trim();
            icDisplay.textContent = formatICNumber(icNumber);
        }
    });
    </script>
</body>
</html>
