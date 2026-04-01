<?php 
    // INICIA A SESSÃO PRIMEIRO DE TUDO
    session_start();

    // VERIFICA SE ESTÁ LOGADO. SE NÃO ESTIVER, CHUTA PRO LOGIN.
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: login.php");
        exit;
    }

    // Pega o ID do operador logado para usar em todas as operações
    $id_operador = $_SESSION['usuario_id'];

    // 1. Inclui a conexão com o banco de dados
    require_once 'conexao.php';

    // 2. Força o fuso horário correto
    date_default_timezone_set('America/Sao_Paulo');

    // 3. Define o mês e ano atual pela URL
    $mesAtual = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
    $anoAtual = isset($_GET['ano']) ? (int)$_GET['ano'] : (int)date('Y');

    // 4. EXCLUIR EVENTO (Agora exige que o evento pertença ao operador logado)
    if (isset($_GET['excluir'])) {
        $id_excluir = (int)$_GET['excluir']; 
        try {
            $stmtExcluir = $pdo->prepare("DELETE FROM eventos WHERE id = :id AND usuario_id = :usuario_id");
            $stmtExcluir->execute([
                'id' => $id_excluir,
                'usuario_id' => $id_operador
            ]);
            $mensagem = "<p class='sucesso'>Evento apagado da rede com sucesso!</p>";
        } catch (PDOException $e) {
            $mensagem = "<p class='erro'>Erro de sistema ao excluir: " . $e->getMessage() . "</p>";
        }
    }

    // 5. PROCESSAR O FORMULÁRIO (Salvar Novo ou Atualizar Existente)
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['salvar_evento'])) {
        $titulo = trim($_POST['titulo']);
        $data_evento = $_POST['data_evento'];
        $descricao = trim($_POST['descricao'] ?? '');
        
        $id_evento = !empty($_POST['id_evento']) ? (int)$_POST['id_evento'] : 0;

        if (!empty($titulo) && !empty($data_evento)) {
            try {
                if ($id_evento > 0) {
                    // ATUALIZAR (UPDATE) - Garante que só atualiza se for o dono do evento
                    $sql = "UPDATE eventos SET titulo = :titulo, descricao = :descricao, data_evento = :data_evento WHERE id = :id AND usuario_id = :usuario_id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':id', $id_evento);
                    $stmt->bindParam(':usuario_id', $id_operador);
                    $mensagem = "<p class='sucesso'>Protocolo atualizado com sucesso!</p>";
                } else {
                    // INSERIR (CREATE) - Salva o ID do operador junto com o evento
                    $sql = "INSERT INTO eventos (titulo, descricao, data_evento, usuario_id) VALUES (:titulo, :descricao, :data_evento, :usuario_id)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':usuario_id', $id_operador);
                    $mensagem = "<p class='sucesso'>Novo evento registrado no sistema!</p>";
                }
                
                $stmt->bindParam(':titulo', $titulo);
                $stmt->bindParam(':descricao', $descricao);
                $stmt->bindParam(':data_evento', $data_evento);
                $stmt->execute();
                
            } catch (PDOException $e) {
                $mensagem = "<p class='erro'>Falha no sistema: " . $e->getMessage() . "</p>";
            }
        } else {
            $mensagem = "<p class='erro'>Parâmetros incompletos. Preencha Título e Data!</p>";
        }
    }

    // 6. BUSCAR DADOS PARA EDIÇÃO (Só busca se o evento for do operador logado)
    $eventoEditando = null;
    if (isset($_GET['editar'])) {
        $id_editar = (int)$_GET['editar'];
        $stmtEd = $pdo->prepare("SELECT * FROM eventos WHERE id = :id AND usuario_id = :usuario_id");
        $stmtEd->execute(['id' => $id_editar, 'usuario_id' => $id_operador]);
        $eventoEditando = $stmtEd->fetch(PDO::FETCH_ASSOC);
    }

    // 7. BUSCAR EVENTOS DO MÊS ATUAL PARA O CALENDÁRIO (Filtra pelo ID do operador)
    $stmtEventos = $pdo->prepare("SELECT * FROM eventos WHERE MONTH(data_evento) = :mes AND YEAR(data_evento) = :ano AND usuario_id = :usuario_id");
    $stmtEventos->execute([
        'mes' => $mesAtual, 
        'ano' => $anoAtual,
        'usuario_id' => $id_operador
    ]);
    $eventosResult = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);

    $eventosOrganizados = [];
    foreach ($eventosResult as $ev) {
        $diaDoEvento = (int)date('j', strtotime($ev['data_evento']));
        $eventosOrganizados[$diaDoEvento][] = $ev;
    }

    // 8. CÁLCULOS DO CALENDÁRIO
    $dataAtual = strtotime("{$anoAtual}-{$mesAtual}-01");
    $mesAnt = date("m", strtotime("-1 month", $dataAtual));
    $anoAnt = date("Y", strtotime("-1 month", $dataAtual));
    $mesProx = date("m", strtotime("+1 month", $dataAtual));
    $anoProx = date("Y", strtotime("+1 month", $dataAtual));

    $primeiroDia = date("w", $dataAtual); 
    $diasMes = date("t", $dataAtual);     

    $anoHoje = (int)date("Y");
    $mesHoje = (int)date("m");
    $diaHoje = (int)date("j"); 

    // 9. ALGORITMO DE FERIADOS NACIONAIS
    function obterFeriadosBrasil($ano) {
        // Feriados Fixos (Mês-Dia)
        $feriados = [
            '01-01' => 'Confraternização Universal',
            '04-21' => 'Tiradentes',
            '05-01' => 'Dia do Trabalhador',
            '09-07' => 'Independência do Brasil',
            '10-12' => 'Nossa Sra. Aparecida',
            '11-02' => 'Finados',
            '11-15' => 'Proclamação da República',
            '12-25' => 'Natal',
        ];

        // Feriados Móveis (Cálculo baseado na Páscoa)
        $pascoa = easter_date($ano); 
        $dia_pascoa = date('j', $pascoa);
        $mes_pascoa = date('n', $pascoa);
        $ano_pascoa = date('Y', $pascoa);

        $sexta_santa = date('m-d', mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 2, $ano_pascoa));
        $carnaval = date('m-d', mktime(0, 0, 0, $mes_pascoa, $dia_pascoa - 47, $ano_pascoa));
        $corpus_christi = date('m-d', mktime(0, 0, 0, $mes_pascoa, $dia_pascoa + 60, $ano_pascoa));

        $feriados[$sexta_santa] = 'Paixão de Cristo';
        $feriados[$carnaval] = 'Carnaval';
        $feriados[$corpus_christi] = 'Corpus Christi';

        return $feriados;
    }

    // Carrega os feriados do ano que o usuário está visualizando
    $feriadosDoAno = obterFeriadosBrasil($anoAtual);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Agendamento</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="width: 100%; max-width: 600px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #00f0ff; padding-bottom: 10px; margin-bottom: 20px;">
        <span style="color: #00f0ff; font-weight: bold;">Operador Conectado: <span style="color: #fcee0a;"><?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></span></span>
        <a href="logout.php" style="color: #ff003c; text-decoration: none; font-family: 'Orbitron', sans-serif; border: 1px solid #ff003c; padding: 5px 10px; font-size: 0.9em;">[ DESCONECTAR ]</a>
    </div>
    <h2> Calendário - <?php echo date("F Y", $dataAtual); ?></h2>
    
    <div class="nav">
        <a href="?mes=<?php echo $mesAnt; ?>&ano=<?php echo $anoAnt; ?>">Anterior</a> |
        <a href="?mes=<?php echo $mesProx; ?>&ano=<?php echo $anoProx; ?>">Próximo</a>
    </div>

    <table>
        <tr>
            <th>Dom</th>
            <th>Seg</th>
            <th>Ter</th>
            <th>Qua</th>
            <th>Qui</th>
            <th>Sex</th>
            <th>Sab</th>
        </tr>
        <tr>
        <?php 
            for ($vazio = 0; $vazio < $primeiroDia; $vazio++) {
                echo '<td class="empty"> </td>';
            }
            
            $dia = 1;
            for ($i = $primeiroDia; $i < 42; $i++) {
                if ($i % 7 == 0 && $i != 0) echo '</tr><tr>';
                
                if ($dia <= $diasMes) {
                    $classes = [];
                    if ($dia == $diaHoje && $mesAtual == $mesHoje && $anoAtual == $anoHoje) $classes[] = "hoje";
                    if ($i % 7 == 0 || $i % 7 == 6) $classes[] = "fim-de-semana";
                    
                    $classeStr = !empty($classes) ? 'class="' . implode(' ', $classes) . '"' : '';
                    
                    echo "<td $classeStr>";
                        echo "<span class='dia-numero'>{$dia}</span>";
                        
                        // NOVO: Renderiza os Feriados Nacionais
                        $dataDoDiaFormatada = sprintf('%02d-%02d', $mesAtual, $dia);
                        
                        if (isset($feriadosDoAno[$dataDoDiaFormatada])) {
                            echo "<div class='evento-tag feriado'>";
                                echo "<span class='titulo-evento'>★ " . $feriadosDoAno[$dataDoDiaFormatada] . "</span>";
                            echo "</div>";
                        }

                        // Eventos criados pelo Operador
                        if (isset($eventosOrganizados[$dia])) {
                            echo "<div class='container-eventos'>";
                            foreach ($eventosOrganizados[$dia] as $evento) {
                                echo "<div class='evento-tag' title='" . htmlspecialchars($evento['descricao']) . "'>";
                                    echo "<span class='titulo-evento'>" . htmlspecialchars($evento['titulo']) . "</span>";
                                    echo "<div class='acoes-evento'>";
                                        // Botão Editar (Engrenagem)
                                        echo "<a href='?mes={$mesAtual}&ano={$anoAtual}&editar={$evento['id']}' class='btn-editar'>⚙</a>";
                                        // Botão Excluir (X)
                                        echo "<a href='?mes={$mesAtual}&ano={$anoAtual}&excluir={$evento['id']}' class='btn-excluir' onclick='return confirm(\"ATENÇÃO: Deseja desintegrar este evento do sistema?\")'>✖</a>";
                                    echo "</div>";
                                echo "</div>";
                            }
                            echo "</div>";
                        }
                    echo "</td>";
                    $dia++;
                } else {
                    if ($i % 7 == 0) break; 
                    echo '<td class="empty"> </td>';
                }
            }
        ?>
        </tr>
    </table>

    <div class="form-container">
        <h3><?php echo $eventoEditando ? 'Reconfigurar Registro' : 'Inserir Novo Registro'; ?></h3>
        
        <?php if (isset($mensagem)) echo $mensagem; ?>

        <form method="POST" action="?mes=<?php echo $mesAtual; ?>&ano=<?php echo $anoAtual; ?>">
            
            <input type="hidden" name="id_evento" value="<?php echo $eventoEditando ? $eventoEditando['id'] : ''; ?>">

            <div>
                <label for="titulo">Identificação do Evento:</label><br>
                <input type="text" name="titulo" id="titulo" required value="<?php echo $eventoEditando ? htmlspecialchars($eventoEditando['titulo']) : ''; ?>">
            </div>

            <div>
                <label for="data_evento">Data de Execução:</label><br>
                <input type="date" name="data_evento" id="data_evento" required value="<?php echo $eventoEditando ? $eventoEditando['data_evento'] : ''; ?>">
            </div>

            <div>
                <label for="descricao">Detalhes Adicionais (opcional):</label><br>
                <textarea name="descricao" id="descricao" rows="3"><?php echo $eventoEditando ? htmlspecialchars($eventoEditando['descricao']) : ''; ?></textarea>
            </div>

            <button type="submit" name="salvar_evento">
                <?php echo $eventoEditando ? 'ATUALIZAR DADOS' : 'SALVAR EVENTO'; ?>
            </button>

            <?php if ($eventoEditando): ?>
                <a href="?mes=<?php echo $mesAtual; ?>&ano=<?php echo $anoAtual; ?>" class="btn-cancelar">Abortar Edição</a>
            <?php endif; ?>

        </form>
    </div>

</body>
</html>