<?php
session_start();
$_SESSION['id'] = 1;
$_SESSION['nome'] = "Miguel Damin";
$_SESSION['perfil'] = "treinador";
header("Location: /pages/perfil.php");
exit();
