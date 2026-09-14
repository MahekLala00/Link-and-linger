<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$message = "";
$message_color = "#cd0f0f";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db_config.php';
    $user_id = $_SESSION['user_id'];
    $gender_id = $_POST['user_gender'] ?? null;
    $race_id = $_POST['race'] ?? null;
    $min_edu = $_POST['minedu_id'] ?? null;
    $profession_id = $_POST['profession'] ?? null;
    $smoking    = $_POST['smoking'] ?? null;
    $drinking   = $_POST['drinking'] ?? null;
    $height     = !empty($_POST['height']) ? intval($_POST['height']) : null;
    $city       = trim($_POST['city'] ?? '');
    $bio        = trim($_POST['bio'] ?? '');

    $photo_path = null; 
    if (isset($_FILES['profile_photos']) && $_FILES['profile_photos']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['profile_photos']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed)) {
            $new_filename = time() . '_profile_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_photos']['tmp_name'], $target_file)) {
                $photo_path = $target_file;
            }
        }
    }

    try {
        $pdo->beginTransaction();

        $checkStmt = $pdo->prepare("SELECT 1 FROM user_profiles WHERE user_id = ?");
        $checkStmt->execute([$user_id]);
        $profileExists = $checkStmt->fetch();

        if ($profileExists) {
            $query = "UPDATE user_profiles SET
                gender_id = ?,
                race_id = ?,
                userminedu_id = ?,
                profession_id = ?,
                smoking_status = ?,
                drinking_status = ?,
                height = ?,
                city = ?,
                bio = ?";

            $params = [$gender_id, $race_id, $min_edu, $profession_id, $smoking, $drinking, $height, $city, $bio];

            if ($photo_path) {
                $query .= ", profile_photo = ?";
                $params[] = $photo_path;
            }

            $query .= " WHERE user_id = ?";
            $params[] = $user_id;

        } else {
            $columns = ["user_id", "gender_id", "race_id", "userminedu_id", "profession_id", "smoking_status", "drinking_status", "height", "city", "bio"];
            $params = [$user_id, $gender_id, $race_id, $min_edu, $profession_id, $smoking, $drinking, $height, $city, $bio];

            if ($photo_path) {
                $columns[] = "profile_photo";
                $params[] = $photo_path;
            }

            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $columnString = implode(', ', $columns);

            $query = "INSERT INTO user_profiles ($columnString) VALUES ($placeholders)";
        }
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        $stmt = $pdo->prepare("DELETE FROM user_hobbies WHERE user_id = ?");
        $stmt->execute([$user_id]);

        if (!empty($_POST['hobbies']) && is_array($_POST['hobbies'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_hobbies (user_id, user_hobby_id) VALUES (?, ?)");
            $unique_hobbies = array_unique($_POST['hobbies']); 
            foreach ($unique_hobbies as $hobby_id) {
                $stmt->execute([$user_id, $hobby_id]);
            }
        }

        $stmt = $pdo->prepare("DELETE FROM user_languages WHERE user_id = ?");
        $stmt->execute([$user_id]);

        if (!empty($_POST['languages']) && is_array($_POST['languages'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO user_languages (user_id, language_id) VALUES (?, ?)");
            $unique_languages = array_unique($_POST['languages']);
            foreach ($unique_languages as $language_id) {
                $stmt->execute([$user_id, $language_id]);
            }
        }

        $_SESSION['completed_details'] = true;
        $stmt = $pdo->prepare("UPDATE users SET onboarding_complete = 2 WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $pdo->commit();

        header("Location: prefpg.php");
        exit();

    } catch (\PDOException $e) {
        $pdo->rollBack();
        $message = "Error saving your details: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
    <title>Your Details Page</title>
    <link rel="stylesheet" href="page.css">
    <style>
        body{
            background: linear-gradient(to top, rgba(0,0,0,0.1) 50%, rgba(0,0,0,0.1) 50%), url(background.png);
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .msg-box {
            color: #ff4d4d;
            margin-bottom: 15px;
            font-weight: bold;
            text-align: center;
            background-color: rgba(255, 77, 77, 0.1);
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<main>
    <div class="pref-header">
        <a href="dash.php" class="back-linkbtn">Back</a>
    </div>
    <div class="pref-container">
        <h2>Your Details</h2>
        <hr><br>

        <?php if (!empty($message)): ?>
            <div class="msg-box"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form id="pref-form" method="POST" enctype="multipart/form-data">
            <section class="pref-section">
                <label>I am</label>
                <div class="pill-group">
                    <input type="radio" id="gen1" name="user_gender" value="gen1"><label for="gen1">Male</label>
                    <input type="radio" id="gen2" name="user_gender" value="gen2"><label for="gen2">Female</label>
                    <input type="radio" id="gen3" name="user_gender" value="gen3"><label for="gen3">Non-binary</label>
                </div>
            </section>

            <section class="pref-section">
                <label>My Race</label>
                <select name="race" class="styled-select">
                    <option value="race07">Other</option>
                    <option value="race02">African</option>
                    <option value="race03">White</option>
                    <option value="race04">Indian</option>
                    <option value="race05">Coloured</option>
                    <option value="race06">Asian</option>
                </select>
            </section>

            <section class="pref-section">
                <label for="education_pref">Education</label>
                <select name="minedu_id" id="education_pref" class="styled-select">
                    <option value="min8">None</option>
                    <option value="min2">High School</option>
                    <option value="min3">Diploma</option>
                    <option value="min4">Degree</option>
                    <option value="min5">Honours</option>
                    <option value="min6">Masters</option>
                    <option value="min7">Other</option>
                </select>
            </section>

            <section class="pref-section">
                <label>Your Profession/Industry</label>
                <select name="profession" class="styled-select">
                    <optgroup label="Technology & Digital">
                        <option value="prof01">Software & IT</option>
                        <option value="prof02">Data & AI</option>
                        <option value="prof03">Design & Creative</option>
                        <option value="prof04">Gaming & Esports</option>
                    </optgroup> 
                    <optgroup label="Business & Finance">
                        <option value="prof05">Entrepreneur/ Founder</option>
                        <option value="prof06">Finance & Banking</option>
                        <option value="prof07">Marketing & PR</option>
                        <option value="prof08">Real Estate</option>
                    </optgroup>
                    <optgroup label="Healthcare & Science">
                        <option value="prof09">Medical / Healthcare</option>
                        <option value="prof10">Psychology / Counseling</option>
                        <option value="prof11">Science & Research</option>
                    </optgroup>
                    <optgroup label="Education & Law">
                        <option value="prof12">Student</option>
                        <option value="prof13">Education / Teaching</option>
                        <option value="prof14">Legal / Law</option>
                    </optgroup>
                    <optgroup label="Other">
                        <option value="prof15">Artisan / Trades</option>
                        <option value="prof16">Hospitality</option>
                        <option value="prof17">Fitness & Sport</option>
                        <option value="prof18">Other</option>
                        <option value="prof19">None</option>
                    </optgroup>
                </select>
            </section>

            <section class="pref-section">
                <label>Hobbies</label>
                <div class="pill-group">
                    <input type="checkbox" id="hob1" name="hobbies[]" value="hob1"><label for="hob1">Traveling</label>
                    <input type="checkbox" id="hob2" name="hobbies[]" value="hob2"><label for="hob2">Hiking</label>
                    <input type="checkbox" id="hob3" name="hobbies[]" value="hob3"><label for="hob3">Gyming</label>
                    <input type="checkbox" id="hob4" name="hobbies[]" value="hob4"><label for="hob4">Cooking</label>
                    <input type="checkbox" id="hob5" name="hobbies[]" value="hob5"><label for="hob5">Gaming</label>
                    <input type="checkbox" id="hob6" name="hobbies[]" value="hob6"><label for="hob6">Photography</label>
                    <input type="checkbox" id="hob7" name="hobbies[]" value="hob7"><label for="hob7">Reading</label>
                </div>
            </section>

            <section class="pref-section">
                <label>Languages Spoken</label>
                <div class="pill-group">
                    <input type="checkbox" id="lang1" name="languages[]" value="lang1"><label for="lang1">English</label>
                    <input type="checkbox" id="lang2" name="languages[]" value="lang2"><label for="lang2">Zulu</label>
                    <input type="checkbox" id="lang3" name="languages[]" value="lang3"><label for="lang3">Afrikaans</label>
                    <input type="checkbox" id="lang4" name="languages[]" value="lang4"><label for="lang4">Xhosa</label>
                    <input type="checkbox" id="lang5" name="languages[]" value="lang5"><label for="lang5">Other</label>
                </div>
            </section>

            <section class="pref-section">
                <label>Smoking</label>
                <select name="smoking" class="styled-select">
                    <option value="Non-Smoker">Non-Smoker</option>
                    <option value="Socially">Socially</option>
                    <option value="Smoker">Smoker</option>
                </select>
            </section>

            <section class="pref-section">
                <label>Drinking</label>
                <select name="drinking" class="styled-select">
                    <option value="Non-Drinker">Non-Drinker</option>
                    <option value="Socially">Socially</option>
                    <option value="Drinker">Drinker</option>
                </select>
            </section>

            <section class="pref-sec">
                <label for="height-input">Height</label>
                <input type="number" id="height-input" name="height" class="input-field" placeholder="Height in cm">
            </section>

            <section class="pref-sec">
                <label for="city-input">City</label>
                <input type="text" id="city-input" name="city" class="input-field" placeholder="Johannesburg">
            </section>

            <section class="pref-sec">
                <label for="bio-input">About Me</label>
                <textarea name="bio" class="bio-input" id="bioinput" rows="8" cols="20" maxlength="300" placeholder="Tell people about yourself"></textarea>
            </section>

            <section class="pref-sec">
                <label for="photos-input">Upload Profile Photos</label>
                <input type="file" id="photos-input" name="profile_photos" accept="image/*">
            </section>

            <button type="submit" class="save-btn">Save Details & Continue</button>
        </form>
    </div>    
    </main>
</body>
</html>