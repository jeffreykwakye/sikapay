<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenancy Scoping Test</title>
    <style>body { font-family: Arial, sans-serif; padding: 20px; } pre { background-color: #eee; padding: 10px; border-radius: 5px; }</style>
</head>
<body>
    <h1><?php echo htmlspecialchars($data['title']); ?></h1>
    <p><strong>Current User:</strong> <?php echo $data['is_admin'] ? 'SUPER ADMIN' : 'TENANT USER'; ?></p>
    <p><strong>Tenant ID:</strong> <?php echo htmlspecialchars((string)$data['tenant_id']); ?></p>
    
    <h2>Fetched Departments:</h2>
    <pre><?php print_r($data['departments']); ?></pre>
    
    <p><a href="/logout">Logout</a> | <a href="/dashboard">Dashboard</a></p>
</body>
</html>