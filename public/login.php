<?php // path: public/login.php
require_once __DIR__ . '/../app/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = null;
if (request_method('POST')) {
    try {
        login_user($_POST['phone'] ?? '', $_POST['password'] ?? '');
        redirect('dashboard.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — SOCKET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-dark text-light d-flex min-vh-100 flex-column">
<nav class="navbar navbar-dark bg-black border-bottom border-secondary">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">SOCKET</a>
    </div>
</nav>
<main class="flex-grow-1 d-flex align-items-center">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card bg-black text-light border border-secondary">
                    <div class="card-body p-4">
                        <h1 class="h4 mb-3">Welcome back</h1>
                        <p class="text-secondary">Login with your Ethiopian mobile number.</p>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= sanitize($error); ?></div>
                        <?php endif; ?>
                        <form method="post" novalidate>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" name="phone" id="phone" class="form-control form-control-lg bg-dark text-light border-secondary" placeholder="+251900000000" required value="<?= sanitize($_POST['phone'] ?? ''); ?>">
                                <div class="form-text text-secondary">Accepts +251, 09xxxxxxx, or 07xxxxxxx formats.</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" name="password" id="password" class="form-control form-control-lg bg-dark text-light border-secondary" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 btn-lg">Login</button>
                        </form>
                        <hr class="border-secondary my-4">
                        <p class="text-secondary mb-0">Need an account? <a href="register.php" class="link-light">Start the free trial</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<footer class="bg-black text-secondary py-3 text-center small">
    <p class="mb-0">&copy; <?= date('Y'); ?> SOCKET.</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
