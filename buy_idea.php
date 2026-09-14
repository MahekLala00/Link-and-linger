<?php
session_start();
require_once 'db_config.php';

// 1. Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Validate the Idea ID from the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Invalid Date Idea request.");
}

$idea_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

try {
    // 3. Fetch the date idea details to populate PayFast fields
    $stmt = $pdo->prepare("SELECT title, price FROM date_ideas WHERE idea_id = ?");
    $stmt->execute([$idea_id]);
    $idea = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$idea) {
        die("Date idea not found.");
    }

    // PayFast requires price to be a formatted decimal string
    $amount = number_format($idea['price'], 2, '.', '');

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// 4. Set up PayFast Data Parameters
$merchant_id   = '10049513';
$merchant_key  = 'vqj7dz8b1p1ma';
$sandbox_url   = 'https://sandbox.payfast.co.za/eng/process';

// Dynamically determine your domain (works for local development like XAMPP or live servers)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$domain = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

// Define return paths
$return_url    = $domain . "/payfast_success.php?id=" . $idea_id;
$cancel_url    = $domain . "/linger.php?payment=cancelled";
$notify_url    = $domain . "/payfast_notify.php"; // ITN/Webhook target

$payfast_data = [
    'merchant_id'   => $merchant_id,
    'merchant_key'  => $merchant_key,
    'return_url'    => $return_url,
    'cancel_url'    => $cancel_url,
    'notify_url'    => $notify_url,
    'item_name'     => $idea['title'],
    'amount'        => $amount,
    // Custom variables to track who bought what when the webhook fires
    'custom_int1'   => $user_id,   
    'custom_int2'   => $idea_id
];

// 5. Generate Query String for Redirection
$html_form_fields = '';
foreach ($payfast_data as $name => $value) {
    $html_form_fields .= '<input type="hidden" name="' . $name . '" value="' . htmlspecialchars($value) . '" />';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Redirecting to PayFast...</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding-top: 100px; color: #333; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #2e7d32; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <h2>Connecting to PayFast Secure Sandbox Gateway...</h2>
    <p>Please wait while we redirect you to process your payment for <strong><?= htmlspecialchars($idea['title']) ?></strong>.</p>
    <div class="spinner"></div>

    <form id="payfast-form" action="<?= $sandbox_url ?>" method="POST">
        <?= $html_form_fields ?>
    </form>

    <script>
        window.onload = function() {
            document.getElementById('payfast-form').submit();
        }
    </script>
</body>
</html>