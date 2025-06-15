<?php
// ==============================================
// CONFIGURAÇÕES E FUNÇÕES
// ==============================================

// Configurações do sistema
define('EMPRESA_NOME', 'VisioGest');
define('EMPRESA_ENDERECO', 'Av. Deolinda, 123, Luanda - Angola');
define('EMPRESA_CONTRIBUINTE', '54/7243604');
define('EMPRESA_TELEFONE', '+244 940 231 794');
define('EMPRESA_EMAIL', 'geral@visiogest.com');
define('EMPRESA_SITE', 'www.visiogest.com');

// Conexão com banco de dados
$db = new PDO('mysql:host=localhost;dbname=bd_visio', 'root', '');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Função para buscar pacientes
function getPacientes($db) {
    $stmt = $db->query("SELECT PacienteID, NomeCompleto FROM tb_pacientes ORDER BY NomeCompleto");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para buscar profissionais
function getProfissionais($db) {
    $stmt = $db->query("SELECT ProfissionalID, NomeCompleto FROM tb_profissional ORDER BY NomeCompleto");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getConsultas($db) {
    $stmt = $db->query("
        SELECT ConsultaID AS consulta, DataHoraRealizacao
        FROM tb_consulta
        ORDER BY DataHoraRealizacao DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para salvar prescrição
function salvarPrescricao($db, $dados) {
    $sql = "INSERT INTO tb_prescricao (
        PacienteID, ProfissionalID, ConsultaID, DataEmissao,
        OD_Esferico, OD_Cilindrico, OD_Eixo, OD_DNP, OD_LPrisma, OD_Base, OD_AV, OD_Add, OD_Perto_DNP,
        OE_Esferico, OE_Cilindrico, OE_Eixo, OE_DNP, OE_Prisma, OE_Base, OE_AV, OE_Add, OE_Perto_DNP,
        Observacoes, ProximaConsulta
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute(array_values($dados));
}

// Função para listar prescrições
function getPrescricoes($db, $filtros = []) {
    $sql = "
        SELECT p.*, pac.NomeCompleto AS PacienteNome, prof.NomeCompleto AS ProfissionalNome
        FROM tb_prescricao p
        LEFT JOIN tb_pacientes pac ON p.PacienteID = pac.PacienteID
        LEFT JOIN tb_profissional prof ON p.ProfissionalID = prof.ProfissionalID
        WHERE 1=1
    ";
    
    $params = [];
    
    if (!empty($filtros['paciente'])) {
        $sql .= " AND p.PacienteID = ?";
        $params[] = $filtros['paciente'];
    }
    
    if (!empty($filtros['data'])) {
        $sql .= " AND DATE(p.DataEmissao) = ?";
        $params[] = $filtros['data'];
    }
    
    $sql .= " ORDER BY p.DataEmissao DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para buscar uma prescrição por ID
function getPrescricaoById($db, $id) {
    $stmt = $db->prepare("
        SELECT p.*, pac.NomeCompleto AS PacienteNome, prof.NomeCompleto AS ProfissionalNome
        FROM tb_prescricao p
        LEFT JOIN tb_pacientes pac ON p.PacienteID = pac.PacienteID
        LEFT JOIN tb_profissional prof ON p.ProfissionalID = prof.ProfissionalID
        WHERE p.PrescricaoOpticaID = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

  function gerarPDF($prescricao) {
    // Configuração dos caminhos do TCPDF
    $tcpdfPath = __DIR__ . '/../tcpdf/';
    
    // Verifica se o TCPDF está instalado corretamente
    if (!file_exists($tcpdfPath . 'tcpdf.php')) {
        die("Erro: Biblioteca TCPDF não encontrada em: " . $tcpdfPath);
    }
    
    // Inclui apenas o arquivo principal do TCPDF
    require_once($tcpdfPath . 'tcpdf.php');
    
    // Cria nova instância do TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Configurações do documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor(EMPRESA_NOME);
    $pdf->SetTitle('Prescrição Óptica - ' . $prescricao['PrescricaoOpticaID']);
    $pdf->SetSubject('Prescrição Óptica');
    
    // Configurações de layout
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(15, 15, 15);
    
    // Adiciona uma página
    $pdf->AddPage();
    
    // HTML do PDF
    $html = '
    <style>
        .titulo { 
            font-size: 16px; 
            font-weight: bold; 
            text-align: center; 
            color: #00003B;
            margin-bottom: 10px;
        }
        .subtitulo { 
            font-size: 14px; 
            font-weight: bold; 
            color: #5A9392;
            margin: 10px 0 5px 0;
        }
        .dados {
            font-size: 12px;
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table th {
            background-color: #5A9392;
            color: white;
            padding: 5px;
            text-align: center;
            font-size: 11px;
        }
        table td {
            padding: 5px;
            border: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
        }
        .rodape {
            font-size: 10px;
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #5A9392;
            padding-top: 5px;
        }
    </style>
    
    <div class="titulo">'.EMPRESA_NOME.'</div>
    <div style="text-align: center; margin-bottom: 15px;">Você nunca viu nada assim!</div>
    
    <div style="border-top: 2px solid #5A9392; border-bottom: 2px solid #5A9392; padding: 5px 0; margin-bottom: 15px;">
        <div class="subtitulo" style="text-align: center;">Prescrição N° '.$prescricao['PrescricaoOpticaID'].'</div>
        <div class="dados" style="text-align: center;">Data '.date('d/m/Y', strtotime($prescricao['DataEmissao'])).'</div>
    </div>
    
    <div class="dados">
        <strong>Cliente N°</strong> '.$prescricao['PacienteID'].'<br>
        '.$prescricao['PacienteNome'].'<br>
        '.EMPRESA_NOME.'<br>
        0 -
    </div>
    
    <table>
        <tr>
            <th rowspan="2">Olho Direito</th>
            <th>Est.</th>
            <th>Cil.</th>
            <th>Eixo</th>
            <th>Dnp</th>
            <th>Prisma</th>
            <th>Base</th>
            <th>Add</th>
            <th>Av</th>
        </tr>
        <tr>
            <td>'.($prescricao['OD_Esferico'] ?? '').'</td>
            <td>'.($prescricao['OD_Cilindrico'] ?? '').'</td>
            <td>'.($prescricao['OD_Eixo'] ?? '').'</td>
            <td>'.($prescricao['OD_DNP'] ?? '').'</td>
            <td>'.($prescricao['OD_LPrisma'] ?? '').'</td>
            <td>'.($prescricao['OD_Base'] ?? '').'</td>
            <td>'.($prescricao['OD_Add'] ?? '').'</td>
            <td>'.($prescricao['OD_AV'] ?? '').'</td>
        </tr>
    </table>
    
    <table>
        <tr>
            <th rowspan="2">Olho Esquerdo</th>
            <th>Est.</th>
            <th>Cil.</th>
            <th>Eixo</th>
            <th>Dnp</th>
            <th>Prisma</th>
            <th>Base</th>
            <th>Add</th>
            <th>Av</th>
        </tr>
        <tr>
            <td>'.($prescricao['OE_Esferico'] ?? '').'</td>
            <td>'.($prescricao['OE_Cilindrico'] ?? '').'</td>
            <td>'.($prescricao['OE_Eixo'] ?? '').'</td>
            <td>'.($prescricao['OE_DNP'] ?? '').'</td>
            <td>'.($prescricao['OE_Prisma'] ?? '').'</td>
            <td>'.($prescricao['OE_Base'] ?? '').'</td>
            <td>'.($prescricao['OE_Add'] ?? '').'</td>
            <td>'.($prescricao['OE_AV'] ?? '').'</td>
        </tr>
    </table>
    
    <div class="subtitulo">Observações</div>
    <div class="dados">'.nl2br($prescricao['Observacoes'] ?? '').'</div>
    <div class="dados">'.($prescricao['ProximaConsulta'] ? 'PRÓXIMA CONSULTA: '.date('d/m/Y', strtotime($prescricao['ProximaConsulta'])) : '').'</div>
    
    <div class="rodape">
        '.EMPRESA_NOME.' | Sede: '.EMPRESA_ENDERECO.'<br>
        Contribuinte nº: '.EMPRESA_CONTRIBUINTE.' | Tel.: '.EMPRESA_TELEFONE.' | '.EMPRESA_EMAIL.' | '.EMPRESA_SITE.'
    </div>
    ';
    
    // Escreve o conteúdo HTML no PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Gera o nome do arquivo com o ID da prescrição
    $filename = 'prescricao_'.$prescricao['PrescricaoOpticaID'].'.pdf';
    
    // Saída do PDF (I para visualizar no navegador)
    $pdf->Output($filename, 'I');
}

// Processamento das ações
$acao = $_GET['acao'] ?? '';

// Gerar PDF
if ($acao == 'gerar_pdf' && isset($_GET['id'])) {
    $prescricao = getPrescricaoById($db, $_GET['id']);
    if ($prescricao) {
        gerarPDF($prescricao);
        exit;
    } else {
        die("Prescrição não encontrada!");
    }
}
// Salvar prescrição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dados = [
        'PacienteID' => $_POST['PacienteID'],
        'ProfissionalID' => $_POST['ProfissionalID'] ?: null,
        'ConsultaID' => $_POST['ConsultaID'] ?: null,
        'DataEmissao' => $_POST['DataEmissao'],
        
        // Olho Direito
        'OD_Esferico' => $_POST['OD_Esferico'] ?: null,
        'OD_Cilindrico' => $_POST['OD_Cilindrico'] ?: null,
        'OD_Eixo' => $_POST['OD_Eixo'] ?: null,
        'OD_DNP' => $_POST['OD_DNP'] ?: null,
        'OD_LPrisma' => $_POST['OD_LPrisma'] ?: null,
        'OD_Base' => $_POST['OD_Base'] ?: null,
        'OD_AV' => $_POST['OD_AV'] ?: null,
        'OD_Add' => $_POST['OD_Add'] ?: null,
        'OD_Perto_DNP' => $_POST['OD_Perto_DNP'] ?: null,
        
        // Olho Esquerdo
        'OE_Esferico' => $_POST['OE_Esferico'] ?: null,
        'OE_Cilindrico' => $_POST['OE_Cilindrico'] ?: null,
        'OE_Eixo' => $_POST['OE_Eixo'] ?: null,
        'OE_DNP' => $_POST['OE_DNP'] ?: null,
        'OE_Prisma' => $_POST['OE_Prisma'] ?: null,
        'OE_Base' => $_POST['OE_Base'] ?: null,
        'OE_AV' => $_POST['OE_AV'] ?: null,
        'OE_Add' => $_POST['OE_Add'] ?: null,
        'OE_Perto_DNP' => $_POST['OE_Perto_DNP'] ?: null,
        
        // Observações
        'Observacoes' => $_POST['Observacoes'] ?: null,
        'ProximaConsulta' => $_POST['ProximaConsulta'] ?: null
    ];
    
    if (salvarPrescricao($db, $dados)) {
        $mensagem = "Prescrição salva com sucesso!";
    } else {
        $erro = "Erro ao salvar prescrição.";
    }
}

// Filtros para listagem
$filtros = [];
if (isset($_GET['paciente'])) $filtros['paciente'] = $_GET['paciente'];
if (isset($_GET['data'])) $filtros['data'] = $_GET['data'];

$prescricoes = getPrescricoes($db, $filtros);
$pacientes = getPacientes($db);
$profissionais = getProfissionais($db);
$consultas = getConsultas($db);

// ==============================================
// INTERFACE HTML
// ==============================================
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescrição Óptica - VisioGest</title>
    <style>
        :root {
            --verde-primario: #5A9392;
            --verde-secundario: #7AB2B1;
            --verde-claro: #E8F4F4;
            --azul-escuro: #00003B;
            --azul-claro: #3B82F6;
            --cinza-claro: #F3F4F6;
            --branco: #FFFFFF;
            --cinza-borda: #D1D5DB;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--cinza-claro);
            margin: 0;
            padding: 0;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background-color: var(--azul-escuro);
            color: white;
            padding: 15px 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .card {
            background-color: var(--branco);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .section-title {
            background-color: var(--verde-primario);
            color: white;
            padding: 10px 15px;
            margin: -20px -20px 20px -20px;
            font-size: 18px;
            border-radius: 0;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px 10px;
            align-items: flex-end;
        }
        
        .form-group {
            flex: 1 0 200px;
            margin: 0 10px 15px;
            min-width: 0;
        }
        
        .form-group-small {
            flex: 0 0 120px;
        }
        
        .form-group-medium {
            flex: 0 0 180px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--azul-escuro);
            font-size: 14px;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--cinza-borda);
            border-radius: 4px;
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
            height: 38px;
        }
        
        textarea {
            height: auto;
            min-height: 80px;
        }
        
        input:focus, select:focus, textarea:focus {
            border-color: var(--verde-primario);
            outline: none;
            box-shadow: 0 0 0 2px rgba(90, 147, 146, 0.2);
        }
        
        .eye-section {
            background-color: var(--verde-claro);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid rgba(90, 147, 146, 0.3);
        }
        
        .eye-title {
            color: var(--azul-escuro);
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            font-size: 16px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 14px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-primary {
            background-color: var(--verde-primario);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--verde-secundario);
        }
        
        .btn-secondary {
            background-color: var(--azul-escuro);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #000052;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .prescriptions-list {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .prescriptions-list th, .prescriptions-list td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--cinza-borda);
        }
        
        .prescriptions-list th {
            background-color: var(--azul-escuro);
            color: white;
            font-weight: 600;
        }
        
        .prescriptions-list tr:nth-child(even) {
            background-color: rgba(90, 147, 146, 0.05);
        }
        
        .prescriptions-list tr:hover {
            background-color: rgba(90, 147, 146, 0.1);
        }
        
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            transition: all 0.2s;
        }
        
        .view-btn {
            background-color: var(--azul-escuro);
            color: white;
        }
        
        .view-btn:hover {
            background-color: #000052;
        }
        
        .print-btn {
            background-color: var(--verde-primario);
            color: white;
        }
        
        .print-btn:hover {
            background-color: var(--verde-secundario);
        }
        
        .tab-container {
            margin-bottom: 20px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--cinza-borda);
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            background-color: #f1f1f1;
            border: 1px solid var(--cinza-borda);
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .tab.active {
            background-color: var(--branco);
            border-bottom: 1px solid var(--branco);
            color: var(--azul-escuro);
            font-weight: 600;
            position: relative;
            top: 1px;
            border-color: var(--verde-primario);
        }
        
        .tab-content {
            display: none;
            padding: 20px;
            background-color: var(--branco);
            border: 1px solid var(--cinza-borda);
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .search-container {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6B7280;
        }
        
        .grid-2-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid-2-col {
                grid-template-columns: 1fr;
            }
            
            .form-group {
                flex: 1 0 100%;
            }
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>Prescrição Óptica - VisioGest</h1>
                <div class="btn-group">
                    <button class="btn btn-secondary" onclick="document.querySelector('.tab[data-tab=\"lista\"]').click()">
                        <i class="fas fa-list"></i> Listar Prescrições
                    </button>
                </div>
            </div>
            
            <div class="tab-container">
                <div class="tabs">
                    <div class="tab active" data-tab="cadastro" onclick="switchTab('cadastro')">Nova Prescrição</div>
                    <div class="tab" data-tab="lista" onclick="switchTab('lista')">Prescrições Realizadas</div>
                </div>
                
                <div id="cadastro" class="tab-content active">
                    <div class="card-body">
                        <?php if (isset($mensagem)): ?>
                            <div class="alert alert-success"><?= $mensagem ?></div>
                        <?php endif; ?>
                        
                        <?php if (isset($erro)): ?>
                            <div class="alert alert-danger"><?= $erro ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" id="prescricaoForm">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="PacienteID">Paciente *</label>
                                    <select id="PacienteID" name="PacienteID" required>
                                        <option value="">Selecione um paciente</option>
                                        <?php foreach ($pacientes as $paciente): ?>
                                            <option value="<?= $paciente['PacienteID'] ?>"><?= htmlspecialchars($paciente['NomeCompleto']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="ProfissionalID">Profissional</label>
                                    <select id="ProfissionalID" name="ProfissionalID">
                                        <option value="">Selecione um profissional</option>
                                        <?php foreach ($profissionais as $profissional): ?>
                                            <option value="<?= $profissional['ProfissionalID'] ?>"><?= htmlspecialchars($profissional['NomeCompleto']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="ConsultaID">Consulta</label>
                                    <select id="ConsultaID" name="ConsultaID">
                                        <option value="">Selecione uma consulta</option>
                                        <?php foreach ($consultas as $consulta): ?>
                                            <option value="<?= $consulta['ConsultaID'] ?>" data-paciente="<?= $consulta['PacienteID'] ?>">
                                                #<?= $consulta['ConsultaID'] ?> - <?= htmlspecialchars($consulta['NomeCompleto']) ?> (<?= date('d/m/Y', strtotime($consulta['DataConsulta'])) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group form-group-medium">
                                    <label for="DataEmissao">Data de Emissão *</label>
                                    <input type="date" id="DataEmissao" name="DataEmissao" required value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                            
                            <div class="grid-2-col">
                                <div>
                                    <div class="section-title">Olho Direito (OD) - Longe</div>
                                    <div class="eye-section">
                                        <div class="form-row">
                                            <div class="form-group form-group-small">
                                                <label for="OD_Esferico">Esférico</label>
                                                <input type="number" step="0.25" id="OD_Esferico" name="OD_Esferico" placeholder="Ex: -2.50">
                                            </div>
                                            
                                            <div class="form-group form-group-small">
                                                <label for="OD_Cilindrico">Cilíndrico</label>
                                                <input type="number" step="0.25" id="OD_Cilindrico" name="OD_Cilindrico" placeholder="Ex: -1.00">
                                            </div>
                                            
                                            <div class="form-group form-group-small">
                                                <label for="OD_Eixo">Eixo</label>
                                                <input type="number" min="0" max="180" id="OD_Eixo" name="OD_Eixo" placeholder="Ex: 120">
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group form-group-small">
                                                <label for="OD_DNP">DNP</label>
                                                <input type="number" step="0.1" id="OD_DNP" name="OD_DNP" placeholder="Ex: 12.5">
                                            </div>
                                            
                                            <div class="form-group form-group-small">
                                                <label for="OD_LPrisma">Prisma</label>
                                                <input type="number" step="0.25" id="OD_LPrisma" name="OD_LPrisma" placeholder="Ex: 2.00">
                                            </div>
                                            
                                            <div class="form-group form-group-small">
                                                <label for="OD_Base">Base</label>
                                                <select id="OD_Base" name="OD_Base">
                                                    <option value="">Selecione</option>
                                                    <option value="BI">BI - Base In</option>
                                                    <option value="BO">BO - Base Out</option>
                                                    <option value="BU">BU - Base Up</option>
                                                    <option value="BD">BD - Base Down</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group form-group-small">
                                                <label for="OD_AV">Acuidade Visual</label>
                                                <input type="text" id="OD_AV" name="OD_AV" placeholder="Ex: 12/10">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="section-title">Adição para Perto/Intermediário</div>
                                    <div class="eye-section">
                                        <div class="form-row">
                                            <div class="form-group form-group-small">
                                                <label for="OD_Add">OD Adição</label>
                                                <input type="number" step="0.25" id="OD_Add" name="OD_Add" placeholder="Ex: +1.50">
                                            </div>
                                            
                                            <div class="form-group form-group-small">
                                                <label for="OD_Perto_DNP">OD Perto DNP</label>
                                                <input type="number" step="0.1" id="OD_Perto_DNP" name="OD_Perto_DNP" placeholder="Ex: 12.5">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="section-title">Olho Esquerdo (OE) - Longe</div>
                                    <div class="eye-section">
                                        <div class="form-row">
                                            <div class="form-group form-group-small">
                                                <label for="OE_Esferico">Esférico</label>
                                                <input type="number" step="0.25" id="OE_Esferico" name="OE_Esferico" placeholder="Ex: -2.50">
                                            </div>
                                            
                                            <div class="form-group form-group-small">
                                                <label for="OE_Cilindrico">Cilíndrico</label>
                                                <input type="number" step="0.25" id="OE_Cilindrico" name="OE_Cilindrico" placeholder="Ex: -1.00">
                                            </div>
                                            
                                            <div class="form-group form-group-small">
                                                <label for="OE_Eixo">Eixo</label>
                                                <input type="number" min="0" max="180" id="OE_Eixo" name="OE_Eixo" placeholder="Ex: 60">
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group form-group-small">
                                                <label for="OE_DNP">DNP</label>
                                                <input type="number" step="0.1" id="OE_DNP" name="OE_DNP" placeholder="Ex: 12.5">
                                            </div>
                                            
                                            <div class="form-group form-group-small">
                                                <label for="OE_Prisma">Prisma</label>
                                                <input type="number" step="0.25" id="OE_Prisma" name="OE_Prisma" placeholder="Ex: 2.00">
                                            </div>
                                            
                                            <div class="form-group form-group-small">
                                                <label for="OE_Base">Base</label>
                                                <select id="OE_Base" name="OE_Base">
                                                    <option value="">Selecione</option>
                                                    <option value="BI">BI - Base In</option>
                                                    <option value="BO">BO - Base Out</option>
                                                    <option value="BU">BU - Base Up</option>
                                                    <option value="BD">BD - Base Down</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group form-group-small">
                                                <label for="OE_AV">Acuidade Visual</label>
                                                <input type="text" id="OE_AV" name="OE_AV" placeholder="Ex: 20/20">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="section-title">Adição para Perto/Intermediário</div>
                                    <div class="eye-section">
                                        <div class="form-row">
                                            <div class="form-group form-group-small">
                                                <label for="OE_Add">OE Adição</label>
                                                <input type="number" step="0.25" id="OE_Add" name="OE_Add" placeholder="Ex: +1.50">
                                            </div>
                                            
                                            <div class="form-group form-group-small">
                                                <label for="OE_Perto_DNP">OE Perto DNP</label>
                                                <input type="number" step="0.1" id="OE_Perto_DNP" name="OE_Perto_DNP" placeholder="Ex: 12.5">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="Observacoes">Observações</label>
                                    <textarea id="Observacoes" name="Observacoes" rows="3" placeholder="Ex: LENTES MONOFOCAIS FOTOCROMÁTICAS COM ANTIREFLEXO"></textarea>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group form-group-medium">
                                    <label for="ProximaConsulta">Próxima Consulta</label>
                                    <input type="text" id="ProximaConsulta" name="ProximaConsulta" placeholder="Ex: PRÓXIMA CONSULTA ABRIL / 2026">
                                </div>
                            </div>
                            
                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Salvar Prescrição
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="document.getElementById('prescricaoForm').reset()">
                                    <i class="fas fa-broom"></i> Limpar Formulário
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div id="lista" class="tab-content">
                    <div class="card-body">
                        <form method="GET" class="form-row">
                            <input type="hidden" name="acao" value="listar">
                            
                            <div class="form-group">
                                <label for="filtroPaciente">Filtrar por Paciente</label>
                                <select id="filtroPaciente" name="paciente">
                                    <option value="">Todos os pacientes</option>
                                    <?php foreach ($pacientes as $paciente): ?>
                                        <option value="<?= $paciente['PacienteID'] ?>" <?= isset($_GET['paciente']) && $_GET['paciente'] == $paciente['PacienteID'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($paciente['NomeCompleto']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group form-group-medium">
                                <label for="filtroData">Filtrar por Data</label>
                                <input type="date" id="filtroData" name="data" value="<?= $_GET['data'] ?? '' ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Filtrar
                                </button>
                                <a href="?" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Limpar
                                </a>
                            </div>
                        </form>
                        
                        <table class="prescriptions-list">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Paciente</th>
                                    <th>Profissional</th>
                                    <th>Data</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($prescricoes)): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">Nenhuma prescrição encontrada</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($prescricoes as $prescricao): ?>
                                        <tr>
                                            <td><?= $prescricao['PrescricaoOpticaID'] ?></td>
                                            <td><?= htmlspecialchars($prescricao['PacienteNome']) ?></td>
                                            <td><?= htmlspecialchars($prescricao['ProfissionalNome'] ?? 'Não informado') ?></td>
                                            <td><?= date('d/m/Y', strtotime($prescricao['DataEmissao'])) ?></td>
                                            <td>
                                                <a href="?acao=gerar_pdf&id=<?= $prescricao['PrescricaoOpticaID'] ?>" class="action-btn print-btn">
                                                    <i class="fas fa-file-pdf"></i> Gerar PDF
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Alternar entre abas
        function switchTab(tabId) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.toggle('active', tab.getAttribute('data-tab') === tabId);
            });
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.toggle('active', content.id === tabId);
            });
        }
        
        // Atualizar consultas quando selecionar um paciente
        document.getElementById('PacienteID').addEventListener('change', function() {
            const pacienteId = this.value;
            const consultaSelect = document.getElementById('ConsultaID');
            
            Array.from(consultaSelect.options).forEach(option => {
                if (option.value === '') return;
                
                const show = option.getAttribute('data-paciente') === pacienteId || pacienteId === '';
                option.style.display = show ? '' : 'none';
                
                if (!show && option.selected) {
                    option.selected = false;
                    consultaSelect.options[0].selected = true;
                }
            });
        });
        
        // Se houver parâmetro na URL para mostrar a lista, mudar para a aba correta
        if (window.location.search.includes('acao=listar')) {
            switchTab('lista');
        }
    </script>
</body>
</html>