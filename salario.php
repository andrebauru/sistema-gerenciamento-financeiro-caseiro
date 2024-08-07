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

// Definir o número de registros por página
$registros_por_pagina = 10;
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * $registros_por_pagina;

// Adicionar salário
if (isset($_POST['valor']) && isset($_POST['data'])) {
    $valor = str_replace(',', '.', $_POST['valor']); // Substituir vírgula por ponto para valores decimais
    $data = $_POST['data'];

    $sql = "INSERT INTO salario (valor, data) VALUES ('$valor', '$data')";
    if (!$mysqli->query($sql)) {
        $error_message = "Erro na inserção: " . $mysqli->error;
        error_log($error_message, 3, $log_file);
        die($error_message);
    }
    echo "Salário registrado com sucesso.";
}

// Atualizar salário
if (isset($_POST['update_id'])) {
    $update_id = $_POST['update_id'];
    $update_valor = str_replace(',', '.', $_POST['update_valor']); // Substituir vírgula por ponto para valores decimais
    $update_data = $_POST['update_data'];

    $sql = "UPDATE salario SET valor='$update_valor', data='$update_data' WHERE id='$update_id'";
    if (!$mysqli->query($sql)) {
        $error_message = "Erro na atualização: " . $mysqli->error;
        error_log($error_message, 3, $log_file);
        die($error_message);
    }
    echo "Salário atualizado com sucesso.";
}

// Excluir salário
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    $sql = "DELETE FROM salario WHERE id='$delete_id'";
    if (!$mysqli->query($sql)) {
        $error_message = "Erro ao excluir: " . $mysqli->error;
        error_log($error_message, 3, $log_file);
        die($error_message);
    }
    echo "Salário excluído com sucesso.";
}

$mes_atual = date('Y-m');
$sql_total = "SELECT COUNT(*) AS total FROM salario WHERE DATE_FORMAT(data, '%Y-%m') = '$mes_atual'";
$result_total = $mysqli->query($sql_total);
$total_registros = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

$sql = "SELECT id, valor, data FROM salario WHERE DATE_FORMAT(data, '%Y-%m') = '$mes_atual' ORDER BY data DESC LIMIT $offset, $registros_por_pagina";
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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salário</title>
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
    <h1>Salário</h1>
    <form method="post" action="">
        <label for="valor">Valor:</label>
        <input type="text" name="valor" id="valor"><br>

        <label for="data">Data:</label>
        <input type="date" name="data" id="data"><br>

        <button type="submit">Adicionar</button>
    </form>

    <h2>Histórico de Salário</h2>
    <table>
        <thead>
            <tr>
                <th>Valor</th>
                <th>Data</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo formatar_valor($row['valor'], $moeda); ?></td>
                    <td><?php echo $row['data']; ?></td>
                    <td>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="update_id" value="<?php echo $row['id']; ?>">
                            <input type="hidden" name="update_valor" value="<?php echo $row['valor']; ?>">
                            <input type="hidden" name="update_data" value="<?php echo $row['data']; ?>">
                            <button type="submit">Editar</button>
                        </form>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                            <button type="submit">Excluir</button>
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
