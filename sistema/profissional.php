<?php
session_start();

// Verificação de login e perfil profissional
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Conexão com o banco de dados
try {
    $host = 'localhost';
    $dbname = 'BD_vISIO';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Consultas para dados do dashboard
$proximas_consultas = [];
$consultas_hoje = 0;
$total_prescricoes = 0;
$consultas_realizadas = 0;
$prescricoes_pendentes = 0;

try {
    $profissional_id = $_SESSION['user_id'];
    $data_atual = date('Y-m-d');
    
    // Próximas consultas
    $stmt = $pdo->prepare("SELECT a.*, c.nome as nome_completo
                          FROM tb_agendamento a 
                          JOIN tb_pacientes c ON a.PacienteID = c.id 
                          WHERE a.id_profissional = :profissional_id 
                          AND a.data_consulta >= :data_atual 
                          ORDER BY a.data_consulta, a.hora_consulta");
    $stmt->execute(['profissional_id' => $profissional_id, 'data_atual' => $data_atual]);
    $proximas_consultas = $stmt->fetchAll();
    
    // Consultas hoje
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tb_agendamento 
                          WHERE id_profissional = :profissional_id 
                          AND data_consulta = :data_atual");
    $stmt->execute(['profissional_id' => $profissional_id, 'data_atual' => $data_atual]);
    $consultas_hoje = $stmt->fetch()['total'];
    
    // Total de prescrições
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tb_prescricoes 
                          WHERE id_profissional = :profissional_id");
    $stmt->execute(['profissional_id' => $profissional_id]);
    $total_prescricoes = $stmt->fetch()['total'];
    
    // Prescrições pendentes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tb_prescricoes 
                          WHERE id_profissional = :profissional_id 
                          AND status = 'Pendente'");
    $stmt->execute(['profissional_id' => $profissional_id]);
    $prescricoes_pendentes = $stmt->fetch()['total'];
    
    // Consultas realizadas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tb_agendamento 
                          WHERE id_profissional = :profissional_id 
                          AND status = 'Realizado'");
    $stmt->execute(['profissional_id' => $profissional_id]);
    $consultas_realizadas = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
}

// Processar alteração de senha se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alterar_senha'])) {
    try {
        $senha_atual = $_POST['senha_atual'];
        $nova_senha = $_POST['nova_senha'];
        $confirmar_senha = $_POST['confirmar_senha'];
        
        // Verificar se as senhas coincidem
        if ($nova_senha !== $confirmar_senha) {
            $senha_error = "As novas senhas não coincidem!";
        } else {
            // Verificar senha atual
            $stmt = $pdo->prepare("SELECT Senha FROM tb_usuario WHERE UsuarioID = :id");
            $stmt->execute(['id' => $profissional_id]);
            $usuario = $stmt->fetch();
            
            if (password_verify($senha_atual, $usuario['Senha'])) {
                // Atualizar senha
                $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE tb_usuario SET Senha = :senha WHERE UsuarioID = :id");
                $stmt->execute(['senha' => $nova_senha_hash, 'id' => $profissional_id]);
                
                $senha_success = "Senha alterada com sucesso!";
            } else {
                $senha_error = "Senha atual incorreta!";
            }
        }
    } catch (PDOException $e) {
        $senha_error = "Erro ao alterar senha: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Profissional - Clínica Ótica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #5A9392;
            --secondary-color: #1E3A5F;
            --light-color: #F5F7FA;
            --dark-color: #333333;
            --success-color: #4CAF50;
            --warning-color: #FFC107;
            --danger-color: #F44336;
            --info-color: #17a2b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            background-color: var(--light-color);
        }
        
        /* Menu Lateral */
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            transition: all 0.3s;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: var(--primary-color);
            text-align: center;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu h3 {
            color: var(--primary-color);
            font-size: 14px;
            padding: 10px 20px;
            text-transform: uppercase;
            margin-top: 10px;
        }
        
        .sidebar-menu ul {
            list-style: none;
        }
        
        .sidebar-menu li a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .sidebar-menu li a:hover {
            background-color: var(--primary-color);
            padding-left: 25px;
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
        }
        
        /* Conteúdo Principal */
        .main-content {
            flex: 1;
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .navbar-left h1 {
            color: var(--secondary-color);
            font-size: 24px;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
        }
        
        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 50px;
            right: 0;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 200px;
            display: none;
            z-index: 1000;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-menu a {
            display: block;
            padding: 10px 15px;
            color: var(--dark-color);
            text-decoration: none;
        }
        
        .dropdown-menu a:hover {
            background-color: var(--light-color);
        }
        
        /* Conteúdo */
        .content {
            padding: 30px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }
        
        .bg-primary {
            background-color: var(--primary-color);
        }
        
        .bg-secondary {
            background-color: var(--secondary-color);
        }
        
        .bg-success {
            background-color: var(--success-color);
        }
        
        .bg-warning {
            background-color: var(--warning-color);
        }
        
        .bg-danger {
            background-color: var(--danger-color);
        }
        
        .bg-info {
            background-color: var(--info-color);
        }
        
        .card-body h2 {
            font-size: 24px;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .card-body p {
            color: #777;
            font-size: 14px;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            border-bottom: 3px solid var(--primary-color);
            color: var(--primary-color);
            font-weight: bold;
        }
        
        .tab:hover:not(.active) {
            border-bottom: 3px solid #ccc;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Tabelas */
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        
        .status-agendado {
            background-color: var(--warning-color);
        }
        
        .status-realizado {
            background-color: var(--success-color);
        }
        
        .status-cancelado {
            background-color: var(--danger-color);
        }
        
        .status-pendente {
            background-color: var(--info-color);
        }
        
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn i {
            margin-right: 5px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #4a7d7c;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .btn-success {
            background-color: var(--success-color);
            color: white;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-info {
            background-color: var(--info-color);
            color: white;
        }
        
        /* Formulários */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1002;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: none;
            border-radius: 8px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }
        
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #333;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                width: 250px;
                z-index: 1001;
            }
            
            .navbar-toggle {
                display: block;
            }
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }
        }
    </style>
</head>
<body>
    <!-- Menu Lateral -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Visio Gest</h2>
        </div>
        
        <div class="sidebar-menu">
            <h3>Menu Profissional</h3>
            <ul>
                <li><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="../consulta/consulta.php"><i class="fas fa-calendar-check"></i> Realizar Consulta</a></li>
                <li><a href="../consulta/prescricao.php"><i class="fas fa-file-prescription"></i> Prescrições</a></li>
           
            </ul>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar">
            <div class="navbar-left">
                <h1>Área do Profissional</h1>
            </div>
            <div class="navbar-right">
                <div class="user-profile" id="userProfile">
                    <img src="https://via.placeholder.com/40" alt="User">
                    <span><?php echo $_SESSION['user_name'] ?? 'Profissional'; ?></span>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="#" onclick="openChangePasswordModal()"><i class="fas fa-key"></i> Alterar Senha</a>
                        <a href="#"><i class="fas fa-cog"></i> Configurações</a>
                        <a href="../HomePage/Home.php" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Conteúdo -->
        <div class="content">
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <h3>Consultas Hoje</h3>
                        <div class="card-icon bg-primary">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $consultas_hoje; ?></h2>
                        <p>Consultas agendadas para hoje</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Próximas Consultas</h3>
                        <div class="card-icon bg-success">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo count($proximas_consultas); ?></h2>
                        <p>Consultas agendadas</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Prescrições</h3>
                        <div class="card-icon bg-info">
                            <i class="fas fa-file-prescription"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $total_prescricoes; ?></h2>
                        <p>Total de prescrições</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Prescrições Pendentes</h3>
                        <div class="card-icon bg-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $prescricoes_pendentes; ?></h2>
                        <p>Prescrições para finalizar</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Consultas Realizadas</h3>
                        <div class="card-icon bg-secondary">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $consultas_realizadas; ?></h2>
                        <p>Consultas concluídas</p>
                    </div>
                </div>
            </div>
            
                <div id="prescricoes-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3>Prescrições</h3>
                            <button class="btn btn-primary" onclick="window.location.href='../consulta/prescricao.php'">
                                <i class="fas fa-plus"></i> Nova Prescrição
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Paciente</th>
                                            <th>Data</th>
                                            <th>Tipo</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Consulta prescrições do profissional
                                        try {
                                            $stmt = $pdo->prepare("SELECT p.*, c.nome as paciente_nome 
                                                                 FROM tb_prescricoes p
                                                                 JOIN tb_pacientes c ON p.id_paciente = c.id
                                                                 WHERE p.id_profissional = :profissional_id
                                                                 ORDER BY p.data_prescricao DESC");
                                            $stmt->execute(['profissional_id' => $profissional_id]);
                                            $prescricoes = $stmt->fetchAll();
                                            
                                            if (empty($prescricoes)) {
                                                echo '<tr><td colspan="6" style="text-align: center;">Nenhuma prescrição encontrada</td></tr>';
                                            } else {
                                                foreach ($prescricoes as $prescricao) {
                                                    $status_class = strtolower($prescricao['status']);
                                                    echo "<tr>
                                                            <td>PR-{$prescricao['id']}</td>
                                                            <td>{$prescricao['paciente_nome']}</td>
                                                            <td>" . date('d/m/Y', strtotime($prescricao['data_prescricao'])) . "</td>
                                                            <td>{$prescricao['tipo']}</td>
                                                            <td><span class='status-badge status-{$status_class}'>{$prescricao['status']}</span></td>
                                                            <td>
                                                                <button class='btn btn-primary btn-sm' onclick=\"window.location.href='../consulta/prescricao.php?id={$prescricao['id']}'\">
                                                                    <i class='fas fa-eye'></i> Visualizar
                                                                </button>
                                                            </td>
                                                        </tr>";
                                                }
                                            }
                                        } catch (PDOException $e) {
                                            echo '<tr><td colspan="6" style="text-align: center;">Erro ao carregar prescrições</td></tr>';
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="proximas-tab" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3>Próximas Consultas</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($proximas_consultas)): ?>
                                <div class="table-container">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Data/Hora</th>
                                                <th>Paciente</th>
                                                <th>Tipo</th>
                                                <th>Status</th>
                                                <th>Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($proximas_consultas as $consulta): ?>
                                                <tr>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($consulta['data_consulta'] . ' ' . $consulta['hora_consulta'])); ?></td>
                                                    <td><?php echo htmlspecialchars($consulta['nome_completo']); ?></td>
                                                    <td><?php echo htmlspecialchars($consulta['tipo_consulta'] ?? 'Rotina'); ?></td>
                                                    <td>
                                                        <span class="status-badge <?php 
                                                            echo $consulta['status'] == 'Realizado' ? 'status-realizado' : 
                                                                 ($consulta['status'] == 'Cancelado' ? 'status-cancelado' : 'status-agendado');
                                                        ?>">
                                                            <?php echo htmlspecialchars($consulta['status'] ?? 'Agendado'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-primary btn-sm" onclick="openConsultModal(<?php echo $consulta['id']; ?>)">
                                                            <i class="fas fa-eye"></i> Detalhes
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>Não há consultas agendadas no momento.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Alteração de Senha -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Alterar Senha</h2>
                <span class="close" onclick="closeModal('changePasswordModal')">&times;</span>
            </div>
            <div class="modal-body">
                <?php if (isset($senha_success)): ?>
                    <div class="alert alert-success"><?php echo $senha_success; ?></div>
                <?php endif; ?>
                <?php if (isset($senha_error)): ?>
                    <div class="alert alert-danger"><?php echo $senha_error; ?></div>
                <?php endif; ?>
                
                <form id="changePasswordForm" method="POST" action="">
                    <input type="hidden" name="alterar_senha" value="1">
                    
                    <div class="form-group">
                        <label for="senha_atual">Senha Atual</label>
                        <input type="password" id="senha_atual" name="senha_atual" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nova_senha">Nova Senha</label>
                        <input type="password" id="nova_senha" name="nova_senha" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Nova Senha</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Alterar Senha
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Consulta -->
    <div id="consultModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes da Consulta</h2>
                <span class="close" onclick="closeModal('consultModal')">&times;</span>
            </div>
            <div class="modal-body" id="consultModalBody">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Inicializa o flatpickr para campos de data/hora
        flatpickr("#data_consulta", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            time_24hr: true,
            locale: "pt",
            minDate: "today"
        });
        
        // Toggle do menu lateral em dispositivos móveis
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle do dropdown do perfil
            const userProfile = document.getElementById('userProfile');
            const dropdownMenu = document.getElementById('dropdownMenu');
            
            userProfile.addEventListener('click', function() {
                dropdownMenu.classList.toggle('show');
            });
            
            // Fechar o dropdown quando clicar fora
            window.addEventListener('click', function(event) {
                if (!event.target.matches('#userProfile') && !event.target.closest('#userProfile')) {
                    if (dropdownMenu.classList.contains('show')) {
                        dropdownMenu.classList.remove('show');
                    }
                }
            });
            
            // Navegação por abas
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Remove a classe active de todas as abas e conteúdos
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                    
                    // Adiciona a classe active à aba clicada
                    this.classList.add('active');
                    
                    // Mostra o conteúdo correspondente
                    const tabId = this.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Formulário de consulta
            document.getElementById('consultaForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Obter dados do formulário
                const paciente = document.getElementById('paciente').value;
                const data_consulta = document.getElementById('data_consulta').value;
                const tipo_consulta = document.getElementById('tipo_consulta').value;
                const observacoes = document.getElementById('observacoes').value;
                
                // Simulação de envio para o servidor
                Swal.fire({
                    title: 'Consulta agendada!',
                    text: 'A consulta foi agendada com sucesso.',
                    icon: 'success',
                    confirmButtonColor: '#5A9392'
                }).then(() => {
                    // Limpar formulário
                    this.reset();
                });
            });
        });
        
        // Função para confirmar logout
        function confirmLogout() {
            Swal.fire({
                title: 'Deseja sair do sistema?',
                text: "Você será desconectado da sua conta.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#5A9392',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, sair',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../logout.php';
                }
            });
        }
        
        // Função para abrir modal de alteração de senha
        function openChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'block';
        }
        
        // Função para abrir modal de consulta
        function openConsultModal(consultaId) {
            // Simulação de carregamento de dados da consulta
            // Na implementação real, você faria uma requisição AJAX para buscar os detalhes
            fetch(`../api/consulta.php?id=${consultaId}`)
                .then(response => response.json())
                .then(data => {
                    const modalBody = document.getElementById('consultModalBody');
                    modalBody.innerHTML = `
                        <div class="form-group">
                            <label>Paciente:</label>
                            <p>${data.paciente || 'N/A'}</p>
                        </div>
                        <div class="form-group">
                            <label>Data/Hora:</label>
                            <p>${data.data_consulta || 'N/A'} ${data.hora_consulta || ''}</p>
                        </div>
                        <div class="form-group">
                            <label>Tipo:</label>
                            <p>${data.tipo_consulta || 'Rotina'}</p>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <p><span class="status-badge status-${data.status ? data.status.toLowerCase() : 'agendado'}">
                                ${data.status || 'Agendado'}
                            </span></p>
                        </div>
                        <div class="form-group">
                            <label>Observações:</label>
                            <p>${data.observacoes || 'Nenhuma observação registrada.'}</p>
                        </div>
                        <div class="form-group">
                            <button class="btn btn-primary" onclick="window.location.href='../consulta/prescricao.php?consulta_id=${consultaId}'">
                                <i class="fas fa-file-prescription"></i> Criar Prescrição
                            </button>
                            ${data.status !== 'Realizado' ? `
                            <button class="btn btn-success" onclick="marcarComoRealizada(${consultaId})">
                                <i class="fas fa-check"></i> Marcar como Realizada
                            </button>
                            ` : ''}
                            ${data.status !== 'Cancelado' ? `
                            <button class="btn btn-danger" onclick="cancelarConsulta(${consultaId})">
                                <i class="fas fa-times"></i> Cancelar Consulta
                            </button>
                            ` : ''}
                        </div>
                    `;
                })
                .catch(error => {
                    document.getElementById('consultModalBody').innerHTML = `
                        <div class="alert alert-danger">
                            Erro ao carregar detalhes da consulta. Por favor, tente novamente.
                        </div>
                    `;
                });
            
            document.getElementById('consultModal').style.display = 'block';
        }
        
        // Funções auxiliares para consulta
        function marcarComoRealizada(consultaId) {
            Swal.fire({
                title: 'Confirmar ação',
                text: "Deseja marcar esta consulta como realizada?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#5A9392',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sim, marcar como realizada',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Simulação de atualização no servidor
                    Swal.fire({
                        title: 'Sucesso!',
                        text: 'Consulta marcada como realizada.',
                        icon: 'success'
                    }).then(() => {
                        closeModal('consultModal');
                        window.location.reload();
                    });
                }
            });
        }
        
        function cancelarConsulta(consultaId) {
            Swal.fire({
                title: 'Confirmar cancelamento',
                text: "Deseja realmente cancelar esta consulta?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#5A9392',
                confirmButtonText: 'Sim, cancelar consulta',
                cancelButtonText: 'Não cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Simulação de atualização no servidor
                    Swal.fire({
                        title: 'Cancelada!',
                        text: 'A consulta foi cancelada com sucesso.',
                        icon: 'success'
                    }).then(() => {
                        closeModal('consultModal');
                        window.location.reload();
                    });
                }
            });
        }
        
        // Função para fechar modais
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        // Fechar modais ao clicar fora
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>