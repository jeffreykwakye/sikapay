<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($data['title']); ?></title>
    <script>
        // Check if the page is being loaded from the bfcache
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // If loaded from cache, immediately force a hard reload from the server.
                window.location.reload(true); 
            }
        });
    </script>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f8ff; padding: 30px; }
        .container { background-color: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        h1 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .info-box { margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background-color: #e9ecef; }
        .logout-link { margin-top: 20px; display: block; font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($data['title']); ?></h1>
        
        <p style="font-size: 1.2em;"><?php echo htmlspecialchars($data['welcomeMessage']); ?></p>

        <div class="info-box">
            <strong>Role Status:</strong> <?php echo htmlspecialchars($data['userRole']); ?><br>
            <strong>Access Context:</strong> <?php echo htmlspecialchars($data['tenantInfo']); ?>
        </div>
        <?php 
        // We know only Super Admin sees this dashboard title, but for clarity:
        if ($data['title'] === 'Super Admin Dashboard'): 
        ?>
            <p class="test-link">
                <a href="/test-scope">Run Multi-Tenancy Scoping Test</a>
            </p>

            <?php
$password = 'password'; // The password you want to hash
$hash = password_hash($password, PASSWORD_DEFAULT);
echo $hash . "\n";
?>
        <?php endif; ?>
        
        <a href="/logout" class="logout-link">Log Out</a>
    </div>
</body>
</html>