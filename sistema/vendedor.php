<?php
session_start();
// Verificação de login e tipo de usuário
// Conexão com o banco de dados (exemplo)
require_once('../conexao.php');
//session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit;
}


// Buscar vendas realizadas pelo vendedor
$vendedor_id = $_SESSION['user_id'];

// Verifique se a conexão está estabelecida
if (!$conn) {
    die("Erro na conexão com o banco de dados: " . mysqli_connect_error());
}

$query = "SELECT v.VendaID, p.NomeCompleto as Paciente, v.DataVenda, v.ValorTotal, v.DescontoTotal, v.FormaPagamento, v.StatusVenda 
          FROM tb_venda v
          JOIN tb_pacientes p ON v.PacienteID = p.PacienteID
          WHERE v.VendedorID = ?
          ORDER BY v.DataVenda DESC";

$stmt = $conn->prepare($query);

// Verifique se a preparação foi bem-sucedida
if ($stmt === false) {
    die("Erro na preparação da consulta: " . $conn->error);
}

// Verifique se o bind_param foi bem-sucedido
if (!$stmt->bind_param("i", $vendedor_id)) {
    die("Erro ao vincular parâmetros: " . $stmt->error);
}

// Verifique se a execução foi bem-sucedida
if (!$stmt->execute()) {
    die("Erro na execução da consulta: " . $stmt->error);
}

$result = $stmt->get_result();

// Verifique se obteve resultados
if ($result === false) {
    die("Erro ao obter resultados: " . $stmt->error);
}

$vendas = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Vendedor Ótico</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <style>
        :root {
            --primary-color: #5A9392;
            --secondary-color: #1E3A5F;
            --light-color: #F5F7FA;
            --dark-color: #333333;
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
        
        /* Menu Lateral Simplificado */
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            height: 100vh;
            position: fixed;
        }
        
        .sidebar-header {
            padding: 20px;
            background-color: var(--primary-color);
            text-align: center;
        }
        
        .sidebar-menu {
            padding: 20px 0;
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
        
        .card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .card-header h2 {
            color: var(--secondary-color);
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #4a7b7a;
        }
        
        /* Tabela de vendas */
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
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
                position: fixed;
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
                margin-right: 15px;
                font-size: 20px;
                cursor: pointer;
            }
        }
    </style>
</head>
<body>
    <!-- Menu Lateral Simplificado -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Clínica Ótica</h2>
        </div>
        
        <div class="sidebar-menu">
            <ul>
                <li><a href="vendedor.php"><i class="fas fa-home"></i> Início</a></li>
                <li><a href="../venda/venda.php"><i class="fas fa-cash-register"></i> Nova Venda</a></li>
                <li><a href="vendas.php"><i class="fas fa-list"></i> Minhas Vendas</a></li>
                <li><a href="../HomePage/Home.php"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
            </ul>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar">
            <div class="navbar-left">
                <h1>Minhas Vendas</h1>
            </div>
            <div class="navbar-right">
                <div class="user-profile" id="userProfile">
                    <img src="https://via.placeholder.com/40" alt="User">
                    <span><?php echo $_SESSION['user_name']; ?></span>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="#"><i class="fas fa-user"></i> Meu Perfil</a>
                        <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Conteúdo -->
        <div class="content">
            <div class="card">
                <div class="card-header">
                    <h2>Histórico de Vendas</h2>
                    <a href="nova_venda.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nova Venda
                    </a>
                </div>
                
                <div class="table-responsive">
                    <table id="tabelaVendas">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Paciente</th>
                                <th>Data</th>
                                <th>Valor Total</th>
                                <th>Desconto</th>
                                <th>Pagamento</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vendas as $venda): ?>
                            <tr>
                                <td><?php echo $venda['VendaID']; ?></td>
                                <td><?php echo $venda['Paciente']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($venda['DataVenda'])); ?></td>
                                <td>Kz <?php echo number_format($venda['ValorTotal'], 2, ',', '.'); ?></td>
                                <td>Kz <?php echo number_format($venda['DescontoTotal'], 2, ',', '.'); ?></td>
                                <td><?php echo $venda['FormaPagamento']; ?></td>
                                <td>
                                    <?php 
                                    $badge_class = '';
                                    if ($venda['StatusVenda'] == 'Concluída') {
                                        $badge_class = 'badge-success';
                                    } elseif ($venda['StatusVenda'] == 'Pendente') {
                                        $badge_class = 'badge-warning';
                                    } elseif ($venda['StatusVenda'] == 'Cancelada') {
                                        $badge_class = 'badge-danger';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $venda['StatusVenda']; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detalhes_venda.php?id=<?php echo $venda['VendaID']; ?>" class="btn btn-sm" title="Ver detalhes">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#tabelaVendas').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/pt-PT.json'
                },
                order: [[2, 'desc']]
            });
            
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
        });
    </script>
</body>
</html>