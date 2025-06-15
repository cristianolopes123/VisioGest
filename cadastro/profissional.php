<?php
session_start();
require_once('../conexao.php');

// Verificar autenticação e permissões
//if (!isset($_SESSION['usuario_id']) || $_SESSION['nivel_acesso'] != 'Administrador') {
//    header('Location: login.php');
//    exit;
//}

// Operações CRUD
$mensagem = '';
$profissional = [
    'ProfissionalID' => '', 
    'id_funcionario' => '',
    'NomeCompleto' => '', 
    'Especialidade' => 'Oftalmologista', 
    'CRM_OU_LICENCA' => '', 
    'Telefone' => '', 
    'Email' => '', 
    'Endereco' => '', 
    'DataNascimento' => '', 
    'Sexo' => '', 
    'FotoURL' => '', 
    'Ativo' => 1
];

// Configurações para upload de fotos
$upload_dir = 'uploads/profissionais/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];

// Obter lista de funcionários para o select
$funcionarios = [];
$sql_funcionarios = "SELECT id_funcionario, nome FROM tb_funcionario ORDER BY nome";
$result_funcionarios = $conn->query($sql_funcionarios);
if ($result_funcionarios) {
    while ($row = $result_funcionarios->fetch_assoc()) {
        $funcionarios[] = $row;
    }
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Preparar dados
        $dados = [
            'id_funcionario' => $conn->real_escape_string($_POST['id_funcionario']),
            'NomeCompleto' => $conn->real_escape_string($_POST['NomeCompleto']),
            'Especialidade' => $conn->real_escape_string($_POST['Especialidade']),
            'CRM_OU_LICENCA' => $conn->real_escape_string($_POST['CRM_OU_LICENCA']),
            'Telefone' => $conn->real_escape_string($_POST['Telefone']),
            'Email' => $conn->real_escape_string($_POST['Email']),
            'Endereco' => $conn->real_escape_string($_POST['Endereco']),
            'DataNascimento' => $conn->real_escape_string($_POST['DataNascimento']),
            'Sexo' => $conn->real_escape_string($_POST['Sexo']),
            'Ativo' => isset($_POST['Ativo']) ? 1 : 0
        ];

        // Processar upload da foto
        if (!empty($_FILES['FotoURL']['name'])) {
            $file_type = $_FILES['FotoURL']['type'];
            if (in_array($file_type, $allowed_types)) {
                $file_name = basename($_FILES['FotoURL']['name']);
                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = 'prof_' . uniqid() . '.' . $file_ext;
                $file_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($_FILES['FotoURL']['tmp_name'], $file_path)) {
                    // Remove a foto antiga se existir
                    if (!empty($profissional['FotoURL']) && file_exists($profissional['FotoURL'])) {
                        unlink($profissional['FotoURL']);
                    }
                    $dados['FotoURL'] = $file_path;
                } else {
                    throw new Exception("Erro ao fazer upload da foto.");
                }
            } else {
                throw new Exception("Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.");
            }
        }

        if (empty($_POST['ProfissionalID'])) {
            // CREATE
            $campos = implode(', ', array_keys($dados));
            $valores = "'" . implode("', '", $dados) . "'";
            $sql = "INSERT INTO tb_profissional ($campos) VALUES ($valores)";
        } else {
            // UPDATE
            $id = (int)$_POST['ProfissionalID'];
            $updates = [];
            foreach ($dados as $campo => $valor) {
                $updates[] = "$campo = '$valor'";
            }
            $sql = "UPDATE tb_profissional SET " . implode(', ', $updates) . " WHERE ProfissionalID = $id";
        }

        if ($conn->query($sql)) {
            $mensagem = ["type" => "success", "text" => "Profissional " . (empty($_POST['ProfissionalID']) ? 'cadastrado' : 'atualizado') . " com sucesso!"];
        } else {
            throw new Exception("Erro no banco de dados: " . $conn->error);
        }
    } catch (Exception $e) {
        $mensagem = ["type" => "danger", "text" => $e->getMessage()];
    }
}

// Editar (READ)
if (isset($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    $sql = "SELECT * FROM tb_profissional WHERE ProfissionalID = $id";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $profissional = $result->fetch_assoc();
    } else {
        $mensagem = ["type" => "warning", "text" => "Profissional não encontrado!"];
    }
}

// Excluir (DELETE)
if (isset($_GET['excluir'])) {
    $id = (int)$_GET['excluir'];
    try {
        // Primeiro obtém o caminho da foto para excluir o arquivo
        $sql = "SELECT FotoURL FROM tb_profissional WHERE ProfissionalID = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['FotoURL']) && file_exists($row['FotoURL'])) {
                unlink($row['FotoURL']);
            }
        }
        
        $sql = "DELETE FROM tb_profissional WHERE ProfissionalID = $id";
        if ($conn->query($sql)) {
            $mensagem = ["type" => "success", "text" => "Profissional excluído com sucesso!"];
            header("Location: profissionais.php");
            exit;
        } else {
            throw new Exception("Erro ao excluir: " . $conn->error);
        }
    } catch (Exception $e) {
        $mensagem = ["type" => "danger", "text" => $e->getMessage()];
    }
}

// Listar todos os profissionais
$pesquisa = isset($_GET['pesquisa']) ? $conn->real_escape_string($_GET['pesquisa']) : '';
$sql = "SELECT p.*, IFNULL(f.nome, 'Nenhum') as NomeFuncionario 
        FROM tb_profissional p
        LEFT JOIN tb_funcionario f ON p.id_funcionario = f.id_funcionario
        WHERE p.NomeCompleto LIKE '%$pesquisa%' OR 
              p.Especialidade LIKE '%$pesquisa%' OR 
              p.CRM_OU_LICENCA LIKE '%$pesquisa%' OR
              f.nome LIKE '%$pesquisa%'
        ORDER BY p.NomeCompleto";
$result = $conn->query($sql);
$profissionais = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $profissionais[] = $row;
    }
} else {
    $mensagem = ["type" => "danger", "text" => "Erro ao listar profissionais: " . $conn->error];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Profissionais - Visio-Gest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #5A9392;
            --primary-dark: #3a6d6b;
            --secondary-color: #1E3A5F;
            --light-color: #F5F7FA;
            --dark-color: #2d3748;
            --gray-light: #edf2f7;
            --gray-medium: #e2e8f0;
            --danger-color: #e53e3e;
            --danger-dark: #c53030;
            --success-color: #38a169;
            --warning-color: #dd6b20;
            --info-color: #3182ce;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
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
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-medium);
        }
        
        .header h1 {
            color: var(--secondary-color);
            font-size: 28px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: var(--danger-dark);
        }
        
        .btn-secondary {
            background-color: var(--gray-medium);
            color: var(--dark-color);
        }
        
        .btn-secondary:hover {
            background-color: #d1d8e0;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-medium);
        }
        
        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            border-bottom: 1px solid var(--gray-medium);
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            color: var(--secondary-color);
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--secondary-color);
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-medium);
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s;
            background-color: white;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(90, 147, 146, 0.2);
        }
        
        textarea.form-control {
            min-height: 120px;
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
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            opacity: 0;
            transform: translateY(-20px);
            animation: fadeInDown 0.5s ease forwards;
        }
        
        .alert-success {
            background-color: #f0fff4;
            color: var(--success-color);
            border-color: #c6f6d5;
        }
        
        .alert-danger {
            background-color: #fff5f5;
            color: var(--danger-color);
            border-color: #fed7d7;
        }
        
        .alert-warning {
            background-color: #fffaf0;
            color: var(--warning-color);
            border-color: #feebc8;
        }
        
        .alert-info {
            background-color: #ebf8ff;
            color: var(--info-color);
            border-color: #bee3f8;
        }
        
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray-medium);
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }
        
        tr:not(:last-child) {
            border-bottom: 1px solid var(--gray-medium);
        }
        
        tr:hover {
            background-color: rgba(90, 147, 146, 0.05);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .action-buttons a {
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 13px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        
        .action-buttons a.edit {
            background-color: rgba(90, 147, 146, 0.1);
            color: var(--primary-color);
        }
        
        .action-buttons a.edit:hover {
            background-color: rgba(90, 147, 146, 0.2);
        }
        
        .action-buttons a.delete {
            background-color: rgba(229, 62, 62, 0.1);
            color: var(--danger-color);
        }
        
        .action-buttons a.delete:hover {
            background-color: rgba(229, 62, 62, 0.2);
        }
        
        .profile-preview {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .profile-preview img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        
        .profile-preview img:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .profile-preview small {
            margin-top: 10px;
            color: var(--dark-color);
            font-size: 13px;
        }
        
        .search-container {
            display: flex;
            margin-bottom: 20px;
            position: relative;
        }
        
        .search-container input {
            flex: 1;
            padding: 12px 15px 12px 40px;
            border: 1px solid var(--gray-medium);
            border-radius: 6px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .search-container input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(90, 147, 146, 0.2);
        }
        
        .search-container::before {
            content: '\f002';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
        }
        
        .search-container button {
            padding: 0 20px;
            margin-left: 10px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .search-container button:hover {
            background-color: var(--primary-dark);
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .badge-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .badge-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .badge-secondary {
            background-color: var(--gray-medium);
            color: var(--dark-color);
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-container input {
            width: auto;
            margin: 0;
        }
        
        .checkbox-container label {
            margin: 0;
            font-weight: normal;
        }
        
        .select2-container .select2-selection--single {
            height: 42px;
            border: 1px solid var(--gray-medium);
            border-radius: 6px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 42px;
        }
        
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: all 0.3s;
            z-index: 100;
            border: none;
        }
        
        .floating-btn:hover {
            transform: translateY(-5px) scale(1.1);
            background-color: var(--primary-dark);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            th, td {
                padding: 12px 8px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .profile-preview img {
                width: 120px;
                height: 120px;
            }
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body>
    <div class="container">
        <div class="header animate__animated animate__fadeIn">
            <h1><i class="fas fa-user-md"></i> Gestão de Profissionais</h1>
            <div>
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $mensagem['type']; ?>">
                <i class="fas fa-<?php echo $mensagem['type'] == 'success' ? 'check-circle' : ($mensagem['type'] == 'danger' ? 'times-circle' : 'info-circle'); ?>"></i>
                <?php echo $mensagem['text']; ?>
            </div>
        <?php endif; ?>
        
        <div class="card animate__animated animate__fadeInUp">
            <div class="card-header">
                <h2><i class="fas fa-<?php echo empty($profissional['ProfissionalID']) ? 'user-plus' : 'user-edit'; ?>"></i> <?php echo empty($profissional['ProfissionalID']) ? 'Novo Profissional' : 'Editar Profissional'; ?></h2>
            </div>
            <form method="POST" action="" enctype="multipart/form-data" id="profissionalForm">
                <input type="hidden" name="ProfissionalID" value="<?php echo $profissional['ProfissionalID']; ?>">
                
                <!-- Preview da foto do profissional -->
                <div class="profile-preview">
                    <?php if (!empty($profissional['FotoURL'])): ?>
                        <img src="<?php echo $profissional['FotoURL']; ?>" alt="Foto do Profissional" id="profileImage">
                    <?php else: ?>
                        <img src="https://via.placeholder.com/150?text=Sem+Imagem" alt="Sem foto" id="profileImage">
                    <?php endif; ?>
                    <small>Pré-visualização da foto</small>
                </div>
                
                <div class="form-group">
                    <label for="FotoURL">Foto do Profissional</label>
                    <input type="file" id="FotoURL" name="FotoURL" class="form-control" accept="image/*">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="id_funcionario">Funcionário Associado</label>
                        <select id="id_funcionario" name="id_funcionario" class="form-control">
                            <option value="">Selecione um funcionário</option>
                            <?php foreach ($funcionarios as $func): ?>
                                <option value="<?php echo $func['id_funcionario']; ?>" 
                                    <?php echo ($profissional['id_funcionario'] == $func['id_funcionario']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($func['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="NomeCompleto">Nome Completo *</label>
                        <input type="text" id="NomeCompleto" name="NomeCompleto" class="form-control" 
                               value="<?php echo htmlspecialchars($profissional['NomeCompleto']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="Especialidade">Especialidade *</label>
                        <select id="Especialidade" name="Especialidade" class="form-control" required>
                            <option value="Oftalmologista" <?php echo ($profissional['Especialidade'] == 'Oftalmologista') ? 'selected' : ''; ?>>Oftalmologista</option>
                            <option value="Optometrista" <?php echo ($profissional['Especialidade'] == 'Optometrista') ? 'selected' : ''; ?>>Optometrista</option>
                            <option value="Técnico de Ótica" <?php echo ($profissional['Especialidade'] == 'Técnico de Ótica') ? 'selected' : ''; ?>>Técnico de Ótica</option>
                            <option value="Ortoqueratologista" <?php echo ($profissional['Especialidade'] == 'Ortoqueratologista') ? 'selected' : ''; ?>>Ortoqueratologista</option>
                            <option value="Outra" <?php echo ($profissional['Especialidade'] == 'Outra') ? 'selected' : ''; ?>>Outra</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="CRM_OU_LICENCA">Registro Profissional (CRM/CBO) *</label>
                        <input type="text" id="CRM_OU_LICENCA" name="CRM_OU_LICENCA" class="form-control" 
                               value="<?php echo htmlspecialchars($profissional['CRM_OU_LICENCA']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="Telefone">Telefone *</label>
                        <input type="tel" id="Telefone" name="Telefone" class="form-control" 
                               value="<?php echo htmlspecialchars($profissional['Telefone']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="Email">E-mail</label>
                        <input type="email" id="Email" name="Email" class="form-control" 
                               value="<?php echo htmlspecialchars($profissional['Email']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="Endereco">Endereço</label>
                    <input type="text" id="Endereco" name="Endereco" class="form-control" 
                           value="<?php echo htmlspecialchars($profissional['Endereco']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="DataNascimento">Data de Nascimento</label>
                        <input type="date" id="DataNascimento" name="DataNascimento" class="form-control" 
                               value="<?php echo $profissional['DataNascimento']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Sexo">Sexo</label>
                        <select id="Sexo" name="Sexo" class="form-control">
                            <option value="">Selecione</option>
                            <option value="Masculino" <?php echo ($profissional['Sexo'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="Feminino" <?php echo ($profissional['Sexo'] == 'Feminino') ? 'selected' : ''; ?>>Feminino</option>
                            <option value="Outro" <?php echo ($profissional['Sexo'] == 'Outro') ? 'selected' : ''; ?>>Outro</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group checkbox-container">
                    <input type="checkbox" id="Ativo" name="Ativo" class="form-control" 
                           <?php echo ($profissional['Ativo'] == 1) ? 'checked' : ''; ?>>
                    <label for="Ativo">Profissional ativo na clínica</label>
                </div>
                
                <div class="form-group" style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo empty($profissional['ProfissionalID']) ? 'Cadastrar' : 'Atualizar'; ?>
                    </button>
                    <?php if (!empty($profissional['ProfissionalID'])): ?>
                        <a href="profissionais.php" class="btn btn-secondary">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <div class="card animate__animated animate__fadeInUp">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Profissionais Cadastrados</h2>
            </div>
            
            <!-- Barra de pesquisa -->
            <form method="GET" action="" class="search-container">
                <input type="text" name="pesquisa" placeholder="Pesquisar por nome, especialidade ou registro..." 
                       value="<?php echo htmlspecialchars($pesquisa); ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
                <?php if (!empty($pesquisa)): ?>
                    <a href="profissionais.php" class="btn btn-secondary" style="margin-left: 10px;">Limpar</a>
                <?php endif; ?>
            </form>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Nome</th>
                            <th>Especialidade</th>
                            <th>Registro</th>
                            <th>Funcionário Associado</th>
                            <th>Contato</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profissionais as $p): ?>
                            <tr class="fade-in">
                                <td>
                                    <?php if (!empty($p['FotoURL'])): ?>
                                        <img src="<?php echo $p['FotoURL']; ?>" alt="Foto" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background-color: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-user-md" style="color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($p['NomeCompleto']); ?></strong></td>
                                <td><?php echo htmlspecialchars($p['Especialidade']); ?></td>
                                <td><?php echo htmlspecialchars($p['CRM_OU_LICENCA']); ?></td>
                                <td><?php echo htmlspecialchars($p['NomeFuncionario']); ?></td>
                                <td>
                                    <?php if (!empty($p['Telefone'])): ?>
                                        <div><?php echo htmlspecialchars($p['Telefone']); ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($p['Email'])): ?>
                                        <div style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($p['Email']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $p['Ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $p['Ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?editar=<?php echo $p['ProfissionalID']; ?>" class="edit">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <a href="?excluir=<?php echo $p['ProfissionalID']; ?>" class="delete" 
                                           onclick="return confirm('Tem certeza que deseja excluir este profissional?')">
                                            <i class="fas fa-trash"></i> Excluir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($profissionais)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">Nenhum profissional cadastrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <a href="#profissionalForm" class="floating-btn animate__animated animate__fadeInUp">
        <i class="fas fa-plus"></i>
    </a>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Inicializar Select2
        $(document).ready(function() {
            $('#Especialidade').select2({
                placeholder: "Selecione uma especialidade",
                allowClear: true,
                width: '100%'
            });
            
            $('#Sexo').select2({
                placeholder: "Selecione o sexo",
                allowClear: true,
                width: '100%'
            });
            
            $('#id_funcionario').select2({
                placeholder: "Selecione um funcionário",
                allowClear: true,
                width: '100%'
            });

            // Preview da foto ao selecionar um arquivo
            $('#FotoURL').change(function() {
                if (this.files && this.files[0]) {
                    var reader = new FileReader();
                    
                    reader.onload = function(e) {
                        $('#profileImage').attr('src', e.target.result);
                        $('#profileImage').addClass('animate__animated animate__pulse');
                        setTimeout(() => {
                            $('#profileImage').removeClass('animate__animated animate__pulse');
                        }, 1000);
                    }
                    
                    reader.readAsDataURL(this.files[0]);
                }
            });

            // Máscara para telefone
            $('#Telefone').inputmask('(99) 99999-9999');

            // Scroll suave para o formulário ao clicar no botão flutuante
            $('.floating-btn').click(function(e) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: $('#profissionalForm').offset().top - 20
                }, 500);
            });

            // Verificar se há mensagem para exibir
            <?php if (!empty($mensagem)): ?>
                setTimeout(function() {
                    $('.alert').fadeOut(1000, function() {
                        $(this).remove();
                    });
                }, 5000);
            <?php endif; ?>
        });

        // Função para confirmar exclusão
        function confirmarExclusao() {
            return confirm('Tem certeza que deseja excluir este profissional? Esta ação não pode ser desfeita.');
        }
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.js"></script>
</body>
</html>
<?php
// Fechar conexão
$conn->close();
?>