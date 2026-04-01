<?php
session_start();
// Limpa todas as variáveis de sessão
session_unset();
// Destrói a sessão
session_destroy();

// Redireciona de volta para o login
header("Location: login.php");
exit;
?>