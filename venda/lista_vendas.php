<?php
session_start();
require_once('../conexao.php');

// Configurações de paginação
$porPagina = 10;
$pagina = isset($_POST['pagina']) ? intval($_POST['pagina']) : 1;
$offset = ($pagina - 1) * $porPagina;

// Construir a consulta SQL com filtros
$sql = "SELECT SQL_CALC_FOUND_ROWS v.VendaID, v.DataVenda, v.ValorTotal, v.DescontoTotal, v.FormaPagamento,
               p.NomeCompleto AS NomePaciente, vo.NomeCompleto AS NomeVendedor
        FROM tb_venda v
        JOIN tb_pacientes p ON v.PacienteID = p.PacienteID
        JOIN tb_vendedor_otico vo ON v.VendedorID = vo.VendedorID
        WHERE 1=1";

$params = array();
$types = '';

// Filtros
if(!empty($_POST['data_inicio'])) {
    $sql .= " AND DATE(v.DataVenda) >= ?";
    $params[] = $_POST['data_inicio'];
    $types .= 's';
}

if(!empty($_POST['data_fim'])) {
    $sql .= " AND DATE(v.DataVenda) <= ?";
    $params[] = $_POST['data_fim'];
    $types .= 's';
}

if(!empty($_POST['paciente'])) {
    $sql .= " AND p.NomeCompleto LIKE ?";
    $params[] = '%' . $_POST['paciente'] . '%';
    $types .= 's';
}

if(!empty($_POST['vendedor'])) {
    $sql .= " AND vo.NomeCompleto LIKE ?";
    $params[] = '%' . $_POST['vendedor'] . '%';
    $types .= 's';
}

if(!empty($_POST['forma_pagamento'])) {
    $sql .= " AND v.FormaPagamento = ?";
    $params[] = $_POST['forma_pagamento'];
    $types .= 's';
}

if(!empty($_POST['min_total'])) {
    $sql .= " AND v.ValorTotal >= ?";
    $params[] = $_POST['min_total'];
    $types .= 'd';
}

if(!empty($_POST['max_total'])) {
    $sql .= " AND v.ValorTotal <= ?";
    $params[] = $_POST['max_total'];
    $types .= 'd';
}

$sql .= " ORDER BY v.DataVenda DESC LIMIT ? OFFSET ?";
$params[] = $porPagina;
$params[] = $offset;
$types .= 'ii';

// Preparar e executar a consulta
$stmt = $conn->prepare($sql);
if($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$vendas = $result->fetch_all(MYSQLI_ASSOC);

// Obter o total de registros para paginação
$totalRegistros = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$totalPaginas = ceil($totalRegistros / $porPagina);

// Retornar resposta JSON
header('Content-Type: application/json');
echo json_encode([
    'vendas' => $vendas,
    'totalPaginas' => $totalPaginas
]);