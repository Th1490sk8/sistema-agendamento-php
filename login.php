<?php
// Inicia a sessão para podermos guardar os dados do usuário logado
session_start();
require_once 'conexao.php';

// Se o usuário já estiver logado, manda ele direto pro calendário
if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['logar'])) {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!empty($email) && !empty($senha)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verifica se achou o usuário e se a senha bate com o Hash do banco
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                // Sucesso! Cria as variáveis de sessão
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                
                // Redireciona para o sistema principal
                header("Location: index.php");
                exit;
            } else {
                $mensagem = "<p class='erro'>Acesso negado: Credenciais inválidas.</p>";
            }
        } catch (PDOException $e) {
            $mensagem = "<p class='erro'>Erro no servidor central: " . $e->getMessage() . "</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Entrar na Matrix</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Autenticação do Sistema</h2>
    
    <div class="form-container">
        <h3>Acesso Restrito</h3>
        <?php if (isset($mensagem)) echo $mensagem; ?>
        
        <form method="POST" action="">
            <div>
                <label for="email">E-mail de Operador:</label><br>
                <input type="email" name="email" id="email" required>
            </div>
            <div>
                <label for="senha">Senha:</label><br>
                <input type="password" name="senha" id="senha" required>
            </div>
            <button type="submit" name="logar">INICIAR CONEXÃO</button>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <a href="recuperar_senha.php" style="color: #ff003c; text-decoration: none; font-size: 0.9em;">Esqueci minha senha de segurança</a>
            <br><br>
            <a href="cadastro.php" style="color: #fcee0a; text-decoration: none; font-weight: bold;">Requisitar Novo Acesso (Cadastro)</a>
        </div>
    </div>
</body>
</html>