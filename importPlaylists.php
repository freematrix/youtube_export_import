<?php
require_once __DIR__. '/_starter.php';


//************************************************************************************
// Setting
// Questa pagina la uso per importare la playlist salvata con il file exportPlaylist.php in un nuovo account google/youtube
//
// Attenzione:
// Per la cancellazione delle playlist al momento non c'è nulla
//
// Se la pagina non funziona provare a lanciare oauth2callback.php e poi questa pagina 
//
// N.B Se ricevo errore per eccesso numero operazioni durante l'inserimento guardare l'ultimo tab che non ha dato errore e 
// da li deddurre da che riga e la lista id che devo inserire nell'url
$thispage = "http://localhost/youyube_export_import/importPlaylists.php?riga=1&lastlist_id=_blank";
$thispage_base = "http://localhost/youyube_export_import/importPlaylists.php";
$credenziali_json = "client_secret_2.json";
$redirect_uri = 'http://localhost/youyube_export_import/oauth2callback.php';


$numero_operazioni_max = 10; //mi dice il numero massimo di video che posso inserire, questo perchè ho un massimo di operazioni che posso eseguire
//************************************************************************************

if (  !isset($_GET['riga'])  ) {
    // riga serve per capire da che riga devo cominciare a inserire i video
    // se creo una lista non vale come operazione ma se inserisco un video vale come oprazione
    //quindi riga mi dice quanti video ho inserito precedentemente
    die("Per poter lanciare la pagina specificare in un query string \"riga\" che indica la riga da cui partire per poter inserire le playlist");
}

if (  !isset($_GET['lastlist_id'])  ) {
    // queso parametro mi serve per capire qual'è stata l'ultima lista creata nelle chiamate precedenti
    // se passo _blank allora sono al primo giro
    die("Per poter lanciare la pagina specificare in un query string \"lastlist_id\" che mi dice l'id dell'ultima lista creata, vuota se la prima chiamata");
}


$numero_riga_partenza = intval($_GET['riga']); // da quale riga comincio ad aggiungere i video alla playlist ? (una riga è un'operazione di inserimento video, nonla creazione delle lista)
$riga = 1;
$lastlist_id = $_GET["lastlist_id"];  // questa è valorizzata non la prima volta ma le volte successive
                                      // mi dice l'id dell'ultima lista creata

$ntot = 0;

try {
    $res = array();    
    $string = file_get_contents("outputPlaylists.json");
    if ($string === false) {
        // deal with error...
        die("errore nel leggere il file outputPlaylists.json");
    }

    $json_a = json_decode($string, true);
    if ($json_a === null) {
        // deal with error...
        die("errore nel decoddificare il json nel file outputPlaylists.json");
    }

    $ntot = calcola_video_totali_da_inserire($json_a);

    $client = new Google_Client();
	$client->setAuthConfig($credenziali_json);
    $client->addScope(Google_Service_Youtube::YOUTUBE_FORCE_SSL);
    
    if (isset($_SESSION['access_token']) && $_SESSION['access_token'])
	{
		$client->setAccessToken($_SESSION['access_token']);
		$service = new Google_Service_YouTube($client);
    
        foreach ($json_a as $item) 
        {
            $title = $item["title"];
            $idlista_creata = "";

            if(  $numero_riga_partenza === 1 || ($numero_riga_partenza !== 1 && $riga > $numero_riga_partenza)  )
            {
                // Define the $playlist object, which will be uploaded as the request body.
                $playlist = new Google_Service_YouTube_Playlist();
                                        
                // Add 'snippet' object to the $playlist object.
                $playlistSnippet = new Google_Service_YouTube_PlaylistSnippet();
                $playlistSnippet->setTitle($title);
                $playlist->setSnippet($playlistSnippet);
                $response = $service->playlists->insert('snippet,contentDetails', $playlist);
                $idlista_creata = $response["id"];    
            }
            else 
            {
                $idlista_creata = $lastlist_id;
            }

            $temp = array("lista" => $title, "video" => array());

            /*
            {
                "kind": "youtube#playlist",
                "etag": "hx8ZSjEQgiqy8Cu6kxOjROnTeys",
                "id": "PLrCwC-Iqdw7JLdhVlbfxqIs-SpQ9gEQat",
                "snippet": {
                    "publishedAt": "2020-07-06T18:05:54Z",
                    "channelId": "UCtqSZL9_xMDmQ_MDieLH0-w",
                    "title": "secondaLista",
                    "description": "",
                    "thumbnails": {
                    "default": {
                        "url": "http://s.ytimg.com/yts/img/no_thumbnail-vfl4t3-4R.jpg",
                        "width": 120,
                        "height": 90
                    },
                    "medium": {
                        "url": "http://s.ytimg.com/yts/img/no_thumbnail-vfl4t3-4R.jpg",
                        "width": 320,
                        "height": 180
                    },
                    "high": {
                        "url": "http://s.ytimg.com/yts/img/no_thumbnail-vfl4t3-4R.jpg",
                        "width": 480,
                        "height": 360
                    }
                    },
                    "channelTitle": "SimonP Degradi",
                    "localized": {
                    "title": "secondaLista",
                    "description": ""
                    }
                },
                "contentDetails": {
                    "itemCount": 0
                }
                }

            */


            foreach($item["video"] as $video)
            {
                if($numero_operazioni_max <= 0)
                {
                    $res[] = $temp;
                    $url = $thispage_base . "?riga=" . $riga . "&lastlist_id=" . $idlista_creata;
                    echo "<html></body>";
                    echo "Numero video totali da inserire (che corrisponde alle righe da processare) = <strong>$ntot</strong><br /><br />";
                    echo "Inserimento in sospeso: aspettare un pò e lanciare quessta pagina<br />";
                    echo "<a href='" . $url . "' target='_blank'>$url</a><br />";
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


                // Define the $playlistItem object, which will be uploaded as the request body.
                $playlistItem = new Google_Service_YouTube_PlaylistItem();

                // Add 'snippet' object to the $playlistItem object.
                $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
                $playlistItemSnippet->setPlaylistId($idlista_creata);
                $resourceId = new Google_Service_YouTube_ResourceId();
                $resourceId->setKind('youtube#video');
                $resourceId->setVideoId($video["videoId"]);
                $playlistItemSnippet->setResourceId($resourceId);
                $playlistItem->setSnippet($playlistItemSnippet);

                $response = $service->playlistItems->insert('snippet,contentDetails', $playlistItem);

                $temp["video"][] = array(
                    "numero" => $video["numero"]
                    , "title" => $video["title"]
                );
                $riga++;
                $numero_operazioni_max--;
                
                /*                
                {
                    "kind": "youtube#playlistItem",
                    "etag": "gZQJyl859nuwIpzFjJPXRDwrZc4",
                    "id": "UExyQ3dDLUlxZHc3SnU3UVlPclh0MkxpMU93MVlHQlZMWS41NkI0NEY2RDEwNTU3Q0M2",
                    "snippet": {
                        "publishedAt": "2020-07-06T17:58:56Z",
                        "channelId": "UCtqSZL9_xMDmQ_MDieLH0-w",
                        "title": "The Chordettes \"Lollipop\" & \"Mr. Sandman\"",
                        "description": "Saturday Night Beech-Nut Show. February 22, 1958. Re-uploaded by request. Both performances included in this upload instead of being separated.",
                        "thumbnails": {
                        "default": {
                            "url": "https://i.ytimg.com/vi/Fty3Nzc-oiY/default.jpg",
                            "width": 120,
                            "height": 90
                        },
                        "medium": {
                            "url": "https://i.ytimg.com/vi/Fty3Nzc-oiY/mqdefault.jpg",
                            "width": 320,
                            "height": 180
                        },
                        "high": {
                            "url": "https://i.ytimg.com/vi/Fty3Nzc-oiY/hqdefault.jpg",
                            "width": 480,
                            "height": 360
                        },
                        "standard": {
                            "url": "https://i.ytimg.com/vi/Fty3Nzc-oiY/sddefault.jpg",
                            "width": 640,
                            "height": 480
                        }
                        },
                        "channelTitle": "SimonP Degradi",
                        "playlistId": "PLrCwC-Iqdw7Ju7QYOrXt2Li1Ow1YGBVLY",
                        "resourceId": {
                        "kind": "youtube#video",
                        "videoId": "Fty3Nzc-oiY"
                        }
                    },
                    "contentDetails": {
                        "videoId": "Fty3Nzc-oiY",
                        "videoPublishedAt": "2012-12-01T03:51:46Z"
                    }
                    }

                */

            }
            $res[] = $temp;
        }

		stamparisultato_a_video($res);
		
	} else {
	  header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
	}        

} catch (Throwable $t) { 
    echo $t->getMessage();
}




function calcola_video_totali_da_inserire($json_a)
{
    $ntot = 0;
    foreach ($json_a as $item) 
    {    
        foreach($item["video"] as $video)
        {
            $ntot++;
        }
    }
    return $ntot;
}