<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php';

if (!isset($_GET['id'])) {
    die("Invalid profile.");
}

$profile_user_id = $_GET['id'];

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
            u.full_name,
            u.username,
            u.dob,
            p.*
        FROM users u
        INNER JOIN user_profiles p
            ON u.user_id = p.user_id
        WHERE u.user_id = ?
    ");

    $stmt->execute([$profile_user_id]);

    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Error: " . $e->getMessage());
}
$stmt = $pdo->prepare("
    SELECT user_hobby_id
    FROM user_hobbies
    WHERE user_id = ?
");

$stmt->execute([$profile_user_id]);
$user_hobbies = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare("
    SELECT language_id
    FROM user_languages
    WHERE user_id = ?
");
$stmt->execute([$profile_user_id]);
$user_languages = $stmt->fetchAll(PDO::FETCH_COLUMN);

function calculateAge($dob)
{
    if (empty($dob)) {
        return 'Not Set';
    }

    return (new DateTime($dob))
        ->diff(new DateTime())
        ->y;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>view Profile</title>
    <link rel="stylesheet" href="page.css">
</head>
<body>
    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-image">
                <img src="<?php echo !empty($profile['profile_photo'])
                    ? htmlspecialchars($profile['profile_photo'])
                    : 'uploads/default.png'; ?>">
            </div>

            <div class="profile-content">

                <div class="profile-name">
                    <?php echo htmlspecialchars($profile['full_name']); ?>
                </div>

                <div class="profile-username">
                    @<?php echo htmlspecialchars($profile['username']); ?>
                </div>

                <hr>
                <div class="profile-grid">
                    <div class="profile-item">
                        <strong>Age:</strong>
                        <?php echo calculateAge($profile['dob']); ?>
                    </div>

                    <div class="profile-item">
                        <strong>Gender:</strong>
                        <?php echo $gender_labels[$profile['gender_id']] ?? 'Not Set'; ?>
                    </div>

                    <div class="profile-item">
                        <strong>Race:</strong>
                        <?php echo $race_labels[$profile['race_id']] ?? 'Not Set'; ?>
                    </div>

                    <div class="profile-item">
                        <strong>Education:</strong>
                        <?php echo $education_labels[$profile['userminedu_id']] ?? 'Not Set'; ?>
                    </div>

                    <div class="profile-item">
                        <strong>Profession:</strong>
                        <?php echo $profession_labels[$profile['profession_id']] ?? 'Not Set'; ?>
                    </div>

                    <div class="profile-item">
                        <strong>Height:</strong>
                        <?php echo htmlspecialchars($profile['height']); ?> cm
                    </div>

                    <div class="profile-item">
                        <strong>Smoking:</strong>
                        <?php echo htmlspecialchars($profile['smoking_status']); ?>
                    </div>

                    <div class="profile-item">
                        <strong>Drinking:</strong>
                        <?php echo htmlspecialchars($profile['drinking_status']); ?>
                    </div>

                    <div class="profile-item">
                        <strong>City:</strong>
                        <?php echo htmlspecialchars($profile['city']); ?>
                    </div>

                </div>

                <div class="idea-description">
                    <strong>Hobbies:</strong>
                    <?php
                    $hobbies = [];
                    foreach ($user_hobbies as $id) {
                        if (isset($hobby_labels[$id])) {
                            $hobbies[] = $hobby_labels[$id];
                        }
                    }
                    echo !empty($hobbies)
                        ? implode(', ', $hobbies)
                        : 'Not Set';
                    ?>
                </div>
                <div class="idea-description">
                    <strong>Languages:</strong>

                    <?php

                    $langs = [];

                    foreach ($user_languages as $id) {
                        if (isset($language_labels[$id])) {
                            $langs[] = $language_labels[$id];
                        }
                    }

                    echo !empty($langs)
                        ? implode(', ', $langs)
                        : 'Not Set';
                    ?>
                </div>
                <div class="idea-description">
                    <strong>About Me:</strong><br>

                    <?php echo nl2br(htmlspecialchars($profile['bio'])); ?>
                </div>
                <div class="idea-footer">

                    <a href="javascript:history.back()"
                    class="action-btn buy-btn">
                        Back
                    </a>

                </div>
        </div>
    </div>  
</body>
</html>