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

if (isset($_POST['nova_senha']) || isset($_POST['moeda']) || isset($_POST['timezone']) || isset($_POST['host']) || isset($_POST['username']) || isset($_POST['password'])) {
    $nova_senha = $_POST['nova_senha'];
    $moeda = $_POST['moeda'];
    $timezone = $_POST['timezone'];
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($nova_senha) {
        define('SENHA_ACESSO', $nova_senha);
    }

    if ($moeda) {
        $sql_moeda = "UPDATE configuracoes SET valor = '$moeda' WHERE chave = 'moeda'";
        if (!$mysqli->query($sql_moeda)) {
            $error_message = "Erro na atualização da moeda: " . $mysqli->error;
            error_log($error_message, 3, $log_file);
            die($error_message);
        }
    }

    if ($timezone) {
        $sql_timezone = "UPDATE configuracoes SET valor = '$timezone' WHERE chave = 'timezone'";
        if (!$mysqli->query($sql_timezone)) {
            $error_message = "Erro na atualização do timezone: " . $mysqli->error;
            error_log($error_message, 3, $log_file);
            die($error_message);
        }
    }

    if ($host || $username || $password) {
        $db_content = file_get_contents('db_connection.php');
        $db_content = preg_replace("/(\\$host\s*=\s*').*?(';)/", "\$1$host\$2", $db_content);
        $db_content = preg_replace("/(\\$user\s*=\s*').*?(';)/", "\$1$username\$2", $db_content);
        $db_content = preg_replace("/(\\$pass\s*=\s*').*?(';)/", "\$1$password\$2", $db_content);
        file_put_contents('db_connection.php', $db_content);
    }

    echo "Configurações atualizadas com sucesso.";
}

// Função para fazer backup do banco de dados e das páginas
function backupData() {
    global $db, $user, $pass;
    $backup_file = 'backup_' . date('Ymd_His') . '.sql';
    $zip_file = 'backup_' . date('Ymd_His') . '.zip';
    
    $command = "mysqldump --user=$user --password=$pass $db > $backup_file";
    system($command);

    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($backup_file);
        $files = glob('*');
        foreach ($files as $file) {
            if (is_file($file) && $file != $zip_file && $file != $backup_file) {
                $zip->addFile($file);
            }
        }
        $zip->close();
        unlink($backup_file);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zip_file . '"');
        header('Content-Length: ' . filesize($zip_file));
        readfile($zip_file);
        unlink($zip_file);
        exit;
    } else {
        echo "Erro ao criar backup.";
    }
}

if (isset($_POST['backup'])) {
    backupData();
}

// Obter configuração atual da moeda
$sql_moeda_atual = "SELECT valor FROM configuracoes WHERE chave = 'moeda'";
$result_moeda_atual = $mysqli->query($sql_moeda_atual);
$moeda_atual = $result_moeda_atual->fetch_assoc()['valor'];

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração</title>
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
        label, input, select, button {
            font-size: 1.2em;
            display: block;
            margin: 10px 0;
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
        .back-button {
            margin-top: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <h1>Administração</h1>
    <form method="post" action="">
        <label for="nova_senha">Nova Senha:</label>
        <input type="password" name="nova_senha" id="nova_senha">

        <label for="moeda">Moeda:</label>
        <select name="moeda" id="moeda">
            <option value="BRL" <?php if ($moeda_atual == 'BRL') echo 'selected'; ?>>BRL</option>
            <option value="USD" <?php if ($moeda_atual == 'USD') echo 'selected'; ?>>USD</option>
            <option value="EUR" <?php if ($moeda_atual == 'EUR') echo 'selected'; ?>>EUR</option>
            <option value="JPY" <?php if ($moeda_atual == 'JPY') echo 'selected'; ?>>JPY</option>
        </select>

        <label for="timezone">Timezone:</label>
        <input type="text" name="timezone" id="timezone">

        <label for="host">Host do Banco de Dados:</label>
        <input type="text" name="host" id="host">

        <label for="username">Username do Banco de Dados:</label>
        <input type="text" name="username" id="username">

        <label for="password">Password do Banco de Dados:</label>
        <input type="password" name="password" id="password">

        <button type="submit">Salvar</button>
    </form>
    <form method="post" action="">
        <button type="submit" name="backup">Fazer Backup</button>
    </form>
    <div class="back-button">
        <a href="dashboard.php" class="button">Voltar ao Dashboard</a>
    </div>
</body>
</html>
