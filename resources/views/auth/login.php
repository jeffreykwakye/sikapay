<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SikaPay Login</title>
    <link rel="stylesheet" href="/assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <img src="/assets/images/tenant_logos/1/main-logo.svg" alt="SikaPay Logo">
        </div>
        <h2>Welcome to SikaPay</h2>
        
        <?php if (!empty($data['error'])): ?>
            <div class="error"><?= $h($data['error']) ?></div>
        <?php endif; ?>

        <form method="POST" action="/attempt-login">
            <?= $CsrfToken::field() ?> 
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="admin@sikapay.local" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" value="password" required>
            </div>
            <button type="submit">Log In</button>
        </form>
    </div>
</body>
</html>