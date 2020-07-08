<?php
require_once __DIR__.'/vendor/autoload.php';

session_start();

//************************************************************************************
// Setting
// questa pagina la uso per autenticarmi a google e poi questa farà la redirect verso 
// la pagina reale che deve richiamare le api
// Modificare $redirect_uri a seconda della pagina che voglio eseguire dopo l'autenticazione
$credenziali_json = "client_secret.json";
$thispage = 'http://localhost/youyube_export_import/oauth2callback.php';
//$redirect_uri = 'http://localhost/youyube_export_import/importSubscriptions.php';
//$redirect_uri = 'http://localhost/youyube_export_import/exportSubscriptions.php';


//************************************************************************************

$client = new Google_Client();
$client->setAuthConfigFile($credenziali_json);
$client->setRedirectUri($thispage);
$client->addScope(Google_Service_Youtube::YOUTUBE_FORCE_SSL);

if (!isset($_GET['code'])) {
	$auth_url = $client->createAuthUrl();
	header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
} else {
	$client->authenticate($_GET['code']);
	$_SESSION['access_token'] = $client->getAccessToken();
	echo "ricaricare pagina che mi interessa lanciare";
	//header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}