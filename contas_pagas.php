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

$data_inicio = isset($_POST['data_inicio']) ? $_POST['data_inicio'] : date('Y-m-01');
$data_fim = isset($_POST['data_fim']) ? $_POST['data_fim'] : date('Y-m-t');
$search = isset($_POST['search']) ? $_POST['search'] : '';

$sql_total = "SELECT COUNT(*) AS total FROM contas_pagas WHERE data BETWEEN '$data_inicio' AND '$data_fim' AND descricao LIKE '%$search%'";
$result_total = $mysqli->query($sql_total);
$total_registros = $result_total->fetch_assoc()['total'];
$total_paginas = ceil($total_registros / 10);
$pagina_atual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
$offset = ($pagina_atual - 1) * 10;

$sql = "SELECT id, descricao, valor, data, hora FROM contas_pagas WHERE data BETWEEN '$data_inicio' AND '$data_fim' AND descricao LIKE '%$search%' ORDER BY data DESC, hora DESC LIMIT $offset, 10";
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
    <title>Contas Pagas</title>
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
    <h1>Contas Pagas</h1>
    <form method="post" action="">
        <label for="data_inicio">Data Início:</label>
        <input type="date" name="data_inicio" id="data_inicio" value="<?php echo $data_inicio; ?>">
        <label for="data_fim">Data Fim:</label>
        <input type="date" name="data_fim" id="data_fim" value="<?php echo $data_fim; ?>">
        <label for="search">Busca:</label>
        <input type="text" name="search" id="search" value="<?php echo $search; ?>">
        <button type="submit">Filtrar</button>
    </form>

    <h2>Histórico de Contas Pagas</h2>
    <table>
        <thead>
            <tr>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Data</th>
                <th>Hora</th>
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
                    <td>
                        <form method="post" action="contas.php" style="display:inline;">
                            <input type="hidden" name="descricao" value="<?php echo $row['descricao']; ?>">
                            <input type="hidden" name="valor" value="<?php echo $row['valor']; ?>">
                            <input type="hidden" name="data" value="<?php echo $row['data']; ?>">
                            <button type="submit">Registrar novamente</button>
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
        <a href="contas.php" class="button">Contas a Pagar</a>
    </div>
</body>
</html>
