<?php
//session_start();
require_once('../conexao.php'); // Inclui sua conexão MySQLi

// Verificar autenticação
//if (!isset($_SESSION['usuario_id'])) {
//    header('Location: login.php');
 //   exit;
//}

// Operações CRUD
$mensagem = '';
$produto = [
    'ProdutoID' => '', 
    'NomeProduto' => '', 
    'Descricao' => '', 
    'PrecoVenda' => '', 
    'CustoAquisicao' => '', 
    'EstoqueAtual' => 0, 
    'EstoqueMinimo' => 0, 
    'CategoriaID' => '', 
    'FotoURL' => '', 
    'Ativo' => 1
];

// Configurações para upload de imagens
$upload_dir = 'uploads/produtos/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

// Obter categorias para o dropdown
$categorias = [];
$sql_categorias = "SELECT CategoriaID, NomeCategoria FROM tb_categoria_produto ORDER BY NomeCategoria";
$result_categorias = $conn->query($sql_categorias);
if ($result_categorias) {
    while ($row = $result_categorias->fetch_assoc()) {
        $categorias[$row['CategoriaID']] = $row['NomeCategoria'];
    }
}

// Criar ou Atualizar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Preparar dados
    $produto['NomeProduto'] = $conn->real_escape_string($_POST['NomeProduto']);
    $produto['Descricao'] = $conn->real_escape_string($_POST['Descricao']);
    $produto['PrecoVenda'] = (float)$_POST['PrecoVenda'];
    $produto['CustoAquisicao'] = !empty($_POST['CustoAquisicao']) ? (float)$_POST['CustoAquisicao'] : null;
    $produto['EstoqueAtual'] = (int)$_POST['EstoqueAtual'];
    $produto['EstoqueMinimo'] = (int)$_POST['EstoqueMinimo'];
    $produto['CategoriaID'] = (int)$_POST['CategoriaID'];
    $produto['Ativo'] = isset($_POST['Ativo']) ? 1 : 0;
    
    // Processar upload da foto
    if (!empty($_FILES['FotoURL']['name'])) {
        $file_type = $_FILES['FotoURL']['type'];
        if (in_array($file_type, $allowed_types)) {
            $file_name = basename($_FILES['FotoURL']['name']);
            $file_tmp = $_FILES['FotoURL']['tmp_name'];
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid() . '.' . $file_ext;
            $file_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $file_path)) {
                // Remove a foto antiga se existir
                if (!empty($produto['FotoURL']) && file_exists($produto['FotoURL'])) {
                    unlink($produto['FotoURL']);
                }
                $produto['FotoURL'] = $file_path;
            } else {
                $mensagem = "Erro ao fazer upload da foto.";
            }
        } else {
            $mensagem = "Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.";
        }
    }
    
    if (empty($_POST['ProdutoID'])) {
        // CREATE
        $sql = "INSERT INTO tb_produto (NomeProduto, Descricao, PrecoVenda, CustoAquisicao, EstoqueAtual, EstoqueMinimo, CategoriaID, FotoURL, Ativo) 
                VALUES ('{$produto['NomeProduto']}', '{$produto['Descricao']}', {$produto['PrecoVenda']}, " . 
                ($produto['CustoAquisicao'] !== null ? $produto['CustoAquisicao'] : 'NULL') . ", 
                {$produto['EstoqueAtual']}, {$produto['EstoqueMinimo']}, {$produto['CategoriaID']}, " .
                (!empty($produto['FotoURL']) ? "'{$produto['FotoURL']}'" : 'NULL') . ", {$produto['Ativo']})";
    } else {
        // UPDATE
        $produto['ProdutoID'] = (int)$_POST['ProdutoID'];
        $sql = "UPDATE tb_produto SET 
                NomeProduto = '{$produto['NomeProduto']}', 
                Descricao = '{$produto['Descricao']}', 
                PrecoVenda = {$produto['PrecoVenda']}, 
                CustoAquisicao = " . ($produto['CustoAquisicao'] !== null ? $produto['CustoAquisicao'] : 'NULL') . ", 
                EstoqueAtual = {$produto['EstoqueAtual']}, 
                EstoqueMinimo = {$produto['EstoqueMinimo']}, 
                CategoriaID = {$produto['CategoriaID']}, " .
                (!empty($produto['FotoURL']) ? "FotoURL = '{$produto['FotoURL']}', " : "") . "
                Ativo = {$produto['Ativo']} 
                WHERE ProdutoID = {$produto['ProdutoID']}";
    }
    
    if (empty($mensagem) && $conn->query($sql)) {
        $mensagem = "Produto " . (empty($_POST['ProdutoID']) ? 'cadastrado' : 'atualizado') . " com sucesso!";
    } elseif (empty($mensagem)) {
        $mensagem = "Erro: " . $conn->error;
    }
}

// Editar (READ)
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $sql = "SELECT * FROM tb_produto WHERE ProdutoID = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $produto = $result->fetch_assoc();
        $produto['CustoAquisicao'] = $produto['CustoAquisicao'] ?? '';
    } else {
        $mensagem = "Produto não encontrado!";
    }
}

// Excluir (DELETE)
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    // Primeiro obtém o caminho da foto para excluir o arquivo
    $sql = "SELECT FotoURL FROM tb_produto WHERE ProdutoID = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['FotoURL']) && file_exists($row['FotoURL'])) {
            unlink($row['FotoURL']);
        }
    }
    
    $sql = "DELETE FROM tb_produto WHERE ProdutoID = $id";
    if ($conn->query($sql)) {
        $mensagem = "Produto excluído com sucesso!";
        header("Location: produtos.php");
        exit;
    } else {
        $mensagem = "Erro ao excluir: " . $conn->error;
    }
}

// Listar todos os produtos com JOIN para categoria
$pesquisa = isset($_GET['pesquisa']) ? $conn->real_escape_string($_GET['pesquisa']) : '';
$sql = "SELECT p.*, c.NomeCategoria 
        FROM tb_produto p 
        JOIN tb_categoria_produto c ON p.CategoriaID = c.CategoriaID 
        WHERE p.NomeProduto LIKE '%$pesquisa%' OR p.Descricao LIKE '%$pesquisa%' OR c.NomeCategoria LIKE '%$pesquisa%'
        ORDER BY p.NomeProduto";
$result = $conn->query($sql);
$produtos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $produtos[] = $row;
    }
} else {
    $mensagem = "Erro ao listar produtos: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Produtos - Clínica Ótica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #5A9392;
            --secondary-color: #1E3A5F;
            --light-color: #F5F7FA;
            --dark-color: #333333;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
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
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
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
        
        .profile-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .profile-preview img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 5px;
            object-fit: cover;
            border: 2px solid var(--primary-color);
            margin-bottom: 10px;
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
        }
        
        .badge-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .badge-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .badge-warning {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
        }
        
        .checkbox-container input {
            margin-right: 10px;
            width: auto;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            th, td {
                padding: 8px 10px;
            }
            
            .profile-preview img {
                max-width: 150px;
                max-height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-box-open"></i> Cadastro de Produtos</h1>
            <div>
                <a href="categorias.php" class="btn btn-success"><i class="fas fa-tags"></i> Categorias</a>
                <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert <?php 
                echo strpos($mensagem, 'Erro') !== false ? 'alert-danger' : 
                    (strpos($mensagem, 'não permitido') !== false ? 'alert-warning' : 'alert-success'); 
            ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo empty($produto['ProdutoID']) ? 'Novo Produto' : 'Editar Produto'; ?></h2>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="ProdutoID" value="<?php echo $produto['ProdutoID']; ?>">
                
                <!-- Preview da foto do produto -->
                <div class="profile-preview">
                    <?php if (!empty($produto['FotoURL'])): ?>
                        <img src="<?php echo $produto['FotoURL']; ?>" alt="Foto do Produto" id="productImage">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/200?text=Sem+Imagem" alt="Sem foto" id="productImage">
                    <?php endif; ?>
                    <small>Pré-visualização da imagem</small>
                </div>
                
                <div class="form-group">
                    <label for="FotoURL">Imagem do Produto</label>
                    <input type="file" id="FotoURL" name="FotoURL" class="form-control" accept="image/*">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="NomeProduto">Nome do Produto *</label>
                        <input type="text" id="NomeProduto" name="NomeProduto" class="form-control" 
                               value="<?php echo htmlspecialchars($produto['NomeProduto']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="CategoriaID">Categoria *</label>
                        <select id="CategoriaID" name="CategoriaID" class="form-control" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categorias as $id => $nome): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($produto['CategoriaID'] == $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($nome); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="Descricao">Descrição</label>
                    <textarea id="Descricao" name="Descricao" class="form-control"><?php echo htmlspecialchars($produto['Descricao']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="PrecoVenda">Preço de Venda (kz) </label>
                        <input type="number" id="PrecoVenda" name="PrecoVenda" class="form-control" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($produto['PrecoVenda']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="CustoAquisicao">Custo de Aquisição (kz)</label>
                        <input type="number" id="CustoAquisicao" name="CustoAquisicao" class="form-control" step="0.01" min="0"
                               value="<?php echo htmlspecialchars($produto['CustoAquisicao']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="EstoqueAtual">Estoque Atual *</label>
                        <input type="number" id="EstoqueAtual" name="EstoqueAtual" class="form-control" min="0"
                               value="<?php echo htmlspecialchars($produto['EstoqueAtual']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="EstoqueMinimo">Estoque Mínimo</label>
                        <input type="number" id="EstoqueMinimo" name="EstoqueMinimo" class="form-control" min="0"
                               value="<?php echo htmlspecialchars($produto['EstoqueMinimo']); ?>">
                    </div>
                </div>
                
                <div class="form-group checkbox-container">
                    <input type="checkbox" id="Ativo" name="Ativo" class="form-control" 
                           <?php echo ($produto['Ativo'] == 1) ? 'checked' : ''; ?>>
                    <label for="Ativo">Produto ativo/disponível para venda</label>
                </div>
                
                <div class="form-group" style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo empty($produto['ProdutoID']) ? 'Cadastrar' : 'Atualizar'; ?>
                    </button>
                    <?php if (!empty($produto['ProdutoID'])): ?>
                        <a href="produtos.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Produtos Cadastrados</h2>
            </div>
            
            <!-- Barra de pesquisa -->
            <form method="GET" action="" class="search-container">
                <input type="text" name="pesquisa" placeholder="Pesquisar por nome, descrição ou categoria..." 
                       value="<?php echo htmlspecialchars($pesquisa); ?>">
                <button type="submit"><i class="fas fa-search"></i> Pesquisar</button>
                <?php if (!empty($pesquisa)): ?>
                    <a href="produtos.php" class="btn btn-secondary" style="margin-left: 10px;">Limpar</a>
                <?php endif; ?>
            </form>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Imagem</th>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $p): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($p['FotoURL'])): ?>
                                        <img src="<?php echo $p['FotoURL']; ?>" alt="Produto" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background-color: #eee; display: flex; align-items: center; justify-content: center; border-radius: 5px;">
                                            <i class="fas fa-box-open" style="color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($p['NomeProduto']); ?></td>
                                <td><?php echo htmlspecialchars($p['NomeCategoria']); ?></td>
                                <td>kz <?php echo number_format($p['PrecoVenda'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                        $estoque_class = 'badge-success';
                                        if ($p['EstoqueAtual'] <= 0) {
                                            $estoque_class = 'badge-danger';
                                        } elseif ($p['EstoqueAtual'] <= $p['EstoqueMinimo']) {
                                            $estoque_class = 'badge-warning';
                                        }
                                    ?>
                                    <span class="badge <?php echo $estoque_class; ?>">
                                        <?php echo $p['EstoqueAtual']; ?>
                                        <?php if ($p['EstoqueMinimo'] > 0): ?>
                                            / <?php echo $p['EstoqueMinimo']; ?> mín.
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $p['Ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $p['Ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="?editar=<?php echo $p['ProdutoID']; ?>"><i class="fas fa-edit"></i> Editar</a>
                                    <a href="?excluir=<?php echo $p['ProdutoID']; ?>" class="delete" 
                                       onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($produtos)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">Nenhum produto cadastrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Preview da imagem ao selecionar
        document.getElementById('FotoURL').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('productImage').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            var nome = document.getElementById('NomeProduto').value.trim();
            var categoria = document.getElementById('CategoriaID').value;
            var preco = document.getElementById('PrecoVenda').value;
            var estoque = document.getElementById('EstoqueAtual').value;
            
            if (nome === '' || categoria === '' || preco === '' || estoque === '') {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios (*)');
            }
            
            if (parseFloat(preco) <= 0) {
                e.preventDefault();
                alert('O preço de venda deve ser maior que zero');
            }
            
            if (parseInt(estoque) < 0) {
                e.preventDefault();
                alert('O estoque não pode ser negativo');
            }
        });
    </script>
</body>
</html>