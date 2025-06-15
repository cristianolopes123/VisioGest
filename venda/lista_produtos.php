<?php
require_once('../conexao.php');

// Configurações de paginação
$porPagina = 9; // Múltiplo de 3 para exibir bem no grid
$pagina = isset($_POST['pagina']) ? intval($_POST['pagina']) : 1;
$offset = ($pagina - 1) * $porPagina;

// Construir a consulta SQL com filtros
$sql = "SELECT SQL_CALC_FOUND_ROWS p.ProdutoID, p.NomeProduto, p.Descricao, 
               p.PrecoVenda, p.EstoqueAtual, p.FotoUrl,
               c.NomeCategoria
        FROM tb_produto p
        LEFT JOIN tb_categoria c ON p.CategoriaID = c.CategoriaID
        WHERE 1=1";

$params = array();
$types = '';

// Filtros
if(!empty($_POST['nome'])) {
    $sql .= " AND p.NomeProduto LIKE ?";
    $params[] = '%' . $_POST['nome'] . '%';
    $types .= 's';
}

if(!empty($_POST['categoria'])) {
    $sql .= " AND p.CategoriaID = ?";
    $params[] = $_POST['categoria'];
    $types .= 'i';
}

if(!empty($_POST['estoque'])) {
    switch($_POST['estoque']) {
        case 'disponivel':
            $sql .= " AND p.EstoqueAtual > 0";
            break;
        case 'esgotado':
            $sql .= " AND p.EstoqueAtual = 0";
            break;
        case 'baixo':
            $sql .= " AND p.EstoqueAtual <= 5 AND p.EstoqueAtual > 0";
            break;
    }
}

if(!empty($_POST['preco_min'])) {
    $sql .= " AND p.PrecoVenda >= ?";
    $params[] = $_POST['preco_min'];
    $types .= 'd';
}

if(!empty($_POST['preco_max'])) {
    $sql .= " AND p.PrecoVenda <= ?";
    $params[] = $_POST['preco_max'];
    $types .= 'd';
}

$sql .= " ORDER BY p.NomeProduto LIMIT ? OFFSET ?";
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
$produtos = $result->fetch_all(MYSQLI_ASSOC);

// Obter o total de registros para paginação
$totalRegistros = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];
$totalPaginas = ceil($totalRegistros / $porPagina);

// Retornar resposta JSON
header('Content-Type: application/json');
echo json_encode([
    'produtos' => $produtos,
    'totalPaginas' => $totalPaginas
]);