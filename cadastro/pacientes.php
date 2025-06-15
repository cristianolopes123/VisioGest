<?php
require_once('../conexao.php'); // Inclui sua conexão MySQLi
$is_admin = (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'Administrador');
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Verifica se o usuário é administrador
$is_admin = (isset($_SESSION['nivel_acesso']) && $_SESSION['nivel_acesso'] === 'Administrador');

$pagina_voltar = 'sistema/sistema.php'; // Página padrão caso não tenha nível definido

// Definir a página de retorno baseada no nível de acesso
if (isset($_SESSION['nivel_acesso'])) {
    switch($_SESSION['nivel_acesso']) {
        case 'Administrador':
            $pagina_voltar = 'admin.php';
            break;
        case 'Profissional':
            $pagina_voltar = 'profissional.php';
            break;
        case 'Recepcionista':
            $pagina_voltar = '../sistema/recepcionista.php';
            break;
        case 'vendedor':
            $pagina_voltar = 'vendedor.php';
            break;
        default:
            $pagina_voltar = 'consulta/agendamento.php';
    }
}

// Operações CRUD
$mensagem = '';
$paciente = [
    'PacienteID' => '', 
    'NomeCompleto' => '', 
    'DataNascimento' => '', 
    'Sexo' => '', 
    'Endereco' => '', 
    'Telefone' => '', 
    'Email' => '', 
    'HistoricoMedicoOcular' => '', 
    'Alergias' => ''
];

// Criar ou Atualizar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Preparar dados
    $nome = $conn->real_escape_string($_POST['NomeCompleto']);
    $dataNasc = $conn->real_escape_string($_POST['DataNascimento']);
    $sexo = $conn->real_escape_string($_POST['Sexo']);
    $endereco = $conn->real_escape_string($_POST['Endereco']);
    $telefone = $conn->real_escape_string($_POST['Telefone']);
    $email = $conn->real_escape_string($_POST['Email']);
    $historico = $conn->real_escape_string($_POST['HistoricoMedicoOcular']);
    $alergias = $conn->real_escape_string($_POST['Alergias']);
    
    if (empty($_POST['PacienteID'])) {
        // CREATE - Permitido para todos os níveis
        $sql = "INSERT INTO tb_Pacientes (NomeCompleto, DataNascimento, Sexo, Endereco, Telefone, Email, HistoricoMedicoOcular, Alergias) 
                VALUES ('$nome', '$dataNasc', '$sexo', '$endereco', '$telefone', '$email', '$historico', '$alergias')";
        if ($conn->query($sql)) {
            $mensagem = "Paciente cadastrado com sucesso!";
        } else {
            $mensagem = "Erro ao cadastrar: " . $conn->error;
        }
    } else {
        // UPDATE - Só permite se for admin
        if ($is_admin) {
            $id = (int)$_POST['PacienteID'];
            $sql = "UPDATE tb_Pacientes SET 
                    NomeCompleto = '$nome', 
                    DataNascimento = '$dataNasc', 
                    Sexo = '$sexo', 
                    Endereco = '$endereco', 
                    Telefone = '$telefone', 
                    Email = '$email', 
                    HistoricoMedicoOcular = '$historico', 
                    Alergias = '$alergias' 
                    WHERE PacienteID = $id";
            if ($conn->query($sql)) {
                $mensagem = "Paciente atualizado com sucesso!";
            } else {
                $mensagem = "Erro ao atualizar: " . $conn->error;
            }
        } else {
            $mensagem = "Apenas administradores podem editar pacientes!";
        }
    }
}

// Editar (READ) - Só permite se for admin
if (isset($_GET['editar'])) {
    if ($is_admin) {
        $id = (int)$_GET['editar'];
        $sql = "SELECT * FROM tb_Pacientes WHERE PacienteID = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $paciente = $result->fetch_assoc();
        } else {
            $mensagem = "Paciente não encontrado!";
        }
    } else {
        $mensagem = "Apenas administradores podem editar pacientes!";
    }
}

// Excluir (DELETE) - Só permite se for admin
if (isset($_GET['excluir'])) {
    if ($is_admin) {
        $id = (int)$_GET['excluir'];
        $sql = "DELETE FROM tb_Pacientes WHERE PacienteID = $id";
        if ($conn->query($sql)) {
            $mensagem = "Paciente excluído com sucesso!";
            header("Location: pacientes.php");
            exit;
        } else {
            $mensagem = "Erro ao excluir: " . $conn->error;
        }
    } else {
        $mensagem = "Apenas administradores podem excluir pacientes!";
    }
}

// Listar todos os pacientes
$sql = "SELECT * FROM tb_Pacientes ORDER BY NomeCompleto";
$result = $conn->query($sql);
$pacientes = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pacientes[] = $row;
    }
} else {
    $mensagem = "Erro ao listar pacientes: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Pacientes - Clínica Ótica</title>
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
        
        .btn-warning {
            background-color: var(--warning-color);
            color: var(--dark-color);
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
        
        .text-muted {
            color: #6c757d;
            font-style: italic;
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
            <h1><i class="fas fa-user"></i> Cadastro de Pacientes</h1>
            <a href="<?php echo $pagina_voltar; ?>" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <?php if (!empty($mensagem)): ?>
            <div class="alert <?php echo strpos($mensagem, 'sucesso') !== false ? 'alert-success' : 'alert-warning'; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2><?php echo empty($paciente['PacienteID']) ? 'Novo Paciente' : 'Editar Paciente'; ?></h2>
            </div>
            
            <?php if (empty($paciente['PacienteID']) || $is_admin): ?>
                <form method="POST" action="">
                    <input type="hidden" name="PacienteID" value="<?php echo $paciente['PacienteID']; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="NomeCompleto">Nome Completo *</label>
                            <input type="text" id="NomeCompleto" name="NomeCompleto" class="form-control" 
                                   value="<?php echo htmlspecialchars($paciente['NomeCompleto']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="DataNascimento">Data de Nascimento *</label>
                            <input type="date" id="DataNascimento" name="DataNascimento" class="form-control" 
                                   value="<?php echo $paciente['DataNascimento']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="Sexo">Sexo</label>
                            <select id="Sexo" name="Sexo" class="form-control">
                                <option value="">Selecione</option>
                                <option value="Masculino" <?php echo ($paciente['Sexo'] == 'Masculino') ? 'selected' : ''; ?>>Masculino</option>
                                <option value="Feminino" <?php echo ($paciente['Sexo'] == 'Feminino') ? 'selected' : ''; ?>>Feminino</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="Telefone">Telefone *</label>
                            <input type="tel" id="Telefone" name="Telefone" class="form-control" 
                                   value="<?php echo htmlspecialchars($paciente['Telefone']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="Endereco">Endereço</label>
                        <input type="text" id="Endereco" name="Endereco" class="form-control" 
                               value="<?php echo htmlspecialchars($paciente['Endereco']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="Email">E-mail</label>
                        <input type="email" id="Email" name="Email" class="form-control" 
                               value="<?php echo htmlspecialchars($paciente['Email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="HistoricoMedicoOcular">Histórico Médico Ocular</label>
                        <textarea id="HistoricoMedicoOcular" name="HistoricoMedicoOcular" class="form-control" 
                                  rows="3"><?php echo htmlspecialchars($paciente['HistoricoMedicoOcular']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="Alergias">Alergias</label>
                        <textarea id="Alergias" name="Alergias" class="form-control" 
                                  rows="2"><?php echo htmlspecialchars($paciente['Alergias']); ?></textarea>
                    </div>
                    
                    <div class="form-group" style="text-align: right;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo empty($paciente['PacienteID']) ? 'Cadastrar' : 'Atualizar'; ?>
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    Apenas administradores podem editar pacientes existentes.
                </div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Pacientes Cadastrados</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Data Nasc.</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pacientes as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['NomeCompleto']); ?></td>
                                <td><?php echo htmlspecialchars($p['Telefone']); ?></td>
                                <td><?php echo htmlspecialchars($p['Email']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($p['DataNascimento'])); ?></td>
                                <td class="action-buttons">
                                    <?php if ($is_admin): ?>
                                        <a href="?editar=<?php echo $p['PacienteID']; ?>"><i class="fas fa-edit"></i> Editar</a>
                                        <a href="?excluir=<?php echo $p['PacienteID']; ?>" class="delete" 
                                           onclick="return confirm('Tem certeza que deseja excluir este paciente?')">
                                            <i class="fas fa-trash"></i> Excluir
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Apenas para administradores</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($pacientes)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">Nenhum paciente cadastrado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
      // Máscara simples para telefone de Angola
document.getElementById('Telefone').addEventListener('input', function(e) {
    // Remove tudo que não é dígito
    let numero = e.target.value.replace(/\D/g, '');
    
    // Aplica a máscara conforme o tamanho do número
    if (numero.length > 0) {
        // Se começar com 244 (código internacional)
        if (numero.startsWith('244')) {
            numero = numero.substring(3); // Remove o 244
            e.target.value = '+244 ' + numero.substring(0, 3) + 
                            (numero.length > 3 ? ' ' + numero.substring(3, 6) : '') +
                            (numero.length > 6 ? ' ' + numero.substring(6, 9) : '');
        } 
        // Formato nacional (9xx xxx xxx ou 2xx xxx xxx)
        else {
            e.target.value = numero.substring(0, 3) + 
                            (numero.length > 3 ? ' ' + numero.substring(3, 6) : '') +
                            (numero.length > 6 ? ' ' + numero.substring(6, 9) : '');
        }
    }
});
        // Validação do formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            var nome = document.getElementById('NomeCompleto').value.trim();
            var telefone = document.getElementById('Telefone').value.trim();
            var dataNasc = document.getElementById('DataNascimento').value;
            
            if (nome === '' || telefone === '' || dataNasc === '') {
                e.preventDefault();
                alert('Por favor, preencha todos os campos obrigatórios (*)');
            }
        });
    </script>
</body>
</html>