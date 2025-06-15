<?php
session_start();
// Verificação de login - exemplo básico
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Clínica Ótica</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        
        .card-body h2 {
            font-size: 24px;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .card-body p {
            color: #777;
            font-size: 14px;
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
        }
    </style>
</head>
<body>
    <!-- Menu Lateral -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>Clínica Ótica</h2>
        </div>
        
        <div class="sidebar-menu">
            <h3>Menu Principal</h3>
            <ul>
                <li><a href="#"><i class="fas fa-home"></i> Dashboard</a></li>
                
                <h3>Cadastros</h3>
                <li><a href="../cadastro/clientes.php"><i class="fas fa-user"></i> Clientes</a></li>
                <li><a href="../cadastro/funcionarios.php"><i class="fas fa-user-md"></i> Funcionários</a></li>
                <li><a href="../cadastro/usuarios.php"><i class="fas fa-users"></i> Usuários</a></li>
                 <li><a href="../cadastro/profissional.php"><i class="fas fa-users"></i> Profissional</a></li>
                  <li><a href="#"><i class="fas fa-users"></i> Fornecedores</a></li>
             
                <li><a href="#"><i class="fas fa-concierge-bell"></i> Serviços</a></li>

                <h3>Produtos</h3>
                 <li><a href="#"><i class="fas fa-box"></i> Produtos</a></li>
                 <li><a href="#"><i class="fas fa-tags"></i> Categorias</a></li>



                <h3>Consultas</h3>
                <li><a href="#"><i class="fas fa-calendar-alt"></i> Agendar Consulta</a></li>
                <li><a href="#"><i class="fas fa-eye"></i> Realizar Consulta</a></li>
                <li><a href="../consulta/agendamento.php"><i class="fas fa-file-prescription"></i> Prescrições</a></li>
                
                <h3>Vendas</h3>
                <li><a href="#"><i class="fas fa-cash-register"></i> Nova Venda</a></li>
                

                <h3>Financeiro</h3>
                <li><a href="#"><i class="fas fa-warehouse"></i> Estoque</a></li>
                <li><a href="#"><i class="fas fa-sign-in-alt"></i> Entradas</a></li>
                <li><a href="#"><i class="fas fa-sign-out-alt"></i> Saídas</a></li>
                <li><a href="#"><i class="fas fa-sign-out-alt"></i> contas a Pagar</a></li> <!--ex: despesas, aluguel, etc -->
                <li><a href="#"><i class="fas fa-sign-out-alt"></i> Contas a Receber</a></li>
                <li><a href="#"><i class="fas fa-sign-out-alt"></i> lista de Vendas</a></li>
                <li><a href="#"><i class="fas fa-sign-out-alt"></i> Compras</a></li> <!--ex: compras, AC,nova mesa, produtos para empresa -->

                
                <h3>Relatórios</h3>
                <li><a href="#"><i class="fas fa-chart-line"></i> Vendas</a></li>
                <li><a href="#"><i class="fas fa-users"></i> Funcionários</a></li>
                <li><a href="#"><i class="fas fa-boxes"></i> Produtos</a></li>
                <li><a href="#"><i class="fas fa-chart-pie"></i> Desempenho</a></li>
                <li><a href="#"><i class="fas fa-exchange-alt"></i> Fluxo</a></li>
               
            </ul>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="main-content">
        <!-- Navbar -->
        <nav class="navbar">
            <div class="navbar-left">
                <h1>Dashboard</h1>
            </div>
            <div class="navbar-right">
                <div class="user-profile" id="userProfile">
                    <img src="https://via.placeholder.com/40" alt="User">
                    <span>Nome do Usuário</span>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="#"><i class="fas fa-user"></i> Perfil</a>
                        <a href="#"><i class="fas fa-cog"></i> Configurações</a>
                        <a href="#"><i class="fas fa-sign-out-alt"></i> Sair</a>
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
                        <h2>12</h2>
                        <p>Consultas agendadas para hoje</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Vendas do Mês</h3>
                        <div class="card-icon bg-secondary">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2>Kz 24.560,00</h2>
                        <p>Total de vendas este mês</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Clientes</h3>
                        <div class="card-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2>342</h2>
                        <p>Clientes cadastrados</p>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3>Estoque</h3>
                        <div class="card-icon bg-secondary">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2>156</h2>
                        <p>Itens em estoque</p>
                    </div>
                </div>
            </div>
            
            <div class="recent-activity">
                <h2>Atividades Recentes</h2>
                <!-- Aqui viria uma tabela ou lista de atividades recentes -->
            </div>
        </div>
    </div>

    <script>
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
            
            // Aqui você pode adicionar mais interações conforme necessário
        });
    </script>
</body>
</html>