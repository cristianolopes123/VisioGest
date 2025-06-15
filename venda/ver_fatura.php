<?php
session_start();
require_once('../conexao.php');
require_once('../tcpdf/tcpdf.php');

if(!isset($_GET['id'])) {
    die("ID da fatura não especificado");
}

$vendaID = intval($_GET['id']);

// Buscar dados da venda
$sql = "SELECT v.*, p.NomeCompleto AS NomePaciente, p.Telefone, p.Endereco AS Morada, 
               vo.NomeCompleto AS NomeVendedor 
        FROM tb_venda v
        JOIN tb_pacientes p ON v.PacienteID = p.PacienteID
        JOIN tb_vendedor_otico vo ON v.VendedorID = vo.VendedorID
        WHERE v.VendaID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $vendaID);
$stmt->execute();
$result = $stmt->get_result();
$venda = $result->fetch_assoc();

if(!$venda) {
    die("Fatura não encontrada");
}

// Buscar itens da venda
$stmt = $conn->prepare("SELECT iv.*, p.NomeProduto, p.Descricao AS DescricaoProduto
                       FROM tb_item_venda iv
                       JOIN tb_produto p ON iv.ProdutoID = p.ProdutoID
                       WHERE iv.VendaID = ?");
$stmt->bind_param("i", $vendaID);
$stmt->execute();
$result = $stmt->get_result();
$itens = $result->fetch_all(MYSQLI_ASSOC);

// Criar PDF (mesmo código que você já tem para gerar faturas)
// ... (insira aqui o código de geração de PDF que você já tem)

$pdf->Output('fatura_' . $vendaID . '.pdf', 'I');