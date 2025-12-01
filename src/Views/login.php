<?php ob_start(); ?>

<div class="login-container animate-fade-in">
    <div class="card login-card">
        <div style="text-align: center; margin-bottom: 2rem;">
            <h1 style="font-size: 1.5rem; margin-bottom: 0.5rem;">เข้าสู่ระบบ</h1>
            <p style="color: var(--text-secondary);">สำหรับเจ้าหน้าที่ดูแลระบบ</p>
        </div>

        <form id="loginForm">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" placeholder="admin">
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
        </form>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    // Implement login logic here
    alert('Login functionality coming soon!');
});
</script>

<?php 
$content = ob_get_clean(); 
require __DIR__ . '/layouts/main.php'; 
?>
