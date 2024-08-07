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

if (isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $sql = "SELECT descricao, valor, data FROM contas_a_pagar WHERE id='$update_id'";
    $result = $mysqli->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
    } else {
        $error_message = "Erro na consulta: " . $mysqli->error;
        error_log($error_message, 3, $log_file);
        die($error_message);
    }
} else if (isset($_POST['descricao']) && isset($_POST['valor']) && isset($_POST['data']) && isset($_POST['id'])) {
    $descricao = $_POST['descricao'];
    $valor = str_replace(',', '.', $_POST['valor']);
    $data = $_POST['data'];
    $id = $_POST['id'];

    $sql = "UPDATE contas_a_pagar SET descricao='$descricao', valor='$valor', data='$data' WHERE id='$id'";
    if (!$mysqli->query($sql)) {
        $error_message = "Erro na atualização: " . $mysqli->error;
        error_log($error_message, 3, $log_file);
        die($error_message);
    }
    header('Location: contas.php');
    exit;
}

function formatar_valor($valor, $moeda) {
    if ($moeda == 'JPY') {
        return number_format($valor, 0) . " " . $moeda;
    } else {
        return number_format($valor, 2, ',', '.') . " " . $moeda;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Conta</title>
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
    </style>
</head>
<body>
    <h1>Editar Conta</h1>
    <form method="post" action="">
        <input type="hidden" name="id" value="<?php echo $update_id; ?>">
        <label for="descricao">Descrição:</label>
        <input type="text" name="descricao" id="descricao" value="<?php echo $row['descricao']; ?>"><br>

        <label for="valor">Valor:</label>
        <input type="text" name="valor" id="valor" value="<?php echo formatar_valor($row['valor'], $moeda); ?>"><br>

        <label for="data">Data:</label>
        <input type="date" name="data" id="data" value="<?php echo $row['data']; ?>"><br>

        <button type="submit">Salvar</button>
    </form>
</body>
</html>
