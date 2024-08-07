<?php
// Ativar exibição de erros para debug e registro em log
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['logado'])) {
    header('Location: index.php');
    exit;
}

include('db_connection.php');

// Obter configuração da moeda
$sql_moeda = "SELECT valor FROM configuracoes WHERE chave = 'moeda'";
$result_moeda = $mysqli->query($sql_moeda);
$moeda = $result_moeda->fetch_assoc()['valor'];

// Adicionar conta
if (isset($_POST['descricao']) && isset($_POST['valor']) && isset($_POST['data'])) {
    $descricao = $_POST['descricao'];
    $valor = str_replace(',', '.', $_POST['valor']); // Substituir vírgula por ponto para valores decimais
    $data = $_POST['data'];
    $hora = date('H:i:s');
    $repetir = isset($_POST['repetir']) ? 1 : 0;

    $sql = "INSERT INTO contas_a_pagar (descricao, valor, data, hora, repetir) VALUES ('$descricao', '$valor', '$data', '$hora', '$repetir')";
    if (!$mysqli->query($sql)) {
        $error_message = "Erro na inserção: " . $mysqli->error;
        error_log($error_message, 3, $log_file);
        die($error_message);
    }
    echo "Conta registrada com sucesso.";
}

// Atualizar conta
if (isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $update_descricao = $_POST['update_descricao'];
    $update_valor = str_replace(',', '.', $_POST['update_valor']); // Substituir vírgula por ponto para valores decimais
    $update_data = $_POST['update_data'];
    $repetir = isset($_POST['repetir']) ? 1 : 0;

    $sql = "UPDATE contas_a_pagar SET descricao='$update_descricao', valor='$update_valor', data='$update_data', repetir='$repetir' WHERE id='$update_id'";
    if (!$mysqli->query($sql)) {
        $error_message = "Erro na atualização: " . $mysqli->error;
        error_log($error_message, 3, $log_file);
        die($error_message);
    }
    echo "Conta atualizada com sucesso.";
}

// Excluir conta
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    $sql = "DELETE FROM contas_a_pagar WHERE id='$delete_id'";
    if (!$mysqli->query($sql)) {
        $error_message = "Erro ao excluir: " . $mysqli->error;
        error_log($error_message, 3, $log_file);
        die($error_message);
    }
    echo "Conta excluída com sucesso.";
}

// Marcar como paga e mover para a tabela contas_pagas
if (isset($_POST['pagar_id'])) {
    $pagar_id = $_POST['pagar_id'];

    // Selecionar a conta a ser paga
    $sql = "SELECT * FROM contas_a_pagar WHERE id='$pagar_id'";
    $result = $mysqli->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();

        // Inserir a conta na tabela contas_pagas
        $sql_insert = "INSERT INTO contas_pagas (descricao, valor, data, hora, repetir) VALUES ('{$row['descricao']}', '{$row['valor']}', '{$row['data']}', '{$row['hora']}', '{$row['repetir']}')";
        if (!$mysqli->query($sql_insert)) {
            $error_message = "Erro ao inserir em contas_pagas: " . $mysqli->error;
            error_log($error_message, 3, $log_file);
            die($error_message);
        }

        // Excluir a conta da tabela contas_a_pagar
        $sql_delete = "DELETE FROM contas_a_pagar WHERE id='$pagar_id'";
        if (!$mysqli->query($sql_delete)) {
            $error_message = "Erro ao excluir de contas_a_pagar: " . $mysqli->error;
            error_log($error_message, 3, $log_file);
            die($error_message);
        }

        echo "Conta marcada como paga.";
    } else {
        $error_message = "Erro ao selecionar conta: " . $mysqli->error;
        error_log($error_message, 3, $log_file);
        die($error_message);
    }
}

$mes_atual = date('Y-m');
$sql_total = "SELECT COUNT(*) AS total FROM contas_a_pagar WHERE DATE_FORMAT(data, '%Y-%m') = '$mes_atual'";
$result_total = $mysqli->query($sql_total);
$total_registros = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / 10);
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * 10;

$sql = "SELECT id, descricao, valor, data, hora, repetir FROM contas_a_pagar WHERE DATE_FORMAT(data, '%Y-%m') = '$mes_atual' ORDER BY data DESC, hora DESC LIMIT $offset, 10";
$result = $mysqli->query($sql);
if (!$result) {
    $error_message = "Erro na consulta: " . $mysqli->error;
    error_log($error_message, 3, $log_file);
    die($error_message);
}

function formatar_valor($valor, $moeda) {
    if ($moeda == 'JPY') {
        return number_format($valor, 0) . " " . $moeda;
    } else {
        return number_format($valor, 2, ',', '.') . " " . $moeda;
    }
}

// Adicionar contas repetitivas para o próximo mês
$contas_repetitivas = "INSERT INTO contas_a_pagar (descricao, valor, data, hora, repetir) SELECT descricao, valor, DATE_ADD(data, INTERVAL 1 MONTH), hora, repetir FROM contas_a_pagar WHERE repetir=1 AND DATE_FORMAT(data, '%Y-%m') = '$mes_atual'";
if (!$mysqli->query($contas_repetitivas)) {
    $error_message = "Erro ao adicionar contas repetitivas: " . $mysqli->error;
    error_log($error_message, 3, $log_file);
    die($error_message);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contas a Pagar</title>
    <style>
        body {
            background: linear-gradient(to right, #87CEFA, #90EE90);
            font-family: Arial, sans-serif;
            text-align: center;
            color: #333;
        }
        h1 {
            font-size: 2.5em;
        }
        form {
            display: inline-block;
            margin-top: 20px;
        }
        label, input, button {
            font-size: 1.2em;
        }
        button {
            padding: 10px 20px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        .back-button {
            margin-top: 20px;
            display: inline-block;
        }
        .pagination {
            margin: 20px 0;
        }
        .pagination a {
            margin: 0 5px;
            padding: 10px 15px;
            background-color: #007BFF;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }
        .pagination a:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Contas a Pagar</h1>
    <form method="post" action="">
        <label for="descricao">Descrição:</label>
        <input type="text" name="descricao" id="descricao"><br>

        <label for="valor">Valor:</label>
        <input type="text" name="valor" id="valor"><br>

        <label for="data">Data:</label>
        <input type="date" name="data" id="data"><br>

        <label for="repetir">Repetir todo mês:</label>
        <input type="checkbox" name="repetir" id="repetir"><br>

        <button type="submit">Adicionar</button>
    </form>

    <h2>Contas do Mês</h2>
    <table>
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Repetir</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['descricao']; ?></td>
                    <td><?php echo formatar_valor($row['valor'], $moeda); ?></td>
                    <td><?php echo $row['data']; ?></td>
                    <td><?php echo $row['hora']; ?></td>
                    <td><?php echo $row['repetir'] ? 'Sim' : 'Não'; ?></td>
                    <td>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="update_descricao" value="<?php echo $row['descricao']; ?>">
                            <input type="hidden" name="update_valor" value="<?php echo $row['valor']; ?>">
                            <input type="hidden" name="update_data" value="<?php echo $row['data']; ?>">
                            <button type="submit">Editar</button>
                        </form>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                            <button type="submit">Excluir</button>
                        </form>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="pagar_id" value="<?php echo $row['id']; ?>">
                            <button type="submit">Pagar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <div class="pagination">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
            <a href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <div class="back-button">
        <a href="dashboard.php" class="button">Voltar ao Dashboard</a>
    </div>
</body>
</html>
