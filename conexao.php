 <?php 
//session_start(); 
$servidor = "localhost"; 
$usuario = "root"; 
$senha = ""; 
$dbname = "bd_Visio"; 
//Criar a conexão 
$conn = mysqli_connect($servidor, $usuario, $senha, $dbname); 
if(!$conn){ 
die("Falha na conexao: " . mysqli_connect_error()); 
}else{ 
//echo "Conexao realizada com sucesso"; 
} 
?> 