<?php
session_start();
require_once('../conexao.php');

// Verificar se o usuário tem permissão de administrador
//if (!isset($_SESSION['usuario_id']) || ($_SESSION['nivel_acesso'] ?? '') != 'Administrador') {
  //  header('Location: ../login.php');
    //exit;
//}

// Operações CRUD
$mensagem = '';
$usuario = [
    'UsuarioID' => '', 
    'id_funcionario' => '',
    'NomeCompleto' => '', 
    'NomeUsuario' => '', 
    'Senha' => '', 
    'SenhaDescriptografada' => '',
    'NivelAcesso' => 'Recepcionista'
];

// Criar ou Atualizar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Preparar dados
    $id_funcionario = $conn->real_escape_string($_POST['id_funcionario'] ?? '');
    $nome_completo = $conn->real_escape_string($_POST['NomeCompleto'] ?? '');
    $nome_usuario = $conn->real_escape_string($_POST['NomeUsuario'] ?? '');
    $nivel_acesso = $conn->real_escape_string($_POST['NivelAcesso'] ?? '');
    $senha_descriptografada = $conn->real_escape_string($_POST['SenhaDescriptografada'] ?? '');
    
    // Verificar se é uma atualização ou novo cadastro
    if (empty($_POST['UsuarioID'])) {
        // CREATE - Novo usuário
        $senha = password_hash($senha_descriptografada, PASSWORD_DEFAULT);
        $sql = "INSERT INTO tb_usuario (id_funcionario, NomeCompleto, NomeUsuario, Senha, SenhaDescriptografada, NivelAcesso) 
                VALUES ('$id_funcionario', '$nome_completo', '$nome_usuario', '$senha', '$senha_descriptografada', '$nivel_acesso')";
    } else {
        // UPDATE - Atualizar usuário
        $id = (int)$_POST['UsuarioID'];
        if (!empty($senha_descriptografada)) {
            $senha = password_hash($senha_descriptografada, PASSWORD_DEFAULT);
            $sql = "UPDATE tb_usuario SET 
                    id_funcionario = '$id_funcionario',
                    NomeCompleto = '$nome_completo', 
                    NomeUsuario = '$nome_usuario', 
                    Senha = '$senha', 
                    SenhaDescriptografada = '$senha_descriptografada',
                    NivelAcesso = '$nivel_acesso' 
                    WHERE UsuarioID = $id";
        } else {
            // Não atualizar a senha se o campo estiver vazio
            $sql = "UPDATE tb_usuario SET 
                    id_funcionario = '$id_funcionario',
                    NomeCompleto = '$nome_completo', 
                    NomeUsuario = '$nome_usuario', 
                    NivelAcesso = '$nivel_acesso' 
                    WHERE UsuarioID = $id";
        }
    }
    
    if ($conn->query($sql)) {
        $mensagem = "Usuário " . (empty($_POST['UsuarioID']) ? 'cadastrado' : 'atualizado') . " com sucesso!";
    } else {
        $mensagem = "Erro: " . $conn->error;
    }
}

// Editar (READ)
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $sql = "SELECT * FROM tb_usuario WHERE UsuarioID = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        $usuario['Senha'] = ''; // Não mostramos a senha hash
        $usuario['SenhaDescriptografada'] = $usuario['SenhaDescriptografada'] ?? '';
    } else {
        $mensagem = "Usuário não encontrado!";
    }
}

// Excluir (DELETE)
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    // Verificar se existe sessão e se não está tentando excluir a si mesmo
    if (isset($_SESSION['usuario_id']) && $id != $_SESSION['usuario_id']) {
        $sql = "DELETE FROM tb_usuario WHERE UsuarioID = $id";
        if ($conn->query($sql)) {
            $mensagem = "Usuário excluído com sucesso!";
            header("Location: usuarios.php");
            exit;
        } else {
            $mensagem = "Erro ao excluir: " . $conn->error;
        }
    } else {
        $mensagem = "Você não pode excluir seu próprio usuário!";
    }
}

// Listar todos os usuários
$sql = "SELECT * FROM tb_usuario ORDER BY NivelAcesso, NomeCompleto";
$result = $conn->query($sql);
$usuarios = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
} else {
    $mensagem = "Erro ao listar usuários: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuários - Clínica Ótica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #5A9392;
            --secondary-color: #1E3A5F;
            --light-color: #F5F7FA;
            --dark-color: #333333;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
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
        
        .btn-warning {
            background-color: var(--warning-color);
            color: var(--dark-color);
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
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
        
        .action-buttons .warning {
            color: var(--warning-color);
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .badge-success {
            background-color: #28a745;
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
        
        .senha-cell {
            font-family: monospace;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
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
            <h1><i class="fas fa-user-shield"></i> Cadastro de Usuários</h1>
            <a href="../Sistema/admin.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert <?php 
                echo strpos($mensagem, 'Erro') !== false ? 'alert-danger' : 
                    (strpos($mensagem, 'Você não pode') !== false ? 'alert-warning' : 'alert-success'); 
            ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo empty($usuario['UsuarioID']) ? 'Novo Usuário' : 'Editar Usuário'; ?></h2>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="UsuarioID" value="<?php echo $usuario['UsuarioID']; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_funcionario">ID Funcionário</label>
                        <input type="text" id="id_funcionario" name="id_funcionario" class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['id_funcionario']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="NomeCompleto">Nome Completo *</label>
                        <input type="text" id="NomeCompleto" name="NomeCompleto" class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['NomeCompleto']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="NomeUsuario">Nome de Usuário *</label>
                        <input type="text" id="NomeUsuario" name="NomeUsuario" class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['NomeUsuario']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="SenhaDescriptografada">Senha *</label>
                        <input type="text" id="SenhaDescriptografada" name="SenhaDescriptografada" class="form-control" 
                               value="<?php echo htmlspecialchars($usuario['SenhaDescriptografada']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="NivelAcesso">Nível de Acesso *</label>
                        <select id="NivelAcesso" name="NivelAcesso" class="form-control" required>
                            <option value="Recepcionista" <?php echo ($usuario['NivelAcesso'] == 'Recepcionista') ? 'selected' : ''; ?>>Recepcionista</option>
                            <option value="Técnico" <?php echo ($usuario['NivelAcesso'] == 'Técnico') ? 'selected' : ''; ?>>Técnico</option>
                            <option value="Profissional" <?php echo ($usuario['NivelAcesso'] == 'Profissional') ? 'selected' : ''; ?>>Profissional</option>
                            <option value="Vendedor" <?php echo ($usuario['NivelAcesso'] == 'Vendedor') ? 'selected' : ''; ?>>Vendedor</option>
                            <option value="Administrador" <?php echo ($usuario['NivelAcesso'] == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group" style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo empty($usuario['UsuarioID']) ? 'Cadastrar' : 'Atualizar'; ?>
                    </button>
                    <?php if (!empty($usuario['UsuarioID'])): ?>
                        <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Usuários Cadastrados</h2>
            </div>
            
            <!-- Barra de pesquisa -->
            <form method="GET" action="" class="search-container">
                <input type="text" name="pesquisa" placeholder="Pesquisar por nome ou nome de usuário..." 
                       value="<?php echo isset($_GET['pesquisa']) ? htmlspecialchars($_GET['pesquisa']) : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i> Pesquisar</button>
                <?php if (isset($_GET['pesquisa'])): ?>
                    <a href="usuarios.php" class="btn btn-secondary" style="margin-left: 10px;">Limpar</a>
                <?php endif; ?>
            </form>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID Funcionário</th>
                            <th>Nome Completo</th>
                            <th>Nome de Usuário</th>
                            <th>Nível de Acesso</th>
                            <th>Senha</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['id_funcionario']); ?></td>
                                <td><?php echo htmlspecialchars($u['NomeCompleto']); ?></td>
                                <td><?php echo htmlspecialchars($u['NomeUsuario']); ?></td>
                                <td>
                                    <?php 
                                        $badge_class = 'badge-secondary';
                                        if ($u['NivelAcesso'] == 'Administrador') $badge_class = 'badge-danger';
                                        elseif ($u['NivelAcesso'] == 'Profissional') $badge_class = 'badge-success';
                                        elseif ($u['NivelAcesso'] == 'Técnico') $badge_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $u['NivelAcesso']; ?></span>
                                </td>
                                <td class="action-buttons">
                                    <a href="?editar=<?php echo $u['UsuarioID']; ?>"><i class="fas fa-edit"></i> Editar</a>
                                    <?php if (isset($_SESSION['usuario_id']) && $u['UsuarioID'] != $_SESSION['usuario_id']): ?>
                                        <a href="?excluir=<?php echo $u['UsuarioID']; ?>" class="delete" 
                                           onclick="return confirm('Tem certeza que deseja excluir este usuário?')">
                                            <i class="fas fa-trash"></i> Excluir
                                        </a>
                                    <?php elseif (isset($_SESSION['usuario_id'])): ?>
                                        <span class="warning"><i class="fas fa-user"></i> Você</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Nenhum usuário cadastrado</td>
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
            var nome = document.getElementById('NomeCompleto').value.trim();
            var usuario = document.getElementById('NomeUsuario').value.trim();
            var nivel = document.getElementById('NivelAcesso').value;
         
            
            if (nome === '' || usuario === '' || nivel === '' || senha === '') {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios (*)');
            }
        });
    </script>
</body>
</html>