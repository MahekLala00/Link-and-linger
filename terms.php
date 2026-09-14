<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'User') {
    header("Location: admin_dash.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action']) && $_POST['action'] === 'agree') {

        $_SESSION['agreed_to_terms'] = true;

        header("Location: yourpg.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions</title>
	<link rel="stylesheet" href="page.css">
</head>
<body>
	<form action="terms.php" method="POST" class="terms-container">
		<h1>Terms & Conditions</h1>
		<p class="last-updated">Last updated: April 2026</p>
		<p>Welcome to our website. Please read these terms and conditions carefully before using our services.</p>

		<div class="alert-box">
        	<strong>Important Notice:</strong> Please report any suspicious activity immediately to our administration team via the platform's reporting tools.
    	</div>

		<hr>

		<ol>
			<li>
				<h2> Identification Verification</h2>
            	<p>To ensure a safe environment for all users on this platform, we enforce a strict identity verification protocol.</p>
				<ul>
					<li><strong>Requirements:</strong> We request a valid ID or Passport number and a corresponding selfie for identification Verification.</li>
					<li><strong>Data Security:</strong> Your identification documents are stored securely and used solely for the purpose of verifying your account.</li>
					<li><strong>Privacy Assurance:</strong> We do not share this data with third parties without your explicit consent.</li>
				</ul>
        	</li>

			<li>
				<h2>User Conduct & Marketplace Rules</h2>
				<p>Users are expected to behave honestly and respectfully at all times. Misconduct will not be tolerated.</p>
				<ul>
					<li><strong>Accurate Listings:</strong> Users must provide accurate descriptions of themseleves and of the services received from businesses.</li>
					<li><strong>Fraud Prevention:</strong> Fraudulent activities will lead to immediate account suspension.</li>
					<li><strong>Cyberbullying Zero-Tolerance:</strong> Cyberbullying, harrasment, hate speech, or intimidation of any kind against other users is strictly prohibited.</li>
				</ul>
			</li>

			<li>
				<h2>Reporting & 3-Strike Policy</h2>
				<p>We empower our community to help us keep the platform safe and violation to this will come with implications.</p>
				<ul>
					<li><strong>Reporting System:</strong> Users can report other users if misconduct in any form may be taking place.</li>
					<li><strong>Evaluation Process:</strong>Once a report is submitted, a thorough evaluation by our team will take place.</li>
					<li><strong>3-Strike Policy:</strong> If a reported user is found guilty of misconduct, he/she will receive a strike according to our 3-strike policy. Upon receiving the final strike, the user will be permanently banned.</li>
				</ul>
			</li>
			
			<li>
				<h2>Platform Limitation of Liability (Stay on the Website)</h2>
				<p>Our safety measures only extend to interactions built directly into our ecosystem</p>
				<ul>
					<li><strong>Keep Communications Internal:</strong> Users are strongly advised to stay on the website for all communications, transactions, and interactions.</li>
					<li><strong>External Interactions Disclaimer:</strong> Anything that happens elsewhere (off our official platform) is outside of our control.</li>
				</ul>
			<p><strong>PLEASE NOTE: </strong>We are not held responsible for any damages, scams, disputes, or incidents that occur.</p>
			</li>

			<li>
            	<h2>Community Safety Tips</h2>
            	<p>Your physical and digital safety is our top priority. Please adhere to the following guidelines:</p>
            	<ul>
                	<li><strong>In-Person Safety:</strong> If you choose to meet another user in person, always meet in a well-lit, public space. Tell a friend or family member where you are going, and never go to a private residence for a first meeting.</li>
                	<li><strong>Online Safety:</strong> Never share sensitive personal information such as your home address, banking details, or passwords with other users.</li>
                	<li><strong>Sexual Health and Consent:</strong> All physical and social interactions must be rooted in clear, enthusiastic, and ongoing mutual consent. Prioritize your sexual health and boundaries; if an interaction makes you feel uncomfortable, remove yourself from the situation immediately and report the user.</li>
            	</ul>
        	</li>
			<br>
			<p>By accessing this website, you agree to be bound by these terms. If you disagree with any part, you may not access the service.</p>
		
		<div class="tabs_foot">
			<button type="submit" name="action" value="decline" class="decline">Decline</button>
			<button type="submit" name="action" value="agree" class="agree">Agree</button>
		</div>
	</form>

	<script>
        document.addEventListener("DOMContentLoaded", function() {
            const declineBtn = document.querySelector(".decline");
            
            declineBtn.addEventListener("click", function(e) {
                alert("You must agree to the Terms and Conditions to use Link & Linger.");
            });
        });
    </script>
</body>
</html>