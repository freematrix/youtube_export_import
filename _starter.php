<?php 
require_once __DIR__.'/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();


function stamparisultato_a_video($res)
{
    echo "<html></body><pre>";
	print_r($res);
	echo "</pre></body></html>";
}

function scrivi_output_json($res, $nome_file_out)
{
    if (file_exists($nome_file_out)) {
        unlink($nome_file_out);
    }

    $myJSON = json_encode($res, JSON_PRETTY_PRINT);
    echo file_put_contents($nome_file_out, $myJSON);
}