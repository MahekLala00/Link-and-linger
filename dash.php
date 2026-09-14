<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php';

$user_id = $_SESSION['user_id'];

$profile_photo = 'uploads/default.png'; 
$profile_data = null;
$preferences_data = null;
$user_hobbies = [];
$user_languages = [];
$user_reports = [];

$gender_labels = [
    'gen1' => 'Male',
    'gen2' => 'Female',
    'gen3' => 'Non-Binary',
    'gen4' => 'Open to all'
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

$profession_labels = [
    'prof01' => 'Software & IT',
    'prof02' => 'Data & AI',
    'prof03' => 'Design & Creative',
    'prof04' => 'Gaming & Esports',
    'prof05' => 'Entrepreneur / Founder',
    'prof06' => 'Finance & Banking',
    'prof07' => 'Marketing & PR',
    'prof08' => 'Real Estate',
    'prof09' => 'Medical / Healthcare',
    'prof10' => 'Psychology / Counseling',
    'prof11' => 'Science & Research',
    'prof12' => 'Student',
    'prof13' => 'Education / Teaching',
    'prof14' => 'Legal / Law',
    'prof15' => 'Artisan / Trades',
    'prof16' => 'Hospitality',
    'prof17' => 'Fitness & Sport',
    'prof18' => 'Other',
    'prof19' => 'None'
];

$hobby_labels = [
    'hob1' => 'Traveling',
    'hob2' => 'Hiking',
    'hob3' => 'Gyming',
    'hob4' => 'Cooking',
    'hob5' => 'Gaming',
    'hob6' => 'Photography',
    'hob7' => 'Reading'
];

$language_labels = [
    'lang1' => 'English',
    'lang2' => 'Zulu',
    'lang3' => 'Afrikaans',
    'lang4' => 'Xhosa',
    'lang5' => 'Other'
];

try {
    $stmt = $pdo->prepare("
        SELECT
            gender_id,
            race_id,
            userminedu_id,
            profession_id,
            smoking_status,
            drinking_status,
            height,
            city,
            bio,
            profile_photo
        FROM user_profiles
        WHERE user_id = ?
    ");

    $stmt->execute([$user_id]);
    $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($profile_data && !empty($profile_data['profile_photo'])) {
        $profile_photo = $profile_data['profile_photo'];
    }

    $stmt = $pdo->prepare("
        SELECT user_hobby_id
        FROM user_hobbies
        WHERE user_id = ?
    ");

    $stmt->execute([$user_id]);
    $user_hobbies = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Languages
    $stmt = $pdo->prepare("
        SELECT language_id
        FROM user_languages
        WHERE user_id = ?
    ");

    $stmt->execute([$user_id]);
    $user_languages = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Preferences
    $stmt = $pdo->prepare("
        SELECT relationship_type
        FROM user_preferences
        WHERE user_id = ?
    ");

    $stmt->execute([$user_id]);
    $preferences_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $age_labels = [
        'age1' => '18 - 25',
        'age2' => '26 - 30',
        'age3' => '31 - 35',
        'age4' => '36 - 40',
        'age5' => '41 - 50',
        'age6' => '51 - 60',
        'age7' => '60+',
        'age8' => 'Any Age'
    ];

    $stmt = $pdo->prepare("
        SELECT
            age_id,
            preferred_race_id,
            relationship_type,
            prefminedu_id
        FROM user_preferences
        WHERE user_id = ?
    ");

    $relationship_labels = [
        'Serious' => 'Serious',
        'Casual' => 'Casual',
        'Friendship' => 'Just Looking'
    ];

    $stmt->execute([$user_id]);
    $preferences_data = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT preferred_gender_id
        FROM preferred_gender
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $preferred_genders = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch reports submitted by the logged-in user (joined with core users table)
    $report_stmt = $pdo->prepare("
        SELECT r.reason, r.status, u.username AS reported_user 
        FROM reports r
        JOIN users u ON r.reported_id = u.user_id
        WHERE r.reporter_id = ?
        ORDER BY r.report_id DESC
    ");
    $report_stmt->execute([$user_id]);
    $user_reports = $report_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Fail silently or log error if needed
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Dashboard</title>
    <link rel="stylesheet" href="page.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <nav class="navbar">

        <div class="nav-logo">
            Link & Linger
        </div>

        <div class="nav-links">
            <a href="linkpg.php">Link</a>
            <a href="linger.php">Linger</a>
            <a href="messages.php">Chat</a>
            <a href="notifications.php">Notifications</a>
            <a href="mydash.php" class="active">My Dashboard</a>
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
                <h2>Welcome Back to Your Hub</h2>
            </div>
        </div>

        <div class="ideas-feed">
            
            <div class="idea-card">
                <div class="idea-details">
                    <div class="idea-main-info">
                        <div class="card-header-row">
                            <h3>My Details</h3>
                            <span class="distance-text">Identity</span>
                        </div>
                        <hr>
                        <div class="rating-tags-container" style="margin-top:12px;">
                            <?php if ($profile_data): ?>

                                <div class="idea-description">
                                    <strong>Gender:</strong>
                                    <?php echo $gender_labels[$profile_data['gender_id']] ?? 'Not Set'; ?>
                                </div>
                                <br>
                                <div class="idea-description">
                                    <strong>Race:</strong>
                                    <?php echo $race_labels[$profile_data['race_id']] ?? 'Not Set'; ?>
                                </div>

                                <div class="idea-description">
                                    options:<strong>Education:</strong>
                                    <?php echo $education_labels[$profile_data['userminedu_id']] ?? 'Not Set'; ?>
                                </div>

                                <div class="idea-description">
                                    <strong>Profession:</strong>
                                    <?php echo $profession_labels[$profile_data['profession_id']] ?? 'Not Set'; ?>
                                </div>

                                <div class="idea-description">
                                    <strong>Hobbies:</strong>
                                    <?php
                                    if (!empty($user_hobbies)) {
                                        $hobbies = [];

                                        foreach ($user_hobbies as $id) {
                                            if (isset($hobby_labels[$id])) {
                                                $hobbies[] = $hobby_labels[$id];
                                            }
                                        }

                                        echo !empty($hobbies) ? implode(', ', $hobbies) : 'None Selected';
                                    } else {
                                        echo 'None Selected';
                                    }
                                    ?>
                                </div>

                                <div class="idea-description">
                                    <strong>Languages:</strong>
                                    <?php
                                    if (!empty($user_languages)) {
                                        $langs = [];

                                        foreach ($user_languages as $id) {
                                            if (isset($language_labels[$id])) {
                                                $langs[] = $language_labels[$id];
                                            }
                                        }

                                        echo !empty($langs) ? implode(', ', $langs) : 'None Selected';
                                    } else {
                                        echo 'None Selected';
                                    }
                                    ?>
                                </div>

                                <div class="idea-description">
                                    <strong>City:</strong>
                                    <?php echo htmlspecialchars($profile_data['city'] ?? 'Not Set'); ?>
                                </div>

                                <div class="idea-description">
                                    <strong>Height:</strong>
                                    <?php echo htmlspecialchars($profile_data['height'] ?? '0'); ?> cm
                                </div>

                                <div class="idea-description">
                                    <strong>Smoking:</strong>
                                    <?php echo htmlspecialchars($profile_data['smoking_status'] ?? 'Not Set'); ?>
                                </div>

                                <div class="idea-description">
                                    <strong>Drinking:</strong>
                                    <?php echo htmlspecialchars($profile_data['drinking_status'] ?? 'Not Set'); ?>
                                </div>

                                <?php if (!empty($profile_data['bio'])): ?>
                                    <p class="idea-description" style="margin-top:10px;">
                                        <strong>About Me:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($profile_data['bio'])); ?>
                                    </p>
                                <?php endif; ?>


                            <?php else: ?>

                                <p class="idea-description">
                                    No profile details saved yet.
                                </p>

                            <?php endif; ?>

                        </div>
                    </div>
                    <div class="idea-footer">
                        <a href="yourpg.php" class="action-btn buy-btn">Modify Details</a>
                    </div>
                </div>
            </div>

            <div class="idea-card">
                <div class="idea-details">
                    <div class="idea-main-info">
                        <div class="card-header-row">
                            <h3>My Preferences</h3>
                            <span class="distance-text">Filters</span>
                        </div>
                        <hr>
                        <div class="rating-tags-container" style="margin-top: 12px;">

                            <?php if ($preferences_data): ?>

                                <div class="idea-description">
                                    <strong>Age Range:</strong>
                                    <?php echo $age_labels[$preferences_data['age_id']] ?? 'Any'; ?>
                                </div>
                                <br>
                                <div class="idea-description">
                                    <strong>Interested In:</strong>
                                    <?php
                                    if (!empty($preferred_genders)) {
                                        $list = [];

                                        foreach ($preferred_genders as $gid) {
                                            $list[] = $gender_labels[$gid] ?? $gid;
                                        }

                                        echo implode(', ', $list);
                                    } else {
                                        echo 'Not Set';
                                    }
                                    ?>
                                </div>

                                <div class="idea-description">
                                    <strong>Preferred Race:</strong>
                                    <?php echo $race_labels[$preferences_data['preferred_race_id']] ?? 'Any'; ?>
                                </div>

                                <div class="idea-description">
                                    <strong>Relationship Type:</strong>
                                    <?php echo htmlspecialchars($preferences_data['relationship_type'] ?? 'Not Set'); ?>
                                </div>

                                <div class="idea-description">
                                    <strong>Minimum Education:</strong>
                                    <?php echo $education_labels[$preferences_data['prefminedu_id']] ?? 'Any'; ?>
                                </div>

                            <?php else: ?>

                                <p class="idea-description">No preferences configured yet.</p>

                            <?php endif; ?>

                        </div>
                    </div>
                    <div class="idea-footer">
                        <a href="prefpg.php" class="action-btn buy-btn">Modify Preferences</a>
                    </div>
                </div>
            </div>

            <div class="idea-card">
                <div class="idea-details">
                    <div class="idea-main-info">
                        <div class="card-header-row">
                            <h3>Your Reports</h3>
                            <span class="distance-text">History</span>
                        </div> 
                        <hr>
                        
                        <div class="reports-list" style="margin-top: 15px; max-height: 180px; overflow-y: auto; padding-right: 5px;">
                            <?php if (!empty($user_reports)): ?>
                                <?php foreach ($user_reports as $report): ?>
                                    <div class="report-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #f0f0f0; font-size: 14px;">
                                        <div>
                                            <span style="font-weight: bold; color: #333;">@<?= htmlspecialchars($report['reported_user']) ?></span>
                                            <br>
                                            <small style="color: #666;"><?= htmlspecialchars($report['reason']) ?></small>
                                        </div>
                                        
                                        <?php 
                                            $status_color = '#e67e22'; // Orange default for Pending
                                            if ($report['status'] === 'Resolved') $status_color = '#2ecc71'; // Green
                                            if ($report['status'] === 'Rejected') $status_color = '#e74c3c'; // Red
                                        ?>
                                        <span style="background: <?= $status_color ?>; color: white; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; white-space: nowrap;">
                                            <?= htmlspecialchars($report['status']) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #888; font-size: 13px; font-style: italic; margin: 15px 0 5px 0;">You haven't submitted any reports yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="idea-footer" style="margin-top: 15px;">
                        <a href="reports.php" class="action-btn buy-btn" style="width: 100%; text-align: center; display: inline-block;">File a New Report</a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>