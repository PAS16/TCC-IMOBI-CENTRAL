<?php
$servername = "localhost";
$username = "root";
$password = "admin";
$dbname = "mydb";

// Criando conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Checa conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}
?>
1