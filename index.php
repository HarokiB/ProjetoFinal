<?php
// index.php - Painel Los Polos (CRUDs integrados, MySQL via XAMPP root sem senha)

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'los_polos';
$charset = 'utf8mb4';

try {
    // Conecta ao servidor (sem DB) para garantir que o DB exista
    $pdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // Criar banco se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    // Conectar ao banco criado
    $pdo->exec("USE `$dbname`");

    // Criar tabelas (se não existirem)
    $pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
      id INT AUTO_INCREMENT PRIMARY KEY,
      email VARCHAR(255) UNIQUE,
      username VARCHAR(100) UNIQUE,
      password VARCHAR(255),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS products (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      price DECIMAL(10,2) NOT NULL DEFAULT 0,
      category VARCHAR(100),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS clients (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      email VARCHAR(255),
      phone VARCHAR(50),
      balance DECIMAL(10,2) DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS employees (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      role VARCHAR(100),
      email VARCHAR(255),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;
    ");

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS sales (
      id INT AUTO_INCREMENT PRIMARY KEY,
      client_id INT,
      product_id INT,
      quantity INT DEFAULT 1,
      total DECIMAL(10,2) DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;
    ");

} catch (PDOException $e) {
    die("Erro na conexão/boas-vindas ao DB: " . $e->getMessage());
}

// --- UTILITÁRIOS E SANITIZAÇÃO RÁPIDA ---
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$entity = $_GET['entity'] ?? 'products'; // products|clients|employees|sales
$action = $_REQUEST['acao'] ?? ''; // adicionar, atualizar, excluir, editar
$msg = '';

// --- TRATAMENTO DE AÇÕES GERAIS PARA CADA ENTIDADE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // PRODUCTS
    if ($entity === 'products') {
        if ($_POST['acao'] ?? '' === 'adicionar') {
            $name = trim($_POST['name'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $category = trim($_POST['category'] ?? '');
            if ($name !== '') {
                $stmt = $pdo->prepare("INSERT INTO products (name, price, category) VALUES (?, ?, ?)");
                $stmt->execute([$name, $price, $category]);
                $msg = 'Produto adicionado.';
            } else { $msg = 'Nome do produto obrigatório.'; }
        }
        if ($_POST['acao'] ?? '' === 'atualizar') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $price = floatval($_POST['price'] ?? 0);
            $category = trim($_POST['category'] ?? '');
            if ($id && $name !== '') {
                $stmt = $pdo->prepare("UPDATE products SET name=?, price=?, category=? WHERE id=?");
                $stmt->execute([$name, $price, $category, $id]);
                $msg = 'Produto atualizado.';
            } else { $msg = 'Dados válidos.'; }
        }
    }

    // CLIENTS
    if ($entity === 'clients') {
        if ($_POST['acao'] ?? '' === 'adicionar') {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $balance = floatval($_POST['balance'] ?? 0);
            if ($name !== '') {
                $stmt = $pdo->prepare("INSERT INTO clients (name, email, phone, balance) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $email, $phone, $balance]);
                $msg = 'Cliente adicionado.';
            } else $msg = 'Nome do cliente obrigatório.';
        }
        if ($_POST['acao'] ?? '' === 'atualizar') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $balance = floatval($_POST['balance'] ?? 0);
            if ($id && $name !== '') {
                $stmt = $pdo->prepare("UPDATE clients SET name=?, email=?, phone=?, balance=? WHERE id=?");
                $stmt->execute([$name, $email, $phone, $balance, $id]);
                $msg = 'Cliente atualizado.';
            } else $msg = 'Dados válidos.';
        }
    }

    // EMPLOYEES
    if ($entity === 'employees') {
        if ($_POST['acao'] ?? '' === 'adicionar') {
            $name = trim($_POST['name'] ?? '');
            $role = trim($_POST['role'] ?? '');
            $email = trim($_POST['email'] ?? '');
            if ($name !== '') {
                $stmt = $pdo->prepare("INSERT INTO employees (name, role, email) VALUES (?, ?, ?)");
                $stmt->execute([$name, $role, $email]);
                $msg = 'Funcionário adicionado.';
            } else $msg = 'Nome do funcionário obrigatório.';
        }
        if ($_POST['acao'] ?? '' === 'atualizar') {
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $role = trim($_POST['role'] ?? '');
            $email = trim($_POST['email'] ?? '');
            if ($id && $name !== '') {
                $stmt = $pdo->prepare("UPDATE employees SET name=?, role=?, email=? WHERE id=?");
                $stmt->execute([$name, $role, $email, $id]);
                $msg = 'Funcionário atualizado.';
            } else $msg = 'Dados válidos.';
        }
    }

    // SALES
    if ($entity === 'sales') {
        if ($_POST['acao'] ?? '' === 'adicionar') {
            $client_id = intval($_POST['client_id'] ?? 0);
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = max(1, intval($_POST['quantity'] ?? 1));
            if ($client_id && $product_id) {
                // obter preço do produto
                $p = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $p->execute([$product_id]);
                $prod = $p->fetch();
                $price = $prod ? floatval($prod['price']) : 0;
                $total = $price * $quantity;
                $stmt = $pdo->prepare("INSERT INTO sales (client_id, product_id, quantity, total) VALUES (?, ?, ?, ?)");
                $stmt->execute([$client_id, $product_id, $quantity, $total]);
                $msg = 'Venda registrada.';
            } else $msg = 'Selecione cliente e produto.';
        }
        if ($_POST['acao'] ?? '' === 'atualizar') {
            $id = intval($_POST['id'] ?? 0);
            $client_id = intval($_POST['client_id'] ?? 0);
            $product_id = intval($_POST['product_id'] ?? 0);
            $quantity = max(1, intval($_POST['quantity'] ?? 1));
            if ($id && $client_id && $product_id) {
                $p = $pdo->prepare("SELECT price FROM products WHERE id = ?");
                $p->execute([$product_id]);
                $prod = $p->fetch();
                $price = $prod ? floatval($prod['price']) : 0;
                $total = $price * $quantity;
                $stmt = $pdo->prepare("UPDATE sales SET client_id=?, product_id=?, quantity=?, total=? WHERE id=?");
                $stmt->execute([$client_id, $product_id, $quantity, $total, $id]);
                $msg = 'Venda atualizada.';
            } else $msg = 'Dados válidos.';
        }
    }
}

// TRATAMENTO DE EXCLUSÃO VIA GET (para manter similaridade com seu código)
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir') {
    $id = intval($_GET['id'] ?? 0);
    if ($id) {
        if ($entity === 'products') {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: ?entity=products");
            exit;
        }
        if ($entity === 'clients') {
            $stmt = $pdo->prepare("DELETE FROM clients WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: ?entity=clients");
            exit;
        }
        if ($entity === 'employees') {
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: ?entity=employees");
            exit;
        }
        if ($entity === 'sales') {
            $stmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
            $stmt->execute([$id]);
            header("Location: ?entity=sales");
            exit;
        }
    }
}

// PARA EDIÇÃO: carregar registro quando ?entity=...&acao=editar&id=...
$editing = false;
$editItem = [];
if (isset($_GET['acao']) && $_GET['acao'] === 'editar' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if ($id) {
        if ($entity === 'products') {
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]); $editItem = $stmt->fetch();
        } elseif ($entity === 'clients') {
            $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
            $stmt->execute([$id]); $editItem = $stmt->fetch();
        } elseif ($entity === 'employees') {
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$id]); $editItem = $stmt->fetch();
        } elseif ($entity === 'sales') {
            $stmt = $pdo->prepare("SELECT * FROM sales WHERE id = ?");
            $stmt->execute([$id]); $editItem = $stmt->fetch();
        }
        if ($editItem) $editing = true;
    }
}

// LISTAS PARA EXIBIÇÃO
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$clients = $pdo->query("SELECT * FROM clients ORDER BY id DESC")->fetchAll();
$employees = $pdo->query("SELECT * FROM employees ORDER BY id DESC")->fetchAll();
$sales = $pdo->query("
    SELECT s.*, c.name AS client_name, p.name AS product_name
    FROM sales s
    LEFT JOIN clients c ON s.client_id = c.id
    LEFT JOIN products p ON s.product_id = p.id
    ORDER BY s.id DESC
")->fetchAll();
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <link rel="stylesheet" href="Css.css">
  <title>Los Polos</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <script>
    function confirmDelete(evt, name){
      if(!confirm('Excluir "'+name+'"?')) evt.preventDefault();
    }
    function switchEntity(e){
      // mudar entidade via link (usa href)
    }
  </script>
</head>
<body>
  <header>
    <div style="text-align:center; margin-top:50px;">
    <a href="login.php" class="btn">Login</a>
    <a href="register.php" class="btn secondary">Cadastrar</a>
</div>
    <div class="container">
      <h1>Los Polos</h1>
      <nav>
        <a href="?entity=products">Produtos</a>
        <a href="?entity=clients">Clientes</a>
        <a href="?entity=employees">Funcionários</a>
        <a href="?entity=sales">Vendas</a>
      </nav>
      <div style="clear:both"></div>
    </div>
  </header>

  <main class="container">
    <?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>

    <div class="tabs">
      <a class="tab <?= $entity==='products' ? 'active':'' ?>" href="?entity=products">Produtos</a>
      <a class="tab <?= $entity==='clients' ? 'active':'' ?>" href="?entity=clients">Clientes</a>
      <a class="tab <?= $entity==='employees' ? 'active':'' ?>" href="?entity=employees">Funcionários</a>
      <a class="tab <?= $entity==='sales' ? 'active':'' ?>" href="?entity=sales">Vendas</a>
    </div>

    <div class="panel">
      <div class="left">
        <div class="card">
          <?php if ($entity === 'products'): ?>
            <h2>Produtos</h2>
            <a class="btn" href="?entity=products&acao=adicionar">+ Novo Produto</a>
            <table class="table">
              <thead><tr><th>ID</th><th>Nome</th><th>Preço</th><th>Categoria</th><th>Ações</th></tr></thead>
              <tbody>
                <?php if (empty($products)): ?>
                  <tr><td colspan="5">Nenhum produto cadastrado.</td></tr>
                <?php else: foreach($products as $p): ?>
                  <tr>
                    <td><?= h($p['id']) ?></td>
                    <td><?= h($p['name']) ?></td>
                    <td>R$ <?= number_format($p['price'],2,',','.') ?></td>
                    <td><?= h($p['category']) ?></td>
                    <td class="acao">
                      <a href="?entity=products&acao=editar&id=<?= h($p['id']) ?>">Editar</a>
                      <a href="?entity=products&acao=excluir&id=<?= h($p['id']) ?>" onclick="return confirm('Excluir este produto?')">Excluir</a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>

          <?php elseif ($entity === 'clients'): ?>
            <h2>Clientes</h2>
            <a class="btn" href="?entity=clients&acao=adicionar">+ Novo Cliente</a>
            <table class="table">
              <thead><tr><th>ID</th><th>Nome</th><th>Email</th><th>Telefone</th><th>Saldo</th><th>Ações</th></tr></thead>
              <tbody>
                <?php if (empty($clients)): ?>
                  <tr><td colspan="6">Nenhum cliente cadastrado.</td></tr>
                <?php else: foreach($clients as $c): ?>
                  <tr>
                    <td><?= h($c['id']) ?></td>
                    <td><?= h($c['name']) ?></td>
                    <td><?= h($c['email']) ?></td>
                    <td><?= h($c['phone']) ?></td>
                    <td>R$ <?= number_format($c['balance'],2,',','.') ?></td>
                    <td class="acao">
                      <a href="?entity=clients&acao=editar&id=<?= h($c['id']) ?>">Editar</a>
                      <a href="?entity=clients&acao=excluir&id=<?= h($c['id']) ?>" onclick="return confirm('Excluir este cliente?')">Excluir</a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>

          <?php elseif ($entity === 'employees'): ?>
            <h2>Funcionários</h2>
            <a class="btn" href="?entity=employees&acao=adicionar">+ Novo Funcionário</a>
            <table class="table">
              <thead><tr><th>ID</th><th>Nome</th><th>Cargo</th><th>Email</th><th>Ações</th></tr></thead>
              <tbody>
                <?php if (empty($employees)): ?>
                  <tr><td colspan="5">Nenhum funcionário cadastrado.</td></tr>
                <?php else: foreach($employees as $e): ?>
                  <tr>
                    <td><?= h($e['id']) ?></td>
                    <td><?= h($e['name']) ?></td>
                    <td><?= h($e['role']) ?></td>
                    <td><?= h($e['email']) ?></td>
                    <td class="acao">
                      <a href="?entity=employees&acao=editar&id=<?= h($e['id']) ?>">Editar</a>
                      <a href="?entity=employees&acao=excluir&id=<?= h($e['id']) ?>" onclick="return confirm('Excluir este funcionário?')">Excluir</a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>

          <?php elseif ($entity === 'sales'): ?>
            <h2>Vendas</h2>
            <a class="btn" href="?entity=sales&acao=adicionar">+ Nova Venda</a>
            <table class="table">
              <thead><tr><th>ID</th><th>Cliente</th><th>Produto</th><th>Qtd</th><th>Total</th><th>Data</th><th>Ações</th></tr></thead>
              <tbody>
                <?php if (empty($sales)): ?>
                  <tr><td colspan="7">Nenhuma venda registrada.</td></tr>
                <?php else: foreach($sales as $s): ?>
                  <tr>
                    <td><?= h($s['id']) ?></td>
                    <td><?= h($s['client_name'] ?? '—') ?></td>
                    <td><?= h($s['product_name'] ?? '—') ?></td>
                    <td><?= h($s['quantity']) ?></td>
                    <td>R$ <?= number_format($s['total'],2,',','.') ?></td>
                    <td><?= h($s['created_at']) ?></td>
                    <td class="acao">
                      <a href="?entity=sales&acao=editar&id=<?= h($s['id']) ?>">Editar</a>
                      <a href="?entity=sales&acao=excluir&id=<?= h($s['id']) ?>" onclick="return confirm('Excluir esta venda?')">Excluir</a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="right">
        <div class="card">
          <?php
            // FORMULÁRIOS ADD / EDIT por entidade (mantendo padrão único parecido com seu exemplo)
            if ($entity === 'products'):
          ?>
            <h3><?= $editing ? 'Editar Produto' : 'Adicionar Produto' ?></h3>
            <form method="post" action="?entity=products">
              <?php if ($editing): ?>
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="id" value="<?= h($editItem['id']) ?>">
              <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
              <?php endif; ?>
              <label>Nome
                <input type="text" name="name" required value="<?= h($editing ? $editItem['name'] : '') ?>">
              </label>
              <label>Preço
                <input type="number" name="price" step="0.01" required value="<?= h($editing ? $editItem['price'] : '0.00') ?>">
              </label>
              <label>Categoria
                <input type="text" name="category" value="<?= h($editing ? $editItem['category'] : '') ?>">
              </label>
              <div class="form-actions">
                <button class="btn" type="submit"><?= $editing ? 'Salvar Alterações' : 'Adicionar' ?></button>
                <?php if ($editing): ?><a class="btn secondary" href="?entity=products">Cancelar</a><?php endif; ?>
              </div>
            </form>

          <?php elseif ($entity === 'clients'): ?>
            <h3><?= $editing ? 'Editar Cliente' : 'Adicionar Cliente' ?></h3>
            <form method="post" action="?entity=clients">
              <?php if ($editing): ?>
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="id" value="<?= h($editItem['id']) ?>">
              <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
              <?php endif; ?>
              <label>Nome
                <input type="text" name="name" required value="<?= h($editing ? $editItem['name'] : '') ?>">
              </label>
              <label>Email
                <input type="text" name="email" value="<?= h($editing ? $editItem['email'] : '') ?>">
              </label>
              <label>Telefone
                <input type="text" name="phone" value="<?= h($editing ? $editItem['phone'] : '') ?>">
              </label>
              <label>Saldo (R$)
                <input type="number" name="balance" step="0.01" value="<?= h($editing ? $editItem['balance'] : '0.00') ?>">
              </label>
              <div class="form-actions">
                <button class="btn" type="submit"><?= $editing ? 'Salvar Alterações' : 'Adicionar' ?></button>
                <?php if ($editing): ?><a class="btn secondary" href="?entity=clients">Cancelar</a><?php endif; ?>
              </div>
            </form>

          <?php elseif ($entity === 'employees'): ?>
            <h3><?= $editing ? 'Editar Funcionário' : 'Adicionar Funcionário' ?></h3>
            <form method="post" action="?entity=employees">
              <?php if ($editing): ?>
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="id" value="<?= h($editItem['id']) ?>">
              <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
              <?php endif; ?>
              <label>Nome
                <input type="text" name="name" required value="<?= h($editing ? $editItem['name'] : '') ?>">
              </label>
              <label>Cargo
                <input type="text" name="role" value="<?= h($editing ? $editItem['role'] : '') ?>">
              </label>
              <label>Email
                <input type="text" name="email" value="<?= h($editing ? $editItem['email'] : '') ?>">
              </label>
              <div class="form-actions">
                <button class="btn" type="submit"><?= $editing ? 'Salvar Alterações' : 'Adicionar' ?></button>
                <?php if ($editing): ?><a class="btn secondary" href="?entity=employees">Cancelar</a><?php endif; ?>
              </div>
            </form>

          <?php elseif ($entity === 'sales'): ?>
            <h3><?= $editing ? 'Editar Venda' : 'Registrar Venda' ?></h3>
            <form method="post" action="?entity=sales">
              <?php if ($editing): ?>
                <input type="hidden" name="acao" value="atualizar">
                <input type="hidden" name="id" value="<?= h($editItem['id']) ?>">
                <?php $selectedClient = $editItem['client_id']; $selectedProduct = $editItem['product_id']; $quantityVal = $editItem['quantity']; ?>
              <?php else: ?>
                <input type="hidden" name="acao" value="adicionar">
                <?php $selectedClient = ''; $selectedProduct = ''; $quantityVal = 1; ?>
              <?php endif; ?>

              <label>Cliente
                <select name="client_id" required>
                  <option value="">-- selecione --</option>
                  <?php foreach($clients as $c): ?>
                    <option value="<?= h($c['id']) ?>" <?= ($selectedClient == $c['id']) ? 'selected':'' ?>><?= h($c['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>

              <label>Produto
                <select name="product_id" required>
                  <option value="">-- selecione --</option>
                  <?php foreach($products as $p): ?>
                    <option value="<?= h($p['id']) ?>" <?= ($selectedProduct == $p['id']) ? 'selected':'' ?>><?= h($p['name']) ?> — R$ <?= number_format($p['price'],2,',','.') ?></option>
                  <?php endforeach; ?>
                </select>
              </label>

              <label>Quantidade
                <input type="number" name="quantity" min="1" value="<?= h($quantityVal) ?>">
              </label>

              <div class="form-actions">
                <button class="btn" type="submit"><?= $editing ? 'Salvar Alterações' : 'Registrar Venda' ?></button>
                <?php if ($editing): ?><a class="btn secondary" href="?entity=sales">Cancelar</a><?php endif; ?>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

  </main>

  <footer>
    <div class="container">
      <small>Los Polos© - Todos os direitos reservados</small>
    </div>
  </footer>
</body>
</html>