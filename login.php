<?php
session_start();

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
	require_once 'db_config.php';

	$form_user = $_POST['username'] ?? '';
	$form_pass = $_POST['password'] ?? '';

	try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$form_user]);
    $user = $stmt->fetch();

    if ($user && $form_pass === $user['password_hash']) {

        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'Admin') {
			header("Location: admin_dash.php");
		} else {
			header("Location: terms.html");
		}

		exit();

    } else {
        $error_message = "Incorrect Username or Password.";
    }

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    $error_message = "A system error occurred. Please try again later.";
}}
?>

<!DOCTYPE html>
<html lang= "en">
<head>
	<meta charset="UTF-8">
	<meta name= "viewport" content="width=device-width,initial-scale=1.0"/>
	<title>Sign In</title>
	<link rel="stylesheet" href="style.css" >
</head>
<body>
	<div class="main">
	<div class="login-container">
		
		<div class="login-card">
			<h2>Welcome Back</h2>
			<p class="subtitle">Please enter your username and password to sign in</p>

			<?php if (!empty($error_message)): ?>
                <div style="color: #ff4d4d; margin-bottom: 15px; font-weight: bold; text-align: center;">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

			<form action="login.php" method="POST">
				<input type="text" name="username" placeholder="User Name" class="input-field" required autofocus=""><br><br>
				<input type="password" name="password" placeholder="User Password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters" class="input-field" required autofocus="">
			
				<div class="remember-me">
					<input type="checkbox" id="remember" name="remember">
					<label for="remember">Remember me?</label>
				</div>
			
				<button type="submit" class="sign-in-btn">Sign in</button><br><br>
			</form>
		
			<div class="footer-links">
				<a href="forgot-password.html">Forgot Password?</a>
				<a href="forgot-username.html">Forgot Username?</a>
				<p>Don't have an account?<a href="signup.php" class="sign-up">  Sign up</a></p>
			</div>
		</div>
	</div>
	</div>
</body>
</html>