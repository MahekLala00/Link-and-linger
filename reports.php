<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php';

$message = "";
$message_color = "green";
$reported_username = "";

// If linked dynamically via GET parameters from another user's profile card
if (isset($_GET['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE user_id = ?");
        $stmt->execute([$_GET['user_id']]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($profile) {
            $reported_username = $profile['username'];
        }
    } catch (PDOException $e) {
        // Fallback gracefully
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reporter_id = $_SESSION['user_id'];
    $reason = trim($_POST['reason'] ?? '');
    $typed_username = trim($_POST['reported_username'] ?? '');

    if (empty($typed_username) || empty($reason)) {
        $message = "Please complete all required fields.";
        $message_color = "red";
        $reported_username = $typed_username;
    } else {
        try {
            // 1. LOOKUP THE CORRECT INT/VARCHAR ID USING THE TYPED USERNAME
            $user_stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $user_stmt->execute([$typed_username]);
            $found_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$found_user) {
                $message = "User '@" . htmlspecialchars($typed_username) . "' not found. Please verify the spelling.";
                $message_color = "red";
                $reported_username = $typed_username;
            } 
            // 2. SELF-REPORTING BLOCK
            elseif ($reporter_id == $found_user['user_id']) {
                $message = "You cannot report yourself.";
                $message_color = "red";
                $reported_username = $typed_username;
            } 
            // 3. EXECUTE VALID INSERTION MATCHING YOUR EXACT DATABASE COLUMNS
            else {
                $reported_id = $found_user['user_id'];
                $report_id = uniqid('rep');
                $screenshot_path = null;

                // Handle optional screenshot upload
                if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'report_uploads/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $file_ext = strtolower(pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png'];

                    if (in_array($file_ext, $allowed)) {
                        $new_filename = 'report_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
                        $target_file = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $target_file)) {
                            $screenshot_path = $target_file;
                        }
                    }
                }

                // report_id, reporter_id, reported_id, reason, status, screenshot
                $stmt = $pdo->prepare("
                    INSERT INTO reports (report_id, reporter_id, reported_id, reason, status, screenshot)
                    VALUES (?, ?, ?, ?, 'Pending', ?)
                ");

                $stmt->execute([
                    $report_id,
                    $reporter_id,
                    $reported_id,
                    $reason,
                    $screenshot_path
                ]);

                $message = "Report submitted successfully against @" . htmlspecialchars($typed_username) . ".";
                $message_color = "green";
                $reported_username = ""; // Clear out text field on success
            }

        } catch (PDOException $e) {
            $message = "Error submitting report: " . $e->getMessage();
            $message_color = "red";
            $reported_username = $typed_username;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report User</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>
    
    <div class="pref-header" style="margin-left: 50px">
        <a href="dash.php" class="back-linkbtn">Back</a>
    </div>

<div class="discover-container" style="max-width: 500px; margin: 40px auto; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">

    <h2>Report User</h2>
    

    <?php if (!empty($message)): ?>
        <p style="color: <?= $message_color ?>; font-weight: bold; padding: 10px; background: #f9f9f9; border-radius: 4px;">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div style="margin-bottom: 15px;">
            <label for="reported_username" style="display:block; font-weight:bold; margin-bottom:5px;">Target Username</label>
            <div style="position: relative; display: flex; align-items: center;">
                <span style="position: absolute; left: 10px; color: #888;">@</span>
                <input 
                    type="text" 
                    name="reported_username" 
                    id="reported_username" 
                    placeholder="Enter exact username" 
                    value="<?= htmlspecialchars($reported_username) ?>" 
                    required
                    style="width: 100%; padding: 10px 10px 10px 25px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;"
                >
            </div>
        </div>

        <div style="margin-bottom: 15px;">
            <label for="reason" style="display:block; font-weight:bold; margin-bottom:5px;">Reason</label>
            <select name="reason" id="reason" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
                <option value="">Select a reason</option>
                <option value="Fake Profile">Fake Profile</option>
                <option value="Harassment">Harassment</option>
                <option value="Spam">Spam</option>
                <option value="Inappropriate Content">Inappropriate Content</option>
                <option value="Other">Other</option>
            </select>
        </div>

        <div style="margin-bottom: 20px;">
            <label for="screenshot" style="display:block; font-weight:bold; margin-bottom:5px;">Upload Screenshot (Optional)</label>
            <input 
                type="file" 
                name="screenshot" 
                id="screenshot" 
                accept=".jpg,.jpeg,.png"
                style="font-size: 14px;"
            >
        </div>

        <button type="submit" style="width: 100%; padding: 12px; background: #a47ae8; color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer;">
            Submit Report
        </button>

    </form>

</div>

</body>
</html>