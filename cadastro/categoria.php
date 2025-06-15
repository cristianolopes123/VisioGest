<?php
//session_start();
require_once('../conexao.php'); // Inclui sua conexão MySQLi

// Verificar autenticação e permissões
//if (!isset($_SESSION['usuario_id'])) {
   // header('Location: login.php');
   // exit;
//}

// Operações CRUD
$mensagem = '';
$categoria = [
    'CategoriaID' => '', 
    'NomeCategoria' => '', 
    'Descricao' => ''
];

// Criar ou Atualizar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Preparar dados
    $nome_categoria = $conn->real_escape_string($_POST['NomeCategoria']);
    $descricao = $conn->real_escape_string($_POST['Descricao']);
    
    if (empty($_POST['CategoriaID'])) {
        // CREATE
        $sql = "INSERT INTO tb_categoria_produto (NomeCategoria, Descricao) 
                VALUES ('$nome_categoria', '$descricao')";
    } else {
        // UPDATE
        $id = (int)$_POST['CategoriaID'];
        $sql = "UPDATE tb_categoria_produto SET 
                NomeCategoria = '$nome_categoria', 
                Descricao = '$descricao' 
                WHERE CategoriaID = $id";
    }
    
    if ($conn->query($sql)) {
        $mensagem = "Categoria " . (empty($_POST['CategoriaID']) ? 'cadastrada' : 'atualizada') . " com sucesso!";
    } else {
        $mensagem = "Erro: " . $conn->error;
    }
}

// Editar (READ)
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $sql = "SELECT * FROM tb_categoria_produto WHERE CategoriaID = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $categoria = $result->fetch_assoc();
    } else {
        $mensagem = "Categoria não encontrada!";
    }
}

// Excluir (DELETE)
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    
    // Verificar se existem produtos nesta categoria antes de excluir
    $sql_check = "SELECT COUNT(*) AS total FROM tb_produtos WHERE CategoriaID = $id";
    $result = $conn->query($sql_check);
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        $mensagem = "Esta categoria não pode ser excluída porque existem produtos vinculados a ela!";
    } else {
        $sql = "DELETE FROM tb_categoria_produto WHERE CategoriaID = $id";
        if ($conn->query($sql)) {
            $mensagem = "Categoria excluída com sucesso!";
            header("Location: categorias.php");
            exit;
        } else {
            $mensagem = "Erro ao excluir: " . $conn->error;
        }
    }
}

// Listar todas as categorias
$sql = "SELECT * FROM tb_categoria_produto ORDER BY NomeCategoria";
$result = $conn->query($sql);
$categorias = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $categorias[] = $row;
    }
} else {
    $mensagem = "Erro ao listar categorias: " . $conn->error;
}

// Pesquisar categorias
$pesquisa = isset($_GET['pesquisa']) ? $conn->real_escape_string($_GET['pesquisa']) : '';
if (!empty($pesquisa)) {
    $sql = "SELECT * FROM tb_categoria_produto WHERE NomeCategoria LIKE '%$pesquisa%' OR Descricao LIKE '%$pesquisa%' ORDER BY NomeCategoria";
    $result = $conn->query($sql);
    $categorias = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias de Produtos - Clínica Ótica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #5A9392;
            --secondary-color: #1E3A5F;
            --light-color: #F5F7FA;
            --dark-color: #333333;
            --danger-color: #dc3545;
            --success-color: #28a745;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            color: var(--dark-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: var(--secondary-color);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #4a7b7a;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            color: var(--secondary-color);
            font-size: 22px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .action-buttons a {
            color: var(--primary-color);
            margin-right: 10px;
            text-decoration: none;
        }
        
        .action-buttons a:hover {
            text-decoration: underline;
        }
        
        .action-buttons .delete {
            color: var(--danger-color);
        }
        
        .search-container {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-container input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
        }
        
        .search-container button {
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background-color: var(--primary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            th, td {
                padding: 8px 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tags"></i> Categorias de Produtos</h1>
            <div>
                <a href="produtos.php" class="btn btn-success"><i class="fas fa-boxes"></i> Ver Produtos</a>
                <a href="/cadastro/produtos" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert <?php 
                echo strpos($mensagem, 'Erro') !== false ? 'alert-danger' : 
                    (strpos($mensagem, 'não pode ser excluída') !== false ? 'alert-warning' : 'alert-success'); 
            ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo empty($categoria['CategoriaID']) ? 'Nova Categoria' : 'Editar Categoria'; ?></h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="CategoriaID" value="<?php echo $categoria['CategoriaID']; ?>">
                
                <div class="form-group">
                    <label for="NomeCategoria">Nome da Categoria *</label>
                    <input type="text" id="NomeCategoria" name="NomeCategoria" class="form-control" 
                           value="<?php echo htmlspecialchars($categoria['NomeCategoria']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="Descricao">Descrição</label>
                    <textarea id="Descricao" name="Descricao" class="form-control"><?php echo htmlspecialchars($categoria['Descricao']); ?></textarea>
                </div>
                
                <div class="form-group" style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo empty($categoria['CategoriaID']) ? 'Cadastrar' : 'Atualizar'; ?>
                    </button>
                    <?php if (!empty($categoria['CategoriaID'])): ?>
                        <a href="categorias.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Categorias Cadastradas</h2>
            </div>
            
            <!-- Barra de pesquisa -->
            <form method="GET" action="" class="search-container">
                <input type="text" name="pesquisa" placeholder="Pesquisar por nome ou descrição..." 
                       value="<?php echo htmlspecialchars($pesquisa); ?>">
                <button type="submit"><i class="fas fa-search"></i> Pesquisar</button>
                <?php if (!empty($pesquisa)): ?>
                    <a href="categorias.php" class="btn btn-secondary" style="margin-left: 10px;">Limpar</a>
                <?php endif; ?>
            </form>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Data Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $cat): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cat['NomeCategoria']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cat['Descricao']) ?: '---'; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($cat['DataCriacao'])); ?></td>
                                <td class="action-buttons">
                                    <a href="?editar=<?php echo $cat['CategoriaID']; ?>"><i class="fas fa-edit"></i> Editar</a>
                                    <a href="?excluir=<?php echo $cat['CategoriaID']; ?>" class="delete" 
                                       onclick="return confirm('Tem certeza que deseja excluir esta categoria?')">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($categorias)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">Nenhuma categoria cadastrada</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            var nome = document.getElementById('NomeCategoria').value.trim();
            
            if (nome === '') {
                e.preventDefault();
                alert('Por favor, informe o nome da categoria');
            }
        });
    </script>
</body>
</html>