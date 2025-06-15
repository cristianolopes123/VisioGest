<?php
session_start();
require_once('../conexao.php');
require_once('../tcpdf/tcpdf.php');

if(!isset($_GET['id'])) {
    die("ID da fatura não especificado");
}

$vendaID = intval($_GET['id']);
$imprimir = isset($_GET['imprimir']);

// Mesma lógica de ver_fatura.php, mas com configurações de impressão
// ... (código similar ao ver_fatura.php)

if($imprimir) {
    $pdf->Output('fatura_' . $vendaID . '.pdf', 'I');
} else {
    $pdf->Output('fatura_' . $vendaID . '.pdf', 'D');
}