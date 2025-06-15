<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>VisioGest - Sistema de Gestão para Clínica Ótica</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #5A9392;
            --secondary-color: #00003b;
            --light-color: #f0f4f7;
            --dark-color: #07587e;
        }
        
        body {
            font-family: 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--dark-color);
            color: white;
            overflow-x: hidden;
        }
        
        /* Navbar Premium */
        .custom-navbar {
            position: relative;
            padding-top: 10px;
            height: 70px;
            background-color: var(--primary-color) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.4s ease;
        }
        
        .custom-navbar.scrolled {
            height: 60px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .custom-navbar::before {
            content: "";
            display: block;
            width: 100%;
            height: 15px;
            background-color: var(--secondary-color);
            position: absolute;
            top: 0;
            left: 0;
            z-index: 1030;
            transition: all 0.3s ease;
        }
        
        .custom-navbar.scrolled::before {
            height: 10px;
        }
        
        .nav-button-dark {
            color: white !important;
            position: relative;
            padding: 8px 15px;
            margin: 0 5px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-button-dark::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            background: white;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            transition: width 0.3s ease;
        }
        
        .nav-button-dark:hover {
            color: var(--secondary-color) !important;
            transform: translateY(-2px);
        }
        
        .nav-button-dark:hover::after {
            width: 80%;
            background: var(--secondary-color);
        }
        
        .navbar-brand {
            font-weight: 700;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        /* Seção de fundo com animação de parallax */
        .background-section {
            background: url('screen.jpg') no-repeat center center;
            background-size: cover;
            height: 600px;
            margin-top: 3px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .background-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 59, 0.3);
        }
        
        .background-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }
        
        .background-content.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Seção de informações com cards animados */
        .info-section {
            background-color: var(--light-color);
            padding: 60px 0;
        }
        
        .module-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .module-card.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        .module-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .text-primary {
            color: var(--secondary-color) !important;
        }
        
        .text-info {
            color: #1d4e89;
            font-size: 15px;
            line-height: 1.6;
        }
        
        /* Seção de cards com efeito de onda */
        .sections {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            padding: 60px 20px;
            background-color: var(--dark-color);
            position: relative;
        }
        
        .sections::before {
            content: '';
            position: absolute;
            top: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" fill="%23f0f4f7" opacity=".25"/><path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" fill="%23f0f4f7" opacity=".5"/><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="%23f0f4f7"/></svg>') no-repeat;
            background-size: cover;
        }
        
        .card {
            width: 250px;
            background-color: white;
            color: var(--secondary-color);
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.4s ease;
            opacity: 0;
            transform: translateY(30px);
        }
        
        .card.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        .card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.12);
        }
        
        .icon {
            font-size: 50px;
            margin-bottom: 20px;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .card:hover .icon {
            transform: scale(1.2);
            color: var(--primary-color);
        }
        
        /* Rodapé com efeito de subida */
        .footer {
            background-color: var(--secondary-color);
            color: white;
            padding: 40px 20px;
            text-align: center;
            font-size: 14px;
            position: relative;
            overflow: hidden;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 0;
            width: 100%;
            height: 40px;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" fill="%2300003b" opacity=".25"/><path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" fill="%2300003b" opacity=".5"/><path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" fill="%2300003b"/></svg>') no-repeat;
            background-size: cover;
        }
        
        .footer-social {
            margin-top: 20px;
            font-size: 16px;
        }
        
        .social-icon {
            color: white;
            font-size: 20px;
            margin: 0 10px;
            transition: all 0.3s ease;
        }
        
        .social-icon:hover {
            color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        /* Botão flutuante */
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 100;
            border: none;
            opacity: 0;
            transform: translateY(20px);
        }
        
        .floating-btn.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .floating-btn:hover {
            transform: translateY(-5px) scale(1.1);
            background-color: var(--secondary-color);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }
        
        /* Efeitos de hover para links */
        a {
            transition: all 0.3s ease;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .background-section {
                height: 400px;
            }
            
            .card {
                width: 100%;
                max-width: 350px;
            }
            
            .custom-navbar {
                height: auto;
                padding: 10px 0;
            }
            
            .custom-navbar.scrolled {
                height: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar Premium -->
    <nav class="navbar navbar-expand-lg custom-navbar">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="logo.png" alt="VisioGest Logo" class="logo">
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"><i class="fas fa-bars"></i></span>
            </button>

            <div class="collapse navbar-collapse justify-content-between" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link nav-button-dark" href="#"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-button-dark" href="../login.php"><i class="fas fa-cogs"></i> Sistema</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-button-dark" href="#"><i class="fas fa-code"></i> Desenvolvedores</a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link nav-button-dark" href="#"><i class="fas fa-envelope"></i> Fale Conosco</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Seção com imagem de fundo -->
    <div class="background-section">
        <div class="background-content">
            <h1 class="display-4 animate__animated animate__fadeInDown">VisioGest 1.0</h1>
            <p class="lead animate__animated animate__fadeInUp animate__delay-1s">Sistema completo para gestão de clínicas óticas</p>
            <button class="btn btn-light btn-lg mt-3 animate__animated animate__fadeInUp animate__delay-2s">Demonstração</button>
        </div>
    </div>

    <!-- Informações e Módulos -->
    <div class="info-section text-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <!-- Especificações -->
                <div class="col-md-5 mb-4 module-card">
                    <img src="https://img.icons8.com/ios-filled/50/00003b/windows8.png" alt="Windows Icon" class="animate__animated">
                    <h5 class="text-primary font-weight-bold mt-3">Especificações</h5>
                    <p class="text-info">
                        Versão: 1.0 | Idioma: Português | Suporte: Windows 8 a 11<br>
                        Plataforma: Desktop | Modo de Uso: Online | Ano de Lançamento: 2025
                    </p>
                </div>

                <!-- Módulos -->
                <div class="col-md-6 module-card">
                    <img src="https://img.icons8.com/ios-filled/50/00003b/database.png" alt="Database Icon" class="animate__animated">
                    <h5 class="text-primary font-weight-bold mt-3">Módulos</h5>
                    <p class="text-info">
                        <strong>Gestão de Usuários</strong> |
                        <strong>Gestão de Faturas</strong> |
                        <strong>Gestão de Serviços</strong> |
                        Gestão de Clientes |
                        <strong>Gestão de Documentos</strong> |
                        Gestão de Produtos |
                        Gestão de Fornecedores |
                        Logística |
                        Contabilidade e Finança
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Cabeçalho -->
    <div class="header">
        <h1 class="animate__animated animate__fadeIn">Requisitos & Outras Especificações</h1>
        <h2 class="animate__animated animate__fadeIn animate__delay-1s">Estrutura e Características Visio Gest 1.0</h2>
    </div>

    <!-- Secções (2 filas de 3 colunas) -->
    <div class="sections">
        <div class="card">
            <div class="icon">💻</div>
            <h3>Usabilidade</h3>
            <p>Uniformização das máscaras, os componentes gráficos, e as mensagens de erro. Qualidade de visualização em qualquer dispositivo de suporte.</p>
        </div>
        <div class="card">
            <div class="icon">📦</div>
            <h3>Disponibilidade</h3>
            <p>Acessível independentemente da localização geográfica, desde que tenha acesso à internet e possua credenciais válidas; Tolerância às falhas e sistema de cópia de segurança.</p>
        </div>
        <div class="card">
            <div class="icon">🚀</div>
            <h3>Desempenho</h3>
            <p>Consulta dos dados não podem demorar mais do que 1 minuto.</p>
        </div>
        <div class="card">
            <div class="icon">💻&lt;/&gt;</div>
            <h3>Suporte</h3>
            <p>Adaptabilidade a alterações de leis que influenciam no processamento de dados (tarifas); Possibilita acesso remoto para posteriormente renovar a licença.</p>
        </div>
        <div class="card">
            <div class="icon">🔐</div>
            <h3>Segurança</h3>
            <p>Restringe o acesso aos recursos do sistema de acordo com o perfil do utilizador. Restringe as operações de acordo com o perfil do utilizador.</p>
        </div>
        <div class="card">
            <div class="icon">🪟</div>
            <h3>Especificações</h3>
            <p>Versão: 1.0 | Idioma: Português<br>Suporte: Windows 8 a 11<br>Plataforma: Desktop | Modo de Uso: Online<br>Ano de Lançamento: 2023</p>
        </div>
    </div>

    <!-- Rodapé -->
    <div class="footer">
        © 2025 Hernane (Visio Gest). Todos os direitos reservados.
        <div class="footer-social">
            <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
        </div>
    </div>

    <!-- Botão flutuante -->
    <button class="floating-btn" id="floatingBtn">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <script>
        // Animação de scroll da navbar
        $(window).scroll(function() {
            if ($(this).scrollTop() > 50) {
                $('.custom-navbar').addClass('scrolled');
            } else {
                $('.custom-navbar').removeClass('scrolled');
            }
            
            // Mostrar/ocultar botão flutuante
            if ($(this).scrollTop() > 300) {
                $('#floatingBtn').addClass('visible');
            } else {
                $('#floatingBtn').removeClass('visible');
            }
        });
        
        // Botão flutuante - voltar ao topo
        $('#floatingBtn').click(function() {
            $('html, body').animate({scrollTop: 0}, 'smooth');
            return false;
        });
        
        // Animação dos elementos ao rolar
        $(document).ready(function() {
            // Ativar animação da seção de fundo
            setTimeout(function() {
                $('.background-content').addClass('animated');
            }, 500);
            
            // Observador de elementos para animação
            const animateOnScroll = function() {
                const windowTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                const windowBottom = windowTop + windowHeight;
                
                $('.module-card, .card').each(function() {
                    const elementTop = $(this).offset().top;
                    
                    if (elementTop < windowBottom - 100) {
                        $(this).addClass('animated');
                    }
                });
            };
            
            // Ativar animações iniciais
            animateOnScroll();
            
            // Ativar animações ao rolar
            $(window).scroll(function() {
                animateOnScroll();
            });
            
            // Efeito hover nos cards
            $('.card').hover(
                function() {
                    $(this).find('.icon').css('transform', 'scale(1.2)');
                },
                function() {
                    $(this).find('.icon').css('transform', 'scale(1)');
                }
            );
            
            // Efeito de digitação no título (opcional)
            if ($('.background-content h1').length) {
                const text = "VisioGest 1.0";
                let i = 0;
                const typingEffect = setInterval(function() {
                    if (i < text.length) {
                        $('.background-content h1').append(text.charAt(i));
                        i++;
                    } else {
                        clearInterval(typingEffect);
                    }
                }, 100);
            }
        });
    </script>
</body>
</html>