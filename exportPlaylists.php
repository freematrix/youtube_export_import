<?php
require_once __DIR__. '/_starter.php';

$res = array();

//************************************************************************************
// Setting
// Questa pagina la chiamo per esportare le playlista salvate e i relativi video
//
//
// Il metodo che chiamo è listPlaylists -> il primo parametro che passo è il tipo di informazione che voglio recuperare
// i restanti parametri vengono passati in un array
// attravero il risultato recuperato che è la lista delle playlist con gli id chiamo il secondo metodo 
// listPlaylistItems
// che recupera i video della singola playlist, 
// vedere pagina https://developers.google.com/youtube/v3/docs che tra l'altro permette di vedere anche il codice php che mi serve per fare la chiamata
//
// ********** Attenzione **********
// I risultati che mi da youtube sono massimo 50 (di più non me ne da) se una chiamata api mi da più di 50 risultati devo implementare la paginazione
// sui video in una plalist ho fatto la paginazione
// sulla lista delle playlist non ho fatto la paginazione  - nel caso implementare
// ********************************
//
// Se voglio cambiare le credenziali con cui eseguo la chiamata (account da importare o esportare)
// cambiare puntamento al file client_secret in modo corretto
//
// Se ricevo un errore del tipo
// { "error": { "code": 401, "message": "Request had invalid authentication credentials. Expected OAuth 2 access token, login cookie or other valid authentication credential. See https://developers.google.com/identity/sign-in/web/devconsole-project.", "errors": [ { "message": "Invalid Credentials", "domain": "global", "reason": "authError", "location": "Authorization", "locationType": "header" } ], "status": "UNAUTHENTICATED" } } 
// svuotare la cache e ricarire la pagina
//
// Se la pagina non funziona provare a lanciare oauth2callback.php e poi questa pagina 
$thispage = "http://localhost/youyube_export_import/exportPlaylists.php";
$credenziali_json = "client_secret.json";
$redirect_uri = 'http://localhost/youyube_export_import/oauth2callback.php';

//************************************************************************************



$client = new Google_Client();
$client->setAuthConfig($credenziali_json);
$client->addScope(Google_Service_Youtube::YOUTUBE_FORCE_SSL);

if (isset($_SESSION['access_token']) && $_SESSION['access_token']) 
{
	$client->setAccessToken($_SESSION['access_token']);
	$youtube = new Google_Service_Youtube($client);

	$queryParams = [
		'maxResults' => 50,
		'mine' => true
	];

	$response = $youtube->playlists->listPlaylists('snippet,contentDetails', $queryParams);
	foreach($response["items"] as $item)
	{
		$numero = 1;
		$response_interna = array();
		$listavideo = array();
		recuperaChunkVideoInCanale($youtube, $item, $response_interna, $listavideo, $numero);
		while(  property_exists($response_interna, "prevPageToken") && !empty($response_interna["nextPageToken"])  )
		{
			recuperaChunkVideoInCanale($youtube, $item, $response_interna, $listavideo, $numero);
		}

		$temp = array(
			"id" => $item["id"]
			, "title" => $item["snippet"]["title"]
			, "itemCount" => $item["contentDetails"]["itemCount"]
			, "video" => $listavideo
		);
		$res[] = $temp;
		
		/*
		$queryParams = [
			'maxResults' => intval($item["contentDetails"]["itemCount"]),
			'playlistId' => $item["id"]
		];
		$response_interna = $youtube->playlistItems->listPlaylistItems('snippet,contentDetails', $queryParams);

		foreach($response_interna as $item_interni)
		{
			$temp2 = array(
				"title" => $item_interni["snippet"]["title"]
				, "videoId" => $item_interni["contentDetails"]["videoId"]
				, "url" => "https://www.youtube.com/watch?v=" . $item_interni["contentDetails"]["videoId"]
			);
			$temp["video"][] = $temp2;
		}
		*/

		//$res[] = $temp;
	}

	stamparisultato_a_video($res);
	scrivi_output_json($res, "outputPlaylists.json");
	
	
} else {
  $redirect_uri = 'http://localhost/youyube_export_import/oauth2callback.php';
  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}




function recuperaChunkVideoInCanale(&$youtube, &$item, &$response_interna, &$listavideo, &$numero)
{
	$queryParams = [
		'maxResults' => intval($item["contentDetails"]["itemCount"]),
		'playlistId' => $item["id"]
	];

	if (  $response_interna != null && property_exists($response_interna, "nextPageToken") && !empty($response_interna["nextPageToken"])  )
	{
		$queryParams["pageToken"] = $response_interna["nextPageToken"];
	}

	$response_interna = $youtube->playlistItems->listPlaylistItems('snippet,contentDetails', $queryParams);

	foreach($response_interna as $item_interni)
	{
		$temp2 = array(
			"numero" => $numero
			, "title" => $item_interni["snippet"]["title"]
			, "videoId" => $item_interni["contentDetails"]["videoId"]
			, "url" => "https://www.youtube.com/watch?v=" . $item_interni["contentDetails"]["videoId"]
		);
		$numero++;
		$listavideo[] = $temp2;
	}
}