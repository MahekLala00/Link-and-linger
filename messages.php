<?php
    
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php';

$user_id = $_SESSION['user_id'];
$profile_photo = 'uploads/default.png';
$matches = [];
$messages = [];
$current_match = null;
$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : null;

try {

    // Current user's photo
    $stmt = $pdo->prepare("
        SELECT profile_photo
        FROM user_profiles
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);

    $me = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($me['profile_photo'])) {
        $profile_photo = $me['profile_photo'];
    }

    // Load matches
    $stmt = $pdo->prepare("
        SELECT
            m.match_id,
            u.user_id,
            u.full_name,
            p.profile_photo
        FROM matches m
        JOIN users u
            ON (
                CASE
                    WHEN m.user1_id = ?
                    THEN m.user2_id
                    ELSE m.user1_id
                END = u.user_id
            )
        LEFT JOIN user_profiles p
            ON u.user_id = p.user_id
        WHERE m.user1_id = ?
        OR m.user2_id = ?
    ");

    $stmt->execute([
        $user_id,
        $user_id,
        $user_id
    ]);

    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send message
    // Send message securely
    if (
        $_SERVER['REQUEST_METHOD'] === 'POST'
        && !empty($_POST['message'])
        && !empty($_POST['match_id'])
    ) {
        $post_match_id = $_POST['match_id'];
        $is_valid_match = false;

        // Verify the user actually belongs to this match ID before inserting
        foreach ($matches as $match) {
            if ($match['match_id'] == $post_match_id) {
                $is_valid_match = true;
                break;
            }
        }

        if ($is_valid_match) {
            $stmt = $pdo->prepare("
                INSERT INTO messages 
                (match_id, sender_id, message_text) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $post_match_id,
                $user_id,
                trim($_POST['message'])
            ]);

            header("Location: messages.php?match_id=".$post_match_id);
            exit();
        } else {
            // Quietly bounce them back if they try to post to a match that isn't theirs
            header("Location: messages.php");
            exit();
        }
    }

    // Load current conversation
    if ($match_id) {

        foreach ($matches as $match) {

            if ($match['match_id'] == $match_id) {
                $current_match = $match;
                break;
            }
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM messages
            WHERE match_id = ?
            ORDER BY created_at ASC
        ");

        $stmt->execute([$match_id]);

        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch(PDOException $e) {

    die("Error: ".$e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Chat</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-logo">
        Link & Linger
    </div>
    <div class="nav-links">
        <a href="linkpg.php">Link</a>
        <a href="linger.php">Linger</a>
        <a href="messages.php" class="active">Chat</a>
        <a href="notifications.php">Notifications</a>
        <a href="dash.php">My Dashboard</a>
    </div>
    <div class="nav-actions">
        <a href="logout.php" class="pref-toggle-btn">Logout</a>
        <div class="user-avatar"
             style="background-image:url('<?php echo htmlspecialchars($profile_photo); ?>');">
        </div>
    </div>
    </nav>
    <div class="discover-container">
        <div class="chat-wrapper">
            <div class="chat-layout">
                <div class="matches-panel">
                    <div class="matches-header">
                        My Matches
                    </div>
                    <?php foreach($matches as $match): ?>

                        <a href="messages.php?match_id=<?php echo $match['match_id']; ?>"
                        class="match-user <?php echo ($match_id == $match['match_id']) ? 'active' : ''; ?>">

                            <div class="match-avatar"
                                style="background-image:url('<?php echo !empty($match['profile_photo']) ? htmlspecialchars($match['profile_photo']) : 'uploads/default.png'; ?>');">
                            </div>

                            <div>
                                <strong>
                                    <?php echo htmlspecialchars($match['full_name']); ?>
                                </strong>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="chat-panel">
                    <?php if($current_match): ?>
                        <div class="chat-header">
                            <strong>
                                <?php echo htmlspecialchars($current_match['full_name']); ?>
                            </strong>
                        </div>
                        <div class="chat-messages">
                            <?php foreach($messages as $message): ?>
                                <div class="message <?php echo ($message['sender_id'] == $user_id) ? 'sent' : 'received'; ?>">
                                    <?php echo htmlspecialchars($message['message_text']); ?>
                                    <div class="message-time">
                                        <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <form method="POST" class="chat-input">
                            <input type="hidden"
                                name="match_id"
                                value="<?php echo $match_id; ?>">
                            <input
                                type="text"
                                name="message"
                                placeholder="Type a message..."
                                required>
                            <button type="submit">
                                Send
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="chat-messages">
                            <h3>Select a match to start chatting</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>