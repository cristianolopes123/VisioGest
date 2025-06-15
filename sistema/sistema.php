<?php
session_start();
// Verificação de login - exemplo básico
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clínica Ótica - <?php echo $is_admin ? 'Admin' : 'Paciente'; ?></title>
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

        .main-content {
            flex: 1;
            margin-left: 250px;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
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
        }

        .dropdown-menu.show {
            display: block;
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
        }

        .appointment-list {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .appointment-item {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .action-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .btn-small {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            background-color: var(--primary-color);
            color: white;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Menu Lateral -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Clínica Ótica</h2>
        </div>
        
        <div class="sidebar-menu">
            <h3>Menu Principal</h3>
            <ul>
                <li><a href="#"><i class="fas fa-home"></i> Dashboard</a></li>
                
                <?php if($is_admin): ?>
                <h3>Cadastros</h3>
                <li><a href="#"><i class="fas fa-user"></i> Clientes</a></li>
                <li><a href="#"><i class="fas fa-user-md"></i> Funcionários</a></li>
                <li><a href="#"><i class="fas fa-users"></i> Fornecedores</a></li>
                <li><a href="#"><i class="fas fa-box"></i> Produtos</a></li>
                <li><a href="#"><i class="fas fa-tags"></i> Categorias</a></li>
                <?php endif; ?>
                
                <h3>Consultas</h3>
                <li><a href="#"><i class="fas fa-calendar-plus"></i> Agendar Consulta</a></li>
                <li><a href="#"><i class="fas fa-list-alt"></i> Minhas Consultas</a></li>
                <li><a href="#"><i class="fas fa-file-prescription"></i> Prescrições</a></li>

                 <h3>Vendas</h3>
                <li><a href="#"><i class="fas fa-cash-register"></i> Nova Venda</a></li>
                
                
                <?php if($is_admin): ?>
                <h3>Administrativo</h3>
                <li><a href="#"><i class="fas fa-warehouse"></i> Estoque</a></li>
                <li><a href="#"><i class="fas fa-chart-line"></i> Relatórios</a></li>
                <?php endif; ?>
                
                <h3>Conta</h3>
                <li><a href="#"><i class="fas fa-user-cog"></i> Perfil</a></li>
                <li><a href="#"><i class="fas fa-sign-out-alt"></i> Sair</a></li>
            </ul>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="main-content">
        <nav class="navbar">
            <div class="navbar-left">
                <h1>Bem-vindo, <?php echo $user_name; ?></h1>
            </div>
            <div class="navbar-right">
                <div class="user-profile" id="userProfile">
                    <img src="https://via.placeholder.com/40" alt="User">
                    <span><?php echo $user_name; ?></span>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="#"><i class="fas fa-user"></i> Perfil</a>
                        <?php if($is_admin): ?>
                        <a href="#"><i class="fas fa-tools"></i> Administração</a>
                        <?php endif; ?>
                        <a href="#"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </nav>
        
        <div class="content">
            <?php if($is_admin): ?>
            <!-- Dashboard Administrativo -->
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
                        <p>Consultas agendadas</p>
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
                        <h2>R$ 24.560,00</h2>
                        <p>Total de vendas</p>
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
            
            <div class="appointment-list">
                <h2>Atividades Recentes</h2>
                <div class="appointment-item">
                    <div>Nova venda realizada</div>
                    <div>R$ 450,00</div>
                    <div>02/11 - 14:30</div>
                </div>
            </div>

            <?php else: ?>
            <!-- Dashboard Usuário Normal -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <h3>Próxima Consulta</h3>
                        <div class="card-icon bg-primary">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2>25/10 - 14:30</h2>
                        <p>Dr. Ana Silva</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Prescrições Ativas</h3>
                        <div class="card-icon bg-secondary">
                            <i class="fas fa-file-prescription"></i>
                        </div>
                    </div>
                    <div class="card-body">
                        <h2>2</h2>
                        <p>Prescrições válidas</p>
                    </div>
                </div>
            </div>

            <div class="appointment-list">
                <h2>Próximas Consultas</h2>
                <div class="appointment-item">
                    <div>25/10 - 14:30</div>
                    <div>Checkup ocular</div>
                    <button class="btn-small">Detalhes</button>
                </div>
            </div>

            <div class="quick-actions">
                <div class="action-card bg-primary">
                    <i class="fas fa-calendar-plus fa-2x"></i>
                    <h4>Agendar Consulta</h4>
                </div>
                <div class="action-card bg-secondary">
                    <i class="fas fa-file-invoice-dollar fa-2x"></i>
                    <h4>Minhas Faturas</h4>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const userProfile = document.getElementById('userProfile');
            const dropdownMenu = document.getElementById('dropdownMenu');
            
            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownMenu.classList.toggle('show');
            });

            window.addEventListener('click', function() {
                if (dropdownMenu.classList.contains('show')) {
                    dropdownMenu.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>