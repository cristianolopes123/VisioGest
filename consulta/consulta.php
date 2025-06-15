<?php
session_start();

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'BD_vISIO';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

// Verificação de autenticação
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$agendamento_id = $_GET['agendamento_id'] ?? 0;

// Buscar agendamentos disponíveis
$stmt = $pdo->query("
    SELECT a.AgendamentoID, p.NomeCompleto, a.DataHoraInicio 
    FROM tb_agendamento a
    JOIN tb_pacientes p ON a.PacienteID = p.PacienteID
    WHERE a.StatusAgendamento = 'Agendado' OR a.StatusAgendamento = 'Realizado'
    ORDER BY a.DataHoraInicio DESC
");
$agendamentos_disponiveis = $stmt->fetchAll();

// Buscar dados da consulta se existir
$consulta = [];
if($agendamento_id) {
    $stmt = $pdo->prepare("SELECT * FROM tb_consulta WHERE AgendamentoID = ?");
    $stmt->execute([$agendamento_id]);
    $consulta = $stmt->fetch();
}

// Busca dados do agendamento e paciente
$agendamento = [];
$paciente = [];
$profissional = [];

if($agendamento_id) {
    try {
        $stmt = $pdo->prepare("SELECT a.*, p.*, pf.* 
                             FROM tb_agendamento a
                             JOIN tb_pacientes p ON a.PacienteID = p.PacienteID
                             JOIN tb_profissional pf ON a.ProfissionalID = pf.ProfissionalID
                             WHERE a.AgendamentoID = ?");
        $stmt->execute([$agendamento_id]);
        $result = $stmt->fetch();
        
        if($result) {
            $agendamento = $result;
            $paciente = [
                'nome' => $result['NomeCompleto'],
                'cpf' => $result['CPF'],
                'data_nascimento' => $result['DataNascimento']
            ];
            $profissional = [
                'nome' => $result['NomeProfissional'],
                'especialidade' => $result['Especialidade']
            ];
        }
    } catch (PDOException $e) {
        $error = "Erro ao carregar agendamento: " . $e->getMessage();
    }
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $agendamento_id = $_POST['agendamento_selecionado'] ?? $agendamento_id;
    
    // Primeiro verifica se o agendamento existe
    try {
        $stmt = $pdo->prepare("SELECT AgendamentoID FROM tb_agendamento WHERE AgendamentoID = ?");
        $stmt->execute([$agendamento_id]);
        $agendamento_existe = $stmt->fetch();
        
        if(!$agendamento_existe) {
            throw new Exception("Agendamento não encontrado!");
        }
        
   $dados = [
    'AgendamentoID' => $agendamento_id,
    'DataHoraRealizacao' => $_POST['data_hora_realizacao'] ?? date('Y-m-d H:i:s'),
    'QueixaPrincipal' => $_POST['queixa_principal'] ?? '',
    'HistoricoDoencaAtual' => $_POST['historico_doenca'] ?? '',
    'HistoricoMedicoGeral' => $_POST['historico_medico'] ?? '',
    'ExamesOftalmologicos' => $_POST['exames_oftalmologicos'] ?? '',
    'ObservacoesGerais' => $_POST['observacoes'] ?? '',
    'DataProximaConsulta' => $_POST['proxima_consulta'] ?? null,
    'CustoConsulta' => $_POST['custo_consulta'] ?? null
];
        
        // Verifica se já existe consulta para este agendamento
        $stmt = $pdo->prepare("SELECT * FROM tb_consulta WHERE AgendamentoID = ?");
        $stmt->execute([$agendamento_id]);
        
        if($stmt->rowCount() > 0) {
            // Atualiza consulta existente
            $cols = [];
            foreach($dados as $key => $value) {
                if($key != 'AgendamentoID') {
                    $cols[] = "$key = :$key";
                }
            }
            
            $query = "UPDATE tb_consulta SET " . implode(", ", $cols) . " WHERE AgendamentoID = :AgendamentoID";
            $stmt = $pdo->prepare($query);
            $stmt->execute($dados);
            
            $success = "Consulta atualizada com sucesso!";
        } else {
            // Insere nova consulta
            $cols = implode(", ", array_keys($dados));
            $vals = ":" . implode(", :", array_keys($dados));
            
            $query = "INSERT INTO tb_consulta ($cols) VALUES ($vals)";
            $stmt = $pdo->prepare($query);
            $stmt->execute($dados);
            
            // Atualiza status do agendamento
            $pdo->prepare("UPDATE tb_agendamento SET StatusAgendamento = 'Realizado' WHERE AgendamentoID = ?")
               ->execute([$agendamento_id]);
            
            $success = "Consulta registrada com sucesso!";
        }
        
        // Recarrega os dados da consulta
        $stmt = $pdo->prepare("SELECT * FROM tb_consulta WHERE AgendamentoID = ?");
        $stmt->execute([$agendamento_id]);
        $consulta = $stmt->fetch();
        
    } catch (PDOException $e) {
        $error = "Erro ao salvar consulta: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta Oftalmológica | OptiClinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary-color: #1E3A5F;
            --secondary-color: #5A9392;
            --light-color: #F5F7FA;
            --dark-color: #333333;
            --success-color: #4CAF50;
            --danger-color: #F44336;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--light-color);
            color: var(--dark-color);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .card-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
        }
        
        .patient-info {
            background-color: rgba(94, 147, 146, 0.1);
            border-left: 4px solid var(--secondary-color);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            color: var(--primary-color);
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        
        .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding: 0 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 12px 25px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #142a47;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #4a7d7b;
        }
        
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background-color: rgba(76, 175, 80, 0.2);
            border-left: 4px solid var(--success-color);
            color: #2e7d32;
        }
        
        .alert-danger {
            background-color: rgba(244, 67, 54, 0.2);
            border-left: 4px solid var(--danger-color);
            color: #c62828;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 20px;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .select-agendamento {
            margin-bottom: 20px;
        }
        
        .select-agendamento select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            background-color: white;
        }
        
        .btn-refresh {
            margin-left: 10px;
            padding: 10px 15px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .col-md-6 {
                flex: 0 0 100%;
                max-width: 100%;
                margin-bottom: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">
                    <i class="fas fa-file-medical"></i> Ficha de Consulta Oftalmológica
                </h1>
                <a href="lista_consultas.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
            
            <?php if(isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="consultaForm">
                <div class="select-agendamento">
                    <label for="agendamento_selecionado" class="form-label">Selecione o Agendamento:</label>
                    <div style="display: flex; align-items: center;">
                        <select name="agendamento_selecionado" id="agendamento_selecionado" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Selecione um agendamento --</option>
                            <?php foreach($agendamentos_disponiveis as $agend): ?>
                                <option value="<?= $agend['AgendamentoID'] ?>" 
                                    <?= ($agend['AgendamentoID'] == $agendamento_id) ? 'selected' : '' ?>>
                                    #<?= $agend['AgendamentoID'] ?> - <?= htmlspecialchars($agend['NomeCompleto']) ?> 
                                    (<?= date('d/m/Y H:i', strtotime($agend['DataHoraInicio'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-secondary btn-refresh" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                
                <input type="hidden" name="agendamento_id" value="<?= htmlspecialchars($agendamento_id) ?>">
                
                <?php if(!empty($paciente)): ?>
                    <div class="patient-info">
                        <h3 style="color: var(--primary-color); margin-bottom: 10px;">
                            <i class="fas fa-user"></i> <?= htmlspecialchars($paciente['nome']) ?>
                        </h3>
                        <p><strong>CPF:</strong> <?= htmlspecialchars($paciente['cpf']) ?></p>
                        <p><strong>Data Nasc.:</strong> <?= date('d/m/Y', strtotime($paciente['data_nascimento'])) ?></p>
                        <?php if(!empty($profissional)): ?>
                            <p><strong>Profissional:</strong> <?= htmlspecialchars($profissional['nome']) ?> (<?= htmlspecialchars($profissional['especialidade']) ?>)</p>
                        <?php endif; ?>
                    </div>
                
                    <div class="form-section">
                        <h3 class="section-title">Dados da Consulta</h3>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="data_hora_realizacao" class="form-label">Data/Hora da Realização *</label>
                                    <input type="datetime-local" name="data_hora_realizacao" id="data_hora_realizacao" class="form-control" 
                                           value="<?= !empty($consulta['DataHoraRealizacao']) ? date('Y-m-d\TH:i', strtotime($consulta['DataHoraRealizacao'])) : date('Y-m-d\TH:i') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="custo_consulta" class="form-label">Custo da Consulta (R$)</label>
                                    <input type="number" step="0.01" name="custo_consulta" id="custo_consulta" class="form-control" 
                                           value="<?= htmlspecialchars($consulta['CustoConsulta'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">Dados Clínicos</h3>
                        
                        <div class="form-group">
                            <label for="queixa_principal" class="form-label">Queixa Principal *</label>
                            <textarea name="queixa_principal" id="queixa_principal" class="form-control" required><?= htmlspecialchars($consulta['QueixaPrincipal'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="historico_doenca" class="form-label">Histórico da Doença Atual</label>
                            <textarea name="historico_doenca" id="historico_doenca" class="form-control"><?= htmlspecialchars($consulta['HistoricoDoencaAtual'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="historico_medico" class="form-label">Histórico Médico Geral</label>
                            <textarea name="historico_medico" id="historico_medico" class="form-control"><?= htmlspecialchars($consulta['HistoricoMedicoGeral'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">Exames Oftalmológicos</h3>
                        
                        <div class="form-group">
                            <label for="exames_oftalmologicos" class="form-label">Resultados dos Exames</label>
                            <textarea name="exames_oftalmologicos" id="exames_oftalmologicos" class="form-control"><?= htmlspecialchars($consulta['ExamesOftalmologicos'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <h3 class="section-title">Encaminhamento</h3>
                        
                        <div class="form-group">
                            <label for="observacoes" class="form-label">Observações Gerais</label>
                            <textarea name="observacoes" id="observacoes" class="form-control"><?= htmlspecialchars($consulta['ObservacoesGerais'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="proxima_consulta" class="form-label">Próxima Consulta (Opcional)</label>
                            <input type="datetime-local" name="proxima_consulta" id="proxima_consulta" class="form-control" 
                                   value="<?= !empty($consulta['DataProximaConsulta']) ? date('Y-m-d\TH:i', strtotime($consulta['DataProximaConsulta'])) : '' ?>">
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar Consulta
                        </button>
                        
                        <a href="../consulta/prescricao.php?agendamento_id=<?= $agendamento_id ?>" class="btn btn-secondary">
                            <i class="fas fa-file-prescription"></i> Prescrever Medicação
                        </a>
                        
                        <?php if(!empty($consulta)): ?>
                            <a href="lista_consultas.php" class="btn btn-secondary">
                                <i class="fas fa-list"></i> Voltar para Lista
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>
    <script>
        // Datepicker para data/hora
        flatpickr("#data_hora_realizacao", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            locale: "pt",
            time_24hr: true
        });
        
        flatpickr("#proxima_consulta", {
            enableTime: true,
            dateFormat: "Y-m-d H:i",
            minDate: "today",
            locale: "pt",
            time_24hr: true
        });
        
        // Validação do formulário
        document.getElementById('consultaForm').addEventListener('submit', function(e) {
            const queixa = document.getElementById('queixa_principal').value.trim();
            const dataHora = document.getElementById('data_hora_realizacao').value.trim();
            
            if(!queixa) {
                e.preventDefault();
                alert('Por favor, preencha a Queixa Principal');
                return;
            }
            
            if(!dataHora) {
                e.preventDefault();
                alert('Por favor, informe a Data/Hora da Realização');
            }
        });
    </script>
</body>
</html>