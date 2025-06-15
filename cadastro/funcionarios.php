<?php
//session_start();
require_once('../conexao.php'); // Inclui sua conexão MySQLi

// Verificar autenticação
//if (!isset($_SESSION['usuario_id'])) {
    //header('Location: login.php');
    //exit;
//}

// Operações CRUD
$mensagem = '';
$funcionario = [
    'id_funcionario' => '', 
    'nome' => '', 
    'morada' => '', 
    'telefone' => '', 
    'email' => '', 
    'foto_perfil' => '', 
    'data_contratacao' => '', 
    'cargo' => '', 
    'salario' => '', 
    'data_nascimento' => '', 
    'sexo' => ''
];

// Processar upload de foto
$upload_dir = 'uploads/funcionarios/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Criar ou Atualizar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Preparar dados
    $nome = $conn->real_escape_string($_POST['nome']);
    $morada = $conn->real_escape_string($_POST['morada']);
    $telefone = $conn->real_escape_string($_POST['telefone']);
    $email = $conn->real_escape_string($_POST['email']);
    $data_contratacao = $conn->real_escape_string($_POST['data_contratacao']);
    $cargo = $conn->real_escape_string($_POST['cargo']);
    $salario = (float)$_POST['salario'];
    $data_nascimento = $conn->real_escape_string($_POST['data_nascimento']);
    $sexo = $conn->real_escape_string($_POST['sexo']);
    
    // Processar upload da foto
    $foto_perfil = $funcionario['foto_perfil']; // Mantém a foto atual se não for alterada
    if (!empty($_FILES['foto_perfil']['name'])) {
        $foto_name = basename($_FILES['foto_perfil']['name']);
        $foto_tmp = $_FILES['foto_perfil']['tmp_name'];
        $foto_path = $upload_dir . uniqid() . '_' . $foto_name;
        
        if (move_uploaded_file($foto_tmp, $foto_path)) {
            $foto_perfil = $foto_path;
            // Remove a foto antiga se existir
            if (!empty($funcionario['foto_perfil']) && file_exists($funcionario['foto_perfil'])) {
                unlink($funcionario['foto_perfil']);
            }
        } else {
            $mensagem = "Erro ao fazer upload da foto.";
        }
    }
    
    if (empty($_POST['id_funcionario'])) {
        // CREATE
        $sql = "INSERT INTO tb_funcionario (nome, morada, telefone, email, foto_perfil, data_contratacao, cargo, salario, data_nascimento, sexo) 
                VALUES ('$nome', '$morada', '$telefone', '$email', '$foto_perfil', '$data_contratacao', '$cargo', $salario, '$data_nascimento', '$sexo')";
        if ($conn->query($sql)) {
            $mensagem = "Funcionário cadastrado com sucesso!";
        } else {
            $mensagem = "Erro ao cadastrar: " . $conn->error;
        }
    } else {
        // UPDATE
        $id = (int)$_POST['id_funcionario'];
        $sql = "UPDATE tb_funcionario SET 
                nome = '$nome', 
                morada = '$morada', 
                telefone = '$telefone', 
                email = '$email', 
                foto_perfil = '$foto_perfil', 
                data_contratacao = '$data_contratacao', 
                cargo = '$cargo', 
                salario = $salario, 
                data_nascimento = '$data_nascimento', 
                sexo = '$sexo' 
                WHERE id_funcionario = $id";
        if ($conn->query($sql)) {
            $mensagem = "Funcionário atualizado com sucesso!";
        } else {
            $mensagem = "Erro ao atualizar: " . $conn->error;
        }
    }
}

// Editar (READ)
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $sql = "SELECT * FROM tb_funcionario WHERE id_funcionario = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $funcionario = $result->fetch_assoc();
    } else {
        $mensagem = "Funcionário não encontrado!";
    }
}

// Excluir (DELETE)
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    
    // Primeiro obtém o caminho da foto para excluir o arquivo
    $sql = "SELECT foto_perfil FROM tb_funcionario WHERE id_funcionario = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (!empty($row['foto_perfil']) && file_exists($row['foto_perfil'])) {
            unlink($row['foto_perfil']);
        }
    }
    
    $sql = "DELETE FROM tb_funcionario WHERE id_funcionario = $id";
    if ($conn->query($sql)) {
        $mensagem = "Funcionário excluído com sucesso!";
        header("Location: funcionarios.php");
        exit;
    } else {
        $mensagem = "Erro ao excluir: " . $conn->error;
    }
}

// Pesquisar funcionários
$pesquisa = isset($_GET['pesquisa']) ? $conn->real_escape_string($_GET['pesquisa']) : '';
$sql = "SELECT * FROM tb_funcionario WHERE nome LIKE '%$pesquisa%' OR email LIKE '%$pesquisa%' OR cargo LIKE '%$pesquisa%' ORDER BY nome";
$result = $conn->query($sql);
$funcionarios = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $funcionarios[] = $row;
    }
} else {
    $mensagem = "Erro ao listar funcionários: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Funcionários - Clínica Ótica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #5A9392;
            --secondary-color: #1E3A5F;
            --light-color: #F5F7FA;
            --dark-color: #333333;
            --danger-color: #dc3545;
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
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
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
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            th, td {
                padding: 8px 10px;
            }
            
            .profile-preview img {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Cadastro de Funcionários</h1>
            <a href="../Sistema/admin.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert <?php echo strpos($mensagem, 'Erro') !== false ? 'alert-danger' : 'alert-success'; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo empty($funcionario['id_funcionario']) ? 'Novo Funcionário' : 'Editar Funcionário'; ?></h2>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id_funcionario" value="<?php echo $funcionario['id_funcionario']; ?>">
                
                <!-- Preview da foto do perfil -->
                <div class="profile-preview">
                    <?php if (!empty($funcionario['foto_perfil'])): ?>
                        <img src="<?php echo $funcionario['foto_perfil']; ?>" alt="Foto do Funcionário" id="profileImage">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/150" alt="Sem foto" id="profileImage">
                    <?php endif; ?>
                    <small>Pré-visualização da foto</small>
                </div>
                
                <div class="form-group">
                    <label for="foto_perfil">Foto do Perfil</label>
                    <input type="file" id="foto_perfil" name="foto_perfil" class="form-control" accept="image/*">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nome">Nome Completo  </label>
                        <input type="text" id="nome" name="nome" class="form-control" 
                               value="<?php echo htmlspecialchars($funcionario['nome']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="data_nascimento">Data de Nascimento  </label>
                        <input type="date" id="data_nascimento" name="data_nascimento" class="form-control" 
                               value="<?php echo $funcionario['data_nascimento']; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sexo">Sexo</label>
                        <select id="sexo" name="sexo" class="form-control">
                            <option value="">Selecione</option>
                            <option value="Masculino" <?php echo ($funcionario['sexo'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Feminino" <?php echo ($funcionario['sexo'] == 'Feminino') ? 'selected' : ''; ?>>Feminino</option>
                            <option value="Outro" <?php echo ($funcionario['sexo'] == 'Outro') ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Telefone  </label>
                        <input type="tel" id="telefone" name="telefone" class="form-control" 
                               value="<?php echo htmlspecialchars($funcionario['telefone']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">E-mail  </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($funcionario['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="morada">Morada</label>
                    <input type="text" id="morada" name="morada" class="form-control" 
                           value="<?php echo htmlspecialchars($funcionario['morada']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="data_contratacao">Data de Contratação  </label>
                        <input type="date" id="data_contratacao" name="data_contratacao" class="form-control" 
                               value="<?php echo $funcionario['data_contratacao']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="cargo">Cargo  </label>
                        <input type="text" id="cargo" name="cargo" class="form-control" 
                               value="<?php echo htmlspecialchars($funcionario['cargo']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="salario">Salário (kz)  </label>
                    <input type="number" id="salario" name="salario" class="form-control" step="0.01" min="0"
                           value="<?php echo htmlspecialchars($funcionario['salario']); ?>" required>
                </div>
                
                <div class="form-group" style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo empty($funcionario['id_funcionario']) ? 'Cadastrar' : 'Atualizar'; ?>
                    </button>
                    <?php if (!empty($funcionario['id_funcionario'])): ?>
                        <a href="funcionarios.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Funcionários Cadastrados</h2>
            </div>
            
            <!-- Barra de pesquisa -->
            <form method="GET" action="" class="search-container">
                <input type="text" name="pesquisa" placeholder="Pesquisar por nome, email ou cargo..." 
                       value="<?php echo htmlspecialchars($pesquisa); ?>">
                <button type="submit"><i class="fas fa-search"></i> Pesquisar</button>
                <?php if (!empty($pesquisa)): ?>
                    <a href="funcionarios.php" class="btn btn-secondary" style="margin-left: 10px;">Limpar</a>
                <?php endif; ?>
            </form>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Cargo</th>
                            <th>Telefone</th>
                            <th>Contratação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($funcionarios as $f): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($f['foto_perfil'])): ?>
                                        <img src="<?php echo $f['foto_perfil']; ?>" alt="Foto" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; border-radius: 50%; background-color: #eee; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user" style="color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($f['nome']); ?></td>
                                <td><?php echo htmlspecialchars($f['cargo']); ?></td>
                                <td><?php echo htmlspecialchars($f['telefone']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($f['data_contratacao'])); ?></td>
                                <td class="action-buttons">
                                    <a href="?editar=<?php echo $f['id_funcionario']; ?>"><i class="fas fa-edit"></i> Editar</a>
                                    <a href="?excluir=<?php echo $f['id_funcionario']; ?>" class="delete" 
                                       onclick="return confirm('Tem certeza que deseja excluir este funcionário?')">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($funcionarios)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Nenhum funcionário cadastrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            var x = e.target.value.replace(/\D/g, '').match(/(\d{0,2})(\d{0,5})(\d{0,4})/);
            e.target.value = !x[2] ? x[1] : '(' + x[1] + ') ' + x[2] + (x[3] ? '-' + x[3] : '');
        });
        
        // Preview da imagem ao selecionar
        document.getElementById('foto_perfil').addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profileImage').src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            var nome = document.getElementById('nome').value.trim();
            var telefone = document.getElementById('telefone').value.trim();
            var email = document.getElementById('email').value.trim();
            var dataContratacao = document.getElementById('data_contratacao').value;
            var cargo = document.getElementById('cargo').value.trim();
            var salario = document.getElementById('salario').value;
            
            if (nome === '' || telefone === '' || email === '' || dataContratacao === '' || cargo === '' || salario === '') {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios (*)');
            }
        });
    </script>
</body>
</html>