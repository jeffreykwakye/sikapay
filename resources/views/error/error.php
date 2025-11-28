<?php
/**
 * Generic Error Page
 *
 * This template is used to display various error messages.
 * It extends the minimal layout to ensure consistent styling.
 *
 * @var int $code The HTTP status code (e.g., 404, 500).
 * @var string $title The title of the error.
 * @var string $message The detailed error message.
 */
?>

<div class="container text-center">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow p-5 mt-5">
                <h1 class="display-1 fw-bold text-danger"><?= $code ?></h1>
                <h2 class="display-4 mb-4"><?= $title ?></h2>
                <p class="lead mb-4"><?= $message ?></p>
                <?php if (!isset($isLoggedIn) || !$isLoggedIn): ?>
                    <a href="/" class="btn btn-primary btn-lg mt-3">Go to Homepage</a>
                <?php else: ?>
                    <a href="/dashboard" class="btn btn-primary btn-lg mt-3">Go to Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
