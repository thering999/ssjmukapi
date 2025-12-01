<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'SSJ Mukdahan API' ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', 'Inter', sans-serif; }
    </style>
</head>
<body>
    <div class="app-container">
        <nav class="navbar">
            <a href="/" class="navbar-brand">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                SSJ Mukdahan
            </a>
            <div class="navbar-nav">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/logout" class="btn btn-primary">ออกจากระบบ</a>
                <?php else: ?>
                    <a href="/login" class="btn btn-primary">เข้าสู่ระบบ</a>
                <?php endif; ?>
            </div>
        </nav>

        <main class="main-content">
            <?= $content ?>
        </main>

        <footer style="text-align: center; padding: 2rem; color: var(--text-secondary); font-size: 0.875rem;">
            &copy; <?= date('Y') ?> SSJ Mukdahan. All rights reserved.
        </footer>
    </div>

    <script src="/assets/js/app.js"></script>
</body>
</html>
