<?php
require_once __DIR__. '/_starter.php';


//************************************************************************************
// Setting
// Questa pagina la uso per importare i canali salvati nel json in un nuovo account google/youtube
// la uso per cancellare i canali che sono dentro uno specifico file json
// In questo secondo caso aggiungo un query string alla pagina
//
// Attenzione:
// Per l'inserimento è obbligatorio inserire un querystring "riga" che mi dice da che riga devo partire del json a inserire gli item
// Questo perchè ho un limite massimo di volte che posso richiamare la funzione insert in periodo di tempo 
// Per questo motivio devo chiamare la prima volta con riga=1 e le successive volte con l'indirizzo che stamperò a video
// 
// Per la cancellazione devo richiamare con il qyerystring delete=y
//
// Se la pagina non funziona perchè non sono autenticato lanciare oauth2callback.php e poi questa pagina 
//
// N.B Se ricevo errore per eccesso numero operazioni durante l'inserimento guardare l'ultimo tab aperto che non ha dato errore e 
// da li dedurre la riga che poi devo inserire nel querystring dell url

$thispage = "http://localhost/youyube_export_import/importSubscriptions.php?riga=1";
$thispage = "http://localhost/youyube_export_import/importSubscriptions.php?delete=y";

$thispage_base = "http://localhost/youyube_export_import/importSubscriptions.php";
$credenziali_json = "client_secret_2.json";
$redirect_uri = 'http://localhost/youyube_export_import/oauth2callback.php';

$step_inseirmento = 50; //mi dice il numero massimo di chiamate insert che faccio ogni volta che ricahiamo la pagina 
$numero_riga_partenza = 1;
//************************************************************************************
$bdelete = false;
if (isset($_GET['delete'])) {
    $bdelete = true;
}
else 
{
    //sono nel caso dell'inserimento dei subscriptions
    //in questo caso devo speicficare un query string che mi dice da che riga del json devo partire a inserire le sottoscrizioni
    if (!isset($_GET['riga'])) {
        die("Per poter lanciare la pagina specificare in un query string \"riga\" che indica la riga da cui partire per poter inserire le subscription");
    }
    else 
    {
        // riprendo a inserire gli item a partire dalla riga specifica
        // la riga fa riferimento al json creato con l'export
        $numero_riga_partenza = intval($_GET['riga']);
    }
}



try {
    $res = array();
    $riga = 1;
    $string = file_get_contents("outputSubscriptions.json");
    if ($string === false) {
        // deal with error...
    }

    $json_a = json_decode($string, true);
    if ($json_a === null) {
        // deal with error...
    }

    $client = new Google_Client();
	$client->setAuthConfig($credenziali_json);
    $client->addScope(Google_Service_Youtube::YOUTUBE_FORCE_SSL);
    
    if (isset($_SESSION['access_token']) && $_SESSION['access_token'])
	{
		$client->setAccessToken($_SESSION['access_token']);
		$service = new Google_Service_YouTube($client);
    
        
        foreach ($json_a as $item) 
        {
            if($bdelete)
            {
                // ******************** delete ********************
                //questo è il caso in cui mi tolgo da un canales.
                $service->subscriptions->delete($item["subscriptionsID"]);
                $res[] = array(
                    "riga" => $riga
                    , "risultato" => "sembra ok"
                );
                
            }
            else 
            {
                if($step_inseirmento <= 0)
                {
                    $url = $thispage_base . "?riga=" . $item["nriga"];
                    echo "<html></body>";
                    echo "Inserimento in sospeso: aspettare un pò e lanciare quessta pagina<br />";
                    echo "<a href='$url' target='_blank'>$url</a>";
                    echo "<br /><br /><br />";
                    echo "<pre>";
                    print_r($res);
                    echo "</pre>";
                    echo "</body></html>";
                    die();
                    break;
                }
                if($numero_riga_partenza !== 1 && $riga < $numero_riga_partenza)
                {
                    $riga++;
                    continue;
                }

                // ******************** insert ********************
                // questo è il caso in cui mi aggiungo a un canale
            
                // Define the $subscription object, which will be uploaded as the request body.
                $subscription = new Google_Service_YouTube_Subscription();
                
                // Add 'snippet' object to the $subscription object.
                $subscriptionSnippet = new Google_Service_YouTube_SubscriptionSnippet();
                $resourceId = new Google_Service_YouTube_ResourceId();
                $resourceId->setChannelId($item["id"]);
                $resourceId->setKind('youtube#channel');
                $subscriptionSnippet->setResourceId($resourceId);
                $subscription->setSnippet($subscriptionSnippet);
                
                $response = $service->subscriptions->insert('snippet,contentDetails', $subscription);
                $res[] = array(
                    "riga" => $item["nriga"]
                    , "canale" => $response["snippet"]["title"]
                );
                $step_inseirmento--;
            }
            
            $riga++;
            
        }

		stamparisultato_a_video($res);
		
	} else {
	  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
	}        

} catch (Throwable $t) { 
    echo $t->getMessage();
}

