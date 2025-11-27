<?php
session_start();

// CONEXÃO BÁSICA
$pdo = new PDO("mysql:host=127.0.0.1;dbname=los_polos;charset=utf8mb4", "root", "");

// PROCESSAR LOGIN
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['password'] ?? '');

    $q = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $q->execute([$email]);
    $user = $q->fetch();

    if ($user && password_verify($senha, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: index.php");
        exit;
    } else {
        $msg = "Email ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Login - Los Polos</title>
    <link rel="stylesheet" href="Css.css">
</head>
<body>
<div class="login-container">
    <h2>Entrar no Painel</h2>

    <?php if ($msg): ?>
        <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="post">

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Senha:</label>
        <input type="password" name="password" required>

        <button class="btn login-btn" type="submit">Entrar</button>
    </form>
    <div style="margin-top:15px;">
    <a class="btn secondary" href="register.php">Criar Conta</a>
</div>
</div>

</body>
</html>