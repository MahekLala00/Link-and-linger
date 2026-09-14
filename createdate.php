<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
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
} catch (PDOException $e) { /* fallback */ }

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    try {
        // Universal query structure handling fallback configurations
        $stmt = $pdo->prepare("SELECT image_path FROM date_ideas WHERE id = ? AND creator_id = ?");
        $stmt->execute([$delete_id, $user_id]);
        $ideaToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ideaToDelete) {
            if (!empty($ideaToDelete['image_path']) && file_exists($ideaToDelete['image_path'])) {
                unlink($ideaToDelete['image_path']);
            }
            $delStmt = $pdo->prepare("DELETE FROM date_ideas WHERE id = ? AND creator_id = ?");
            $delStmt->execute([$delete_id, $user_id]);
            header("Location: linger.php?deleted=1");
            exit();
        }
    } catch (PDOException $e) {
        die("Deletion error: " . $e->getMessage());
    }
}

$isEditMode = false;
$idea_id = 0;
$idea = [
    'title' => '', 'location_name' => '', 'address' => '', 
    'price' => '', 'description' => '', 'vibe_rating' => 5, 
    'safety_rating' => 5, 'recommend_rating' => 5, 'image_path' => ''
];

if (isset($_GET['id'])) {
    $idea_id = intval($_GET['id']);
    
    // Safety matching context mapping query parameters
    $stmt = $pdo->prepare("SELECT * FROM date_ideas WHERE id = ? AND creator_id = ?");
    $stmt->execute([$idea_id, $user_id]);
    $fetchedIdea = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($fetchedIdea) {
        $idea = $fetchedIdea;
        $isEditMode = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $location_name = trim($_POST['location_name']);
        $address = trim($_POST['address']);
        $price = $_POST['price'];
        $vibe_rating = intval($_POST['vibe_rating']);
        $safety_rating = intval($_POST['safety_rating']);
        $recommend_rating = intval($_POST['recommend_rating']);

        $image_path = $isEditMode ? $idea['image_path'] : null;

        if (isset($_FILES['image_path']) && $_FILES['image_path']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "uploads/date_ideas/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $filename = time() . "_" . basename($_FILES['image_path']['name']);
            $target_file = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['image_path']['tmp_name'], $target_file)) {
                if ($isEditMode && !empty($idea['image_path']) && file_exists($idea['image_path'])) {
                    unlink($idea['image_path']);
                }
                $image_path = $target_file;
            }
        }

        if ($isEditMode) {
            $stmt = $pdo->prepare("
                UPDATE date_ideas SET 
                    title = ?, description = ?, location_name = ?, address = ?, 
                    image_path = ?, price = ?, vibe_rating = ?, safety_rating = ?, recommend_rating = ?
                WHERE id = ? AND creator_id = ?
            ");
            $stmt->execute([
                $title, $description, $location_name, $address,
                $image_path, $price, $vibe_rating, $safety_rating, $recommend_rating,
                $idea_id, $user_id
            ]);
            header("Location: linger.php?updated=1");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO date_ideas (
                    creator_id, title, description, location_name, address, 
                    image_path, price, vibe_rating, safety_rating, recommend_rating
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id, $title, $description, $location_name, $address,
                $image_path, $price, $vibe_rating, $safety_rating, $recommend_rating
            ]);
            header("Location: linger.php?success=1");
        }
        exit();

    } catch (PDOException $e) {
        die("Error saving data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Linger - <?= $isEditMode ? 'Edit' : 'Create' ?> Date Idea</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-logo">Link & Linger</div>
        <div class="nav-links">
            <a href="linkpg.php">Link</a>
            <a href="linger.php" class="active">Linger</a>
            <a href="chat.php">Chat</a>
            <a href="notifications.php">Notifications</a>
            <a href="dash.php">My Dashboard</a>
        </div>
        <div class="nav-actions">
            <a href="logout.php" class="pref-toggle-btn">Logout</a>
            <div class="user-avatar" style="background-image:url('<?= htmlspecialchars($profile_photo) ?>');"></div>
        </div>
    </nav>
    <div class="pref-header" style="margin-left: 50px">
        <a href="linger.php" class="back-linkbtn">Back</a>
    </div>

    <main class="discover-container">
        <section class="profile-card" style="padding: 25px;">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #222;">
                <?= $isEditMode ? 'Edit Your Date Idea' : 'Share a Date Idea' ?>
            </h3>
            
            <form id="idea-form" action="createdate.php<?= $isEditMode ? '?id='.$idea_id : '' ?>" method="POST" enctype="multipart/form-data">
                
                <div class="pref-sec" style="margin-bottom: 15px;">
                    <input type="text" name="title" class="input-field" placeholder="Title" value="<?= htmlspecialchars($idea['title']) ?>" required>
                </div>

                <div class="pref-sec" style="margin-bottom: 15px;">
                    <input type="text" name="location_name" class="input-field" placeholder="Location Name" value="<?= htmlspecialchars($idea['location_name']) ?>" required>
                </div>

                <div class="pref-sec" style="margin-bottom: 15px;">
                    <input type="text" name="address" class="input-field" placeholder="Street Address" value="<?= htmlspecialchars($idea['address']) ?>" required>
                </div>

                <div class="pref-sec" style="margin-bottom: 15px;">
                    <div class="price-container">
                        <span class="currency-symbol">R</span>
                        <input type="number" name="price" class="input-field price-input" placeholder="Set selling price" min="0" step="0.01" value="<?= htmlspecialchars($idea['price']) ?>" required>
                    </div>
                </div>

                <div class="pref-sec" style="margin-bottom: 15px;">
                    <textarea name="description" class="bio-input" rows="4" placeholder="Give a brief summary..." required><?= htmlspecialchars($idea['description']) ?></textarea>
                </div>

                <div class="rating-section" style="margin-bottom: 20px;">
                    <h4 style="margin-bottom: 15px;">Ratings & Scores</h4>
                    
                    <div class="rating-row" style="margin-bottom: 15px;">
                        <span class="rating-label">Vibe & Ambience</span>
                        <div class="rating-group">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <input type="radio" id="vibe-<?= $i ?>" name="vibe_rating" value="<?= $i ?>" <?= $idea['vibe_rating'] == $i ? 'checked' : '' ?> /><label for="vibe-<?= $i ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="rating-row" style="margin-bottom: 15px;">
                        <span class="rating-label">Safety Score</span>
                        <div class="rating-group">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <input type="radio" id="safety-<?= $i ?>" name="safety_rating" value="<?= $i ?>" <?= $idea['safety_rating'] == $i ? 'checked' : '' ?> /><label for="safety-<?= $i ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="rating-row" style="margin-bottom: 15px;">
                        <span class="rating-label">Overall Recommendation</span>
                        <div class="rating-group">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <input type="radio" id="rec-<?= $i ?>" name="recommend_rating" value="<?= $i ?>" <?= $idea['recommend_rating'] == $i ? 'checked' : '' ?> /><label for="rec-<?= $i ?>">★</label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <input type="file" name="image_path" accept="image/*" style="font-size: 13px; color: #666;">
                        <?php if($isEditMode && !empty($idea['image_path'])): ?>
                            <span style="font-size:11px; color:green; display:block;">Current photo saved.</span>
                        <?php endif; ?>
                    </div>
                    
                    <div style="display:flex; gap:10px;">
                        <button type="submit" class="control-btn link-btn" style="padding: 12px 24px; cursor: pointer;">
                            <?= $isEditMode ? 'Save Changes' : 'Post Idea' ?>
                        </button>
                    </div>
                </div>
            </form>
        </section>
    </main>
</body>
</html>