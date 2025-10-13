<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($data['title']); ?></title>
    <style> /* Basic styling here */ </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($data['title']); ?></h1>
    <?php if ($data['success']): ?>
        <p style="color: green;"><?php echo htmlspecialchars($data['success']); ?></p>
    <?php endif; ?>

    <p><a href="/tenants/create">Create New Tenant</a></p>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Subdomain</th>
                <th>Status</th>
                <th>Flow</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data['tenants'] as $tenant): ?>
                <tr>
                    <td><?php echo htmlspecialchars((string)$tenant['id']); ?></td>
                    <td><?php echo htmlspecialchars($tenant['name']); ?></td>
                    <td><?php echo htmlspecialchars($tenant['subdomain']); ?></td>
                    <td><?php echo htmlspecialchars($tenant['subscription_status']); ?></td>
                    <td><?php echo htmlspecialchars($tenant['payroll_approval_flow']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><a href="/dashboard">Back to Dashboard</a></p>
</body>
</html>