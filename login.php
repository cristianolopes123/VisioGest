<?php
session_start();
require_once('conexao.php');

// Habilitar erros para depuração
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Processar login
$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $_SESSION['loggedin'] = true;
    
    // Validação básica dos campos
    if (empty($username) || empty($password)) {
        $error = "Por favor, preencha todos os campos!";
    } else {
        try {
            // Preparar a consulta usando MySQLi
            $stmt = $conn->prepare("SELECT UsuarioID, NomeCompleto, senha, NivelAcesso FROM tb_usuario WHERE nomeUsuario = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                
                if (password_verify($password, $user['senha'])) {
                    // Validação do nível de acesso
                    $niveisPermitidos = ['Administrador', 'Profissional', 'Recepcionista', 'Vendedor'];
                    
                    if (in_array($user['NivelAcesso'], $niveisPermitidos)) {
                        $_SESSION['loggedin'] = true;
                        $_SESSION['user_id'] = $user['UsuarioID'];
                        $_SESSION['user_name'] = $user['NomeCompleto'];
                        $_SESSION['nivel_acesso'] = $user['NivelAcesso'];
                        
                        // Redirecionamento baseado no nível de acesso
                        switch($user['NivelAcesso']) {
                            case 'Administrador':
                                header("Location: ../Ofta_/sistema/admin.php");
                                break;
                            case 'Profissional':
                                header("Location: ../Ofta_/sistema/profissional.php");
                                break;
                            case 'Recepcionista':
                                header("Location: ../Ofta_/sistema/recepcionista.php");
                                break;
                            case 'Vendedor':
                                header("Location: ../Ofta_/sistema/vendedor.php");
                                break;
                            default:
                                header("Location: consulta/agendamento.php");
                        }
                        exit;
                    } else {
                        $error = "Seu nível de acesso não está configurado corretamente!";
                    }
                } else {
                    $error = "Senha ou Usuario incorreta!";
                }
            } else {
                $error = "Usuário não encontrado!";
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $error = "Erro no sistema: " . $e->getMessage();
        }
    }

}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - VisioGest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --verde-principal: #5A9392;
            --azul-escuro: #2A4E6B;
            --branco: #FFFFFF;
            --cinza-claro: #F5F7FA;
            --cinza-escuro: #333333;
            --destaque: #FF7E5F;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, var(--azul-escuro), var(--verde-principal));
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--cinza-escuro);
            overflow: hidden;
        }
        
        .login-container {
            background-color: var(--branco);
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 420px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            z-index: 1;
            animation: fadeIn 0.8s ease-out;
        }
        
        .login-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(90,147,146,0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: -1;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .logo-img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            animation: float 4s ease-in-out infinite;
        }
        
        .logo h1 {
            color: var(--azul-escuro);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--azul-escuro);
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            border-color: var(--verde-principal);
            box-shadow: 0 0 0 3px rgba(90, 147, 146, 0.2);
            outline: none;
        }
        
        .form-group i {
            position: absolute;
            left: 15px;
            top: 42px;
            color: var(--verde-principal);
        }
        
        .btn-login {
            background: linear-gradient(to right, var(--verde-principal), var(--azul-escuro));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
            animation: pulse 1.5s infinite;
        }
        
        .error-message {
            color: #e74c3c;
            background-color: rgba(231, 76, 60, 0.1);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            animation: fadeIn 0.5s ease-out;
        }
        
        .forgot-password {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--azul-escuro);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        
        .forgot-password:hover {
            color: var(--verde-principal);
            text-decoration: underline;
        }
        
        footer {
            margin-top: 30px;
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            background-color: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 10s infinite linear;
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 0 15px;
            }
            
            .logo h1 {
                font-size: 100px;
            }
            
            .logo-img {
                width: 100px;
                height: 100px;
            }
        }
    </style>
</head>
<body>
    <div class="particles" id="particles"></div>
    
    <div class="login-container">
        <div class="logo">
            <div class="logo-container">
                <img src="HomePage/Visio_Gest.png" alt="VisioGest Logo" class="logo-img">
            </div>
            <h1>VISIO-GEST</h1>
            <p>Sistema de Gestão Clínica</p>
        </div>
        
        <?php if(isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Nome de Usuário</label>
                <i class="fas fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Digite seu usuário" required>
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
            </div>
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
            <a href="#" class="forgot-password">Esqueceu sua senha?</a>
        </form>
        
        <footer>
            © <?= date('Y') ?> VisioGest - Todos os direitos reservados
        </footer>
    </div>
    
    <script>
        // Cria partículas flutuantes
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Tamanho aleatório entre 5px e 15px
                const size = Math.random() * 10 + 5;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Posição aleatória
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Opacidade aleatória
                particle.style.opacity = Math.random() * 0.5 + 0.1;
                
                // Duração da animação aleatória
                particle.style.animationDuration = `${Math.random() * 15 + 5}s`;
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                particlesContainer.appendChild(particle);
            }
            
            // Efeito de foco nos inputs
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.querySelector('i').style.color = 'var(--azul-escuro)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.querySelector('i').style.color = 'var(--verde-principal)';
                });
            });
        });
    </script>
</body>
</html>