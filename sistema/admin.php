<?php
session_start();
require_once '../conexao.php';

// Verificação de login e nível de acesso
//if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] !== true) {
   // header("Location: ../login.php");
   // exit;
//}

// Define um valor padrão se o nome não estiver definido
$_SESSION['nome_usuario'] = $_SESSION['nome_usuario'] ?? 'Usuário';

// Verifica se o usuário tem permissão de administrador
if ($_SESSION['nivel_acesso'] != 'Administrador') {
    // Redireciona para a página adequada conforme o nível de acesso
    switch ($_SESSION['nivel_acesso']) {
        case 'Profissional':
            header('Location: profissional.php');
            break;
        case 'VendedorOtico':
            header('Location: vendedor.php');
            break;
        case 'Recepcionista':
            header('Location: recepcionista.php');
            break;
        default:
            header('Location: login.php');
    }
    exit();
}

// Consultas para os dados do dashboard
$result = $conn->query("SELECT COUNT(*) FROM tb_pacientes");
$total_pacientes = $result->fetch_row()[0];

$result = $conn->query("SELECT COUNT(*) FROM tb_funcionario");
$total_funcionarios = $result->fetch_row()[0];

$result = $conn->query("SELECT COUNT(*) FROM tb_usuario");
$total_usuarios = $result->fetch_row()[0];

$result = $conn->query("SELECT COUNT(*) FROM tb_produto");
$total_produtos = $result->fetch_row()[0];

$result = $conn->query("SELECT COUNT(*) FROM tb_profissional");
$total_profissionais = $result->fetch_row()[0];

// Consulta para faturamento mensal
$result = $conn->query("SELECT COALESCE(SUM(ValorTotal), 0) AS total_mensal FROM tb_venda WHERE MONTH(DataVenda) = MONTH(CURRENT_DATE()) AND YEAR(DataVenda) = YEAR(CURRENT_DATE())");

if ($result === false) {
    die("Erro na consulta: " . $conn->error);
}

$row = $result->fetch_assoc();
$faturamento_mensal = $row['total_mensal'];

// Consulta para faturamento total
$result = $conn->query("SELECT SUM(ValorTotal) FROM tb_venda");
$faturamento_total = $result->fetch_row()[0] ?? 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador - Gestão Clínica Ótica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #5A9392;
            --secondary-color: #00003B;
            --light-color: #F5F7FA;
            --dark-color: #333333;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
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
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
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
            background-color: rgba(90, 147, 146, 0.2);
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
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar-left h1 {
            color: var(--secondary-color);
            font-size: 24px;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
            margin-right: 10px;
            text-align: right;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark-color);
        }
        
        .user-role {
            font-size: 12px;
            color: var(--primary-color);
            text-transform: capitalize;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid var(--primary-color);
        }
        
        .dropdown-arrow {
            margin-left: 8px;
            font-size: 12px;
            color: var(--dark-color);
            transition: transform 0.3s;
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
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-menu a {
            display: block;
            padding: 10px 15px;
            color: var(--dark-color);
            text-decoration: none;
            transition: background-color 0.2s;
        }
        
        .dropdown-menu a:hover {
            background-color: var(--light-color);
        }
        
        .dropdown-menu a i {
            margin-right: 8px;
            width: 18px;
            text-align: center;
        }
        
        /* Conteúdo */
        .content {
            padding: 30px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card.pacientes {
            border-left-color: var(--primary-color);
        }
        
        .card.funcionarios {
            border-left-color: var(--info-color);
        }
        
        .card.usuarios {
            border-left-color: var(--warning-color);
        }
        
        .card.produtos {
            border-left-color: var(--success-color);
        }
        
        .card.profissionais {
            border-left-color: var(--danger-color);
        }
        
        .card.faturamento {
            border-left-color: var(--secondary-color);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
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
        
        .bg-info {
            background-color: var(--info-color);
        }
        
        .bg-warning {
            background-color: var(--warning-color);
        }
        
        .bg-danger {
            background-color: var(--danger-color);
        }
        
        .card-body h2 {
            font-size: 28px;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .card-body p {
            color: #777;
            font-size: 14px;
        }
        
        .card-footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
        }
        
        /* Tabelas */
        .table-container {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .table-container h2 {
            margin-bottom: 20px;
            color: var(--secondary-color);
            display: flex;
            align-items: center;
        }
        
        .table-container h2 i {
            margin-right: 10px;
            color: var(--primary-color);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 15px;
            text-align: left;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        table tr:hover {
            background-color: rgba(90, 147, 146, 0.1);
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
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            color: var(--secondary-color);
        }
        
        .modal-header .close {
            font-size: 24px;
            cursor: pointer;
            color: #999;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #4a7d7c;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
                z-index: 1001;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.active {
                width: 250px;
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }
        
        /* Animações */
        .animate-bounce {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <!-- Menu Lateral -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>VisioGest</h2>
            <p>Painel do Administrador</p>
        </div>
        
        <div class="sidebar-menu">
            <h3>Menu Principal</h3>
            <ul>
                <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                
                <h3>Cadastros</h3>
                <li><a href="../cadastro/pacientes.php"><i class="fas fa-user-injured"></i> Pacientes</a></li>
                <li><a href="../cadastro/funcionarios.php"><i class="fas fa-user-tie"></i> Funcionários</a></li>
                <li><a href="../cadastro/usuarios.php"><i class="fas fa-users-cog"></i> Usuários</a></li>
                <li><a href="../cadastro/profissional.php"><i class="fas fa-user-md"></i> Profissionais</a></li>
                <li><a href="../cadastro/fornecedor.php"><i class="fas fa-truck"></i> Fornecedores</a></li>

                <h3>Produtos</h3>
                <li><a href="../cadastro/produtos.php"><i class="fas fa-box-open"></i> Produtos</a></li>
                <li><a href="../cadastro/categoria.php"><i class="fas fa-tags"></i> Categorias</a></li>

                
                <h3>Financeiro</h3>
                <li><a href="../financeiro/estoque.php"><i class="fas fa-warehouse"></i> Estoque</a></li>
                <li><a href="../financeiro/entradas.php"><i class="fas fa-sign-in-alt"></i> Entradas</a></li>
                <li><a href="../financeiro/saidas.php"><i class="fas fa-sign-out-alt"></i> Saídas</a></li>
                <li><a href="../financeiro/contas_pagar.php"><i class="fas fa-money-bill-wave"></i> Contas a Pagar</a></li>
                <li><a href="../financeiro/contas_receber.php"><i class="fas fa-hand-holding-usd"></i> Contas a Receber</a></li>
                <li><a href="../financeiro/vendas.php"><i class="fas fa-chart-line"></i> Relatório de Vendas</a></li>
                <li><a href="../financeiro/compras.php"><i class="fas fa-shopping-cart"></i> Compras</a></li>

                <h3>Relatórios</h3>
                <li><a href="../relatorios/vendas.php"><i class="fas fa-chart-bar"></i> Vendas</a></li>
                <li><a href="../relatorios/funcionarios.php"><i class="fas fa-users"></i> Funcionários</a></li>
                <li><a href="../relatorios/produtos.php"><i class="fas fa-boxes"></i> Produtos</a></li>
                <li><a href="../relatorios/desempenho.php"><i class="fas fa-chart-pie"></i> Desempenho</a></li>
                <li><a href="../relatorios/fluxo.php"><i class="fas fa-exchange-alt"></i> Fluxo de Caixa</a></li>
               
                <h3>Administração</h3>
                <li><a href="../admin/backup.php"><i class="fas fa-database"></i> Backup</a></li>
                <li><a href="../admin/auditoria.php"><i class="fas fa-clipboard-list"></i> Auditoria</a></li>
                <li><a href="../admin/configuracoes.php"><i class="fas fa-cog"></i> Configurações</a></li>
            </ul>
        </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <!-- Navbar -->
        <div class="navbar">
            <div class="navbar-left">
                <h1>Dashboard</h1>
            </div>
            <div class="navbar-right">
                <div class="user-profile" id="userProfile">
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($_SESSION['nome_usuario'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="user-role"><?php echo $_SESSION['nivel_acesso']; ?></span>
                    </div>
                    <img src="../uploads/<?php echo $_SESSION['foto_perfil'] ?? 'default.png'; ?>" alt="User" class="user-avatar">
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="#" id="profileLink"><i class="fas fa-user-circle"></i> Perfil</a>
                        <a href="#" id="changePasswordLink"><i class="fas fa-key"></i> Alterar Senha</a>
                        <a href="#" id="settingsLink"><i class="fas fa-cog"></i> Configurações</a>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo -->
        <div class="content">
            <div class="dashboard-cards">
                <div class="card pacientes">
                    <div class="card-header">
                        <h3>Pacientes</h3>
                        <div class="card-icon bg-primary">
                            <i class="fas fa-user-injured"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $total_pacientes; ?></h2>
                        <p>Pacientes cadastrados</p>
                    </div>
                    <div class="card-footer">
                        <a href="../cadastro/pacientes.php">Ver todos</a>
                    </div>
                </div>
                
                <div class="card funcionarios">
                    <div class="card-header">
                        <h3>Funcionários</h3>
                        <div class="card-icon bg-info">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $total_funcionarios; ?></h2>
                        <p>Funcionários cadastrados</p>
                    </div>
                    <div class="card-footer">
                        <a href="../cadastro/funcionarios.php">Ver todos</a>
                    </div>
                </div>
                
                <div class="card usuarios">
                    <div class="card-header">
                        <h3>Usuários</h3>
                        <div class="card-icon bg-warning">
                            <i class="fas fa-users-cog"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $total_usuarios; ?></h2>
                        <p>Usuários do sistema</p>
                    </div>
                    <div class="card-footer">
                        <a href="../cadastro/usuarios.php">Ver todos</a>
                    </div>
                </div>
                
                <div class="card produtos">
                    <div class="card-header">
                        <h3>Produtos</h3>
                        <div class="card-icon bg-success">
                            <i class="fas fa-box-open"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $total_produtos; ?></h2>
                        <p>Produtos cadastrados</p>
                    </div>
                    <div class="card-footer">
                        <a href="../cadastro/produtos.php">Ver todos</a>
                    </div>
                </div>
                
                <div class="card profissionais">
                    <div class="card-header">
                        <h3>Profissionais</h3>
                        <div class="card-icon bg-danger">
                            <i class="fas fa-user-md"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2><?php echo $total_profissionais; ?></h2>
                        <p>Profissionais de saúde</p>
                    </div>
                    <div class="card-footer">
                        <a href="../cadastro/profissional.php">Ver todos</a>
                    </div>
                </div>
                
                <div class="card faturamento">
                    <div class="card-header">
                        <h3>Faturamento</h3>
                        <div class="card-icon bg-secondary">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2>Kz <?php echo number_format($faturamento_mensal, 2, ',', '.'); ?></h2>
                        <p>Faturamento este mês</p>
                    </div>
                    <div class="card-footer">
                        <span>Total: Kz <?php echo number_format($faturamento_total, 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Últimas Vendas -->
            <div class="table-container animate__animated animate__fadeInUp">
                <h2><i class="fas fa-shopping-cart"></i> Últimas Vendas</h2>
                <div class="table-responsive">
                    <table id="vendasTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Paciente</th>
                                <th>Vendedor</th>
                                <th>Valor Total</th>
                                <th>Desconto</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $query = $conn->query("SELECT v.VendaID, v.DataVenda, p.NomeCompleto AS Paciente, f.NomeCompleto AS Vendedor, v.ValorTotal, v.DescontoTotal 
                                                     FROM tb_venda v
                                                     JOIN tb_pacientes p ON v.PacienteID = p.PacienteID
                                                     JOIN tb_funcionario f ON v.VendedorID = f.id_funcionario
                                                     ORDER BY v.DataVenda DESC LIMIT 10");
                                
                                if ($query === false) {
                                    throw new Exception("Erro na consulta SQL");
                                }
                                
                                if ($query->num_rows > 0) {
                                    while ($row = $query->fetch_assoc()) {
                                        echo "<tr>
                                            <td>{$row['VendaID']}</td>
                                            <td>" . date('d/m/Y', strtotime($row['DataVenda'])) . "</td>
                                            <td>{$row['Paciente']}</td>
                                            <td>{$row['Vendedor']}</td>
                                            <td>Kz " . number_format($row['ValorTotal'], 2, ',', '.') . "</td>
                                            <td>Kz " . number_format($row['DescontoTotal'], 2, ',', '.') . "</td>
                                            <td>
                                                <a href='../vendas/detalhes.php?id={$row['VendaID']}' class='badge badge-primary'>Detalhes</a>
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='7'>Nenhuma venda encontrada</td></tr>";
                                }
                            } catch (Exception $e) {
                                echo "<tr><td colspan='7'>Erro: " . $e->getMessage() . "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Últimos Pacientes Cadastrados -->
            <div class="table-container animate__animated animate__fadeInUp">
                <h2><i class="fas fa-user-injured"></i> Últimos Pacientes Cadastrados</h2>
                <div class="table-responsive">
                    <table id="pacientesTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Telefone</th>
                                <th>Email</th>
                                <th>Data Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = $conn->query("SELECT PacienteID, NomeCompleto, Telefone, Email, DataCadastro FROM tb_pacientes ORDER BY DataCadastro DESC LIMIT 10");

                            if ($query === false) {
                                die("Erro na consulta SQL: " . $conn->error);
                            }

                            if ($query->num_rows > 0) {
                                while ($row = $query->fetch_assoc()) {
                                    echo "<tr>
                                        <td>{$row['PacienteID']}</td>
                                        <td>{$row['NomeCompleto']}</td>
                                        <td>{$row['Telefone']}</td>
                                        <td>{$row['Email']}</td>
                                        <td>" . date('d/m/Y', strtotime($row['DataCadastro'])) . "</td>
                                        <td>
                                            <a href='../cadastro/pacientes.php?edit={$row['PacienteID']}' class='badge badge-primary'>Editar</a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>Nenhum paciente cadastrado</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Perfil -->
    <div class="modal" id="profileModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Meu Perfil</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="profileForm">
                    <div class="form-group">
                        <label for="profileName">Nome</label>
                        <input type="text" id="profileName" value="<?php echo $_SESSION['nome_usuario']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="profileEmail">Email</label>
                        <input type="email" id="profileEmail" value="<?php echo $_SESSION['email_usuario']; ?>">
                    </div>
                    <div class="form-group">
                        <label for="profilePhone">Telefone</label>
                        <input type="text" id="profilePhone" value="<?php echo $_SESSION['telefone_usuario'] ?? ''; ?>">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="closeProfile">Cancelar</button>
                <button class="btn btn-primary" id="saveProfile">Salvar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Alteração de Senha -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Alterar Senha</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="form-group">
                        <label for="currentPassword">Senha Atual</label>
                        <input type="password" id="currentPassword" required>
                    </div>
                    <div class="form-group">
                        <label for="newPassword">Nova Senha</label>
                        <input type="password" id="newPassword" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirmNewPassword">Confirmar Nova Senha</label>
                        <input type="password" id="confirmNewPassword" required minlength="6">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="closeChangePassword">Cancelar</button>
                <button class="btn btn-primary" id="saveNewPassword">Alterar Senha</button>
            </div>
        </div>
    </div>

    <!-- Modal de Configurações -->
    <div class="modal" id="settingsModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Configurações</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="settingsForm">
                    <div class="form-group">
                        <label for="language">Idioma</label>
                        <select id="language">
                            <option value="pt">Português</option>
                            <option value="en">Inglês</option>
                            <option value="es">Espanhol</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="theme">Tema</label>
                        <select id="theme">
                            <option value="light">Claro</option>
                            <option value="dark">Escuro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="notifications">Notificações</label>
                        <select id="notifications">
                            <option value="enabled">Ativadas</option>
                            <option value="disabled">Desativadas</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="closeSettings">Cancelar</button>
                <button class="btn btn-primary" id="saveSettings">Salvar Configurações</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar DataTables
            $('#vendasTable, #pacientesTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-PT.json'
                },
                responsive: true
            });
            
            // Toggle do dropdown do perfil
            $('#userProfile').click(function(e) {
                e.stopPropagation();
                $('#dropdownMenu').toggleClass('show');
            });
            
            // Fechar o dropdown quando clicar fora
            $(document).click(function() {
                if ($('#dropdownMenu').hasClass('show')) {
                    $('#dropdownMenu').removeClass('show');
                }
            });
            
            // Abrir modal de perfil
            $('#profileLink').click(function(e) {
                e.preventDefault();
                $('#profileModal').css('display', 'flex');
            });
            
            // Abrir modal de alteração de senha
            $('#changePasswordLink').click(function(e) {
                e.preventDefault();
                $('#changePasswordModal').css('display', 'flex');
            });
            
            // Abrir modal de configurações
            $('#settingsLink').click(function(e) {
                e.preventDefault();
                $('#settingsModal').css('display', 'flex');
            });
            
            // Fechar modais
            $('.close').click(function() {
                $(this).closest('.modal').css('display', 'none');
            });
            
            $('#closeProfile, #closeChangePassword, #closeSettings').click(function() {
                $(this).closest('.modal').css('display', 'none');
            });
            
            // Fechar modal quando clicar fora
            $(window).click(function(e) {
                if ($(e.target).hasClass('modal')) {
                    $(e.target).css('display', 'none');
                }
            });
            
            // Salvar perfil
            $('#saveProfile').click(function() {
                const nome = $('#profileName').val();
                const email = $('#profileEmail').val();
                const telefone = $('#profilePhone').val();
                
                // Validação básica
                if (!nome || !email) {
                    alert('Nome e Email são obrigatórios!');
                    return;
                }
                
                // Simulação de atualização
                alert('Perfil atualizado com sucesso!');
                $('#profileModal').css('display', 'none');
                
                // Aqui você pode adicionar uma chamada AJAX para atualizar no servidor
                /*
                $.ajax({
                    url: 'atualizar_perfil.php',
                    method: 'POST',
                    data: {
                        nome: nome,
                        email: email,
                        telefone: telefone
                    },
                    success: function(response) {
                        alert('Perfil atualizado com sucesso!');
                        $('#profileModal').css('display', 'none');
                    },
                    error: function() {
                        alert('Erro ao atualizar perfil!');
                    }
                });
                */
            });
            
            // Alterar senha
            $('#saveNewPassword').click(function() {
                const currentPass = $('#currentPassword').val();
                const newPass = $('#newPassword').val();
                const confirmPass = $('#confirmNewPassword').val();
                
                if (!currentPass || !newPass || !confirmPass) {
                    alert('Todos os campos são obrigatórios!');
                    return;
                }
                
                if (newPass !== confirmPass) {
                    alert('As novas senhas não coincidem!');
                    return;
                }
                
                if (newPass.length < 6) {
                    alert('A nova senha deve ter pelo menos 6 caracteres!');
                    return;
                }
                
                // Simulação de alteração de senha
                alert('Senha alterada com sucesso!');
                $('#changePasswordModal').css('display', 'none');
                $('#changePasswordForm')[0].reset();
                
                // Aqui você pode adicionar uma chamada AJAX para atualizar no servidor
                /*
                $.ajax({
                    url: 'alterar_senha.php',
                    method: 'POST',
                    data: {
                        currentPass: currentPass,
                        newPass: newPass
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Senha alterada com sucesso!');
                            $('#changePasswordModal').css('display', 'none');
                            $('#changePasswordForm')[0].reset();
                        } else {
                            alert(response.message || 'Erro ao alterar senha!');
                        }
                    },
                    error: function() {
                        alert('Erro ao alterar senha!');
                    }
                });
                */
            });
            
            // Salvar configurações
            $('#saveSettings').click(function() {
                const language = $('#language').val();
                const theme = $('#theme').val();
                const notifications = $('#notifications').val();
                
                // Simulação de salvamento
                alert('Configurações salvas com sucesso!');
                $('#settingsModal').css('display', 'none');
                
                // Aqui você pode aplicar as configurações ou enviar para o servidor
                if (theme === 'dark') {
                    $('body').addClass('dark-theme');
                } else {
                    $('body').removeClass('dark-theme');
                }
                
                // Aqui você pode adicionar uma chamada AJAX para salvar no servidor
                /*
                $.ajax({
                    url: 'salvar_configuracoes.php',
                    method: 'POST',
                    data: {
                        language: language,
                        theme: theme,
                        notifications: notifications
                    },
                    success: function(response) {
                        alert('Configurações salvas com sucesso!');
                        $('#settingsModal').css('display', 'none');
                    },
                    error: function() {
                        alert('Erro ao salvar configurações!');
                    }
                });
                */
            });
            
            // Animações para os cards
            $('.card').hover(
                function() {
                    $(this).addClass('animate__animated animate__pulse');
                },
                function() {
                    $(this).removeClass('animate__animated animate__pulse');
                }
            );
        });
    </script>
</body>
</html>