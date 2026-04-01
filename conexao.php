<?php
// Configurações do banco de dados
$host = 'localhost';        // Geralmente 'localhost' se estiver usando XAMPP/WAMP/MAMP
$dbname = 'calendario_db';  // Substitua pelo nome do seu banco de dados
$user = 'root';             // Seu usuário do MySQL (padrão é root no XAMPP)
$pass = 'Fobos@Deimos07';                 // Sua senha do MySQL (padrão é vazio no XAMPP)

try {
    // Cria a conexão usando PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    
    // Configura o PDO para lançar exceções em caso de erros (ótimo para debug)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    //echo "Conexão realizada com sucesso!"; // Descomente esta linha para testar a conexão

} catch (PDOException $e) {
    // Caso ocorra algum erro na conexão, o script para e exibe a mensagem
    die("Erro ao conectar com o banco de dados: " . $e->getMessage());
}
?>