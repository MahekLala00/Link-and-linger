<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php';

$user_id = $_SESSION['user_id'];
$profile_photo = 'uploads/default.png';

$requests = [];
$purchased_ideas = [];
$error_message = "";

try {
    $stmt = $pdo->prepare("
        SELECT profile_photo
        FROM user_profiles
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($profile['profile_photo'])) {
        $profile_photo = $profile['profile_photo'];
    }

    $stmt = $pdo->prepare("
        SELECT
            lr.request_id,
            lr.sender_id,
            u.full_name,
            u.username,
            p.profile_photo
        FROM link_requests lr
        INNER JOIN users u
            ON lr.sender_id = u.user_id
        LEFT JOIN user_profiles p
            ON u.user_id = p.user_id
        WHERE lr.receiver_id = ?
        AND lr.status = 'Pending'
        ORDER BY lr.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT 
            p.purchase_id,
            p.purchased_at,
            d.title,
            d.location_name,
            d.address,
            d.description,
            d.image_path,
            creator.profile_photo AS creator_photo
        FROM purchased_ideas p
        INNER JOIN date_ideas d ON p.idea_id = d.idea_id
        LEFT JOIN user_profiles creator ON d.creator_id = creator.user_id
        WHERE p.user_id = ?
        ORDER BY p.purchased_at DESC
    ");
    $stmt->execute([$user_id]);
    $purchased_ideas = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Safely logs detailed error to server logs and sets user-friendly alert message
    error_log("Database Error in notifications.php: " . $e->getMessage());
    $error_message = "A system error occurred. Please try again later.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications & Unlocked Ideas</title>
    <link rel="stylesheet" href="page.css">
    <style>
        .section-divider {
            margin: 40px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
            color: #333;
        }
        .unlocked-details {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #2e7d32;
        }
        .unlocked-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }
        .system-error-banner {
            background-color: #ffebee;
            color: #c62828;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 5px solid #c62828;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-logo">Link & Linger</div>
        <div class="nav-links">
            <a href="linkpg.php">Link</a>
            <a href="linger.php">Linger</a>
            <a href="messages.php">Chat</a>
            <a href="notifications.php" class="active">Notifications</a>
            <a href="dash.php">My Dashboard</a>
        </div>
        <div class="nav-actions">
            <a href="logout.php" class="pref-toggle-btn">Logout</a>
            <div class="user-avatar" style="background-image:url('<?php echo htmlspecialchars($profile_photo); ?>');"></div>
        </div>
    </nav>

    <div class="discover-container">
        
        <?php if (!empty($error_message)): ?>
            <div class="system-error-banner">
                ⚠️ <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="feed-header">
            <div class="header-title">
                <h2>Link Requests</h2>
                <p>People who want to link with you</p>
            </div>
        </div>

        <div class="ideas-feed">
            <?php if (!empty($requests)): ?>
                <?php foreach ($requests as $request): ?>
                    <div class="idea-card">
                        <div class="idea-details">
                            <div class="idea-image-wrapper">
                                <img src="<?php echo !empty($request['profile_photo']) ? htmlspecialchars($request['profile_photo']) : 'uploads/default.png'; ?>" alt="Profile Photo">
                            </div>
                            <div class="idea-main-info">
                                <div class="card-header-row">
                                    <h3><?php echo htmlspecialchars($request['full_name']); ?></h3>
                                </div>
                                <p class="posted-by">@<?php echo htmlspecialchars($request['username']); ?></p>
                                <div class="idea-description">
                                    💕 Wants to link with you.
                                </div>
                            </div>
                            <div class="idea-footer">
                                <a href="view_profile.php?id=<?php echo $request['sender_id']; ?>" class="action-btn">View Profile</a>
                                <a href="accept_request.php?id=<?php echo $request['request_id']; ?>" class="action-btn buy-btn">Accept</a>
                                <a href="reject_request.php?id=<?php echo $request['request_id']; ?>" class="action-btn linger-btn">Reject</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="idea-card">
                    <div class="idea-details">
                        <div class="idea-main-info">
                            <h3>No New Requests</h3>
                            <p class="idea-description">You don't have any pending link requests right now.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="section-divider">🔓 Unlocked Hotspots</h2>
        <p style="color: #666; margin-bottom: 20px;">Your collection of purchased date itineraries.</p>

        <div class="ideas-feed">
            <?php if (!empty($purchased_ideas)): ?>
                <?php foreach ($purchased_ideas as $purchased): ?>
                    <div class="idea-card">
                        <div class="idea-details">
                            <div style="text-align:center; margin-bottom:15px;">
                                <?php if (!empty($purchased['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($purchased['image_path']) ?>" alt="Date" style="width:120px; height:120px; border-radius:12px; object-fit:cover;">
                                <?php else: ?>
                                    <div class="idea-placeholder-icon" style="font-size:40px;">🗺️</div>
                                <?php endif; ?>
                            </div>

                            <div class="idea-main-info">
                                <div class="unlocked-meta">
                                    Unlocked on: <?= date("d M Y", strtotime($purchased['purchased_at'])) ?>
                                </div>
                                <h3><?= htmlspecialchars($purchased['title']) ?></h3>
                                
                                <div class="unlocked-details">
                                    <p><strong>📍 Location:</strong> <?= htmlspecialchars($purchased['location_name']) ?></p>
                                    <p><strong> Address:</strong> <?= htmlspecialchars($purchased['address']) ?></p>
                                    <p style="margin-top: 8px;"><strong>📝 Plan:</strong><br><?= nl2br(htmlspecialchars($purchased['description'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="idea-card">
                    <div class="idea-details">
                        <div class="idea-main-info">
                            <h3>No Itineraries Unlocked</h3>
                            <p class="idea-description">Ideas you buy on the <a href="linger.php" style="color: #2e7d32; font-weight: bold;">Linger</a> feed will safely reveal themselves here.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>