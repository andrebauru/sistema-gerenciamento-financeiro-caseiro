<?php
// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
define('SENHA_ACESSO', '1234');

if (isset($_POST['senha'])) {
    if ($_POST['senha'] === SENHA_ACESSO) {
        $_SESSION['logado'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $erro = "Senha incorreta.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
        label, input {
            font-size: 1.2em;
        }
        button {
            font-size: 1.2em;
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
    <h1>Login</h1>
    <?php if (isset($erro)) echo '<p style="color:red;">' . $erro . '</p>'; ?>
    <form method="post" action="">
        <label for="senha">Senha:</label>
        <input type="password" name="senha" id="senha">
        <button type="submit">Entrar</button>
    </form>
</body>
</html>
