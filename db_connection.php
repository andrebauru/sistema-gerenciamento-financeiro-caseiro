<?php
// Ativar exibição de erros para debug e registro em log
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir arquivo de log
$log_file = 'error_log.txt';

// Verificar se a função de log de erros já foi definida
if (!function_exists('log_error')) {
    // Definir função de log de erros personalizados
    function log_error($errno, $errstr, $errfile, $errline) {
        global $log_file;
        $log_message = "[" . date("Y-m-d H:i:s") . "] Erro: $errstr em $errfile na linha $errline
";
        error_log($log_message, 3, $log_file);
    }
    set_error_handler("log_error");
}

$host = 'localhost';
$db = 'terreiro_andretsc';
$user = 'terreiro_andretsc';
$pass = 'Wilhelm1988';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    $error_message = 'Erro de conexão (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error;
    error_log($error_message, 3, $log_file);
    die($error_message);
}
?>