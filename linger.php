<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$profile_photo = "uploads/default.png";

try {
    $stmt = $pdo->prepare("SELECT profile_photo FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $myProfile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($myProfile && !empty($myProfile['profile_photo'])) {
        $profile_photo = $myProfile['profile_photo'];
    }
} catch (PDOException $e) { 
    $profile_photo = "uploads/default.png";
}

$purchased_ideas = [];
try {
    $stmt = $pdo->prepare("SELECT idea_id FROM purchases WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $purchased_ideas = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $purchased_ideas = [];
}

$ideas = [];
try {
    $stmt = $pdo->query("SELECT d.*, u.user_id AS creator_name 
                          FROM date_ideas d 
                          LEFT JOIN user_profiles u ON d.creator_id = u.user_id 
                          ORDER BY d.created_at DESC");
    $ideas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $stmt = $pdo->query("SELECT * FROM date_ideas ORDER BY created_at DESC");
        $ideas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        $ideas = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linger - Explore Date Ideas</title>
    <link rel="stylesheet" href="page.css">
    
    <style>
        .blur-text {
            filter: blur(6px);
            user-select: none;
            pointer-events: none;
            display: inline-block;
        }
        .revealed-text {
            filter: none;
            color: #1b5e20;
            font-weight: bold;
        }
    </style>
</head>

<body>
<nav class="navbar">
    <div class="nav-logo">Link & Linger</div>
    <div class="nav-links">
        <a href="linkpg.php">Link</a>
        <a href="linger.php" class="active">Linger</a>
        <a href="messages.php">Chat</a>
        <a href="notifications.php">Notifications</a>
        <a href="dash.php">My Dashboard</a>
    </div>
    <div class="nav-actions">
        <a href="logout.php" class="pref-toggle-btn">Logout</a>
        <div class="user-avatar" style="background-image:url('<?= htmlspecialchars($profile_photo) ?>');"></div>
    </div>
</nav>

<main class="discover-container">

    <?php if (isset($_GET['unlocked']) && $_GET['unlocked'] == 1): ?>
        <div class="success-banner" style="background-color: #e8f5e9; border-left: 5px solid #2e7d32; color: #1b5e20; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><strong>Success!</strong> Your new date idea has been unlocked. Scroll to notifications see the hidden location details!</span>
            <button onclick="this.parentElement.style.display='none';" style="background: none; border: none; font-size: 16px; cursor: pointer; color: #1b5e20;">✕</button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['payment']) && $_GET['payment'] === 'cancelled'): ?>
        <div class="cancel-banner" style="background-color: #ffebee; border-left: 5px solid #c62828; color: #c62828; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span>Payment was cancelled. The date destination remains locked.</span>
            <button onclick="this.parentElement.style.display='none';" style="background: none; border: none; font-size: 16px; cursor: pointer; color: #c62828;">✕</button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
        <div class="success-banner" style="background-color: #ffebee; border-left: 5px solid #c62828; color: #c62828; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><strong>Deleted!</strong> Your date idea has been removed successfully.</span>
            <button onclick="this.parentElement.style.display='none';" style="background: none; border: none; font-size: 16px; cursor: pointer; color: #c62828;">✕</button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div class="success-banner" style="background-color: #e3f2fd; border-left: 5px solid #1e88e5; color: #0d47a1; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
            <span><strong>Updated!</strong> Your modifications have been securely applied.</span>
            <button onclick="this.parentElement.style.display='none';" style="background: none; border: none; font-size: 16px; cursor: pointer; color: #0d47a1;">✕</button>
        </div>
    <?php endif; ?>

<header class="feed-header">
    <div class="header-title">
        <h2>Linger Hotspots</h2>
        <p>Unlock or buy curated date itineraries around town!</p>
    </div>
    <a href="createdate.php" class="create-btn">Share an Idea</a>
</header>

<section class="ideas-feed">

<?php if (!empty($ideas)): ?>
    <?php foreach ($ideas as $idea): ?>
        <?php
        $actual_id = isset($idea['id']) ? $idea['id'] : (isset($idea['idea_id']) ? $idea['idea_id'] : 0);
        $isPurchased = in_array($actual_id, $purchased_ideas) || $idea['creator_id'] == $user_id;
    	    
    ?>
    	

        <div class="idea-card" >
            <div style="text-align:center; margin-bottom:15px; margin-top: 40px;">
                <?php if (!empty($idea['image_path'])): ?>
                    <img src="<?= htmlspecialchars($idea['image_path']) ?>"
                         alt="Date Idea"
                         style="width:150px; height:150px; border-radius:12px; object-fit:cover; border:2px solid #ddd;">
                <?php else: ?>
                    <div class="idea-placeholder-icon"></div>
                <?php endif; ?>
            </div>

            <div class="idea-details">
                <div class="idea-main-info">
                    <div class="card-header-row">
                        <h3><?= htmlspecialchars($idea['title']) ?></h3>
                        <p style="margin:0; color:#666;">
                            @<?= htmlspecialchars($idea['creator_name'] ?? 'User' . $idea['creator_id']) ?> • <?= date("d M Y", strtotime($idea['created_at'])) ?>
                        </p>
                    </div>

                    <hr>

                    <div class="rating-tags-container unified-card-block">
                        <div class="idea-description">
                            <strong>Location:</strong>
                            <span class="<?= $isPurchased ? 'revealed-text' : 'blur-text' ?>">
                                <?= htmlspecialchars($idea['location_name']) ?>
                            </span>
                        </div>
                        <break>
                        <div class="idea-description">
                            <strong>Address:</strong>
                            <span class="<?= $isPurchased ? 'revealed-text' : 'blur-text' ?>">
                                <?= htmlspecialchars($idea['address']) ?>
                            </span>
                        </div>
                        <div class="idea-description" style="margin-top: 15px; word-break: break-word;">
                            <strong>Description:</strong><br>
                            <span><?= nl2br(htmlspecialchars($idea['description'])) ?></span>
                        </div>
                        <div class="idea-description" style="margin-top: 15px;">
                            <strong>Vibe:</strong>
                            <?= str_repeat("★", round($idea['vibe_rating'])) ?>
                        </div>
                        <div class="idea-description">
                            <strong>Safety:</strong>
                            <?= str_repeat("★", round($idea['safety_rating'])) ?>
                        </div>
                        <div class="idea-description">
                            <strong>Recommendation:</strong>
                            <?= str_repeat("★", round($idea['recommend_rating'])) ?>
                        </div>
                        <div class="idea-description" style="margin-top: 10px;">
                            <strong>Price:</strong>
                            R <?= number_format($idea['price'], 2) ?>
                        </div>
                    </div>
                </div>
                
                <div class="idea-footer" style="margin-top: 15px; position: relative; min-height: 45px;">
                    <div class="purchase-action-container">
                        <?php if (!$isPurchased): ?>
                            <a href="buy_idea.php?id=<?= $actual_id ?>" class="action-btn buy-btn">
                                Unlock
                            </a>
                        <?php else: ?>
                            <span class="action-btn bought-btn" style="background:#e8f5e9; color:#2e7d32; padding:5px 10px; border-radius:4px; font-size:13px; display: inline-block;">Unlocked ✓</span>
                        <?php endif; ?>
                    </div>

                    <?php if ($idea['creator_id'] == $user_id): ?>
                        <div class="owner-controls" style="position: absolute; right: 0; top: 50%; transform: translateY(-50%); display: flex; gap: 12px; align-items: center;">
                            <a href="createdate.php?id=<?= $actual_id ?>" 
                               style="color: #1976d2; text-decoration: none; font-size: 13px; font-weight: bold; background: #e3f2fd; padding: 6px 12px; border-radius: 4px; display: inline-block;">Edit</a>
                            <a href="createdate.php?delete_id=<?= $actual_id ?>" 
                               onclick="return confirm('Are you sure you want to permanently delete this date idea?');" 
                               style="color: #d32f2f; text-decoration: none; font-size: 13px; font-weight: bold; background: #ffebee; padding: 6px 12px; border-radius: 4px; display: inline-block;">Delete</a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="idea-card">
        <div class="idea-details">
            <p>No date ideas available yet.</p>
        </div>
    </div>
<?php endif; ?>

</section>
</main>
</body>
</html>