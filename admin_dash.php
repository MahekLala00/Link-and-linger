<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['logged_in']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php'; 

$message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- 1. VERIFICATION ACTIONS ---
        if (isset($_POST['action_verify'])) {
            $target_user_id = intval($_POST['target_user_id']);
            $status = $_POST['action_verify'] === 'accept' ? 'verified' : 'rejected';
            
            $stmt = $pdo->prepare("UPDATE users SET verification_status = ? WHERE user_id = ?");
            $stmt->execute([$status, $target_user_id]);
            
            $message = "User verification updated to: " . ucfirst($status);
        }

        // --- 2. REPORT ACTIONS (Strike / Dismiss) ---
        if (isset($_POST['action_report'])) {
            $report_id = intval($_POST['report_id']); 
            $reported_id = intval($_POST['reported_id']);
            
            if ($_POST['action_report'] === 'strike') {
                if ($reported_id === 0) {
                    $error_message = "Cannot issue a strike to an unknown or deleted user.";
                } else {
                    // Fetch current strikes
                    $stmt = $pdo->prepare("SELECT strikes FROM users WHERE user_id = ?");
                    $stmt->execute([$reported_id]);
                    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user_data) {
                        $new_strikes = intval($user_data['strikes'] ?? 0) + 1;
                        $status_update_sql = "";
                        
                        if ($new_strikes >= 3) {
                            $status_update_sql = ", account_status = 'blocked', verification_status = 'rejected'";
                        }
                        
                        // Apply strike to user
                        $query = "UPDATE users SET strikes = ?, new_strike_notification = 1 $status_update_sql WHERE user_id = ?";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([$new_strikes, $reported_id]);
                        
                        // Mark this report as resolved now that action is taken
                        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE report_id = ?");
                        $stmt->execute([$report_id]);
                        
                        $message = "Strike successfully issued. User now has $new_strikes/3 strikes.";
                        if ($new_strikes >= 3) {
                            $message .= " Account automatically restricted/blocked.";
                        }
                    } else {
                        $error_message = "Target user for strike not found in database.";
                    }
                }
            } elseif ($_POST['action_report'] === 'dismiss') {
                // Simply mark the report as dismissed without punishing the user
                $stmt = $pdo->prepare("UPDATE reports SET status = 'dismissed' WHERE report_id = ?");
                $stmt->execute([$report_id]);
                $message = "Report safely dismissed.";
            }
        }
    } catch (PDOException $e) {
        error_log("Admin Action Error: " . $e->getMessage());
        $error_message = "An error occurred executing administration action.";
    }
}

// Fetch Pending Verifications
try {
    $verify_stmt = $pdo->prepare("SELECT user_id, full_name, username, email, id_type, id_number, id_document_url, selfie_with_id_url FROM users WHERE verification_status = 'pending' AND role != 'admin' ORDER BY user_id DESC");
    $verify_stmt->execute();
    $pending_users = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pending_users = [];
}
// Fetch Pending Reports
try {
    $report_stmt = $pdo->prepare("
        SELECT
            r.report_id,
            r.reason,
            r.screenshot,
            r.status,
            COALESCE(u1.username, 'Deleted User') AS reporter_username,
            COALESCE(u2.user_id, 0) AS reported_id,
            COALESCE(u2.username, 'Unknown User') AS reported_username,
            COALESCE(u2.strikes, 0) AS strikes
        FROM reports r
        LEFT JOIN users u1 ON r.reporter_id = u1.user_id
        LEFT JOIN users u2 ON r.reported_id = u2.user_id
        WHERE r.status = 'Pending'
        ORDER BY r.report_id DESC
    ");
    $report_stmt->execute();
    $active_reports = $report_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Admin report query error: " . $e->getMessage());
    $active_reports = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Link & Linger</title>
    <link rel="stylesheet" href="ad.css">
</head>
<body>
    <main class="admin-main">
        <h1>Admin Control Panel</h1>
        <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong></p>
        <p><a href="logout.php" style="color: #d32f2f; font-weight: bold;">Log Out</a></p>
        <hr style="border: 0; border-top: 1px solid #ccc; margin: 20px 0;">

        <?php if (!empty($message)): ?>
            <div class="banner banner-success" style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px; border-radius:4px;">✓ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="banner banner-error" style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px; border-radius:4px;">⚠️ <?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <section style="margin-bottom: 40px;">
            <h2>Pending Profile Verifications</h2>
            <?php if (!empty($pending_users)): ?>
                <table border="1" style="width:100%; border-collapse:collapse; margin-top:10px;">
                    <thead>
                        <tr style="background:#f4f4f4; text-align:left;">
                            <th style="padding:10px;">User Details</th>
                            <th style="padding:10px;">ID Number</th>
                            <th style="padding:10px;">Verification Documents</th>
                            <th style="padding:10px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_users as $user): ?>
                            <tr>
                                <td style="padding:10px;">
                                    <strong><?= htmlspecialchars($user['full_name']) ?></strong><br>
                                    <span style="color:#666;">@<?= htmlspecialchars($user['username']) ?></span><br>
                                    <small><?= htmlspecialchars($user['email']) ?></small>
                                </td>
                                <td style="padding:10px;">
                                    <span style="text-transform: uppercase; font-size:12px; font-weight:bold; color:#555;">
                                        <?= htmlspecialchars($user['id_type']) ?>
                                    </span><br>
                                    <code><?= htmlspecialchars($user['id_number']) ?></code>
                                </td>
                                <td style="padding:10px;">
                                    <div style="display: flex; gap: 10px;">
                                        <div>
                                            <small style="display:block; color:#666;">ID Doc:</small>
                                            <a href="<?= htmlspecialchars($user['id_document_url']) ?>" target="_blank">
                                                <img src="<?= htmlspecialchars($user['id_document_url']) ?>" class="preview-img" alt="ID File" style="max-height: 50px; border:1px solid #ccc; border-radius:4px;">
                                            </a>
                                        </div>
                                        <div>
                                            <small style="display:block; color:#666;">Selfie Proof:</small>
                                            <a href="<?= htmlspecialchars($user['selfie_with_id_url']) ?>" target="_blank">
                                                <img src="<?= htmlspecialchars($user['selfie_with_id_url']) ?>" class="preview-img" alt="Selfie File" style="max-height: 50px; border:1px solid #ccc; border-radius:4px;">
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td style="padding:10px;">
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="target_user_id" value="<?= $user['user_id'] ?>">
                                        <button type="submit" name="action_verify" value="accept" style="margin-bottom:5px; width:100px; background:#28a745; color:white; border:none; padding:5px; border-radius:4px; cursor:pointer;">Accept</button><br>
                                        <button type="submit" name="action_verify" value="decline" style="width:100px; background:#dc3545; color:white; border:none; padding:5px; border-radius:4px; cursor:pointer;">Decline</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">No pending verification requests right now.</p>
            <?php endif; ?>
        </section>

        <section>
            <h2>User Abuse & Community Reports</h2>
            <?php if (!empty($active_reports)): ?>
                <table border="1" style="width:100%; border-collapse:collapse; margin-top:10px;">
                    <thead>
                        <tr style="background:#f4f4f4; text-align:left;">
                            <th style="padding:10px;">Reported Individual</th>
                            <th style="padding:10px;">Current Infractions</th>
                            <th style="padding:10px;">Filed By</th>
                            <th style="padding:10px;">Reason / Context Provided</th>
                            <th style="padding:10px;">Evidence Screenshot</th>
                            <th style="padding:10px;">Resolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_reports as $report): ?>
                            <tr>
                                <td style="padding:10px;"><strong>@<?= htmlspecialchars($report['reported_username']) ?></strong></td>
                                <td style="padding:10px;">
                                    <span class="badge" style="background:#fff3cd; color:#856404; padding:3px 8px; border-radius:12px; font-size:12px; font-weight:bold;">
                                        <?= intval($report['strikes']) ?> / 3 Strikes
                                    </span>
                                </td>
                                <td style="padding:10px;">@<?= htmlspecialchars($report['reporter_username']) ?></td>
                                <td style="padding:10px;"><p style="margin:0; font-size:14px; max-width:250px;"><?= htmlspecialchars($report['reason']) ?></p></td>
                                <td style="padding:10px;">
                                    <?php if (!empty($report['screenshot'])): ?>
                                        <a href="<?= htmlspecialchars($report['screenshot']) ?>" target="_blank">
                                            <img src="<?= htmlspecialchars($report['screenshot']) ?>" alt="Evidence" style="max-width:80px; max-height:60px; border:1px solid #ccc; border-radius:4px; object-fit:cover;">
                                        </a>
                                    <?php else: ?>
                                        <span style="color:#aaa; font-style:italic; font-size:13px;">None Provided</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:10px;">
                                    <form method="POST" style="display:flex; gap:8px; margin:0;">
                                        <input type="hidden" name="report_id" value="<?= htmlspecialchars($report['report_id']) ?>">
                                        <input type="hidden" name="reported_id" value="<?= intval($report['reported_id']) ?>">
                                        <button type="submit" name="action_report" value="strike" style="background:#dc3545; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-weight:bold;">Issue Strike</button>
                                        <button type="submit" name="action_report" value="dismiss" style="background:#6c757d; color:#fff; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-weight:bold;">Dismiss</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666; font-style: italic;">Clean slate! No incoming active user reports found.</p>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>