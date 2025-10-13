<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($data['title']); ?></title>
    <style> /* Basic styling here */ </style>
</head>
<body>
    <h1><?php echo htmlspecialchars($data['title']); ?></h1>
    
    <?php if ($data['error']): ?>
        <p style="color: red;"><?php echo htmlspecialchars($data['error']); ?></p>
    <?php endif; ?>

    <form method="POST" action="/tenants">
        <h2>Tenant Details</h2>
        <label for="tenant_name">Company Name:</label>
        <input type="text" id="tenant_name" name="tenant_name" value="<?php echo htmlspecialchars($data['input']['tenant_name'] ?? ''); ?>" required><br><br>

        <label for="subdomain">Subdomain:</label>
        <input type="text" id="subdomain" name="subdomain" value="<?php echo htmlspecialchars($data['input']['subdomain'] ?? ''); ?>" required><br><br>

        <label for="payroll_flow">Payroll Flow:</label>
        <select id="payroll_flow" name="payroll_flow">
            <option value="ACCOUNTANT_FINAL">Accountant Final</option>
            <option value="ADMIN_FINAL">Admin Final</option>
        </select><br><br>

        <h2>Subscription Details</h2>
        <label for="plan_id">Select Plan:</label>
        <select id="plan_id" name="plan_id" required>
            <option value="">-- Select a Plan --</option>
            <?php foreach ($data['plans'] as $plan): ?>
                <option 
                    value="<?php echo htmlspecialchars((string)$plan['id']); ?>"
                    <?php echo (isset($data['input']['plan_id']) && (int)$data['input']['plan_id'] === $plan['id']) ? 'selected' : ''; ?>
                >
                    <?php echo htmlspecialchars($plan['name']); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <h2>Initial Admin User Details</h2>
        <label for="admin_fname">First Name:</label>
        <input type="text" id="admin_fname" name="admin_fname" value="<?php echo htmlspecialchars($data['input']['admin_fname'] ?? ''); ?>" required><br><br>

        <label for="admin_lname">Last Name:</label>
        <input type="text" id="admin_lname" name="admin_lname" value="<?php echo htmlspecialchars($data['input']['admin_lname'] ?? ''); ?>" required><br><br>

        <label for="admin_email">Email:</label>
        <input type="email" id="admin_email" name="admin_email" value="<?php echo htmlspecialchars($data['input']['admin_email'] ?? ''); ?>" required><br><br>

        <label for="admin_password">Password:</label>
        <input type="password" id="admin_password" name="admin_password" required><br><br>

        <button type="submit">Create Tenant</button>
    </form>
    <p><a href="/tenants">Back to Tenant List</a></p>
</body>
</html>