<?php
require_once('../conexao.php');

$sql = "SELECT CategoriaID, NomeCategoria FROM tb_categoria ORDER BY NomeCategoria";
$result = $conn->query($sql);
$categorias = $result->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($categorias);