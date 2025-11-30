<?php
// Note: This is an email template.
// It will be rendered by the EmailService and embedded in the body of an email.
// The CSS is in public/assets/css/email.css
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $subject; ?></title>
    <link rel="stylesheet" href="<?php echo $site_url; ?>/assets/css/email.css">
</head>
<body class="email-body-class">
    <div class="email-container">
        <div class="email-header">
            <a href="<?php echo $site_url; ?>">
                <img src="<?php echo $logo_url; ?>" alt="<?php echo $tenant_name; ?> Logo">
            </a>
        </div>
        <div class="email-body">
            <?php echo $body; ?>
        </div>
        <div class="email-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $tenant_name; ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>