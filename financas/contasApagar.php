<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contas a Pagar - Sistema Financeiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --verde-principal: #5A9392;
            --azul-escuro: #00003B;
            --branco: #FFFFFF;
            --cinza-claro: #F5F7FA;
        }
        
        body {
            background-color: var(--cinza-claro);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: none;
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
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-apagar {
            background-color: #ffcccc;
            color: #cc0000;
        }
        
        .status-pago {
            background-color: #ccffcc;
            color: #006600;
        }
        
        .status-parcial {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-atrasado {
            background-color: #f8d7da;
            color: #721c24;
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
        
        .valor-input {
            position: relative;
        }
        
        .valor-input::before {
            content: "Kz";
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            font-weight: bold;
            color: var(--azul-escuro);
        }
        
        .valor-input input {
            padding-left: 30px;
        }
        
        .logo-img {
            height: 40px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <i class="fas fa-file-invoice-dollar me-2"></i>
                            <span>Registrar Conta a Pagar</span>
                        </div>
                        <button class="btn btn-sm btn-light" id="btnHistorico">
                            <i class="fas fa-history me-1"></i> Histórico
                        </button>
                    </div>
                    <div class="card-body">
                        <form id="formContaPagar">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="descricao" class="form-label">Descrição da Despesa*</label>
                                    <input type="text" class="form-control" id="descricao" name="descricao" required>
                                    <div class="invalid-feedback">Por favor, informe a descrição da despesa.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="tipoDespesa" class="form-label">Tipo de Despesa*</label>
                                    <select class="form-select" id="tipoDespesa" name="tipoDespesa" required>
                                        <option value="" selected disabled>Selecione...</option>
                                        <option value="Aluguel">Aluguel</option>
                                        <option value="Salário">Salário</option>
                                        <option value="Material de Escritório">Material de Escritório</option>
                                        <option value="Fornecedores">Fornecedores</option>
                                        <option value="Contas de Consumo">Contas de Consumo</option>
                                        <option value="Manutenção">Manutenção</option>
                                        <option value="Outros">Outros</option>
                                    </select>
                                    <div class="invalid-feedback">Por favor, selecione o tipo de despesa.</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="fornecedor" class="form-label">Fornecedor (Opcional)</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="fornecedor" placeholder="Buscar fornecedor...">
                                        <button class="btn btn-outline-secondary" type="button" id="btnNovoFornecedor">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div id="resultadosFornecedor" class="search-results mt-1"></div>
                                    <input type="hidden" id="fornecedorID" name="fornecedorID">
                                    <div id="infoFornecedor" class="mt-2 p-2 bg-light rounded d-none">
                                        <small class="text-muted">Fornecedor selecionado:</small>
                                        <div id="nomeFornecedor" class="fw-bold"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="dataVencimento" class="form-label">Data de Vencimento*</label>
                                    <input type="date" class="form-control" id="dataVencimento" name="dataVencimento" required>
                                    <div class="invalid-feedback">Por favor, informe a data de vencimento.</div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="valorOriginal" class="form-label">Valor Original*</label>
                                    <div class="valor-input">
                                        <input type="text" class="form-control" id="valorOriginal" name="valorOriginal" required>
                                    </div>
                                    <div class="invalid-feedback">Por favor, informe o valor da conta.</div>
                                </div>
                                <div class="col-md-4">
                                    <label for="valorPago" class="form-label">Valor Pago</label>
                                    <div class="valor-input">
                                        <input type="text" class="form-control" id="valorPago" name="valorPago" value="0,00">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label for="statusPagamento" class="form-label">Status*</label>
                                    <select class="form-select" id="statusPagamento" name="statusPagamento" required>
                                        <option value="A Pagar" selected>A Pagar</option>
                                        <option value="Parcialmente Pago">Parcialmente Pago</option>
                                        <option value="Pago">Pago</option>
                                        <option value="Atrasado">Atrasado</option>
                                        <option value="Cancelado">Cancelado</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="meioPagamento" class="form-label">Meio de Pagamento</label>
                                    <select class="form-select" id="meioPagamento" name="meioPagamento">
                                        <option value="" selected disabled>Selecione...</option>
                                        <option value="Transferência">Transferência Bancária</option>
                                        <option value="Dinheiro">Dinheiro</option>
                                        <option value="Cheque">Cheque</option>
                                        <option value="Cartão de Crédito">Cartão de Crédito</option>
                                        <option value="Cartão de Débito">Cartão de Débito</option>
                                        <option value="Boleto">Boleto</option>
                                        <option value="PIX">PIX</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="dataPagamento" class="form-label">Data de Pagamento</label>
                                    <input type="date" class="form-control" id="dataPagamento" name="dataPagamento">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="observacoes" class="form-label">Observações</label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" id="btnCancelar">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary" id="btnSalvar">
                                    <i class="fas fa-save me-1"></i> Salvar Conta
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Novo Fornecedor -->
    <div class="modal fade" id="modalFornecedor" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cadastrar Novo Fornecedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="novoFornecedorNome" class="form-label">Nome do Fornecedor*</label>
                        <input type="text" class="form-control" id="novoFornecedorNome" required>
                    </div>
                    <div class="mb-3">
                        <label for="novoFornecedorCNPJ" class="form-label">CNPJ/CPF</label>
                        <input type="text" class="form-control" id="novoFornecedorCNPJ">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSalvarFornecedor">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Histórico -->
    <div class="modal fade" id="modalHistorico" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-history me-2"></i> Histórico de Contas a Pagar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="filtroDataInicio" class="form-label">Data Início</label>
                                <input type="date" class="form-control" id="filtroDataInicio">
                            </div>
                            <div class="col-md-3">
                                <label for="filtroDataFim" class="form-label">Data Fim</label>
                                <input type="date" class="form-control" id="filtroDataFim">
                            </div>
                            <div class="col-md-3">
                                <label for="filtroStatus" class="form-label">Status</label>
                                <select class="form-select" id="filtroStatus">
                                    <option value="">Todos</option>
                                    <option value="A Pagar">A Pagar</option>
                                    <option value="Pago">Pago</option>
                                    <option value="Parcialmente Pago">Parcialmente Pago</option>
                                    <option value="Atrasado">Atrasado</option>
                                    <option value="Cancelado">Cancelado</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button class="btn btn-primary w-100" id="btnFiltrarHistorico">
                                    <i class="fas fa-filter me-1"></i> Filtrar
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Descrição</th>
                                    <th>Tipo</th>
                                    <th>Fornecedor</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                    <th>Pagamento</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="tabelaHistorico">
                                <!-- Dados serão carregados via AJAX -->
                            </tbody>
                        </table>
                    </div>
                    <div id="semRegistros" class="text-center py-5 text-muted">
                        <i class="fas fa-file-invoice fa-3x mb-3"></i>
                        <p>Nenhuma conta a pagar encontrada</p>
                    </div>
                    <nav aria-label="Paginação do histórico">
                        <ul class="pagination justify-content-center" id="paginacaoHistorico">
                            <!-- Paginação será gerada aqui -->
                        </ul>
                    </nav>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="btnExportarHistorico">
                        <i class="fas fa-file-export me-1"></i> Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Máscara para valores monetários (Kz)
            $('#valorOriginal, #valorPago').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                value = (value/100).toFixed(2) + '';
                value = value.replace(".", ",");
                value = value.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
                value = value.replace(/(\d)(\d{3}),/g, "$1.$2,");
                $(this).val(value);
            });
            
            // Buscar fornecedores
            $('#fornecedor').on('input', function() {
                const termo = $(this).val();
                if(termo.length > 2) {
                    // Simulação de busca AJAX - substitua por chamada real ao servidor
                    const resultados = $('#resultadosFornecedor');
                    resultados.empty();
                    
                    // Exemplo de dados simulados
                    const fornecedoresSimulados = [
                        { FornecedorID: 1, Nome: "Fornecedor A Ltda", CNPJ: "12.345.678/0001-00" },
                        { FornecedorID: 2, Nome: "Distribuidora B S.A.", CNPJ: "98.765.432/0001-00" },
                        { FornecedorID: 3, Nome: "Serviços C ME", CNPJ: "123.456.789-00" }
                    ].filter(f => f.Nome.toLowerCase().includes(termo.toLowerCase()));
                    
                    if(fornecedoresSimulados.length > 0) {
                        fornecedoresSimulados.forEach(function(fornecedor) {
                            resultados.append(`
                                <div class="list-group-item list-group-item-action" 
                                     data-id="${fornecedor.FornecedorID}" 
                                     data-nome="${fornecedor.Nome}">
                                    <strong>${fornecedor.Nome}</strong><br>
                                    <small class="text-muted">${fornecedor.CNPJ}</small>
                                </div>
                            `);
                        });
                        resultados.show();
                    } else {
                        resultados.hide();
                    }
                } else {
                    $('#resultadosFornecedor').hide();
                }
            });
            
            // Selecionar fornecedor
            $('#resultadosFornecedor').on('click', '.list-group-item', function() {
                const fornecedorID = $(this).data('id');
                const nomeFornecedor = $(this).data('nome');
                
                $('#fornecedorID').val(fornecedorID);
                $('#nomeFornecedor').text(nomeFornecedor);
                $('#infoFornecedor').removeClass('d-none');
                $('#resultadosFornecedor').hide();
                $('#fornecedor').val('');
            });
            
            // Abrir modal para novo fornecedor
            $('#btnNovoFornecedor').click(function() {
                $('#modalFornecedor').modal('show');
            });
            
            // Salvar novo fornecedor (simulado)
            $('#btnSalvarFornecedor').click(function() {
                const nome = $('#novoFornecedorNome').val();
                const cnpj = $('#novoFornecedorCNPJ').val();
                
                if(!nome) {
                    alert('Por favor, informe o nome do fornecedor.');
                    return;
                }
                
                // Simular cadastro - substitua por chamada AJAX real
                const novoId = Math.floor(Math.random() * 1000) + 10;
                
                $('#fornecedorID').val(novoId);
                $('#nomeFornecedor').text(nome);
                $('#infoFornecedor').removeClass('d-none');
                $('#modalFornecedor').modal('hide');
                
                // Limpar formulário
                $('#novoFornecedorNome').val('');
                $('#novoFornecedorCNPJ').val('');
            });
            
            // Atualizar status e campos relacionados
            $('#statusPagamento').change(function() {
                const status = $(this).val();
                const hoje = new Date().toISOString().split('T')[0];
                
                if(status === 'Pago') {
                    $('#dataPagamento').val(hoje);
                    $('#valorPago').val($('#valorOriginal').val());
                    $('#meioPagamento').prop('required', true);
                } else if(status === 'Parcialmente Pago') {
                    $('#meioPagamento').prop('required', true);
                } else {
                    $('#meioPagamento').prop('required', false);
                }
            });
            
            // Validar formulário
            $('#formContaPagar').submit(function(e) {
                e.preventDefault();
                
                // Validação simples - substitua por validação mais robusta
                let valido = true;
                const camposObrigatorios = ['#descricao', '#tipoDespesa', '#dataVencimento', '#valorOriginal'];
                
                camposObrigatorios.forEach(function(campo) {
                    if(!$(campo).val()) {
                        $(campo).addClass('is-invalid');
                        valido = false;
                    } else {
                        $(campo).removeClass('is-invalid');
                    }
                });
                
                if($('#statusPagamento').val() === 'Pago' && !$('#dataPagamento').val()) {
                    $('#dataPagamento').addClass('is-invalid');
                    valido = false;
                } else {
                    $('#dataPagamento').removeClass('is-invalid');
                }
                
                if(valido) {
                    // Simular envio do formulário - substitua por AJAX real
                    alert('Conta a pagar registrada com sucesso!');
                    $('#formContaPagar')[0].reset();
                    $('#infoFornecedor').addClass('d-none');
                } else {
                    alert('Por favor, preencha todos os campos obrigatórios.');
                }
            });
            
            // Cancelar formulário
            $('#btnCancelar').click(function() {
                if(confirm('Deseja realmente cancelar? Todos os dados não salvos serão perdidos.')) {
                    $('#formContaPagar')[0].reset();
                    $('#infoFornecedor').addClass('d-none');
                }
            });
            
            // Verificar vencimento
            $('#dataVencimento').change(function() {
                const vencimento = new Date($(this).val());
                const hoje = new Date();
                
                if(vencimento < hoje) {
                    $('#statusPagamento').val('Atrasado').trigger('change');
                }
            });
            
            // Abrir modal de histórico
            $('#btnHistorico').click(function() {
                $('#modalHistorico').modal('show');
                carregarHistorico();
            });
            
            // Carregar histórico de contas a pagar
            function carregarHistorico(pagina = 1) {
                const dataInicio = $('#filtroDataInicio').val();
                const dataFim = $('#filtroDataFim').val();
                const status = $('#filtroStatus').val();
                
                // Simulação de dados - substitua por chamada AJAX real
                $.ajax({
                    url: 'buscar_contas_pagar.php',
                    type: 'POST',
                    data: {
                        pagina: pagina,
                        data_inicio: dataInicio,
                        data_fim: dataFim,
                        status: status
                    },
                    dataType: 'json',
                    success: function(response) {
                        const tabela = $('#tabelaHistorico');
                        const paginacao = $('#paginacaoHistorico');
                        const semRegistros = $('#semRegistros');
                        
                        tabela.empty();
                        paginacao.empty();
                        
                        if(response.contas && response.contas.length > 0) {
                            semRegistros.hide();
                            
                            response.contas.forEach(function(conta) {
                                // Formatar datas
                                const dataVencimento = conta.DataVencimento ? new Date(conta.DataVencimento).toLocaleDateString('pt-BR') : '-';
                                const dataPagamento = conta.DataPagamento ? new Date(conta.DataPagamento).toLocaleDateString('pt-BR') : '-';
                                
                                // Determinar classe do status
                                let statusClass = '';
                                switch(conta.StatusPagamento) {
                                    case 'Pago': statusClass = 'status-pago'; break;
                                    case 'Parcialmente Pago': statusClass = 'status-parcial'; break;
                                    case 'Atrasado': statusClass = 'status-atrasado'; break;
                                    default: statusClass = 'status-apagar';
                                }
                                
                                tabela.append(`
                                    <tr>
                                        <td>${conta.ContaPagarID}</td>
                                        <td>${conta.Descricao}</td>
                                        <td>${conta.TipoDespesa}</td>
                                        <td>${conta.FornecedorNome || '-'}</td>
                                        <td class="text-end">Kz ${formatarMoeda(conta.ValorOriginal)}</td>
                                        <td>${dataVencimento}</td>
                                        <td>${dataPagamento}</td>
                                        <td><span class="status-badge ${statusClass}">${conta.StatusPagamento}</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary btn-editar" data-id="${conta.ContaPagarID}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger btn-excluir" data-id="${conta.ContaPagarID}">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `);
                            });
                            
                            // Paginação (simulada)
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
                            semRegistros.show();
                        }
                    },
                    error: function() {
                        alert('Erro ao carregar histórico');
                    }
                });
            }
            
            // Filtrar histórico
            $('#btnFiltrarHistorico').click(function() {
                carregarHistorico();
            });
            
            // Navegar entre páginas
            $('#paginacaoHistorico').on('click', '.page-link', function(e) {
                e.preventDefault();
                const pagina = $(this).data('pagina');
                carregarHistorico(pagina);
            });
            
            // Função auxiliar para formatar moeda
            function formatarMoeda(valor) {
                return parseFloat(valor).toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }
        });
    </script>
</body>
</html>