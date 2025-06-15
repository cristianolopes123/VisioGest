<?php
session_start();
require_once('../conexao.php');

// Verificar se o usuário está logado
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Obter informações do usuário
$usuario_id = $_SESSION['user_id'];
$query_usuario = $conn->prepare("SELECT NomeCompleto, NomeUsuario FROM tb_usuario WHERE UsuarioID = ?");
$query_usuario->bind_param("i", $usuario_id);
$query_usuario->execute();
$result_usuario = $query_usuario->get_result();
$usuario = $result_usuario->fetch_assoc();

// Obter consultas do dia
$data_hoje = date('Y-m-d');

// Verificar conexão
if (!$conn) {
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

$sql = "
    SELECT 
        a.AgendamentoID,
        a.PacienteID,
        a.ProfissionalID,
        a.UsuarioID,
        a.DataHoraInicio,
        a.DataHoraFim,
        a.TipoConsulta,
        a.StatusAgendamento,
        a.Observacoes,
        a.DataRegistro,
        p.NomeCompleto AS PacienteNome,
        pr.NomeCompleto AS ProfissionalNome
    FROM tb_agendamento a
    JOIN tb_pacientes p ON a.PacienteID = p.PacienteID
    JOIN tb_profissional pr ON a.ProfissionalID = pr.ProfissionalID
    WHERE DATE(a.DataHoraInicio) = ?
    ORDER BY a.DataHoraInicio ASC
";

$query_consultas = $conn->prepare($sql);

if (!$query_consultas) {
    die("Erro na preparação da query: " . $conn->error);
}

if (!$query_consultas->bind_param("s", $data_hoje)) {
    die("Erro no bind_param: " . $query_consultas->error);
}

if (!$query_consultas->execute()) {
    die("Erro na execução: " . $query_consultas->error);
}

$consultas = $query_consultas->get_result();

if (!$consultas) {
    die("Erro ao obter resultados: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Recepcionista - VisioGest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --verde-principal: #5A9392;
            --azul-escuro: #00003B;
            --branco: #FFFFFF;
            --cinza-claro: #F5F7FA;
            --cinza-escuro: #333333;
            --verde-claro: rgba(90, 147, 146, 0.1);
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
            background-color: var(--cinza-claro);
        }
        
        /* Menu Lateral */
        .sidebar {
            width: 250px;
            background-color: var(--azul-escuro);
            color: white;
            height: 100vh;
            position: fixed;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: var(--verde-principal);
            text-align: center;
        }
        
        .sidebar-header h2 {
            color: white;
            font-weight: 600;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu h3 {
            color: var(--verde-principal);
            font-size: 14px;
            padding: 10px 20px;
            text-transform: uppercase;
            margin-top: 10px;
            font-weight: 600;
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
            font-size: 15px;
        }
        
        .sidebar-menu li a:hover {
            background-color: var(--verde-principal);
            padding-left: 25px;
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
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
            color: var(--azul-escuro);
            font-size: 24px;
            font-weight: 600;
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
            padding: 8px 12px;
            border-radius: 30px;
            transition: all 0.3s;
        }
        
        .user-profile:hover {
            background-color: var(--verde-claro);
        }
        
        .user-profile img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid var(--verde-principal);
        }
        
        .user-profile span {
            font-weight: 500;
            color: var(--azul-escuro);
        }
        
        .dropdown-menu {
            position: absolute;
            top: 60px;
            right: 0;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 220px;
            display: none;
            z-index: 1000;
            overflow: hidden;
        }
        
        .dropdown-menu.show {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--cinza-escuro);
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .dropdown-menu a i {
            margin-right: 10px;
            color: var(--verde-principal);
            width: 20px;
        }
        
        .dropdown-menu a:hover {
            background-color: var(--verde-claro);
            color: var(--azul-escuro);
        }
        
        .dropdown-divider {
            height: 1px;
            background-color: #eee;
            margin: 5px 0;
        }
        
        /* Conteúdo */
        .content {
            padding: 30px;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, var(--verde-principal), var(--azul-escuro));
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .welcome-section h2 {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .welcome-section p {
            opacity: 0.9;
        }
        
        .section-title {
            color: var(--azul-escuro);
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            color: var(--verde-principal);
        }
        
        .consultas-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .consulta-card {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border-left: 4px solid var(--verde-principal);
        }
        
        .consulta-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .consulta-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .consulta-id {
            background-color: var(--verde-claro);
            color: var(--verde-principal);
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .consulta-horario {
            font-weight: 600;
            color: var(--azul-escuro);
        }
        
        .consulta-body {
            margin-bottom: 15px;
        }
        
        .consulta-info {
            display: flex;
            margin-bottom: 8px;
        }
        
        .consulta-info i {
            color: var(--verde-principal);
            margin-right: 10px;
            width: 20px;
        }
        
        .consulta-info span {
            color: var(--cinza-escuro);
        }
        
        .consulta-footer {
            display: flex;
            justify-content: flex-end;
        }
        
        .btn-acoes {
            background-color: var(--verde-principal);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 15px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .btn-acoes i {
            margin-right: 5px;
        }
        
        .btn-acoes:hover {
            background-color: var(--azul-escuro);
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .modal.show {
            display: flex;
            animation: fadeIn 0.3s;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        .modal-header {
            padding: 15px 20px;
            background-color: var(--verde-principal);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            font-weight: 600;
        }
        
        .modal-header .close {
            font-size: 24px;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--azul-escuro);
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 15px;
        }
        
        .form-group input:focus {
            border-color: var(--verde-principal);
            outline: none;
        }
        
        .modal-footer {
            padding: 15px 20px;
            background-color: #f9f9f9;
            display: flex;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--verde-principal);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--azul-escuro);
        }
        
        .btn-secondary {
            background-color: #ddd;
            color: var(--cinza-escuro);
            margin-right: 10px;
        }
        
        .btn-secondary:hover {
            background-color: #ccc;
        }
        
        /* Confirmação de saída */
        .confirm-modal {
            text-align: center;
        }
        
        .confirm-modal .modal-body {
            padding: 30px;
        }
        
        .confirm-icon {
            font-size: 50px;
            color: var(--verde-principal);
            margin-bottom: 20px;
        }
        
        .confirm-text {
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
                z-index: 1001;
            }
            
            .sidebar.active {
                width: 250px;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .navbar-toggle {
                display: block;
            }
            
            .consultas-container {
                grid-template-columns: 1fr;
            }
        }

        /* Mensagem quando não há consultas */
        .no-consultas {
            grid-column: 1 / -1;
            text-align: center;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <!-- Menu Lateral -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>VisioGest</h2>
        </div>
        
        <div class="sidebar-menu">
            <h3>Menu Principal</h3>
            <ul>
                <li><a href="#"><i class="fas fa-home"></i> Dashboard</a></li>
            </ul>
            
            <h3>Cadastros</h3>
            <ul>
                <li><a href="../cadastro/pacientes.php"><i class="fas fa-user-plus"></i> Cadastrar Paciente</a></li>
            </ul>

            <h3>Consultas</h3>
            <ul>
                <li><a href="../consulta/agendamento.php"><i class="fas fa-calendar-plus"></i> Agendar Consulta</a></li>
            </ul>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar">
            <div class="navbar-left">
                <h1>Dashboard Recepcionista</h1>
            </div>
            <div class="navbar-right">
                <div class="user-profile" id="userProfile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($usuario['NomeCompleto']) ?>&background=5A9392&color=fff" alt="User">
                    <span><?= htmlspecialchars($usuario['NomeCompleto']) ?></span>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="#"><i class="fas fa-user"></i> Meu Perfil</a>
                        <a href="#" id="alterarSenhaBtn"><i class="fas fa-key"></i> Alterar Senha</a>
                        <div class="dropdown-divider"></div>
                        <a href="../HomePage/home.php" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Conteúdo -->
        <div class="content">
            <div class="welcome-section">
                <h2>Bem-vindo, <?= htmlspecialchars(explode(' ', $usuario['NomeCompleto'])[0]) ?>!</h2>
                <p>Aqui estão as consultas agendadas para hoje.</p>
            </div>
            
            <div class="section-title">
                <i class="fas fa-calendar-day"></i>
                <h3>Próximas Consultas - <?= date('d/m/Y') ?></h3>
            </div>
            
            <div class="consultas-container">
                <?php if ($consultas->num_rows > 0): ?>
                    <?php while ($consulta = $consultas->fetch_assoc()): ?>
                        <div class="consulta-card">
                            <div class="consulta-header">
                                <span class="consulta-id">#<?= $consulta['AgendamentoID'] ?></span>
                                <span class="consulta-horario">
                                    <?= date('H:i', strtotime($consulta['DataHoraInicio'])) ?> - <?= date('H:i', strtotime($consulta['DataHoraFim'])) ?>
                                </span>
                            </div>
                            <div class="consulta-body">
                                <div class="consulta-info">
                                    <i class="fas fa-user"></i>
                                    <span><?= htmlspecialchars($consulta['PacienteNome']) ?></span>
                                </div>
                                <div class="consulta-info">
                                    <i class="fas fa-user-md"></i>
                                    <span>Dr(a). <?= htmlspecialchars($consulta['ProfissionalNome']) ?></span>
                                </div>
                                <div class="consulta-info">
                                    <i class="fas fa-stethoscope"></i>
                                    <span><?= htmlspecialchars($consulta['TipoConsulta']) ?></span>
                                </div>
                                <div class="consulta-info">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Status: <?= htmlspecialchars($consulta['StatusAgendamento']) ?></span>
                                </div>
                            </div>
                            <div class="consulta-footer">
                                <button class="btn-acoes">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-consultas">
                        <p>Não há consultas agendadas para hoje.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Alterar Senha -->
    <div class="modal" id="alterarSenhaModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Alterar Senha</h3>
                <span class="close">&times;</span>
            </div>
            <form id="formAlterarSenha" method="POST" action="alterar_senha.php">
                <div class="modal-body">
                    <input type="hidden" name="UsuarioID" value="<?= $usuario_id ?>">
                    <div class="form-group">
                        <label for="senhaAtual">Senha Atual</label>
                        <input type="password" id="senhaAtual" name="senhaAtual" required>
                    </div>
                    <div class="form-group">
                        <label for="novaSenha">Nova Senha</label>
                        <input type="password" id="novaSenha" name="novaSenha" required>
                    </div>
                    <div class="form-group">
                        <label for="confirmarSenha">Confirmar Nova Senha</label>
                        <input type="password" id="confirmarSenha" name="confirmarSenha" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Confirmar Saída -->
    <div class="modal confirm-modal" id="confirmarSaidaModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirmar Saída</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div class="confirm-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <p class="confirm-text">Tem certeza que deseja sair do sistema?</p>
                <div>
                    <button class="btn btn-secondary close">Cancelar</button>
                    <button class="btn btn-primary" id="confirmLogout">Sair</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Dropdown do perfil
            const userProfile = document.getElementById('userProfile');
            const dropdownMenu = document.getElementById('dropdownMenu');
            
            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });
            
            // Fechar dropdown quando clicar fora
            document.addEventListener('click', function() {
                if (dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                }
            });
            
            // Modal Alterar Senha
            const alterarSenhaBtn = document.getElementById('alterarSenhaBtn');
            const alterarSenhaModal = document.getElementById('alterarSenhaModal');
            const closeButtons = document.querySelectorAll('.close');
            
            alterarSenhaBtn.addEventListener('click', function(e) {
                e.preventDefault();
                dropdownMenu.classList.remove('show');
                alterarSenhaModal.classList.add('show');
            });
            
            // Modal Confirmar Saída
            const logoutBtn = document.getElementById('logoutBtn');
            const confirmarSaidaModal = document.getElementById('confirmarSaidaModal');
            const confirmLogout = document.getElementById('confirmLogout');
            
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                dropdownMenu.classList.remove('show');
                confirmarSaidaModal.classList.add('show');
            });
            
            confirmLogout.addEventListener('click', function() {
                window.location.href = '../logout.php';
            });
            
            // Fechar modais
            closeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.querySelectorAll('.modal').forEach(modal => {
                        modal.classList.remove('show');
                    });
                });
            });
            
            // Fechar modais ao clicar fora
            window.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    e.target.classList.remove('show');
                }
            });
            
            // Validação do formulário de alteração de senha
            const formAlterarSenha = document.getElementById('formAlterarSenha');
            if (formAlterarSenha) {
                formAlterarSenha.addEventListener('submit', function(e) {
                    const novaSenha = document.getElementById('novaSenha').value;
                    const confirmarSenha = document.getElementById('confirmarSenha').value;
                    
                    if (novaSenha !== confirmarSenha) {
                        e.preventDefault();
                        alert('As senhas não coincidem!');
                    }
                });
            }
        });
    </script>
</body>
</html>