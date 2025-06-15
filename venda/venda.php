<?php
session_start();
require_once('../conexao.php');

// Verificar se usuário está logado
//if(!isset($_SESSION['loggedin']) {
   // header("Location: login.php");
   // exit;
//}

// Funções úteis
function formatarMoeda($valor) {
    return number_format($valor, 2, ',', '.');
}

// Processar formulário de venda
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['finalizar_venda'])) {
        // Processar finalização da venda
        $pacienteID = $_POST['paciente'];
        $vendedorID = $_POST['vendedor'];
        $formaPagamento = $_POST['forma_pagamento'];
        $descontoTotal = str_replace(['.', ','], ['', '.'], $_POST['desconto_total']);
        $observacoes = $_POST['observacoes'];
        $itens = json_decode($_POST['itens_json'], true);
        
        // Calcular total
        $total = 0;
        foreach($itens as $item) {
            $subtotal = ($item['preco'] * $item['quantidade']) - $item['desconto'];
            $total += $subtotal;
        }
        $total -= $descontoTotal;
        
        // Inserir venda
        $stmt = $conn->prepare("INSERT INTO tb_venda (PacienteID, VendedorID, ValorTotal, DescontoTotal, FormaPagamento, Observacoes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiddss", $pacienteID, $vendedorID, $total, $descontoTotal, $formaPagamento, $observacoes);
        $stmt->execute();
        $vendaID = $stmt->insert_id;
        $stmt->close();
        
        // Inserir itens e atualizar estoque
        foreach($itens as $item) {
            $subtotal = ($item['preco'] * $item['quantidade']) - $item['desconto'];
            
            $stmt = $conn->prepare("INSERT INTO tb_item_venda (VendaID, ProdutoID, Quantidade, PrecoUnitario, DescontoItem, SubTotal, DescricaoItem) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiddds", $vendaID, $item['id'], $item['quantidade'], $item['preco'], $item['desconto'], $subtotal, $item['descricao']);
            $stmt->execute();
            $stmt->close();
            
            // Atualizar estoque
            $stmt = $conn->prepare("UPDATE tb_produto SET EstoqueAtual = EstoqueAtual - ? WHERE ProdutoID = ?");
            $stmt->bind_param("ii", $item['quantidade'], $item['id']);
            $stmt->execute();
            $stmt->close();
        }
        
        // Retornar ID da venda para gerar fatura
        $_SESSION['venda_gerada'] = $vendaID;
        echo json_encode(['success' => true, 'vendaID' => $vendaID]);
        exit;
    }
    
    // Buscar pacientes
    if(isset($_POST['buscar_paciente'])) {
        $termo = "%{$_POST['termo']}%";
        $stmt = $conn->prepare("SELECT PacienteID, NomeCompleto FROM tb_pacientes WHERE NomeCompleto LIKE ? LIMIT 10");
        $stmt->bind_param("s", $termo);
        $stmt->execute();
        $result = $stmt->get_result();
        $pacientes = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($pacientes);
        exit;
    }
    
    // Buscar vendedores
    if(isset($_POST['buscar_vendedor'])) {
        $termo = "%{$_POST['termo']}%";
        $stmt = $conn->prepare("SELECT VendedorID, NomeCompleto FROM tb_vendedor_otico WHERE NomeCompleto LIKE ? LIMIT 10");
        $stmt->bind_param("s", $termo);
        $stmt->execute();
        $result = $stmt->get_result();
        $vendedores = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($vendedores);
        exit;
    }
    
    // Buscar produtos
    if(isset($_POST['buscar_produto'])) {
        $termo = "%{$_POST['termo']}%";
        $stmt = $conn->prepare("SELECT ProdutoID, NomeProduto, Descricao, PrecoVenda, EstoqueAtual FROM tb_produto WHERE NomeProduto LIKE ? OR Descricao LIKE ? LIMIT 10");
        $stmt->bind_param("ss", $termo, $termo);
        $stmt->execute();
        $result = $stmt->get_result();
        $produtos = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($produtos);
        exit;
    }
}

// Se foi solicitado gerar fatura
if(isset($_GET['gerar_fatura']) && isset($_SESSION['venda_gerada'])) {
    // Limpar qualquer buffer de saída
    ob_clean();
    ob_start();
    
    require_once('../tcpdf/tcpdf.php');
    
    // Criar novo documento PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Configurações do documento
    $pdf->SetCreator('VisioGest');
    $pdf->SetAuthor('VisioGest');
    $pdf->SetTitle('Fatura #' . $_SESSION['venda_gerada']);
    $pdf->SetSubject('Fatura de Venda');
    
    // Remover headers e footers padrão
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Adicionar uma página
    $pdf->AddPage();
    
    // Buscar dados da venda
    $sql = "SELECT v.*, p.NomeCompleto AS NomePaciente, p.Telefone, p.Endereco AS Morada, vo.NomeCompleto AS NomeVendedor 
           FROM tb_venda v
           JOIN tb_pacientes p ON v.PacienteID = p.PacienteID
           JOIN tb_vendedor_otico vo ON v.VendedorID = vo.VendedorID
           WHERE v.VendaID = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Erro na preparação da consulta: " . $conn->error);
    }
    $stmt->bind_param("i", $_SESSION['venda_gerada']);
    if (!$stmt->execute()) {
        die("Erro na execução da consulta: " . $stmt->error);
    }
    $result = $stmt->get_result();
    $venda = $result->fetch_assoc();
    $stmt->close();

    // Buscar itens da venda com descrição detalhada
    $stmt = $conn->prepare("SELECT iv.*, p.NomeProduto, p.Descricao AS DescricaoProduto
                           FROM tb_item_venda iv
                           JOIN tb_produto p ON iv.ProdutoID = p.ProdutoID
                           WHERE iv.VendaID = ?");
    $stmt->bind_param("i", $_SESSION['venda_gerada']);
    $stmt->execute();
    $result = $stmt->get_result();
    $itens = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Incluir o logo
    $logo = '../img/Visio_Gest.png';
    
    // HTML da fatura
    $html = '
    <style>
        .header { 
            color: #00003B; 
            font-size: 16px; 
            font-weight: bold; 
            margin-bottom: 10px; 
            text-align: center;
        }
        .titulo { 
            color: #00003B; 
            font-size: 24px; 
            font-weight: bold; 
            text-align: center; 
            margin-bottom: 15px; 
            text-transform: uppercase;
        }
        .dados-empresa {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 15px;
        }
        .dados-cliente { 
            margin-bottom: 15px; 
            border: 1px solid #ddd;
            padding: 10px;
            background-color: #f9f9f9;
            font-size: 12px;
        }
        .dados-cliente strong { 
            color: #00003B; 
        }
        .tabela-itens { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 20px; 
            font-size: 10px;
        }
        .tabela-itens th { 
            background-color: #5A9392; 
            color: white; 
            padding: 8px; 
            text-align: center; 
            font-weight: bold;
        }
        .tabela-itens td { 
            padding: 8px; 
            border: 1px solid #ddd; 
            text-align: center;
            vertical-align: top;
        }
        .descricao-item {
            text-align: left;
            font-size: 9px;
            color: #555;
        }
        .tabela-totais { 
            width: 50%; 
            margin-left: auto; 
            border-collapse: collapse;
            font-size: 12px;
            margin-top: 30px;
        }
        .tabela-totais td { 
            padding: 10px; 
            border: 1px solid #ddd;
        }
        .total-geral { 
            font-weight: bold; 
            color: #00003B; 
            font-size: 14px; 
            background-color: #f0f0f0;
        }
        .footer { 
            margin-top: 40px; 
            font-size: 10px; 
            text-align: center; 
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            line-height: 1.6;
        }
        .info-venda {
            text-align: right;
            margin-bottom: 15px;
            font-size: 12px;
        }
        .consumidor-final {
            text-align: center;
            font-weight: bold;
            margin: 15px 0;
            color: #5A9392;
            font-size: 14px;
        }
    </style>
    
    <div class="dados-empresa">
        <img src="HomePage/Visio_Gest.png" alt="VisioGest Logo" style="height: 60px; margin-bottom: 10px;"><br>
        <div style="font-size: 20px; font-weight: bold; margin-bottom: 5px;">VISIOGEST</div>
        <div style="font-size: 16px; margin-bottom: 15px;">Sistema de Gestão Óptica</div>
    </div>
    
    <div class="info-venda">
        <strong>FATURA Nº:</strong> ' . $venda['VendaID'] . '<br>
        <strong>DATA:</strong> ' . date('d/m/Y H:i', strtotime($venda['DataVenda'])) . '<br>
        <strong>VENDEDOR:</strong> ' . $venda['NomeVendedor'] . '
    </div>
    
    <div class="dados-cliente">
        <div><strong>Nº de Cliente:</strong> ' . $venda['PacienteID'] . '</div>
        <div><strong>Nome:</strong> ' . $venda['NomePaciente'] . '</div>
        <div><strong>Telefone:</strong> ' . $venda['Telefone'] . '</div>
        <div><strong>Endereço:</strong> ' . $venda['Morada'] . '</div>
    </div>
    
    <div class="consumidor-final">* CONSUMIDOR FINAL *</div>
    
    <table class="tabela-itens">
        <tr>
            <th width="5%">Item</th>
            <th width="35%">Descrição</th>
            <th width="8%">Qtd.</th>
            <th width="12%">Pr. Unit</th>
            <th width="12%">Desc%</th>
            <th width="12%">Subtotal</th>
            <th width="16%">Total</th>
        </tr>';
    
    $contador = 1;
    foreach($itens as $item) {
        $html .= '
        <tr>
            <td>' . $contador++ . '</td>
            <td>
                <strong>' . $item['NomeProduto'] . '</strong><br>
                <div class="descricao-item">' . $item['DescricaoProduto'] . '</div>
                ' . (!empty($item['DescricaoItem']) ? '<div class="descricao-item">Obs: ' . $item['DescricaoItem'] . '</div>' : '') . '
            </td>
            <td>' . $item['Quantidade'] . '</td>
            <td>' . formatarMoeda($item['PrecoUnitario']) . '</td>
            <td>' . ($item['DescontoItem'] > 0 ? formatarMoeda($item['DescontoItem']) : '0.00') . '</td>
            <td>' . formatarMoeda($item['SubTotal']) . '</td>
            <td>' . formatarMoeda($item['SubTotal']) . '</td>
        </tr>';
    }
    
    $html .= '
    </table>
    
    <table class="tabela-totais">
        <tr class="total-geral">
            <td width="70%">TOTAL BRUTO></td>
            <td width="30%">' . formatarMoeda($venda['ValorTotal'] + $venda['DescontoTotal']) . '</td>
        </tr>
        <tr>
            <td>DESCONTO TOTAL</td>
            <td>' . formatarMoeda($venda['DescontoTotal']) . '</td>
        </tr>
        <tr class="total-geral">
            <td>TOTAL PAGO</td>
            <td>' . formatarMoeda($venda['ValorTotal']) . '</td>
        </tr>
    </table>
    
    <div class="footer">
        <strong>VISIOGEST - SISTEMA DE GESTÃO ÓPTICA</strong><br>
        Av. Ho Chi-Minh nº 352-358, 26 Pião, Luanda - Angola<br>
        Tel.: +244 923 190 900 | Email: geral@visiogest.com | Website: www.visiogest.com<br><br>
        Todos os artigos têm garantia de 1 ano contra defeitos de fabrico, não cobre má utilização.
    </div>';
    
    // Escrever HTML no PDF
    $pdf->writeHTML($html, true, false, true, false, '');
    
    // Limpar buffer e enviar cabeçalhos
    ob_end_clean();
    
    // Gerar PDF e enviar para o navegador
    $pdf->Output('fatura_' . $_SESSION['venda_gerada'] . '.pdf', 'I');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Venda - VisioGest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --verde-principal: #5A9392;
            --azul-escuro: #00003B;
            --branco: #FFFFFF;
            --cinza-claro: #F5F7FA;
            --cinza-escuro: #333333;
            --destaque: #FF7E5F;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--cinza-escuro);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--azul-escuro);
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background-color: var(--verde-principal);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: 600;
        }
        
        .btn-primary {
            background-color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        .btn-primary:hover {
            background-color: #4a7b7a;
            border-color: #4a7b7a;
        }
        
        .btn-outline-primary {
            color: var(--verde-principal);
            border-color: var(--verde-principal);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--verde-principal);
            color: white;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--verde-principal);
            box-shadow: 0 0 0 0.25rem rgba(90, 147, 146, 0.25);
        }
        
        .search-results {
            position: absolute;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 5px 5px;
            display: none;
        }
        
        .search-results .list-group-item {
            cursor: pointer;
        }
        
        .search-results .list-group-item:hover {
            background-color: var(--cinza-claro);
        }
        
        .item-venda {
            border-left: 4px solid var(--verde-principal);
            margin-bottom: 10px;
            padding: 10px;
            background-color: white;
            border-radius: 5px;
        }
        
        .item-venda:hover {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .total-venda {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--azul-escuro);
        }
        
        .modal-content {
            border-radius: 10px;
        }
        
        .progress-bar {
            background-color: var(--verde-principal);
        }
        
        #progressModal .modal-body {
            text-align: center;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 1.5s infinite;
        }
        
        .logo-img {
            height: 40px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../HomePage/Visio_Gest.png" alt="VisioGest Logo" class="logo-img">
                VisioGest
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                
<li class="nav-item">
    <a class="nav-link" href="#" id="navVendas"><i class="fas fa-cash-register me-1"></i> Vendas</a>
</li>
                   
<li class="nav-item">
    <a class="nav-link" href="#" id="navFaturas"><i class="fas fa-file-invoice me-1"></i> Faturas</a>
</li>
                   
<li class="nav-item">
    <a class="nav-link" href="#" id="navProdutos"><i class="fas fa-box-open me-1"></i> Produtos</a>
</li>
                    <li class="nav-item">
                        <a class="nav-link" href="../HomePage/Home.php"><i class="fas fa-sign-out-alt me-1"></i> Sair</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Conteúdo Principal -->
    <div class="container py-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-shopping-cart me-2"></i>Registrar Venda</span>
                        <button id="btnAdicionarItem" class="btn btn-sm btn-light">
                            <i class="fas fa-plus me-1"></i> Adicionar Item
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Formulário de Busca de Paciente -->
                        <div class="mb-4">
                            <label for="buscarPaciente" class="form-label">Paciente</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="buscarPaciente" placeholder="Buscar paciente...">
                                <button class="btn btn-outline-secondary" type="button" id="btnNovoPaciente">
                                    <i class="fas fa-user-plus"></i>
                                </button>
                            </div>
                            <div id="resultadosPaciente" class="search-results mt-1"></div>
                            <input type="hidden" id="pacienteID" name="paciente">
                            <div id="infoPaciente" class="mt-2 p-2 bg-light rounded d-none">
                                <small class="text-muted">Paciente selecionado:</small>
                                <div id="nomePaciente" class="fw-bold"></div>
                            </div>
                        </div>

                        <!-- Formulário de Busca de Vendedor -->
                        <div class="mb-4">
                            <label for="buscarVendedor" class="form-label">Vendedor</label>
                            <input type="text" class="form-control" id="buscarVendedor" placeholder="Buscar vendedor...">
                            <div id="resultadosVendedor" class="search-results mt-1"></div>
                            <input type="hidden" id="vendedorID" name="vendedor">
                            <div id="infoVendedor" class="mt-2 p-2 bg-light rounded d-none">
                                <small class="text-muted">Vendedor selecionado:</small>
                                <div id="nomeVendedor" class="fw-bold"></div>
                            </div>
                        </div>

                        <!-- Itens da Venda -->
                        <div class="mb-3">
                            <label class="form-label">Itens da Venda</label>
                            <div id="itensVenda">
                                <div class="text-center py-4 text-muted">
                                    <i class="fas fa-shopping-basket fa-2x mb-2"></i>
                                    <p>Nenhum item adicionado à venda</p>
                                </div>
                            </div>
                        </div>

                        <!-- Resumo da Venda -->
                        <div class="border-top pt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
                                        <select class="form-select" id="forma_pagamento" name="forma_pagamento">
                                            <option value="Dinheiro">Dinheiro</option>
                                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                                            <option value="Cartão de Débito">Cartão de Débito</option>
                                            <option value="Transferência">Transferência</option>
                                            <option value="Cheque">Cheque</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="desconto_total" class="form-label">Desconto Total (AOA)</label>
                                        <input type="text" class="form-control" id="desconto_total" name="desconto_total" value="0,00">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-receipt me-2"></i>Resumo da Venda
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span id="subtotal">0,00 AOA</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Desconto:</span>
                            <span id="desconto">0,00 AOA</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between total-venda mb-4">
                            <span>Total:</span>
                            <span id="total">0,00 AOA</span>
                        </div>
                        <button id="btnFinalizarVenda" class="btn btn-primary w-100 py-2 pulse">
                            <i class="fas fa-check-circle me-2"></i>Finalizar Venda
                        </button>
                        <button id="btnGerarFatura" class="btn btn-outline-primary w-100 mt-2 py-2 d-none">
                            <i class="fas fa-file-pdf me-2"></i>Gerar Fatura
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Adicionar Item -->
    <div class="modal fade" id="modalItem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Item à Venda</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="buscarProduto" class="form-label">Buscar Produto</label>
                        <input type="text" class="form-control" id="buscarProduto" placeholder="Digite o nome ou descrição do produto...">
                        <div id="resultadosProduto" class="search-results mt-1"></div>
                    </div>
                    
                    <div id="detalhesProduto" class="border p-3 rounded mb-3 d-none">
                        <input type="hidden" id="produtoID">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 id="nomeProduto"></h5>
                                <p id="descricaoProduto" class="text-muted small"></p>
                                <div class="mb-2">
                                    <span class="fw-bold">Preço:</span> 
                                    <span id="precoProduto"></span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="quantidade" class="form-label">Quantidade</label>
                                    <input type="number" class="form-control" id="quantidade" min="1" value="1">
                                </div>
                                <div class="mb-3">
                                    <label for="descontoItem" class="form-label">Desconto (AOA)</label>
                                    <input type="text" class="form-control" id="descontoItem" value="0,00">
                                </div>
                                <div class="mb-3">
                                    <label for="descricaoItem" class="form-label">Descrição Adicional</label>
                                    <input type="text" class="form-control" id="descricaoItem" placeholder="Opcional">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnAdicionarAoCarrinho" class="btn btn-primary">Adicionar à Venda</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Progresso para Gerar Fatura -->
    <div class="modal fade" id="progressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center py-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <h5 class="mb-3">Gerando Fatura...</h5>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Modal para Listar Faturas -->
<div class="modal fade" id="modalFaturas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Faturas Emitidas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="filtroDataInicio" class="form-label">Data Início</label>
                            <input type="date" class="form-control" id="filtroDataInicio">
                        </div>
                        <div class="col-md-6">
                            <label for="filtroDataFim" class="form-label">Data Fim</label>
                            <input type="date" class="form-control" id="filtroDataFim">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label for="filtroPaciente" class="form-label">Paciente</label>
                            <input type="text" class="form-control" id="filtroPaciente" placeholder="Filtrar por paciente...">
                        </div>
                        <div class="col-md-6">
                            <label for="filtroVendedor" class="form-label">Vendedor</label>
                            <input type="text" class="form-control" id="filtroVendedor" placeholder="Filtrar por vendedor...">
                        </div>
                    </div>
                    <button id="btnFiltrarFaturas" class="btn btn-primary mt-3">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Nº Fatura</th>
                                <th>Data</th>
                                <th>Paciente</th>
                                <th>Vendedor</th>
                                <th>Total</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="listaFaturas">
                            <!-- As faturas serão carregadas aqui via AJAX -->
                        </tbody>
                    </table>
                </div>
                <div id="semFaturas" class="text-center py-5 text-muted">
                    <i class="fas fa-file-invoice fa-3x mb-3"></i>
                    <p>Nenhuma fatura encontrada</p>
                </div>
                <nav aria-label="Paginação de faturas">
                    <ul class="pagination justify-content-center" id="paginacaoFaturas">
                        <!-- Paginação será gerada aqui -->
                    </ul>
                </nav>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Listar Vendas -->
<div class="modal fade" id="modalVendas" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vendas Registradas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="filtroVendaDataInicio" class="form-label">Data Início</label>
                            <input type="date" class="form-control" id="filtroVendaDataInicio">
                        </div>
                        <div class="col-md-3">
                            <label for="filtroVendaDataFim" class="form-label">Data Fim</label>
                            <input type="date" class="form-control" id="filtroVendaDataFim">
                        </div>
                        <div class="col-md-3">
                            <label for="filtroVendaPaciente" class="form-label">Paciente</label>
                            <input type="text" class="form-control" id="filtroVendaPaciente" placeholder="Filtrar por paciente...">
                        </div>
                        <div class="col-md-3">
                            <label for="filtroVendaVendedor" class="form-label">Vendedor</label>
                            <input type="text" class="form-control" id="filtroVendaVendedor" placeholder="Filtrar por vendedor...">
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-3">
                            <label for="filtroVendaFormaPagamento" class="form-label">Forma Pagamento</label>
                            <select class="form-select" id="filtroVendaFormaPagamento">
                                <option value="">Todas</option>
                                <option value="Dinheiro">Dinheiro</option>
                                <option value="Cartão de Crédito">Cartão de Crédito</option>
                                <option value="Cartão de Débito">Cartão de Débito</option>
                                <option value="Transferência">Transferência</option>
                                <option value="Cheque">Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtroVendaMinTotal" class="form-label">Valor Mínimo</label>
                            <input type="number" class="form-control" id="filtroVendaMinTotal" placeholder="Valor mínimo">
                        </div>
                        <div class="col-md-3">
                            <label for="filtroVendaMaxTotal" class="form-label">Valor Máximo</label>
                            <input type="number" class="form-control" id="filtroVendaMaxTotal" placeholder="Valor máximo">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button id="btnFiltrarVendas" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filtrar
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Nº Venda</th>
                                <th>Data</th>
                                <th>Paciente</th>
                                <th>Vendedor</th>
                                <th>Forma Pagamento</th>
                                <th>Total</th>
                                <th>Desconto</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="listaVendas">
                            <!-- As vendas serão carregadas aqui via AJAX -->
                        </tbody>
                    </table>
                </div>
                <div id="semVendas" class="text-center py-5 text-muted">
                    <i class="fas fa-cash-register fa-3x mb-3"></i>
                    <p>Nenhuma venda encontrada</p>
                </div>
                <nav aria-label="Paginação de vendas">
                    <ul class="pagination justify-content-center" id="paginacaoVendas">
                        <!-- Paginação será gerada aqui -->
                    </ul>
                </nav>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalhes da Venda -->
<div class="modal fade" id="modalDetalhesVenda" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes da Venda <span id="numeroVenda"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Informações do Cliente</h6>
                        <p><strong>Nome:</strong> <span id="detalhePacienteNome"></span></p>
                        <p><strong>Telefone:</strong> <span id="detalhePacienteTelefone"></span></p>
                        <p><strong>Endereço:</strong> <span id="detalhePacienteEndereco"></span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Informações da Venda</h6>
                        <p><strong>Data:</strong> <span id="detalheVendaData"></span></p>
                        <p><strong>Vendedor:</strong> <span id="detalheVendaVendedor"></span></p>
                        <p><strong>Forma Pagamento:</strong> <span id="detalheVendaFormaPagamento"></span></p>
                        <p><strong>Observações:</strong> <span id="detalheVendaObservacoes"></span></p>
                    </div>
                </div>
                
                <h6 class="mt-4">Itens da Venda</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Descrição</th>
                                <th>Qtd</th>
                                <th>Preço Unit.</th>
                                <th>Desc.</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="detalheItensVenda">
                            <!-- Itens serão carregados aqui -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Total Bruto:</strong></td>
                                <td><span id="detalheTotalBruto"></span></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="text-end"><strong>Desconto Total:</strong></td>
                                <td><span id="detalheDescontoTotal"></span></td>
                            </tr>
                            <tr class="table-active">
                                <td colspan="5" class="text-end"><strong>Total Final:</strong></td>
                                <td><strong><span id="detalheTotalFinal"></span></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnImprimirFatura">
                    <i class="fas fa-print me-1"></i> Imprimir Fatura
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Listar Produtos -->
<div class="modal fade" id="modalProdutos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Catálogo de Produtos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="filtroProdutoNome" class="form-label">Nome do Produto</label>
                            <input type="text" class="form-control" id="filtroProdutoNome" placeholder="Buscar por nome...">
                        </div>
                        <div class="col-md-4">
                            <label for="filtroProdutoCategoria" class="form-label">Categoria</label>
                            <select class="form-select" id="filtroProdutoCategoria">
                                <option value="">Todas</option>
                                <!-- As categorias serão carregadas via AJAX -->
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="filtroProdutoEstoque" class="form-label">Estoque</label>
                            <select class="form-select" id="filtroProdutoEstoque">
                                <option value="">Todos</option>
                                <option value="disponivel">Disponível (estoque > 0)</option>
                                <option value="esgotado">Esgotado (estoque = 0)</option>
                                <option value="baixo">Estoque baixo (estoque ≤ 5)</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label for="filtroProdutoPrecoMin" class="form-label">Preço Mínimo</label>
                            <input type="number" class="form-control" id="filtroProdutoPrecoMin" placeholder="Preço mínimo">
                        </div>
                        <div class="col-md-6">
                            <label for="filtroProdutoPrecoMax" class="form-label">Preço Máximo</label>
                            <input type="number" class="form-control" id="filtroProdutoPrecoMax" placeholder="Preço máximo">
                        </div>
                    </div>
                    <button id="btnFiltrarProdutos" class="btn btn-primary mt-3">
                        <i class="fas fa-filter me-1"></i> Filtrar
                    </button>
                </div>
                
                <div class="row" id="listaProdutos">
                    <!-- Os produtos serão carregados aqui via AJAX -->
                </div>
                <div id="semProdutos" class="text-center py-5 text-muted">
                    <i class="fas fa-box-open fa-3x mb-3"></i>
                    <p>Nenhum produto encontrado</p>
                </div>
                <nav aria-label="Paginação de produtos">
                    <ul class="pagination justify-content-center" id="paginacaoProdutos">
                        <!-- Paginação será gerada aqui -->
                    </ul>
                </nav>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Detalhes do Produto -->
<div class="modal fade" id="modalDetalhesProduto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Produto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-5">
                        <div class="text-center mb-3">
                            <img id="produtoFoto" src="../img/sem-foto.jpg" class="img-fluid rounded" style="max-height: 300px;" alt="Foto do Produto">
                        </div>
                    </div>
                    <div class="col-md-7">
                        <h3 id="produtoNome"></h3>
                        <div class="mb-3">
                            <span class="badge bg-primary" id="produtoCategoria"></span>
                            <span class="badge ms-2" id="produtoEstoqueBadge"></span>
                        </div>
                        <p id="produtoDescricao" class="text-muted"></p>
                        
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="card-title">Informações Comerciais</h6>
                                        <p class="mb-1"><strong>Preço de Venda:</strong> <span id="produtoPreco" class="text-success fw-bold"></span></p>
                                        <p class="mb-1"><strong>Código:</strong> <span id="produtoCodigo"></span></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="card-title">Estoque</h6>
                                        <p class="mb-1"><strong>Disponível:</strong> <span id="produtoEstoque"></span></p>
                                        <p class="mb-1"><strong>Status:</strong> <span id="produtoStatus"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="produtoMensagemEstoque"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let itensVenda = [];
            
            // Máscara para valores monetários
            $('body').on('input', '#desconto_total, #descontoItem', function() {
                let value = $(this).val().replace(/\D/g, '');
                value = (value/100).toFixed(2) + '';
                value = value.replace(".", ",");
                value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
                value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
                $(this).val(value);
            });
            
            // Abrir modal para adicionar item
            $('#btnAdicionarItem').click(function() {
                $('#modalItem').modal('show');
            });
            
            // Buscar pacientes
            $('#buscarPaciente').on('input', function() {
                const termo = $(this).val();
                if(termo.length > 2) {
                    $.post('', { buscar_paciente: true, termo: termo }, function(data) {
                        const resultados = $('#resultadosPaciente');
                        resultados.empty();
                        
                        if(data.length > 0) {
                            data.forEach(function(paciente) {
                                resultados.append(`
                                    <div class="list-group-item list-group-item-action" 
                                         data-id="${paciente.PacienteID}" 
                                         data-nome="${paciente.NomeCompleto}">
                                        ${paciente.NomeCompleto}
                                    </div>
                                `);
                            });
                            resultados.show();
                        } else {
                            resultados.hide();
                        }
                    }, 'json');
                } else {
                    $('#resultadosPaciente').hide();
                }
            });
            
            // Selecionar paciente
            $('#resultadosPaciente').on('click', '.list-group-item', function() {
                const pacienteID = $(this).data('id');
                const nomePaciente = $(this).data('nome');
                
                $('#pacienteID').val(pacienteID);
                $('#nomePaciente').text(nomePaciente);
                $('#infoPaciente').removeClass('d-none');
                $('#resultadosPaciente').hide();
                $('#buscarPaciente').val('');
            });
            
            // Buscar vendedores
            $('#buscarVendedor').on('input', function() {
                const termo = $(this).val();
                if(termo.length > 2) {
                    $.post('', { buscar_vendedor: true, termo: termo }, function(data) {
                        const resultados = $('#resultadosVendedor');
                        resultados.empty();
                        
                        if(data.length > 0) {
                            data.forEach(function(vendedor) {
                                resultados.append(`
                                    <div class="list-group-item list-group-item-action" 
                                         data-id="${vendedor.VendedorID}" 
                                         data-nome="${vendedor.NomeCompleto}">
                                        ${vendedor.NomeCompleto}
                                    </div>
                                `);
                            });
                            resultados.show();
                        } else {
                            resultados.hide();
                        }
                    }, 'json');
                } else {
                    $('#resultadosVendedor').hide();
                }
            });
            
            // Selecionar vendedor
            $('#resultadosVendedor').on('click', '.list-group-item', function() {
                const vendedorID = $(this).data('id');
                const nomeVendedor = $(this).data('nome');
                
                $('#vendedorID').val(vendedorID);
                $('#nomeVendedor').text(nomeVendedor);
                $('#infoVendedor').removeClass('d-none');
                $('#resultadosVendedor').hide();
                $('#buscarVendedor').val('');
            });
            
            // Buscar produtos
            $('#buscarProduto').on('input', function() {
                const termo = $(this).val();
                if(termo.length > 2) {
                    $.post('', { buscar_produto: true, termo: termo }, function(data) {
                        const resultados = $('#resultadosProduto');
                        resultados.empty();
                        
                        if(data.length > 0) {
                            data.forEach(function(produto) {
                                resultados.append(`
                                    <div class="list-group-item list-group-item-action" 
                                         data-id="${produto.ProdutoID}" 
                                         data-nome="${produto.NomeProduto}"
                                         data-descricao="${produto.Descricao}"
                                         data-preco="${produto.PrecoVenda}"
                                         data-estoque="${produto.EstoqueAtual}">
                                        <strong>${produto.NomeProduto}</strong>
                                        <div class="small text-muted">${produto.Descricao}</div>
                                        <div class="small">Preço: ${formatarMoeda(produto.PrecoVenda)} | Estoque: ${produto.EstoqueAtual}</div>
                                    </div>
                                `);
                            });
                            resultados.show();
                        } else {
                            resultados.hide();
                        }
                    }, 'json');
                } else {
                    $('#resultadosProduto').hide();
                }
            });
            
            // Selecionar produto
            $('#resultadosProduto').on('click', '.list-group-item', function() {
                const produtoID = $(this).data('id');
                const nomeProduto = $(this).data('nome');
                const descricao = $(this).data('descricao');
                const preco = $(this).data('preco');
                const estoque = $(this).data('estoque');
                
                $('#produtoID').val(produtoID);
                $('#nomeProduto').text(nomeProduto);
                $('#descricaoProduto').text(descricao);
                $('#precoProduto').text(formatarMoeda(preco));
                $('#quantidade').attr('max', estoque);
                $('#quantidade').val(1);
                $('#descontoItem').val('0,00');
                $('#descricaoItem').val('');
                
                $('#detalhesProduto').removeClass('d-none');
                $('#resultadosProduto').hide();
                $('#buscarProduto').val('');
            });
            
            // Adicionar item ao carrinho
            $('#btnAdicionarAoCarrinho').click(function() {
                const produtoID = $('#produtoID').val();
                const nomeProduto = $('#nomeProduto').text();
                const descricaoProduto = $('#descricaoProduto').text();
                const preco = parseFloat($('#precoProduto').text().replace('.', '').replace(',', '.'));
                const quantidade = parseInt($('#quantidade').val());
                const desconto = parseFloat($('#descontoItem').val().replace('.', '').replace(',', '.') || 0);
                const descricaoItem = $('#descricaoItem').val();
                
                if(!produtoID || !quantidade) {
                    alert('Preencha todos os campos obrigatórios!');
                    return;
                }
                
                // Verificar se o item já existe na venda
                const itemExistente = itensVenda.find(item => item.id == produtoID);
                if(itemExistente) {
                    if(confirm('Este produto já foi adicionado à venda. Deseja atualizar a quantidade?')) {
                        itemExistente.quantidade += quantidade;
                        itemExistente.desconto += desconto;
                    }
                } else {
                    // Adicionar novo item
                    itensVenda.push({
                        id: produtoID,
                        nome: nomeProduto,
                        descricao: descricaoProduto,
                        preco: preco,
                        quantidade: quantidade,
                        desconto: desconto,
                        descricaoItem: descricaoItem
                    });
                }
                
                atualizarItensVenda();
                $('#modalItem').modal('hide');
                $('#detalhesProduto').addClass('d-none');
            });
            
            // Remover item da venda
            $('#itensVenda').on('click', '.btn-remover-item', function() {
                const index = $(this).data('index');
                itensVenda.splice(index, 1);
                atualizarItensVenda();
            });
            
            // Atualizar desconto total
            $('#desconto_total').on('change', function() {
                atualizarTotais();
            });
            
            // Finalizar venda
            $('#btnFinalizarVenda').click(function() {
                const pacienteID = $('#pacienteID').val();
                const vendedorID = $('#vendedorID').val();
                const formaPagamento = $('#forma_pagamento').val();
                const descontoTotal = $('#desconto_total').val().replace('.', '').replace(',', '.') || 0;
                const observacoes = $('#observacoes').val();
                
                if(!pacienteID || !vendedorID || itensVenda.length === 0) {
                    alert('Preencha todos os campos obrigatórios!');
                    return;
                }
                
                const dados = {
                    finalizar_venda: true,
                    paciente: pacienteID,
                                        vendedor: vendedorID,
                    forma_pagamento: formaPagamento,
                    desconto_total: descontoTotal,
                    observacoes: observacoes,
                    itens_json: JSON.stringify(itensVenda)
                };
                
                // Mostrar modal de progresso
                $('#progressModal').modal('show');
                
                // Enviar dados via AJAX
                $.post('', dados, function(response) {
                    if(response.success) {
                        // Esconder modal de progresso
                        $('#progressModal').modal('hide');
                        
                        // Mostrar mensagem de sucesso
                        alert('Venda registrada com sucesso! Número da venda: ' + response.vendaID);
                        
                        // Habilitar botão para gerar fatura
                        $('#btnGerarFatura').removeClass('d-none');
                        
                        // Desabilitar botão de finalizar venda
                        $('#btnFinalizarVenda').prop('disabled', true);
                    } else {
                        alert('Erro ao registrar venda!');
                        $('#progressModal').modal('hide');
                    }
                }, 'json').fail(function() {
                    alert('Erro na comunicação com o servidor!');
                    $('#progressModal').modal('hide');
                });
            });
            
            // Gerar fatura
            $('#btnGerarFatura').click(function() {
                window.location.href = '?gerar_fatura=true';
            });
            
            // Função para atualizar a lista de itens na tela
            function atualizarItensVenda() {
                const container = $('#itensVenda');
                
                if(itensVenda.length === 0) {
                    container.html(`
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-shopping-basket fa-2x mb-2"></i>
                            <p>Nenhum item adicionado à venda</p>
                        </div>
                    `);
                } else {
                    container.empty();
                    
                    itensVenda.forEach(function(item, index) {
                        const subtotal = (item.preco * item.quantidade) - item.desconto;
                        
                        container.append(`
                            <div class="item-venda">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">${item.nome}</h6>
                                        <small class="text-muted">${item.descricao}</small>
                                        ${item.descricaoItem ? '<div class="small"><em>' + item.descricaoItem + '</em></div>' : ''}
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger btn-remover-item" data-index="${index}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <span class="me-3">${item.quantidade} x ${formatarMoeda(item.preco)}</span>
                                        ${item.desconto > 0 ? '<span class="text-danger">- ' + formatarMoeda(item.desconto) + '</span>' : ''}
                                    </div>
                                    <span class="fw-bold">${formatarMoeda(subtotal)}</span>
                                </div>
                            </div>
                        `);
                    });
                }
                
                atualizarTotais();
            }
            
            // Função para atualizar os totais da venda
            function atualizarTotais() {
                let subtotal = 0;
                let desconto = parseFloat($('#desconto_total').val().replace('.', '').replace(',', '.') || 0);
                
                itensVenda.forEach(function(item) {
                    subtotal += (item.preco * item.quantidade) - item.desconto;
                });
                
                const total = subtotal - desconto;
                
                $('#subtotal').text(formatarMoeda(subtotal) + ' AOA');
                $('#desconto').text(formatarMoeda(desconto) + ' AOA');
                $('#total').text(formatarMoeda(total) + ' AOA');
            }
            
            // Função para formatar valores monetários
            function formatarMoeda(valor) {
                return parseFloat(valor).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        });

// No documento ready, adicione este código:

// Abrir modal de faturas
$('#navFaturas').click(function() {
    $('#modalFaturas').modal('show');
    carregarFaturas();
});

// Carregar faturas via AJAX
function carregarFaturas(pagina = 1) {
    const dataInicio = $('#filtroDataInicio').val();
    const dataFim = $('#filtroDataFim').val();
    const paciente = $('#filtroPaciente').val();
    const vendedor = $('#filtroVendedor').val();
    
    $.post('lista_faturas.php', {
        pagina: pagina,
        data_inicio: dataInicio,
        data_fim: dataFim,
        paciente: paciente,
        vendedor: vendedor
    }, function(response) {
        const lista = $('#listaFaturas');
        const paginacao = $('#paginacaoFaturas');
        const semFaturas = $('#semFaturas');
        
        lista.empty();
        paginacao.empty();
        
        if(response.faturas.length > 0) {
            semFaturas.hide();
            
            response.faturas.forEach(function(fatura) {
                lista.append(`
                    <tr>
                        <td>${fatura.VendaID}</td>
                        <td>${formatarData(fatura.DataVenda)}</td>
                        <td>${fatura.NomePaciente}</td>
                        <td>${fatura.NomeVendedor}</td>
                        <td>${formatarMoeda(fatura.ValorTotal)}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary btn-visualizar-fatura" data-id="${fatura.VendaID}">
                                <i class="fas fa-eye"></i> Visualizar
                            </button>
                            <button class="btn btn-sm btn-outline-secondary btn-imprimir-fatura" data-id="${fatura.VendaID}">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                        </td>
                    </tr>
                `);
            });
            
            // Paginação
            if(response.totalPaginas > 1) {
                for(let i = 1; i <= response.totalPaginas; i++) {
                    paginacao.append(`
                        <li class="page-item ${i === pagina ? 'active' : ''}">
                            <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                        </li>
                    `);
                }
            }
        } else {
            semFaturas.show();
        }
    }, 'json');
}

// Filtrar faturas
$('#btnFiltrarFaturas').click(function() {
    carregarFaturas();
});

// Navegar entre páginas
$('#paginacaoFaturas').on('click', '.page-link', function(e) {
    e.preventDefault();
    const pagina = $(this).data('pagina');
    carregarFaturas(pagina);
});

// Visualizar fatura
$('#listaFaturas').on('click', '.btn-visualizar-fatura', function() {
    const vendaID = $(this).data('id');
    window.open(`ver_fatura.php?id=${vendaID}`, '_blank');
});

// Imprimir fatura
$('#listaFaturas').on('click', '.btn-imprimir-fatura', function() {
    const vendaID = $(this).data('id');
    window.open(`gerar_fatura.php?id=${vendaID}&imprimir=true`, '_blank');
});

// Função para formatar data
function formatarData(dataString) {
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR') + ' ' + data.toLocaleTimeString('pt-BR');
}

// No documento ready, adicione este código:

// Abrir modal de vendas
$('#navVendas').click(function() {
    $('#modalVendas').modal('show');
    carregarVendas();
});

// Carregar vendas via AJAX
function carregarVendas(pagina = 1) {
    const dataInicio = $('#filtroVendaDataInicio').val();
    const dataFim = $('#filtroVendaDataFim').val();
    const paciente = $('#filtroVendaPaciente').val();
    const vendedor = $('#filtroVendaVendedor').val();
    const formaPagamento = $('#filtroVendaFormaPagamento').val();
    const minTotal = $('#filtroVendaMinTotal').val();
    const maxTotal = $('#filtroVendaMaxTotal').val();
    
    $.post('lista_vendas.php', {
        pagina: pagina,
        data_inicio: dataInicio,
        data_fim: dataFim,
        paciente: paciente,
        vendedor: vendedor,
        forma_pagamento: formaPagamento,
        min_total: minTotal,
        max_total: maxTotal
    }, function(response) {
        const lista = $('#listaVendas');
        const paginacao = $('#paginacaoVendas');
        const semVendas = $('#semVendas');
        
        lista.empty();
        paginacao.empty();
        
        if(response.vendas.length > 0) {
            semVendas.hide();
            
            response.vendas.forEach(function(venda) {
                lista.append(`
                    <tr>
                        <td>${venda.VendaID}</td>
                        <td>${formatarData(venda.DataVenda)}</td>
                        <td>${venda.NomePaciente}</td>
                        <td>${venda.NomeVendedor}</td>
                        <td>${venda.FormaPagamento}</td>
                        <td>${formatarMoeda(venda.ValorTotal)}</td>
                        <td>${formatarMoeda(venda.DescontoTotal)}</td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary btn-detalhes-venda" data-id="${venda.VendaID}">
                                <i class="fas fa-eye"></i> Detalhes
                            </button>
                            <button class="btn btn-sm btn-outline-secondary btn-imprimir-venda" data-id="${venda.VendaID}">
                                <i class="fas fa-print"></i> Fatura
                            </button>
                        </td>
                    </tr>
                `);
            });
            
            // Paginação
            if(response.totalPaginas > 1) {
                for(let i = 1; i <= response.totalPaginas; i++) {
                    paginacao.append(`
                        <li class="page-item ${i === pagina ? 'active' : ''}">
                            <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                        </li>
                    `);
                }
            }
        } else {
            semVendas.show();
        }
    }, 'json');
}

// Filtrar vendas
$('#btnFiltrarVendas').click(function() {
    carregarVendas();
});

// Navegar entre páginas
$('#paginacaoVendas').on('click', '.page-link', function(e) {
    e.preventDefault();
    const pagina = $(this).data('pagina');
    carregarVendas(pagina);
});

// Visualizar detalhes da venda
$('#listaVendas').on('click', '.btn-detalhes-venda', function() {
    const vendaID = $(this).data('id');
    carregarDetalhesVenda(vendaID);
});

// Carregar detalhes da venda
function carregarDetalhesVenda(vendaID) {
    $.get('detalhes_venda.php', { id: vendaID }, function(response) {
        $('#numeroVenda').text('#' + response.venda.VendaID);
        $('#detalhePacienteNome').text(response.venda.NomePaciente);
        $('#detalhePacienteTelefone').text(response.venda.Telefone || 'Não informado');
        $('#detalhePacienteEndereco').text(response.venda.Morada || 'Não informado');
        $('#detalheVendaData').text(formatarData(response.venda.DataVenda));
        $('#detalheVendaVendedor').text(response.venda.NomeVendedor);
        $('#detalheVendaFormaPagamento').text(response.venda.FormaPagamento);
        $('#detalheVendaObservacoes').text(response.venda.Observacoes || 'Nenhuma');
        
        // Itens da venda
        const itensContainer = $('#detalheItensVenda');
        itensContainer.empty();
        
        response.itens.forEach(function(item, index) {
            itensContainer.append(`
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <strong>${item.NomeProduto}</strong><br>
                        <small class="text-muted">${item.DescricaoProduto}</small>
                        ${item.DescricaoItem ? '<div><small>Obs: ' + item.DescricaoItem + '</small></div>' : ''}
                    </td>
                    <td>${item.Quantidade}</td>
                    <td>${formatarMoeda(item.PrecoUnitario)}</td>
                    <td>${item.DescontoItem > 0 ? formatarMoeda(item.DescontoItem) : '-'}</td>
                    <td>${formatarMoeda(item.SubTotal)}</td>
                </tr>
            `);
        });
        
        // Totais
        const totalBruto = parseFloat(response.venda.ValorTotal) + parseFloat(response.venda.DescontoTotal);
        $('#detalheTotalBruto').text(formatarMoeda(totalBruto));
        $('#detalheDescontoTotal').text(formatarMoeda(response.venda.DescontoTotal));
        $('#detalheTotalFinal').text(formatarMoeda(response.venda.ValorTotal));
        
        // Configurar botão de impressão
        $('#btnImprimirFatura').off('click').on('click', function() {
            window.open('gerar_fatura.php?id=' + vendaID + '&imprimir=true', '_blank');
        });
        
        $('#modalDetalhesVenda').modal('show');
    }, 'json');
}

// Imprimir fatura diretamente da lista
$('#listaVendas').on('click', '.btn-imprimir-venda', function() {
    const vendaID = $(this).data('id');
    window.open('gerar_fatura.php?id=' + vendaID + '&imprimir=true', '_blank');
});

// No documento ready, adicione este código:

// Abrir modal de produtos
$('#navProdutos').click(function() {
    $('#modalProdutos').modal('show');
    carregarCategorias();
    carregarProdutos();
});

// Carregar categorias para o filtro
function carregarCategorias() {
    $.get('lista_categorias.php', function(data) {
        const select = $('#filtroProdutoCategoria');
        select.empty();
        select.append('<option value="">Todas</option>');
        
        data.forEach(function(categoria) {
            select.append(`<option value="${categoria.CategoriaID}">${categoria.NomeCategoria}</option>`);
        });
    }, 'json');
}

// Carregar produtos via AJAX
function carregarProdutos(pagina = 1) {
    const nome = $('#filtroProdutoNome').val();
    const categoria = $('#filtroProdutoCategoria').val();
    const estoque = $('#filtroProdutoEstoque').val();
    const precoMin = $('#filtroProdutoPrecoMin').val();
    const precoMax = $('#filtroProdutoPrecoMax').val();
    
    $.post('lista_produtos.php', {
        pagina: pagina,
        nome: nome,
        categoria: categoria,
        estoque: estoque,
        preco_min: precoMin,
        preco_max: precoMax
    }, function(response) {
        const lista = $('#listaProdutos');
        const paginacao = $('#paginacaoProdutos');
        const semProdutos = $('#semProdutos');
        
        lista.empty();
        paginacao.empty();
        
        if(response.produtos.length > 0) {
            semProdutos.hide();
            
            response.produtos.forEach(function(produto) {
                const estoqueClass = produto.EstoqueAtual > 5 ? 'text-success' : 
                                    produto.EstoqueAtual > 0 ? 'text-warning' : 'text-danger';
                const estoqueText = produto.EstoqueAtual > 0 ? `${produto.EstoqueAtual} disponíveis` : 'Esgotado';
                
                lista.append(`
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="${produto.FotoUrl || '../img/sem-foto.jpg'}" class="card-img-top" style="height: 180px; object-fit: cover;" alt="${produto.NomeProduto}">
                                <span class="position-absolute top-0 end-0 m-2 badge ${produto.EstoqueAtual > 5 ? 'bg-success' : produto.EstoqueAtual > 0 ? 'bg-warning' : 'bg-danger'}">
                                    ${estoqueText}
                                </span>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">${produto.NomeProduto}</h5>
                                <p class="card-text text-muted small" style="min-height: 40px;">${produto.Descricao.substring(0, 80)}${produto.Descricao.length > 80 ? '...' : ''}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">${formatarMoeda(produto.PrecoVenda)}</span>
                                    <button class="btn btn-sm btn-outline-primary btn-detalhes-produto" data-id="${produto.ProdutoID}">
                                        <i class="fas fa-eye"></i> Detalhes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            });
            
            // Paginação
            if(response.totalPaginas > 1) {
                for(let i = 1; i <= response.totalPaginas; i++) {
                    paginacao.append(`
                        <li class="page-item ${i === pagina ? 'active' : ''}">
                            <a class="page-link" href="#" data-pagina="${i}">${i}</a>
                        </li>
                    `);
                }
            }
        } else {
            semProdutos.show();
        }
    }, 'json');
}

// Filtrar produtos
$('#btnFiltrarProdutos').click(function() {
    carregarProdutos();
});

// Navegar entre páginas
$('#paginacaoProdutos').on('click', '.page-link', function(e) {
    e.preventDefault();
    const pagina = $(this).data('pagina');
    carregarProdutos(pagina);
});

// Visualizar detalhes do produto
$('#listaProdutos').on('click', '.btn-detalhes-produto', function() {
    const produtoID = $(this).data('id');
    carregarDetalhesProduto(produtoID);
});

// Carregar detalhes do produto
function carregarDetalhesProduto(produtoID) {
    $.get('detalhes_produto.php', { id: produtoID }, function(response) {
        $('#produtoNome').text(response.NomeProduto);
        $('#produtoDescricao').text(response.Descricao);
        $('#produtoPreco').text(formatarMoeda(response.PrecoVenda));
        $('#produtoCodigo').text(response.ProdutoID);
        $('#produtoEstoque').text(response.EstoqueAtual);
        
        // Foto do produto
        const fotoUrl = response.FotoUrl || '../img/sem-foto.jpg';
        $('#produtoFoto').attr('src', fotoUrl);
        
        // Categoria
        if(response.NomeCategoria) {
            $('#produtoCategoria').text(response.NomeCategoria).show();
        } else {
            $('#produtoCategoria').hide();
        }
        
        // Status do estoque
        let estoqueClass, estoqueText, mensagemEstoque;
        if(response.EstoqueAtual > 10) {
            estoqueClass = 'bg-success';
            estoqueText = 'Disponível';
            mensagemEstoque = 'Produto com bom estoque disponível.';
        } else if(response.EstoqueAtual > 0) {
            estoqueClass = 'bg-warning';
            estoqueText = 'Estoque baixo';
            mensagemEstoque = 'Atenção! Estoque deste produto está acabando.';
        } else {
            estoqueClass = 'bg-danger';
            estoqueText = 'Esgotado';
            mensagemEstoque = 'Produto esgotado. Considere fazer um novo pedido.';
        }
        
        $('#produtoEstoqueBadge')
            .removeClass('bg-success bg-warning bg-danger')
            .addClass(estoqueClass)
            .text(estoqueText);
            
        $('#produtoStatus').text(estoqueText);
        $('#produtoMensagemEstoque').text(mensagemEstoque);
        
        $('#modalDetalhesProduto').modal('show');
    }, 'json');
}










// Gerar fatura
$('#btnGerarFatura').click(function() {
    // Abrir o PDF em uma nova aba
    window.open('?gerar_fatura=true', '_blank');
});


    </script>
</body>
</html>
<?php
// Fechar conexão com o banco de dados
$conn->close();
?>