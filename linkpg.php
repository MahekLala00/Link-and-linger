<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php';

$user_id = $_SESSION['user_id'];

$matches = [];
$profile_photo = "uploads/default.png";

try {
    $stmt = $pdo->prepare("
        SELECT profile_photo
        FROM user_profiles
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $myProfile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($myProfile['profile_photo'])) {
        $profile_photo = $myProfile['profile_photo'];
    }

    $stmt = $pdo->prepare("
        SELECT
            age_id,
            preferred_race_id,
            relationship_type,
            prefminedu_id
        FROM user_preferences
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $preferences = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT preferred_gender_id
        FROM preferred_gender
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $preferred_genders = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $sql = "
    SELECT
        u.user_id,
        u.username,
        u.full_name,
        u.dob,
        p.gender_id,
        p.race_id,
        p.profession_id,
        p.smoking_status,
        p.drinking_status,
        p.height,
        p.userminedu_id,
        p.city,
        p.bio,
        p.profile_photo
    FROM users u
    INNER JOIN user_profiles p
        ON u.user_id = p.user_id
    WHERE u.user_id != ?
";

    $params = [$user_id];

    if (!empty($preferred_genders) && !in_array('gen4', $preferred_genders)) {
        $placeholders = implode(',', array_fill(0, count($preferred_genders), '?'));
        $sql .= " AND p.gender_id IN ($placeholders)";
        foreach ($preferred_genders as $gender) {
            $params[] = $gender;
        }
    }

    if (!empty($preferences['preferred_race_id']) &&
        $preferences['preferred_race_id'] !== 'race01') {

        $sql .= " AND p.race_id = ?";
        $params[] = $preferences['preferred_race_id'];
    }

    if (!empty($preferences['prefminedu_id']) &&
        $preferences['prefminedu_id'] !== 'min1') {

        $sql .= " AND p.userminedu_id = ?";
        $params[] = $preferences['prefminedu_id'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($matches as &$match) {

    // Hobbies
    $stmtHobbies = $pdo->prepare("
        SELECT h.hobby_name
        FROM user_hobbies uh
        INNER JOIN hobbies h
            ON uh.user_hobby_id = h.hobby_id
        WHERE uh.user_id = ?
    ");
    $stmtHobbies->execute([$match['user_id']]);

    $match['hobbies'] = implode(
        ', ',
        $stmtHobbies->fetchAll(PDO::FETCH_COLUMN)
    );

    // Languages
    $stmtLanguages = $pdo->prepare("
        SELECT l.language_name
        FROM user_languages ul
        INNER JOIN languages l
            ON ul.language_id = l.language_id
        WHERE ul.user_id = ?
    ");
    $stmtLanguages->execute([$match['user_id']]);

    $match['languages'] = implode(
        ', ',
        $stmtLanguages->fetchAll(PDO::FETCH_COLUMN)
    );
}
unset($match);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}

$gender_labels = [
    'gen1' => 'Male',
    'gen2' => 'Female',
    'gen3' => 'Non-Binary'
];

$race_labels = [
    'race02' => 'African',
    'race03' => 'White',
    'race04' => 'Indian',
    'race05' => 'Coloured',
    'race06' => 'Asian',
    'race07' => 'Other'
];

$education_labels = [
    'min2' => 'High School',
    'min3' => 'Diploma',
    'min4' => 'Degree',
    'min5' => 'Honours',
    'min6' => 'Masters',
    'min7' => 'Other',
    'min8' => 'None'
];
$profession_labels = [];

try {
    $stmt = $pdo->query("
        SELECT profession_id, profession_name
        FROM professions
    ");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $profession_labels[$row['profession_id']] = $row['profession_name'];
    }

} catch(PDOException $e) {
    // Ignore if profession table fails
}
function calculateAge($dob)
{
    if (empty($dob)) {
        return 'Not Set';
    }

    $birthDate = new DateTime($dob);
    $today = new DateTime();

    return $birthDate->diff($today)->y;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>

<nav class="navbar">

    <div class="nav-logo">
        Link & Linger
    </div>

    <div class="nav-links">
        <a href="linkpg.php" class="active">Link</a>
        <a href="linger.php">Linger</a>
        <a href="messages.php">Chat</a>
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

    <div class="feed-header">
        <div class="header-title">
            <h2>Suggested Matches</h2>
        </div>
    </div>

    <div class="ideas-feed">

        <?php if (!empty($matches)): ?>
            <?php foreach ($matches as $match): ?>
                <div class="idea-card">
                    <div style="text-align:center; margin-bottom:15px;">
                        <img src="<?php echo !empty($match['profile_photo']) ? htmlspecialchars($match['profile_photo']) : 'uploads/default.png'; ?>"alt="Profile Photo" style="margin-top: 40px;width:150px; height:150px; border-radius:50%; object-fit:cover; border:2px solid #ddd;">
                    </div>
                    <div class="idea-details">
                        <div class="idea-main-info">
                            <div class="card-header-row">
                                <h3><?php echo htmlspecialchars($match['full_name']); ?></h3>
                                <p>
                                    @<?php echo htmlspecialchars($match['username']); ?>
                                </p>
                            </div>
                            <hr>
                            <div class="rating-tags-container" style="margin-top:12px;">
                                <break>
                                <div class="idea-description">
                                    <strong>Gender:</strong>
                                    <?php echo $gender_labels[$match['gender_id']] ?? 'Not Set'; ?>
                                </div>
                                <div class="idea-description">
                                    <strong>Age:</strong>
                                    <?php echo calculateAge($match['dob']); ?>
                                </div>
                                <div class="idea-description">
                                    <strong>Race:</strong>
                                    <?php echo $race_labels[$match['race_id']] ?? 'Not Set'; ?>
                                </div>
                                <div class="idea-description">
                                    <strong>Height:</strong>
                                    <?php echo htmlspecialchars($match['height']); ?> cm
                                </div>
                                <div class="idea-description">
                                    <strong>Education:</strong>
                                    <?php echo $education_labels[$match['userminedu_id']] ?? 'Not Set'; ?>
                                </div>
                                <div class="idea-description">
                                    <strong>Profession:</strong>
                                    <?php echo $profession_labels[$match['profession_id']] ?? 'Not Set'; ?>
                                </div>
                                <div class="idea-description">
                                    <strong>City:</strong>
                                    <?php echo htmlspecialchars($match['city']); ?>
                                </div>
                                <div class="idea-description">
                                    <strong>Smoking:</strong>
                                    <?php echo htmlspecialchars($match['smoking_status']); ?>
                                </div>
                                <div class="idea-description">
                                    <strong>Drinking:</strong>
                                    <?php echo htmlspecialchars($match['drinking_status']); ?>
                                </div>
                                <div class="idea-description">
                                    <strong>Languages:</strong>
                                    <?php echo !empty($match['languages']) ? htmlspecialchars($match['languages']) : 'Not Set'; ?>
                                </div>

                                <div class="idea-description">
                                    <strong>Hobbies:</strong>
                                    <?php echo !empty($match['hobbies']) ? htmlspecialchars($match['hobbies']) : 'Not Set'; ?>
                                </div>
                                <?php if (!empty($match['bio'])): ?>

                                    <div class="idea-description">
                                        <strong>About:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($match['bio'])); ?>
                                    </div>

                                <?php endif; ?>

                            </div>

                        </div>

                        <div class="idea-footer">

                            <a href="send_link_request.php?id=<?php echo $match['user_id']; ?>"
                            class="action-btn buy-btn">
                                Link
                            </a>

                        </div>

                    </div>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <div class="idea-card">
                <div class="idea-details">
                    <p>No matching profiles found yet.</p>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>
</body>
</html>