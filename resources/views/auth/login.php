<div class="d-flex justify-content-center align-items-center min-vh-100">
<div class="login-container card shadow p-4">
        <div class="login-logo mb-4">
            <img src="/assets/images/tenant_logos/1/main-logo.svg" alt="SikaPay Logo">
        </div>
        <h3 class="mb-2">Welcome to SikaPay</h3>
        <p class="mb-3 text-center">Enter your email and password to proceed</p>
        
        <?php if (!empty($data['error'])): ?>
            <div class="alert alert-danger mb-3"><?= $h($data['error']) ?></div>
        <?php endif; ?>

        <form method="POST" action="/attempt-login">
            <?= $CsrfToken::field() ?> 
            
            <div class="mb-3">
                <label for="email" class="form-label text-start">Email</label>
                <input type="email" id="email" name="email" value="" required class="form-control">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label text-start">Password</label>
                <input type="password" id="password" name="password" value="" required class="form-control">
            </div>
            <button type="submit" class="btn btn-success w-100 mt-3">
                <i class="fas fa-sign-in-alt"></i> Log In
            </button>
        </form>
    </div>
</div>