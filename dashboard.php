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

$sql_gastos = "SELECT SUM(valor) AS total_gastos FROM gastos WHERE data BETWEEN '$data_inicio' AND '$data_fim'";
$sql_contas = "SELECT SUM(valor) AS total_contas FROM contas_a_pagar WHERE data BETWEEN '$data_inicio' AND '$data_fim'";
$sql_salario = "SELECT SUM(valor) AS total_salario FROM salario WHERE data BETWEEN '$data_inicio' AND '$data_fim'";
$sql_pago = "SELECT SUM(valor) AS total_pago FROM contas_pagas WHERE data BETWEEN '$data_inicio' AND '$data_fim'";

$result_gastos = $mysqli->query($sql_gastos);
$result_contas = $mysqli->query($sql_contas);
$result_salario = $mysqli->query($sql_salario);
$result_pago = $mysqli->query($sql_pago);

if (!$result_gastos || !$result_contas || !$result_salario || !$result_pago) {
    $error_message = "Erro na consulta: " . $mysqli->error;
    error_log($error_message, 3, $log_file);
    die($error_message);
}

$total_gastos = $result_gastos->fetch_assoc()['total_gastos'] ?? 0;
$total_contas = $result_contas->fetch_assoc()['total_contas'] ?? 0;
$total_salario = $result_salario->fetch_assoc()['total_salario'] ?? 0;
$total_pago = $result_pago->fetch_assoc()['total_pago'] ?? 0;

$total_restante = $total_salario - $total_gastos - $total_contas;

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
    <title>Dashboard</title>
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
        p {
            font-size: 1.5em;
        }
        .button {
            display: inline-block;
            margin: 10px;
            padding: 15px 25px;
            font-size: 1.5em;
            color: #fff;
            background-color: #007BFF;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }
        .button:hover {
            background-color: #0056b3;
        }
        .button-gastos {
            background-color: #FF5733;
        }
        .button-gastos:hover {
            background-color: #C70039;
        }
        .button-contas {
            background-color: #FFC300;
        }
        .button-contas:hover {
            background-color: #FF5733;
        }
        .button-salario {
            background-color: #DAF7A6;
        }
        .button-salario:hover {
            background-color: #FFC300;
        }
        .button-meta {
            background-color: #33FF57;
        }
        .button-meta:hover {
            background-color: #28B463;
        }
        .button-investimento {
            background-color: #3380FF;
        }
        .button-investimento:hover {
            background-color: #2853A6;
        }
        .button-admin {
            background-color: #800080;
        }
        .button-admin:hover {
            background-color: #550055;
        }
        .button-pagas {
            background-color: #FF69B4;
        }
        .button-pagas:hover {
            background-color: #FF1493;
        }
        nav {
            margin-top: 20px;
        }
        .back-button {
            margin-top: 20px;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 60vh;
            width: 80vw;
        }
        form {
            margin: 20px 0;
        }
        label, input {
            font-size: 1.2em;
            margin: 0 10px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Dashboard</h1>
    <form method="post" action="">
        <label for="data_inicio">Data Início:</label>
        <input type="date" name="data_inicio" id="data_inicio" value="<?php echo $data_inicio; ?>">
        <label for="data_fim">Data Fim:</label>
        <input type="date" name="data_fim" id="data_fim" value="<?php echo $data_fim; ?>">
        <button type="submit" class="button">Filtrar</button>
    </form>
    <p>Gastos: <?php echo formatar_valor($total_gastos, $moeda); ?></p>
    <p>Contas a pagar: <span style="color:<?php echo ($total_contas > $total_salario) ? 'red' : 'black'; ?>;"><?php echo formatar_valor($total_contas, $moeda); ?></span></p>
    <p>Salário: <?php echo formatar_valor($total_salario, $moeda); ?></p>
    <p>Total Pago: <?php echo formatar_valor($total_pago, $moeda); ?></p>
    <p>Total Restante: <span style="color:<?php echo ($total_restante < 0) ? 'red' : 'black'; ?>;"><?php echo formatar_valor($total_restante, $moeda); ?></span></p>

    <div class="chart-container">
        <canvas id="financeChart"></canvas>
    </div>
    <script>
        const ctx = document.getElementById('financeChart').getContext('2d');
        const financeChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Gastos', 'Contas a Pagar', 'Contas Pagas', 'Salário', 'Total Restante'],
                datasets: [{
                    label: 'Valor em <?php echo $moeda; ?>',
                    data: [<?php echo $total_gastos; ?>, <?php echo $total_contas; ?>, <?php echo $total_pago; ?>, <?php echo $total_salario; ?>, <?php echo $total_restante; ?>],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(255, 159, 64, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(60, 179, 113, 0.2)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(60, 179, 113, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Resumo Financeiro'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: '<?php echo $moeda; ?>' }).format(context.parsed.y);
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Valor (<?php echo $moeda; ?>)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Categoria'
                        }
                    }
                }
            }
        });
    </script>

    <nav>
        <a href="gastos.php" class="button button-gastos">💸 Gastos</a>
        <a href="contas.php" class="button button-contas">📅 Contas a Pagar</a>
        <a href="salario.php" class="button button-salario">💰 Salário</a>
        <a href="meta.php" class="button button-meta">🎯 Meta</a>
        <a href="investimento.php" class="button button-investimento">📈 Investimento</a>
        <a href="admin.php" class="button button-admin">⚙️ Administração</a>
        <a href="contas_pagas.php" class="button button-pagas">✅ Contas Pagas</a>
    </nav>
    <div class="back-button">
        <a href="dashboard.php" class="button">Voltar ao Dashboard</a>
    </div>
</boyzdy>
<hju/html>
