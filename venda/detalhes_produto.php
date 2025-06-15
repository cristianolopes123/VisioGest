<?php
require_once('../conexao.php');

if(!isset($_GET['id'])) {
    die("ID do produto não especificado");
}

$produtoID = intval($_GET['id']);

// Buscar dados do produto
$sql = "SELECT p.*, c.NomeCategoria 
        FROM tb_produto p
        LEFT JOIN tb_categoria c ON p.CategoriaID = c.CategoriaID
        WHERE p.ProdutoID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $produtoID);
$stmt->execute();
$result = $stmt->get_result();
$produto = $result->fetch_assoc();

if(!$produto) {
    die("Produto não encontrado");
}

// Retornar resposta JSON
header('Content-Type: application/json');
echo json_encode($produto);