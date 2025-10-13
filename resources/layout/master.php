<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'SikaPay Platform' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css"> 
</head>
<body>

    <?php require __DIR__ . '/partials/header.php'; ?>

    <div class="main-layout">
        
        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <main class="content-wrapper">
            <?php 
            // This is where the page-specific view fragment is loaded
            if (isset($__content_file)) {
                require $__content_file;
            }
            ?>
        </main>
        
    </div> <?php require __DIR__ . '/partials/footer.php'; ?>
    
</body>
</html>