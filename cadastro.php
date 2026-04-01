<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cadastrar'])) {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    if (!empty($nome) && !empty($email) && !empty($senha)) {
        // Criptografando a senha para máxima segurança
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (:nome, :email, :senha)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['nome' => $nome, 'email' => $email, 'senha' => $senhaHash]);
            $mensagem = "<p class='sucesso'>Operador registrado! Você já pode fazer login.</p>";
        } catch (PDOException $e) {
            // Código 23000 do MySQL significa que o email já existe (UNIQUE)
            if ($e->getCode() == 23000) {
                $mensagem = "<p class='erro'>Erro: Este email já está na rede.</p>";
            } else {
                $mensagem = "<p class='erro'>Erro no sistema: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        $mensagem = "<p class='erro'>Preencha todos os parâmetros, operador.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro - Acesso à Rede</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Registro de Novo Operador</h2>
    
    <div class="form-container">
        <h3>Credenciais de Acesso</h3>
        <?php if (isset($mensagem)) echo $mensagem; ?>
        
        <form method="POST" action="">
            <div>
                <label for="nome">Nome de Operador (Codinome):</label><br>
                <input type="text" name="nome" id="nome" required>
            </div>
            <div>
                <label for="email">Endereço de E-mail:</label><br>
                <input type="email" name="email" id="email" required>
            </div>
            <div>
                <label for="senha">Senha de Segurança:</label><br>
                <input type="password" name="senha" id="senha" required>
            </div>
            <button type="submit" name="cadastrar">REGISTRAR ACESSO</button>
        </form>
        <div style="text-align: center; margin-top: 15px;">
            <a href="login.php" style="color: #00f0ff; text-decoration: none; font-weight: bold;">Já possui acesso? Iniciar Sessão</a>
        </div>
    </div>
</body>
</html>