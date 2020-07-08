<?php

require_once __DIR__. '/_starter.php';

$res = array();
$numero = 1;

//************************************************************************************
// Setting
// Questa pagina la uso per esportare i canali a cui mi sono iscritto, 
//
// Il metodo che chiamo è listSubscriptions -> il primo parametro che passo è il tipo di informazione che voglio recuperare
// i restanti parametri vengono passati in un array
// vedere pagina https://developers.google.com/youtube/v3/docs che tra l'altro permette di vedere anche il codice php che mi serve per fare la chiamata
//
// ********** Attenzione **********
// I risultati che mi da youtube sono massimo 50 (di più non me ne da) se una chiamata api mi da più di 50 risultati devo implementare la paginazione
// sulla lista dei canali sottoscritti ho messo la paginazione
// ********************************
//
// il numero potrebbe non essere a quello che mi da la pagina 
// https://www.youtube.com/subscription_manager
// perchè alcuni canali probabilmente non sono più disponibili. Comunque quello che tiro 
// fuori dalle api è corrretto
// 
// Se voglio cambiare le credenziali con cui eseguo la chiamata (account da importare o esportare)
// cambiare puntamento al file client_secret in modo corretto
//
// Se ricevo un errore del tipo
// { "error": { "code": 401, "message": "Request had invalid authentication credentials. Expected OAuth 2 access token, login cookie or other valid authentication credential. See https://developers.google.com/identity/sign-in/web/devconsole-project.", "errors": [ { "message": "Invalid Credentials", "domain": "global", "reason": "authError", "location": "Authorization", "locationType": "header" } ], "status": "UNAUTHENTICATED" } } 
// svuotare la cache e ricarire la pagina
//
// Se la pagina non funziona provare a lanciare oauth2callback.php e poi questa pagina 
$thispage = "http://localhost/youyube_export_import/exportSubscriptions.php";
$credenziali_json = "client_secret.json";
$redirect_uri = 'http://localhost/youyube_export_import/oauth2callback.php';

//************************************************************************************


try {
	$client = new Google_Client();
	$client->setAuthConfig($credenziali_json);
	$client->addScope(Google_Service_Youtube::YOUTUBE_FORCE_SSL);

	if (isset($_SESSION['access_token']) && $_SESSION['access_token']) 
	{
		$client->setAccessToken($_SESSION['access_token']);
		$youtube = new Google_Service_Youtube($client);
		
		
		$response = array();
		recuperaChunkSubscription($youtube, $response, $res, $numero);	
		while(  property_exists($response, "prevPageToken") && !empty($response["nextPageToken"])  )
		{
			recuperaChunkSubscription($youtube, $response, $res, $numero);	
		}
		
		stamparisultato_a_video($res);
		scrivi_output_json($res, "outputSubscriptions.json");
		
	} else {
	  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
	}    
} catch (Throwable $t) { 
    echo $t->getMessage();
}




//simulo una paginazione con questa funzione
function recuperaChunkSubscription(&$youtube, &$response, &$res, &$numero)
{
	$queryParams = [
		'maxResults' => 50,
		'mine' => true,
		'order' => 'alphabetical'
	];
	
	if (  $response != null && property_exists($response, "nextPageToken") && !empty($response["nextPageToken"])  )
	{
		$queryParams["pageToken"] = $response["nextPageToken"];
	}
	
	$response = $youtube->subscriptions->listSubscriptions('snippet,contentDetails', $queryParams);
	
	

	foreach($response["items"] as $item)
	{
		//stamparisultato_a_video($item);
		//die();

		$res[] = array(
			"nriga" => $numero
			, "titolo" => $item["snippet"]["title"]
			, "id" => $item["snippet"]["resourceId"]["channelId"]
			, "subscriptionsID" => $item["id"]
			, "doppione" => cercadoppione($res, $item["snippet"]["resourceId"]["channelId"])
		);
		$numero++;
	}
}

function cercadoppione($res, $channelId)
{
	foreach($res as $t)
	{
		if($t["id"] == $channelId)
			return "sidoppione";
	}
	return "";
}