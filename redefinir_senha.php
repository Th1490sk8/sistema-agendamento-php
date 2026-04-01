<?php
require_once 'conexao.php';
date_default_timezone_set('America/Sao_Paulo');

$token = $_GET['token'] ?? '';
$tokenValido = false;
$usuario_id = null;

if (!empty($token)) {
    // Verifica se o token existe e não está expirado
    $agora = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE reset_token = :token AND reset_expiracao > :agora");
    $stmt->execute(['token' => $token, 'agora' => $agora]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        $tokenValido = true;
        $usuario_id = $usuario['id'];
    } else {
        $erro_token = "A chave de recuperação é inválida ou expirou.";
    }
} else {
    $erro_token = "Chave de recuperação não fornecida.";
}

// Processa a nova senha
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['atualizar_senha']) && $tokenValido) {
    $nova_senha = $_POST['nova_senha'];
    
    if (!empty($nova_senha)) {
        $senhaHash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        // Atualiza a senha e APAGA o token para que não possa ser usado novamente
        $sql = "UPDATE usuarios SET senha = :senha, reset_token = NULL, reset_expiracao = NULL WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute(['senha' => $senhaHash, 'id' => $usuario_id]);
        
        $mensagem_sucesso = "Sua chave de segurança foi reconfigurada! <br><br> <a href='login.php' style='color:#00f0ff;'>Clique aqui para iniciar conexão</a>.";
        $tokenValido = false; // Esconde o formulário após sucesso
    } else {
        $mensagem = "<p class='erro'>A senha não pode estar vazia.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Reconfigurar Chave</h2>
    
    <div class="form-container">
        <h3>Nova Credencial</h3>
        
        <?php if (isset($erro_token)): ?>
            <p class='erro'><?php echo $erro_token; ?></p>
            <div style="text-align: center; margin-top: 15px;">
                <a href="recuperar_senha.php" style="color: #fcee0a; text-decoration: none;">Solicitar nova chave</a>
            </div>
        <?php endif; ?>

        <?php if (isset($mensagem_sucesso)): ?>
            <div class='sucesso' style='text-align: center;'><?php echo $mensagem_sucesso; ?></div>
        <?php endif; ?>
        
        <?php if (isset($mensagem)) echo $mensagem; ?>

        <?php if ($tokenValido): ?>
            <form method="POST" action="">
                <div>
                    <label for="nova_senha">Digite a Nova Senha:</label><br>
                    <input type="password" name="nova_senha" id="nova_senha" required>
                </div>
                <button type="submit" name="atualizar_senha">CONFIRMAR NOVA SENHA</button>
            </form>
        <?php endif; ?>
        
    </div>
</body>
</html>