<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];

    $age_id = $_POST['age_id'] ?? null;
    $preferred_race_id = $_POST['preferred_race'] ?? null;
    $relationship_type = $_POST['relationship_type'] ?? null;
    $prefminedu_id = $_POST['prefminedu_id'] ?? null;

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences (
                user_id, 
                age_id, 
                preferred_race_id, 
                relationship_type, 
                prefminedu_id) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                age_id = VALUES(age_id),
                preferred_race_id = VALUES(preferred_race_id),
                relationship_type = VALUES(relationship_type),
                prefminedu_id = VALUES(prefminedu_id)");

        $stmt->execute([
            $user_id,
            $age_id,
            $preferred_race_id,
            $relationship_type,
            $prefminedu_id
        ]);
        
        $stmt = $pdo->prepare("DELETE FROM preferred_gender WHERE user_id = ?");
        $stmt->execute([$user_id]);

        if (!empty($_POST['preferred_gender']) && is_array($_POST['preferred_gender'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO preferred_gender (user_id, preferred_gender_id) VALUES (?, ?)");
            
            $unique_genders = array_unique($_POST['preferred_gender']);
            
            foreach ($unique_genders as $gender_id) {
                $stmt->execute([$user_id, $gender_id]);
            }
        }
        
        $stmt = $pdo->prepare("UPDATE users SET onboarding_complete = 3 WHERE user_id = ?");
        $stmt->execute([$user_id]);

        $pdo->commit();

        $_SESSION['onboarding_complete'] = 3;
        
        header("Location: dash.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error saving preferences: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name= "viewport" content="width=device-width,initial-scale=1.0"/>
	<title>Preference Page</title>
	<link rel="stylesheet" href="page.css">

</head>
<body>
    <style>
            body{
                background: linear-gradient(to top, rgba(0,0,0,0.1) 50%, rgba(0,0,0,0.1) 50%), url(background.png);
                background-attachment: fixed;
                background-position: center;
                background-repeat: no-repeat;
                background-size: cover;
            }
    </style>    
    <main>
    
        <div class="pref-header">
            <a href="dash.php" class="back-linkbtn">Back</a>
        </div>
        <div class="pref-container">
            <h2>Your Preferences</h2>
            <hr><br>
            <form id="pref-form" method="POST" action="prefpg.php" enctype="multipart/form-data">   
                <section class="pref-section">
                    <label for="ages">Preferred Age Range</label>
                    <select name="age_id" id="ages" class="styled-select">
                        <option value="age1">18 - 25</option>
                        <option value="age2">26 - 30</option>
                        <option value="age3">31 - 35</option>
                        <option value="age4">36 - 40</option>              
                        <option value="age5">41 - 50</option>
                        <option value="age6">51 - 60</option>
                        <option value="age7">60+</option>
                        <option value="age8">ANY Age</option>
                    </select>
                </section>

                <section class="pref-section">
                    <label>Interested In</label>
                    <div  class="pill-group">
                        <input type="checkbox" id="gen1" name="preferred_gender[]" value="gen1"><label for="gen1">Male</label>
                        <input type="checkbox" id="gen2" name="preferred_gender[]" value="gen2"><label for="gen2">Female</label>
                        <input type="checkbox" id="gen3" name="preferred_gender[]" value="gen3"><label for="gen3">Non-binary</label>
                        <input type="checkbox" id="gen4" name="preferred_gender[]" value="gen4"><label for="gen4">Open to all</label>
                    </div>
                </section>

                <section class="pref-section">
                    <label>Preferred Race</label>
                    <select name="preferred_race" class="styled-select">
                        <option value="race01">Open to all</option>
                        <option value="race02">African</option>
                        <option value="race03">White</option>
                        <option value="race04">Indian</option>
                        <option value="race05">Colored</option>
                        <option value="race06">Asian</option>
                    </select>
                </section>

                <section class="pref-section">
                    <label>Relationship Type</label>
                    <div class="pill-group">
                        <input type="radio" id="rel1" name="relationship_type" value="Serious">
                        <label for="rel1">Serious</label>

                        <input type="radio" id="rel2" name="relationship_type" value="Casual">
                        <label for="rel2">Casual</label>

                        <input type="radio" id="rel3" name="relationship_type" value="Friendship">
                        <label for="rel3">Just Looking</label>
                    </div>
                </section>

                <section class="pref-section">
                    <label for="education_pref">Minimum Education</label>
                    <select name="prefminedu_id" id="education_pref" class="styled-select">
                        <option value="min1">Any</option>
                        <option value="min2">High School</option>
                        <option value="min3">Diploma</option>
                        <option value="min4">Degree</option>
                        <option value="min5">Honours</option>
                        <option value="min6">Masters</option>
                        <option value="min7">Other</option>
                    </select>
                </section>

                <button type="submit" class="save-btn">Save Preferences & Continue</button>
            </form>
        </div>
    </main>
</body>
</html>