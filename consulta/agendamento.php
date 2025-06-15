<?php
// Configurações do banco de dados
$host = 'localhost';
$dbname = 'bd_visio';
$username = 'root';
$password = '';

// Inicia a sessão e verifica se o usuário está logado
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ../login.php");
    exit;
}

// Verifica se o user_id está definido
if (!isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obter informações completas do usuário logado
   $stmt = $pdo->prepare("SELECT 
            u.UsuarioID, 
            u.NomeCompleto, 
            u.NivelAcesso,
            f.id_funcionario,
            f.nome AS nome_funcionario,
            f.cargo,
            p.ProfissionalID,
            p.Especialidade,
            p.CRM_OU_LICENCA
          FROM tb_usuario u
          LEFT JOIN tb_funcionario f ON u.UsuarioID = UsuarioID
          LEFT JOIN tb_profissional p ON f.id_funcionario = p.id_funcionario
          WHERE u.UsuarioID = ?");
    
    $stmt->execute([$_SESSION['user_id']]);
    $usuarioLogado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuarioLogado) {
        // Se não encontrou o usuário no banco, faz logout
        session_unset();
        session_destroy();
        header("Location: ../login.php");
        exit;
    }

    // Atualiza a sessão com os dados do banco
    $_SESSION['user_data'] = [
        'UsuarioID' => $usuarioLogado['UsuarioID'],
        'NomeCompleto' => $usuarioLogado['NomeCompleto'],
        'NivelAcesso' => $usuarioLogado['NivelAcesso'],
        'id_funcionario' => $usuarioLogado['id_funcionario'],
        'nome_funcionario' => $usuarioLogado['nome_funcionario'],
        'cargo' => $usuarioLogado['cargo'],
        'ProfissionalID' => $usuarioLogado['ProfissionalID'],
        'Especialidade' => $usuarioLogado['Especialidade'],
        'CRM_OU_LICENCA' => $usuarioLogado['CRM_OU_LICENCA']
    ];

    // Verificar nível de acesso
    $is_admin = ($_SESSION['user_data']['NivelAcesso'] ?? '') === 'Administrador';
    $is_profissional = !empty($_SESSION['user_data']['ProfissionalID']);

    // Verificação de tabelas (opcional)
    $tabelasNecessarias = ['tb_pacientes', 'tb_profissional', 'tb_agendamento', 'tb_usuario', 'tb_funcionario'];
    foreach($tabelasNecessarias as $tabela) {
        $stmt = $pdo->query("SELECT 1 FROM $tabela LIMIT 1");
        if($stmt === false) {
            die("Erro: Tabela $tabela não encontrada");
        }
    }
    
} catch (PDOException $e) {
    die("Erro de conexão: " . $e->getMessage());
}

// ... [restante do código permanece igual] ...
function gerarComprovativo($agendamento, $usuario) {
    // Carrega a biblioteca TCPDF
    require_once('../tcpdf/tcpdf.php');
    
    // Cria novo documento PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurações do documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('VisioGest');
    $pdf->SetTitle('Comprovativo de Agendamento');
    $pdf->SetSubject('Comprovativo');
    $pdf->SetKeywords('Agendamento, Consulta, Comprovativo');
    
    // Remove cabeçalho e rodapé padrão
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Adiciona uma página
    $pdf->AddPage();
    
    // Logo da clínica (substitua pelo caminho correto da sua imagem)
    $logo = 'HomePage/Visio_Gest.png';
    $pdf->Image($logo, 15, 15, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    
    // Título
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 20, 'COMPROVATIVO DE AGENDAMENTO', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Dados do agendamento
    $pdf->SetFont('helvetica', '', 12);
    
    $dataHora = date('d/m/Y H:i', strtotime($agendamento['DataHoraInicio']));
    $dataEmissao = date('d/m/Y H:i');
    
    $html = <<<EOD
    <table border="0" cellpadding="5">
        <tr>
            <td width="30%"><strong>Paciente:</strong></td>
            <td width="70%">{$agendamento['PacienteNome']}</td>
        </tr>
        <tr>
            <td><strong>Profissional:</strong></td>
            <td>{$agendamento['ProfissionalNome']}</td>
        </tr>
        <tr>
            <td><strong>Data/Hora:</strong></td>
            <td>$dataHora</td>
        </tr>
        <tr>
            <td><strong>Tipo de Consulta:</strong></td>
            <td>{$agendamento['TipoConsulta']}</td>
        </tr>
        <tr>
            <td><strong>Status:</strong></td>
            <td>{$agendamento['StatusAgendamento']}</td>
        </tr>
        <tr>
            <td><strong>Agendado por:</strong></td>
            <td>{$usuario['NomeCompleto']}</td>
        </tr>
    </table>
    
    <p><strong>Observações:</strong><br>{$agendamento['Observacoes']}</p>
    
    <p style="text-align: center; margin-top: 30px;">_________________________________</p>
    <p style="text-align: center;">Assinatura do Recepcionista</p>
    
    <p style="text-align: right; font-size: 10px; margin-top: 30px;">
        Emitido em: $dataEmissao
    </p>
EOD;
    
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Gera o PDF e envia para o navegador
    $pdf->Output('comprovativo_agendamento_'.$agendamento['AgendamentoID'].'.pdf', 'I');
    exit;
}

// Processar requisições AJAX
if(isset($_GET['action'])) {
    try {
        switch($_GET['action']) {
            case 'carregar_agendamentos':
                header('Content-Type: application/json');
                
                // Filtro para profissionais (só podem ver seus próprios agendamentos)
                $filtroProfissional = "";
                if($is_profissional && !$is_admin) {
                    $filtroProfissional = " AND a.ProfissionalID = " . $_SESSION['user_data']['ProfissionalID'];
                }
                
                $query = "SELECT a.*, p.NomeCompleto AS PacienteNome, pf.NomeCompleto AS ProfissionalNome, pf.Especialidade 
                          FROM tb_agendamento a
                          JOIN tb_pacientes p ON a.PacienteID = p.PacienteID
                          JOIN tb_profissional pf ON a.ProfissionalID = pf.ProfissionalID
                          WHERE 1=1 $filtroProfissional
                          ORDER BY a.DataHoraInicio DESC";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute();
                $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $html = '';
                foreach($agendamentos as $agendamento) {
                    $dataHora = date('d/m/Y H:i', strtotime($agendamento['DataHoraInicio']));
                    $statusClass = strtolower($agendamento['StatusAgendamento']);
                    
                    $html .= "<tr>
                        <td>{$agendamento['PacienteNome']}</td>
                        <td>{$agendamento['ProfissionalNome']} ({$agendamento['Especialidade']})</td>
                        <td>{$dataHora}</td>
                        <td>{$agendamento['TipoConsulta']}</td>
                        <td><span class='badge badge-{$statusClass}'>{$agendamento['StatusAgendamento']}</span></td>
                        <td>";
                    
                    if($is_admin) {
                        $html .= "<button class='action-btn btn-editar' data-id='{$agendamento['AgendamentoID']}' title='Editar'>
                                    <i class='fas fa-edit'></i>
                                  </button>";
                    }
                    
                    $html .= "<button class='action-btn btn-status' data-id='{$agendamento['AgendamentoID']}' data-status='Confirmado' title='Confirmar'>
                                <i class='fas fa-check'></i>
                              </button>
                              <button class='action-btn btn-status' data-id='{$agendamento['AgendamentoID']}' data-status='Realizado' title='Marcar como Realizado'>
                                <i class='fas fa-calendar-check'></i>
                              </button>";
                    
                    if($is_admin) {
                        $html .= "<button class='action-btn btn-excluir' data-id='{$agendamento['AgendamentoID']}' title='Cancelar'>
                                    <i class='fas fa-trash'></i>
                                  </button>";
                    }
                    
                    $html .= "<button class='action-btn btn-comprovativo' data-id='{$agendamento['AgendamentoID']}' title='Gerar Comprovativo'>
                                <i class='fas fa-file-pdf'></i>
                              </button>
                        </td>
                    </tr>";
                }
                
                echo json_encode(['html' => $html]);
                break;
                
            case 'gerar_comprovativo':
                if(!isset($_GET['id'])) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'ID não fornecido']);
                    exit;
                }
                
                $query = "SELECT a.*, p.NomeCompleto AS PacienteNome, pf.NomeCompleto AS ProfissionalNome, pf.Especialidade 
                          FROM tb_agendamento a
                          JOIN tb_pacientes p ON a.PacienteID = p.PacienteID
                          JOIN tb_profissional pf ON a.ProfissionalID = pf.ProfissionalID
                          WHERE a.AgendamentoID = ?";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([$_GET['id']]);
                $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(!$agendamento) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Agendamento não encontrado']);
                } else {
                    gerarComprovativo($agendamento, $_SESSION['user_data']);
                }
                break;
                
            case 'atualizar_status':
                header('Content-Type: application/json');
                if(!isset($_POST['id']) || !isset($_POST['status'])) {
                    echo json_encode(['error' => 'Dados incompletos']);
                    exit;
                }
                
                // Verifica se o usuário tem permissão para atualizar este agendamento
                if(!$is_admin) {
                    $stmt = $pdo->prepare("SELECT 1 FROM tb_agendamento WHERE AgendamentoID = ? AND ProfissionalID = ?");
                    $stmt->execute([$_POST['id'], $_SESSION['user_data']['ProfissionalID'] ?? 0]);
                    
                    if($stmt->rowCount() === 0) {
                        echo json_encode(['error' => 'Acesso negado - você só pode atualizar seus próprios agendamentos']);
                        exit;
                    }
                }
                
                $stmt = $pdo->prepare("UPDATE tb_agendamento SET StatusAgendamento = ? WHERE AgendamentoID = ?");
                $stmt->execute([$_POST['status'], $_POST['id']]);
                
                echo json_encode(['success' => true]);
                break;
                
            case 'excluir_agendamento':
                header('Content-Type: application/json');
                if(!$is_admin) {
                    echo json_encode(['error' => 'Acesso negado - apenas administradores podem excluir agendamentos']);
                    exit;
                }
                
                if(!isset($_POST['id'])) {
                    echo json_encode(['error' => 'ID não fornecido']);
                    exit;
                }
                
                $stmt = $pdo->prepare("DELETE FROM tb_agendamento WHERE AgendamentoID = ?");
                $stmt->execute([$_POST['id']]);
                
                echo json_encode(['success' => true]);
                break;
                
            case 'obter_agendamento':
                header('Content-Type: application/json');
                if(!isset($_GET['id'])) {
                    echo json_encode(['error' => 'ID não fornecido']);
                    exit;
                }
                
                $query = "SELECT a.*, p.NomeCompleto AS PacienteNome, pf.NomeCompleto AS ProfissionalNome, pf.Especialidade 
                          FROM tb_agendamento a
                          JOIN tb_pacientes p ON a.PacienteID = p.PacienteID
                          JOIN tb_profissional pf ON a.ProfissionalID = pf.ProfissionalID
                          WHERE a.AgendamentoID = ?";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([$_GET['id']]);
                $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(!$agendamento) {
                    echo json_encode(['error' => 'Agendamento não encontrado']);
                } else {
                    echo json_encode($agendamento);
                }
                break;
                
            case 'atualizar_agendamento':
                header('Content-Type: application/json');
                if(!$is_admin) {
                    echo json_encode(['error' => 'Acesso negado - apenas administradores podem editar agendamentos']);
                    exit;
                }
                
                $dados = [
                    'PacienteID' => $_POST['paciente'],
                    'ProfissionalID' => $_POST['profissional'],
                    'DataHoraInicio' => $_POST['data'] . ' ' . $_POST['hora'],
                    'TipoConsulta' => $_POST['tipo_consulta'],
                    'StatusAgendamento' => $_POST['status'],
                    'Observacoes' => $_POST['observacoes'],
                    'AgendamentoID' => $_POST['id']
                ];
                
                $datetime = new DateTime($dados['DataHoraInicio']);
                $datetime->add(new DateInterval('PT1H'));
                $dados['DataHoraFim'] = $datetime->format('Y-m-d H:i:s');
                
                $stmt = $pdo->prepare("UPDATE tb_agendamento SET 
                                      PacienteID = :PacienteID,
                                      ProfissionalID = :ProfissionalID,
                                      DataHoraInicio = :DataHoraInicio,
                                      DataHoraFim = :DataHoraFim,
                                      TipoConsulta = :TipoConsulta,
                                      StatusAgendamento = :StatusAgendamento,
                                      Observacoes = :Observacoes
                                      WHERE AgendamentoID = :AgendamentoID");
                
                $stmt->execute($dados);
                
                echo json_encode(['success' => true]);
                break;
                
            default:
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Ação inválida']);
        }
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Processa o formulário de agendamento
if($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_GET['action'])) {
    $dados = [
        'PacienteID' => $_POST['paciente'] ?? null,
        'ProfissionalID' => $_POST['profissional'] ?? null,
        'UsuarioID' => $_SESSION['user_data']['UsuarioID'],
        'DataHoraInicio' => ($_POST['data'] ?? '') . ' ' . ($_POST['hora'] ?? ''),
        'TipoConsulta' => $_POST['tipo_consulta'] ?? '',
        'Observacoes' => $_POST['observacoes'] ?? '',
        'StatusAgendamento' => 'Agendado'
    ];
    
    // Verifica se todos os campos obrigatórios foram preenchidos
    if (empty($dados['PacienteID']) || empty($dados['ProfissionalID']) || empty($dados['DataHoraInicio']) || empty($dados['TipoConsulta'])) {
        $error = "Por favor, preencha todos os campos obrigatórios!";
    } else {
        try {
            $datetime = new DateTime($dados['DataHoraInicio']);
            $datetime->add(new DateInterval('PT1H'));
            $dados['DataHoraFim'] = $datetime->format('Y-m-d H:i:s');
            
            // Verificar conflito de horário
            $stmt = $pdo->prepare("SELECT * FROM tb_agendamento 
                                  WHERE ProfissionalID = :profissional 
                                  AND ((DataHoraInicio <= :fim AND DataHoraFim >= :inicio))");
            $stmt->execute([
                ':profissional' => $dados['ProfissionalID'],
                ':inicio' => $dados['DataHoraInicio'],
                ':fim' => $dados['DataHoraFim']
            ]);
            
            if($stmt->rowCount() > 0) {
                $error = "Profissional já possui consulta agendada neste horário!";
            } else {
                // Inserir novo agendamento
                $cols = implode(", ", array_keys($dados));
                $vals = ":" . implode(", :", array_keys($dados));
                
                $stmt = $pdo->prepare("INSERT INTO tb_agendamento ($cols) VALUES ($vals)");
                $stmt->execute($dados);
                
                $agendamentoID = $pdo->lastInsertId();
                
                // Obter dados completos do agendamento para o comprovativo
                $query = "SELECT a.*, p.NomeCompleto AS PacienteNome, pf.NomeCompleto AS ProfissionalNome, pf.Especialidade 
                          FROM tb_agendamento a
                          JOIN tb_pacientes p ON a.PacienteID = p.PacienteID
                          JOIN tb_profissional pf ON a.ProfissionalID = pf.ProfissionalID
                          WHERE a.AgendamentoID = ?";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute([$agendamentoID]);
                $agendamento = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $success = "Consulta agendada com sucesso!";
                
                // Gerar comprovativo automaticamente
                gerarComprovativo($agendamento, $_SESSION['user_data']);
            }
        } catch (PDOException $e) {
            $error = "Erro ao agendar: " . $e->getMessage();
        }
    }
}

// Busca pacientes e profissionais
try {
    $pacientes = $pdo->query("SELECT PacienteID, NomeCompleto FROM tb_pacientes ORDER BY NomeCompleto")->fetchAll(PDO::FETCH_ASSOC);
    
    // Se for um profissional, só pode ver a si mesmo na lista
    if($is_profissional && !$is_admin) {
        $profissionais = $pdo->prepare("SELECT ProfissionalID, NomeCompleto, Especialidade 
                                       FROM tb_profissional 
                                       WHERE ProfissionalID = ?
                                       ORDER BY NomeCompleto");
        $profissionais->execute([$_SESSION['user_data']['ProfissionalID']]);
    } else {
        $profissionais = $pdo->query("SELECT ProfissionalID, NomeCompleto, Especialidade 
                                     FROM tb_profissional 
                                     ORDER BY NomeCompleto");
    }
    $profissionais = $profissionais->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao carregar dados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Consulta | VisioGest</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary-dark: #00003B;
            --primary-light: #5A9392;
            --accent-blue: #2A5C8D;
            --accent-green: #4CAF50;
            --light-bg: #F5F7FA;
            --text-dark: #333333;
            --text-light: #FFFFFF;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light-bg);
            color: var(--text-dark);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
            border-top: 4px solid var(--primary-light);
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            color: var(--primary-dark);
            font-size: 24px;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-dark);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
            background-color: #fff;
        }
        
        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(90, 147, 146, 0.2);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .select-wrapper {
            position: relative;
        }
        
        .select-wrapper::after {
            content: '\f078';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            color: var(--primary-dark);
            pointer-events: none;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .btn-primary {
            background-color: var(--primary-dark);
            color: var(--text-light);
        }
        
        .btn-primary:hover {
            background-color: #00002a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 59, 0.2);
        }
        
        .btn-secondary {
            background-color: var(--primary-light);
            color: var(--text-light);
        }
        
        .btn-secondary:hover {
            background-color: #4a7d7b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(90, 147, 146, 0.2);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary-dark);
            color: var(--primary-dark);
        }
        
        .btn-outline:hover {
            background-color: rgba(0, 0, 59, 0.1);
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: fadeIn 0.5s ease-out;
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.2);
            border-left: 4px solid var(--accent-green);
            color: #2e7d32;
        }
        
        .alert-danger {
            background-color: rgba(244, 67, 54, 0.2);
            border-left: 4px solid #f44336;
            color: #c62828;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .animated {
            animation: fadeIn 0.6s ease-out forwards;
        }
        
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        .table-responsive {
            overflow-x: auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background-color: var(--primary-dark);
            color: white;
            font-weight: 500;
            position: sticky;
            top: 0;
        }
        
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .table tr:hover {
            background-color: #f1f1f1;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-agendado {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        .badge-confirmado {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-realizado {
            background-color: #e3f2fd;
            color: #1565c0;
        }
        
        .badge-cancelado {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .action-btn {
            padding: 8px 12px;
            border-radius: 50%;
            font-size: 14px;
            margin-right: 5px;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .btn-editar {
            background-color: #2196F3;
            color: white;
        }
        
        .btn-status {
            background-color: #4CAF50;
            color: white;
        }
        
        .btn-excluir {
            background-color: #f44336;
            color: white;
        }
        
        .btn-comprovativo {
            background-color: #FF9800;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .modal.show {
            display: flex;
            opacity: 1;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(-20px);
            transition: transform 0.3s;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border-top: 4px solid var(--primary-light);
        }
        
        .modal.show .modal-content {
            transform: translateY(0);
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--primary-dark);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #eee;
            text-align: right;
        }
        
        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: white;
        }
        
        .search-container {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(90, 147, 146, 0.2);
            outline: none;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-dark);
        }
        
        .select-search-container {
            position: relative;
        }
        
        .select-search-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            margin-bottom: 5px;
        }
        
        .select-search-options {
            position: absolute;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 6px;
            z-index: 100;
            display: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .select-search-option {
            padding: 10px 15px;
            cursor: pointer;
        }
        
        .select-search-option:hover {
            background-color: #f5f5f5;
        }
        
        @media (max-width: 768px) {
            .card {
                padding: 20px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .form-control {
                padding: 10px 12px;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 14px;
            }
            
            .action-btn {
                width: 32px;
                height: 32px;
                font-size: 12px;
                margin-right: 3px;
            }
        }
        
        .loader {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        [data-tooltip] {
            position: relative;
        }
        
        [data-tooltip]:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 100;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card animated">
            <div class="card-header">
                <h1 class="card-title
                                <h1 class="card-title">Agendar Nova Consulta</h1>
                <button id="verAgendamentos" class="btn btn-outline">
                    <i class="fas fa-calendar-alt"></i> Ver Agendamentos
                </button>
            </div>

            <?php if(isset($error)): ?>
                <div class="alert alert-danger animated">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($success)): ?>
                <div class="alert alert-success animated">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form id="formAgendamento" method="POST" class="animated delay-1">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="paciente" class="form-label">Paciente *</label>
                        <div class="select-search-container">
                            <select id="paciente" name="paciente" class="form-control" required>
                                <option value="">Selecione um paciente</option>
                                <?php foreach($pacientes as $paciente): ?>
                                    <option value="<?php echo $paciente['PacienteID']; ?>">
                                        <?php echo htmlspecialchars($paciente['NomeCompleto']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="profissional" class="form-label">Profissional *</label>
                        <select id="profissional" name="profissional" class="form-control" required>
                            <option value="">Selecione um profissional</option>
                            <?php foreach($profissionais as $profissional): ?>
                  <option value="<?= htmlspecialchars($profissional['ProfissionalID']) ?>">
    <?= htmlspecialchars($profissional['NomeCompleto'] . ' (' . $profissional['Especialidade'] . ')') ?>
</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="data" class="form-label">Data *</label>
                        <input type="date" id="data" name="data" class="form-control" required>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="hora" class="form-label">Hora *</label>
                        <input type="time" id="hora" name="hora" class="form-control" min="08:00" max="18:00" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="tipo_consulta" class="form-label">Tipo de Consulta *</label>
                    <select id="tipo_consulta" name="tipo_consulta" class="form-control" required>
                        <option value="">Selecione o tipo de consulta</option>
                        <option value="Consulta de Rotina">Consulta de Rotina</option>
                        <option value="Consulta de Emergência">Consulta de Emergência</option>
                        <option value="Acompanhamento">Acompanhamento</option>
                        <option value="Exame">Exame</option>
                        <option value="Retorno">Retorno</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="observacoes" class="form-label">Observações</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="3"></textarea>
                </div>

                <div class="form-group text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Agendar Consulta
                    </button>
                </div>
            </form>
        </div>

        <!-- Modal de Agendamentos -->
        <div id="agendamentosModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Lista de Agendamentos</h2>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" id="searchAgendamentos" class="search-input" placeholder="Pesquisar agendamentos...">
                    </div>
                    <div class="table-responsive">
                        <table class="table" id="tabelaAgendamentos">
                            <thead>
                                <tr>
                                    <th>Paciente</th>
                                    <th>Profissional</th>
                                    <th>Data/Hora</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="agendamentosBody">
                                <!-- Os agendamentos serão carregados via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="fecharModal" class="btn btn-secondary">Fechar</button>
                </div>
            </div>
        </div>

        <!-- Modal de Edição -->
        <div id="editarModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Editar Agendamento</h2>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="formEditarAgendamento">
                        <input type="hidden" id="edit_id" name="id">
                        <div class="form-group">
                            <label for="edit_paciente" class="form-label">Paciente *</label>
                            <select id="edit_paciente" name="paciente" class="form-control" required>
                                <option value="">Selecione um paciente</option>
                                <?php foreach($pacientes as $paciente): ?>
                                    <option value="<?php echo $paciente['PacienteID']; ?>">
                                        <?php echo htmlspecialchars($paciente['NomeCompleto']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_profissional" class="form-label">Profissional *</label>
                            <select id="edit_profissional" name="profissional" class="form-control" required>
                                <option value="">Selecione um profissional</option>
                                <?php foreach($profissionais as $profissional): ?>
                                   <option value="<?php echo $profissional['ProfissionalID']; ?>">
    <?php echo htmlspecialchars($profissional['NomeCompleto'] . ' (' . $profissional['Especialidade'] . ')'); ?>
</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="edit_data" class="form-label">Data *</label>
                                <input type="date" id="edit_data" name="data" class="form-control" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="edit_hora" class="form-label">Hora *</label>
                                <input type="time" id="edit_hora" name="hora" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="edit_tipo_consulta" class="form-label">Tipo de Consulta *</label>
                            <select id="edit_tipo_consulta" name="tipo_consulta" class="form-control" required>
                                <option value="">Selecione o tipo de consulta</option>
                                <option value="Consulta de Rotina">Consulta de Rotina</option>
                                <option value="Consulta de Emergência">Consulta de Emergência</option>
                                <option value="Acompanhamento">Acompanhamento</option>
                                <option value="Exame">Exame</option>
                                <option value="Retorno">Retorno</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_status" class="form-label">Status *</label>
                            <select id="edit_status" name="status" class="form-control" required>
                                <option value="Agendado">Agendado</option>
                                <option value="Confirmado">Confirmado</option>
                                <option value="Realizado">Realizado</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="edit_observacoes" class="form-label">Observações</label>
                            <textarea id="edit_observacoes" name="observacoes" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button id="cancelarEdicao" class="btn btn-outline">Cancelar</button>
                    <button id="salvarEdicao" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script>
        $(document).ready(function() {
            // Configuração do datepicker
            flatpickr("#data", {
                dateFormat: "Y-m-d",
                minDate: "today",
                locale: "pt"
            });

            flatpickr("#hora", {
                enableTime: true,
                noCalendar: true,
                dateFormat: "H:i",
                minTime: "08:00",
                maxTime: "18:00",
                minuteIncrement: 30
            });

            // Modal de agendamentos
            const modal = $("#agendamentosModal");
            const verAgendamentosBtn = $("#verAgendamentos");
            const fecharModalBtn = $("#fecharModal");
            const closeBtn = $(".close-btn");

            verAgendamentosBtn.click(function() {
                carregarAgendamentos();
                modal.addClass("show");
            });

            fecharModalBtn.click(function() {
                modal.removeClass("show");
            });

            closeBtn.click(function() {
                modal.removeClass("show");
                $("#editarModal").removeClass("show");
            });

            $(window).click(function(event) {
                if (event.target == modal[0]) {
                    modal.removeClass("show");
                    $("#editarModal").removeClass("show");
                }
            });

            // Carregar agendamentos via AJAX
            function carregarAgendamentos() {
                $.ajax({
                    url: "?action=carregar_agendamentos",
                    type: "GET",
                    dataType: "json",
                    beforeSend: function() {
                        $("#agendamentosBody").html("<tr><td colspan='6' class='text-center'>Carregando...</td></tr>");
                    },
                    success: function(response) {
                        $("#agendamentosBody").html(response.html);
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        $("#agendamentosBody").html("<tr><td colspan='6' class='text-center text-danger'>Erro ao carregar agendamentos</td></tr>");
                    }
                });
            }

            // Pesquisa de agendamentos
            $("#searchAgendamentos").keyup(function() {
                const value = $(this).val().toLowerCase();
                $("#tabelaAgendamentos tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });

            // Ações dos botões na tabela
            $(document).on("click", ".btn-status", function() {
                const id = $(this).data("id");
                const status = $(this).data("status");
                
                $.ajax({
                    url: "?action=atualizar_status",
                    type: "POST",
                    data: { id: id, status: status },
                    dataType: "json",
                    success: function(response) {
                        if (!response.error) {
                            carregarAgendamentos();
                        } else {
                            alert(response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Erro ao atualizar status: " + error);
                    }
                });
            });

            $(document).on("click", ".btn-excluir", function() {
                if (confirm("Tem certeza que deseja cancelar este agendamento?")) {
                    const id = $(this).data("id");
                    
                    $.ajax({
                        url: "?action=excluir_agendamento",
                        type: "POST",
                        data: { id: id },
                        dataType: "json",
                        success: function(response) {
                            if (!response.error) {
                                carregarAgendamentos();
                            } else {
                                alert(response.error);
                            }
                        },
                        error: function(xhr, status, error) {
                            alert("Erro ao excluir agendamento: " + error);
                        }
                    });
                }
            });

            $(document).on("click", ".btn-comprovativo", function() {
                const id = $(this).data("id");
                window.open("?action=gerar_comprovativo&id=" + id, "_blank");
            });

            $(document).on("click", ".btn-editar", function() {
                const id = $(this).data("id");
                
                $.ajax({
                    url: "?action=obter_agendamento",
                    type: "GET",
                    data: { id: id },
                    dataType: "json",
                    success: function(response) {
                        if (!response.error) {
                            // Preencher o formulário de edição
                            $("#edit_id").val(response.AgendamentoID);
                            $("#edit_paciente").val(response.PacienteID);
                            $("#edit_profissional").val(response.ProfissionalID);
                            
                            const dataHora = response.DataHoraInicio.split(" ");
                            $("#edit_data").val(dataHora[0]);
                            $("#edit_hora").val(dataHora[1].substring(0, 5));
                            
                            $("#edit_tipo_consulta").val(response.TipoConsulta);
                            $("#edit_status").val(response.StatusAgendamento);
                            $("#edit_observacoes").val(response.Observacoes);
                            
                            // Mostrar modal de edição
                            $("#editarModal").addClass("show");
                        } else {
                            alert(response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Erro ao carregar agendamento: " + error);
                    }
                });
            });

            // Salvar edição
            $("#salvarEdicao").click(function() {
                const formData = $("#formEditarAgendamento").serialize();
                
                $.ajax({
                    url: "?action=atualizar_agendamento",
                    type: "POST",
                    data: formData,
                    dataType: "json",
                    success: function(response) {
                        if (!response.error) {
                            carregarAgendamentos();
                            $("#editarModal").removeClass("show");
                        } else {
                            alert(response.error);
                        }
                    },
                    error: function(xhr, status, error) {
                        alert("Erro ao atualizar agendamento: " + error);
                    }
                });
            });

            $("#cancelarEdicao").click(function() {
                $("#editarModal").removeClass("show");
            });

            // Melhorar experiência do select
            $("select").each(function() {
                $(this).on("focus", function() {
                    $(this).css("border-color", "#5A9392");
                    $(this).css("box-shadow", "0 0 0 3px rgba(90, 147, 146, 0.2)");
                }).on("blur", function() {
                    $(this).css("border-color", "#ddd");
                    $(this).css("box-shadow", "none");
                });
            });
        });
    </script>
</body>
</html>