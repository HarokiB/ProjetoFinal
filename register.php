<?php
session_start();

$pdo = new PDO("mysql:host=127.0.0.1;dbname=los_polos;charset=utf8mb4", "root", "");

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $senha = trim($_POST['password'] ?? '');

    if ($email && $username && $senha) {
        // Verifica se já existe
        $q = $pdo->prepare("SELECT * FROM users WHERE email = ? OR username = ?");
        $q->execute([$email, $username]);

        if ($q->rowCount() > 0) {
            $msg = "Email ou usuário já está em uso.";
        } else {
            // Cadastrar
            $stmt = $pdo->prepare("INSERT INTO users (email, username, password) VALUES (?, ?, ?)");
            $stmt->execute([
                $email,
                $username,
                password_hash($senha, PASSWORD_DEFAULT)
            ]);

            header("Location: login.php?msg=Cadastro realizado!");
            exit;
        }
    } else {
        $msg = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cadastrar - Los Polos</title>
    <link rel="stylesheet" href="Css.css">
</head>
<body>

<div class="login-container">
    <h2>Criar Conta</h2>

    <?php if ($msg): ?>
        <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="post">

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Usuário:</label>
        <input type="text" name="username" required>

        <label>Senha:</label>
        <input type="password" name="password" required>

        <button class="btn login-btn" type="submit">Cadastrar</button>

        <p style="margin-top:10px;">Já tem conta?
            <a href="login.php">Entrar</a>
        </p>

    </form>
</div>

</body>
</html>