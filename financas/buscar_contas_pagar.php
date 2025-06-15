<?php
require_once('conexao.php');

// Configurações de paginação
$porPagina = 10;
$pagina = isset($_POST['pagina']) ? intval($_POST['pagina']) : 1;
$offset = ($pagina - 1) * $porPagina;

// Construir a consulta SQL com filtros
$sql = "SELECT SQL_CALC_FOUND_ROWS cp.*, f.Nome AS FornecedorNome 
        FROM tb_contas_a_pagar cp
        LEFT JOIN tb_fornecedor f ON cp.FornecedorID = f.FornecedorID
        WHERE 1=1";

$params = array();
$types = '';

// Filtros
if(!empty($_POST['data_inicio'])) {
    $sql .= " AND cp.DataVencimento >= ?";
    $params[] = $_POST['data_inicio'];
    $types .= 's';
}

if(!empty($_POST['data_fim'])) {
    $sql .= " AND cp.DataVencimento <= ?";
    $params[] = $_POST['data_fim'];
    $types .= 's';
}

if(!empty($_POST['status'])) {
    $sql .= " AND cp.StatusPagamento = ?";
    $params[] = $_POST['status'];
    $types .= 's';
}

$sql .= " ORDER BY cp.DataVencimento DESC LIMIT ? OFFSET ?";
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
$contas = $result->fetch_all(MYSQLI_ASSOC);

// Obter o total de registros para paginação
$totalRegistros = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$totalPaginas = ceil($totalRegistros / $porPagina);

// Retornar resposta JSON
header('Content-Type: application/json');
echo json_encode([
    'contas' => $contas,
    'totalPaginas' => $totalPaginas
]);
?>