<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SikaPay Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background-color: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); width: 300px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="email"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #0056b3; }
        .error { color: #dc3545; text-align: center; margin-bottom: 10px; border: 1px solid #f5c6cb; padding: 8px; background-color: #f8d7da; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>SikaPay Login</h2>
        
        <?php if (!empty($data['error'])): ?>
            <div class="error"><?php echo htmlspecialchars($data['error']); ?></div>
        <?php endif; ?>

        <form method="POST" action="/attempt-login">
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