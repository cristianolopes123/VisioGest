<?php
//session_start();
require_once('../conexao.php'); // Inclui sua conexão MySQLi

// Verificar autenticação
//if (!isset($_SESSION['usuario_id'])) {
   // header('Location: login.php');
   // exit;
//}

// Operações CRUD
$mensagem = '';
$fornecedor = [
    'FornecedorID' => '', 
    'NomeFornecedor' => '', 
    'ContatoPrincipal' => '', 
    'Telefone' => '', 
    'Email' => '', 
    'Endereco' => '', 
    'Cidade' => '', 
    'Pais' => 'Portugal', // Valor padrão
    'NIF_NIPC' => '', 
    'Observacoes' => '', 
    'Ativo' => 1
];

// Criar ou Atualizar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Preparar dados
    $fornecedor['NomeFornecedor'] = $conn->real_escape_string($_POST['NomeFornecedor']);
    $fornecedor['ContatoPrincipal'] = $conn->real_escape_string($_POST['ContatoPrincipal']);
    $fornecedor['Telefone'] = $conn->real_escape_string($_POST['Telefone']);
    $fornecedor['Email'] = $conn->real_escape_string($_POST['Email']);
    $fornecedor['Endereco'] = $conn->real_escape_string($_POST['Endereco']);
    $fornecedor['Cidade'] = $conn->real_escape_string($_POST['Cidade']);
    $fornecedor['Pais'] = $conn->real_escape_string($_POST['Pais']);
    $fornecedor['NIF_NIPC'] = $conn->real_escape_string($_POST['NIF_NIPC']);
    $fornecedor['Observacoes'] = $conn->real_escape_string($_POST['Observacoes']);
    $fornecedor['Ativo'] = isset($_POST['Ativo']) ? 1 : 0;
    
    if (empty($_POST['FornecedorID'])) {
        // CREATE
        $sql = "INSERT INTO tb_fornecedor (NomeFornecedor, ContatoPrincipal, Telefone, Email, Endereco, Cidade, Pais, NIF_NIPC, Observacoes, Ativo) 
                VALUES ('{$fornecedor['NomeFornecedor']}', '{$fornecedor['ContatoPrincipal']}', '{$fornecedor['Telefone']}', 
                '{$fornecedor['Email']}', '{$fornecedor['Endereco']}', '{$fornecedor['Cidade']}', '{$fornecedor['Pais']}', 
                '{$fornecedor['NIF_NIPC']}', '{$fornecedor['Observacoes']}', {$fornecedor['Ativo']})";
    } else {
        // UPDATE
        $fornecedor['FornecedorID'] = (int)$_POST['FornecedorID'];
        $sql = "UPDATE tb_fornecedor SET 
                NomeFornecedor = '{$fornecedor['NomeFornecedor']}', 
                ContatoPrincipal = '{$fornecedor['ContatoPrincipal']}', 
                Telefone = '{$fornecedor['Telefone']}', 
                Email = '{$fornecedor['Email']}', 
                Endereco = '{$fornecedor['Endereco']}', 
                Cidade = '{$fornecedor['Cidade']}', 
                Pais = '{$fornecedor['Pais']}', 
                NIF_NIPC = '{$fornecedor['NIF_NIPC']}', 
                Observacoes = '{$fornecedor['Observacoes']}', 
                Ativo = {$fornecedor['Ativo']} 
                WHERE FornecedorID = {$fornecedor['FornecedorID']}";
    }
    
    if ($conn->query($sql)) {
        $mensagem = "Fornecedor " . (empty($_POST['FornecedorID']) ? 'cadastrado' : 'atualizado') . " com sucesso!";
    } else {
        $mensagem = "Erro: " . $conn->error;
    }
}

// Editar (READ)
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $sql = "SELECT * FROM tb_fornecedor WHERE FornecedorID = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $fornecedor = $result->fetch_assoc();
    } else {
        $mensagem = "Fornecedor não encontrado!";
    }
}

// Excluir (DELETE)
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    
    // Verificar se existem produtos vinculados a este fornecedor
    $sql_check = "SELECT COUNT(*) AS total FROM tb_produtos WHERE FornecedorID = $id";
    $result = $conn->query($sql_check);
    $row = $result->fetch_assoc();
    
    if ($row['total'] > 0) {
        $mensagem = "Este fornecedor não pode ser excluído porque existem produtos vinculados a ele!";
    } else {
        $sql = "DELETE FROM tb_fornecedor WHERE FornecedorID = $id";
        if ($conn->query($sql)) {
            $mensagem = "Fornecedor excluído com sucesso!";
            header("Location: fornecedores.php");
            exit;
        } else {
            $mensagem = "Erro ao excluir: " . $conn->error;
        }
    }
}

// Listar todos os fornecedores
$pesquisa = isset($_GET['pesquisa']) ? $conn->real_escape_string($_GET['pesquisa']) : '';
$sql = "SELECT * FROM tb_fornecedor 
        WHERE NomeFornecedor LIKE '%$pesquisa%' OR 
              ContatoPrincipal LIKE '%$pesquisa%' OR 
              Email LIKE '%$pesquisa%' OR 
              NIF_NIPC LIKE '%$pesquisa%'
        ORDER BY NomeFornecedor";
$result = $conn->query($sql);
$fornecedores = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $fornecedores[] = $row;
    }
} else {
    $mensagem = "Erro ao listar fornecedores: " . $conn->error;
}

// Lista de países para o dropdown (poderia ser obtida de uma tabela tb_paises)
$paises = ['Portugal', 'Espanha', 'França', 'Alemanha', 'Itália', 'Brasil', 'Outro'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Fornecedores - Clínica Ótica</title>
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-truck"></i> Cadastro de Fornecedores</h1>
            <div>
                <a href="produtos.php" class="btn btn-success"><i class="fas fa-boxes"></i> Produtos</a>
                <a href="../Sistema/admin.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert <?php 
                echo strpos($mensagem, 'Erro') !== false ? 'alert-danger' : 
                    (strpos($mensagem, 'não pode ser excluído') !== false ? 'alert-warning' : 'alert-success'); 
            ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo empty($fornecedor['FornecedorID']) ? 'Novo Fornecedor' : 'Editar Fornecedor'; ?></h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="FornecedorID" value="<?php echo $fornecedor['FornecedorID']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="NomeFornecedor">Nome do Fornecedor *</label>
                        <input type="text" id="NomeFornecedor" name="NomeFornecedor" class="form-control" 
                               value="<?php echo htmlspecialchars($fornecedor['NomeFornecedor']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ContatoPrincipal">Contato Principal</label>
                        <input type="text" id="ContatoPrincipal" name="ContatoPrincipal" class="form-control" 
                               value="<?php echo htmlspecialchars($fornecedor['ContatoPrincipal']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="Telefone">Telefone *</label>
                        <input type="tel" id="Telefone" name="Telefone" class="form-control" 
                               value="<?php echo htmlspecialchars($fornecedor['Telefone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="Email">E-mail</label>
                        <input type="email" id="Email" name="Email" class="form-control" 
                               value="<?php echo htmlspecialchars($fornecedor['Email']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="NIF_NIPC">NIF/NIPC *</label>
                        <input type="text" id="NIF_NIPC" name="NIF_NIPC" class="form-control" 
                               value="<?php echo htmlspecialchars($fornecedor['NIF_NIPC']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="Pais">País *</label>
                        <select id="Pais" name="Pais" class="form-control" required>
                            <?php foreach ($paises as $pais): ?>
                                <option value="<?php echo $pais; ?>" <?php echo ($fornecedor['Pais'] == $pais) ? 'selected' : ''; ?>>
                                    <?php echo $pais; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="Endereco">Endereço</label>
                    <input type="text" id="Endereco" name="Endereco" class="form-control" 
                           value="<?php echo htmlspecialchars($fornecedor['Endereco']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="Cidade">Cidade</label>
                        <input type="text" id="Cidade" name="Cidade" class="form-control" 
                               value="<?php echo htmlspecialchars($fornecedor['Cidade']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="Observacoes">Observações</label>
                    <textarea id="Observacoes" name="Observacoes" class="form-control"><?php echo htmlspecialchars($fornecedor['Observacoes']); ?></textarea>
                </div>
                
                <div class="form-group checkbox-container">
                    <input type="checkbox" id="Ativo" name="Ativo" class="form-control" 
                           <?php echo ($fornecedor['Ativo'] == 1) ? 'checked' : ''; ?>>
                    <label for="Ativo">Fornecedor ativo</label>
                </div>
                
                <div class="form-group" style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo empty($fornecedor['FornecedorID']) ? 'Cadastrar' : 'Atualizar'; ?>
                    </button>
                    <?php if (!empty($fornecedor['FornecedorID'])): ?>
                        <a href="fornecedores.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Fornecedores Cadastrados</h2>
            </div>
            
            <!-- Barra de pesquisa -->
            <form method="GET" action="" class="search-container">
                <input type="text" name="pesquisa" placeholder="Pesquisar por nome, contato, email ou NIF..." 
                       value="<?php echo htmlspecialchars($pesquisa); ?>">
                <button type="submit"><i class="fas fa-search"></i> Pesquisar</button>
                <?php if (!empty($pesquisa)): ?>
                    <a href="fornecedores.php" class="btn btn-secondary" style="margin-left: 10px;">Limpar</a>
                <?php endif; ?>
            </form>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Fornecedor</th>
                            <th>Contato</th>
                            <th>Telefone</th>
                            <th>NIF/NIPC</th>
                            <th>País</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fornecedores as $f): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($f['NomeFornecedor']); ?></strong>
                                    <?php if (!empty($f['Email'])): ?>
                                        <br><small><?php echo htmlspecialchars($f['Email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($f['ContatoPrincipal']) ?: '---'; ?></td>
                                <td><?php echo htmlspecialchars($f['Telefone']); ?></td>
                                <td><?php echo htmlspecialchars($f['NIF_NIPC']); ?></td>
                                <td><?php echo htmlspecialchars($f['Pais']); ?></td>
                                <td>
                                    <span class="badge <?php echo $f['Ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $f['Ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td class="action-buttons">
                                    <a href="?editar=<?php echo $f['FornecedorID']; ?>"><i class="fas fa-edit"></i> Editar</a>
                                    <a href="?excluir=<?php echo $f['FornecedorID']; ?>" class="delete" 
                                       onclick="return confirm('Tem certeza que deseja excluir este fornecedor?')">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($fornecedores)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">Nenhum fornecedor cadastrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Máscara para telefone
        document.getElementById('Telefone').addEventListener('input', function(e) {
            var x = e.target.value.replace(/\D/g, '').match(/(\d{0,3})(\d{0,3})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : x[1] + ' ' + x[2] + (x[3] ? ' ' + x[3] : '');
        });
        
        // Máscara para NIF/NIPC (exemplo para Portugal: 9 dígitos)
        document.getElementById('NIF_NIPC').addEventListener('input', function(e) {
            var x = e.target.value.replace(/\D/g, '').match(/(\d{0,9})/);
            e.target.value = x[1];
        });
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            var nome = document.getElementById('NomeFornecedor').value.trim();
            var telefone = document.getElementById('Telefone').value.trim();
            var nif = document.getElementById('NIF_NIPC').value.trim();
            var pais = document.getElementById('Pais').value;
            
            if (nome === '' || telefone === '' || nif === '' || pais === '') {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios (*)');
            }
            
            if (nif.length < 9) {
                e.preventDefault();
                alert('O NIF/NIPC deve ter pelo menos 9 dígitos');
            }
        });
    </script>
</body>
</html>