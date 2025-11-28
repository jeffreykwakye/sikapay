<?php
/**
 * 403 Access Denied Error Page
 *
 * This template is used to display a 403 error message.
 * The layout (minimal or master) is determined by ErrorResponder based on login status.
 *
 * @var int $code The HTTP status code (e.g., 403).
 * @var string $title The title of the error.
 * @var string $message The detailed error message.
 */
?>

<div class="container text-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="display-1 fw-bold text-danger"><?= $code ?></h1>
            <h2 class="display-4 mb-4"><?= $title ?></h2>
            <p class="lead mb-4"><?= $message ?></p>
            <?php if (!$isLoggedIn): ?>
                <a href="/" class="btn btn-primary btn-lg mt-3">Go to Homepage</a>
            <?php else: ?>
                <a href="/dashboard" class="btn btn-primary btn-lg mt-3">Go to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</div>
