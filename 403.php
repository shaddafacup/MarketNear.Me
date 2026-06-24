<?php
// 403.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 text-center">
            <div class="mb-4">
                <i class="fas fa-lock fa-5x text-danger"></i>
            </div>
            <h1 class="display-1 fw-bold text-danger">403</h1>
            <h2 class="mb-3">Access Forbidden</h2>
            <p class="lead text-muted mb-4">
                You don't have permission to access this page.
                <?php if (!Auth::isLoggedIn()): ?>
                Please login with an appropriate account.
                <?php else: ?>
                This area requires additional privileges.
                <?php endif; ?>
            </p>
            <div class="d-flex justify-content-center gap-2">
                <a href="/marketnearme/index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Go Home
                </a>
                <?php if (!Auth::isLoggedIn()): ?>
                <a href="/marketnearme/login.php" class="btn btn-outline-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>