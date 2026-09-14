<?php
session_start();

$message = "";
$message_color = "#ff4d4d";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'db_config.php';

    $fullName    = trim($_POST['fullName'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $dob         = $_POST['dob'] ?? '';
    $email       = trim($_POST['email'] ?? '');
    $password    = $_POST['password'] ?? '';
    $idType      = $_POST['id_type'] ?? '';
    $idNumber    = trim($_POST['id_number'] ?? '');
    
    if (!empty($_POST['username'])) {
        $username = trim($_POST['username']);
    } else {
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $fullName)) . rand(1000, 9999);
    }

    if (empty($email) || empty($password) || empty($username)) {
        $message = "Please fill in all required fields.";
    } else {
        $hashedPassword = $password;
        $role = 'user';
        $verification_status = 'pending';

        try {
            $check_stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
            $check_stmt->execute([$email, $username]);
            
            if ($check_stmt->fetch()) {
                $message = "An account with this email or username already exists.";
            } else {
                $upload_dir = 'uploads/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                } 

                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
                
                $id_ext = strtolower(pathinfo($_FILES['id_document_url']['name'], PATHINFO_EXTENSION));
                $selfie_ext = strtolower(pathinfo($_FILES['selfie_with_id_url']['name'], PATHINFO_EXTENSION));

                if (!in_array($id_ext, $allowed_extensions) || !in_array($selfie_ext, $allowed_extensions)) {
                    $message = "Invalid file type. Only JPG, PNG, and PDF files are allowed.";
                } else if ($_FILES['id_document_url']['error'] !== UPLOAD_ERR_OK || $_FILES['selfie_with_id_url']['error'] !== UPLOAD_ERR_OK) {
                    $message = "An error occurred during file upload. Please try again.";
                } else {
                    $id_doc_name     = time() . '_id_' . bin2hex(random_bytes(4)) . '.' . $id_ext;
                    $selfie_doc_name = time() . '_selfie_' . bin2hex(random_bytes(4)) . '.' . $selfie_ext;
                        
                    $id_target     = $upload_dir . $id_doc_name;
                    $selfie_target = $upload_dir . $selfie_doc_name;

                    if (move_uploaded_file($_FILES['id_document_url']['tmp_name'], $id_target) && 
                        move_uploaded_file($_FILES['selfie_with_id_url']['tmp_name'], $selfie_target)) {
    
                        $query = "INSERT INTO users (full_name, username, phone_number, dob, email, password_hash, id_type, id_number, id_document_url, selfie_with_id_url, role, verification_status) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                        $stmt = $pdo->prepare($query); 
                        $stmt->execute([
                            $fullName, 
                            $username, 
                            $phoneNumber, 
                            $dob, 
                            $email, 
                            $hashedPassword,
                            $idType, 
                            $idNumber, 
                            $id_target,
                            $selfie_target,
                            $role,
                            $verification_status
                        ]);

                        header("Location: index.php?signup=success");
                        exit();
                    } else {
                        $message = "Failed to save your identity verification documents.";
                    }
                }
            }
        } catch (\PDOException $e) {
            $message = "Database processing error occurred: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sign Up Page | Link & Linger</title>
    <link rel="stylesheet" href="style.css" >
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2>Sign Up</h2>
            <p class="subtitle">Please fill in your details to get started.</p>

            <?php if (!empty($message)): ?>
                <div style="color: <?php echo $message_color; ?>; margin-bottom: 15px; font-weight: bold; text-align: center; background-color: rgba(255, 77, 77, 0.1); padding: 10px; border-radius: 5px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form id="signup-form" action="signup.php" method="post" enctype="multipart/form-data">
                <div class="input-group">
                    <input type="text" id="fullName" name="fullName" placeholder="Full Name" class="input-field" required autofocus>
                </div>

                <div class="input-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Username will appear" class="input-field" readonly>
                </div>

                <div class="input-group">
                    <input type="tel" id="phoneNumber" name="phone_number" placeholder="Phone Number(e.g. 0821234567)" 
                        class="input-field" maxlength="10" pattern="^0[678]\d{8}$" 
                        title="Must be a valid 10-digit South African mobile number starting with 06, 07, or 08" required>
                </div>
                
                <div class="input-group">
                    <label for="dob">Date of Birth</label><br>
                    <input type="date" id="dob" name="dob" class="input-field" required>
                </div>

                <div class="input-group">
                    <input type="email" name="email" id="email" placeholder="Email Address" class="input-field" required>
                </div>
                
                <div class="input-group">
                    <input type="password" id="password_hash" name="password" placeholder="User Password" class="input-field" required>
                </div>
                
                <div class="input-group">
                    <label for="idType">Identification Type</label>
                    <select id="idType" name="id_type" class="input-field">
                        <option value="id">SA ID Number</option>
                        <option value="passport">Passport Number</option>
                    </select>
                </div>
                
                <div class="input-group">
                    <input type="text" id="idNumber" name="id_number" placeholder="Enter ID Number" class="input-field" maxlength="13" pattern="\d{13}" required>
                </div>
                
                <div class="file-section">
                    <label>Upload Identification Document</label>
                    <input type="file" name="id_document_url" class="file-input" accept="image/*" required>
                </div>
                
                <div class="file-section">
                    <label>Selfie with Identification</label>
                    <input type="file" name="selfie_with_id_url" class="file-input" accept="image/*" required>
                </div>
                
                <button type="submit" class="sign-up-btn">Sign Up</button>
            </form>
            <div class="footer-links">
                <p>Already have an account? <a href="index.php">Sign In</a></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const nameInput = document.getElementById("fullName");
            const usernameInput = document.getElementById("username");
            const idTypeSelect = document.getElementById("idType");
            const idNumberInput = document.getElementById("idNumber");

            let sessionRandomDigits = Math.floor(1000 + Math.random() * 9000); 

            nameInput.addEventListener("input", function() {
                let cleanName = nameInput.value
                    .trim()
                    .toLowerCase()
                    .replace(/[^a-z0-9]/g, '');

                if (cleanName.length > 0) {
                    usernameInput.value = `${cleanName}${sessionRandomDigits}`;
                } else {
                    usernameInput.value = "";
                }
            });

            nameInput.addEventListener("blur", function() {
                if (nameInput.value.trim() === "") {
                    sessionRandomDigits = Math.floor(1000 + Math.random() * 9000);
                }
            });

            function updateIdValidation() {
                if (idTypeSelect.value === "id") {
                    idNumberInput.placeholder = "Enter 13-digit SA ID";
                    idNumberInput.setAttribute("pattern", "\\d{13}");
                    idNumberInput.setAttribute("maxlength", "13");
                } else {
                    idNumberInput.placeholder = "Enter Passport Number";
                    idNumberInput.removeAttribute("pattern");
                    idNumberInput.setAttribute("maxlength", "20");
                }
            }

            idTypeSelect.addEventListener("change", updateIdValidation);
            updateIdValidation();
        });
    </script>
</body>
</html>