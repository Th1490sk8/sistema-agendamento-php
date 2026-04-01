<?php
require_once 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['recuperar'])) {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        // Verifica se o e-mail existe no sistema
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
        $stmt->execute(['email' => $email]);
        
        if ($stmt->rowCount() > 0) {
            // Gera um token aleatório seguro de 50 caracteres
            $token = bin2hex(random_bytes(25));
            // Define a expiração para 1 hora no futuro
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Salva o token no banco
            $sql = "UPDATE usuarios SET reset_token = :token, reset_expiracao = :expiracao WHERE email = :email";
            $stmtUpdate = $pdo->prepare($sql);
            $stmtUpdate->execute(['token' => $token, 'expiracao' => $expiracao, 'email' => $email]);

            // SIMULAÇÃO DO ENVIO DE E-MAIL
            $link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/redefinir_senha.php?token=" . $token;
            
            $mensagem = "<div class='sucesso' style='word-break: break-all;'>
                <p><strong>SIMULAÇÃO DE E-MAIL:</strong><br>
                Operador, sua requisição foi aceita. Clique no link abaixo para reconfigurar sua chave de acesso. O link expira em 1 hora.</p>
                <a href='$link' style='color:#00f0ff;'>$link</a>
            </div>";
        } else {
            $mensagem = "<p class='erro'>E-mail não encontrado no mainframe.</p>";
        }
    } else {
        $mensagem = "<p class='erro'>Informe seu e-mail de acesso.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Acesso</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Protocolo de Recuperação</h2>
    
    <div class="form-container">
        <h3>Identificação Necessária</h3>
        <?php if (isset($mensagem)) echo $mensagem; ?>
        
        <form method="POST" action="">
            <div>
                <label for="email">E-mail de Operador:</label><br>
                <input type="email" name="email" id="email" required placeholder="Digite o e-mail cadastrado">
            </div>
            <button type="submit" name="recuperar">GERAR CHAVE DE RECUPERAÇÃO</button>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <a href="login.php" style="color: #fcee0a; text-decoration: none;">Cancelar e Voltar ao Login</a>
        </div>
    </div>
</body>
</html>