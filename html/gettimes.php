<?php
/**
http://developer.tmsapi.com/io-docs - API Movie Data fetcher
 */

 error_reporting(E_ERROR);
 ini_set('display_errors', 1);

//  CACHEING
require("vendor/autoload.php");
use Phpfastcache\Helper\Psr16Adapter;
$defaultDriver = 'Files';
$Psr16Adapter = new Psr16Adapter($defaultDriver);

// $apikey = "tbuae4zpk4byd8c5un4d7acm";
$apikey = "5m4jsfc3ermyus7yfafghdvh";	//new api key for drhmedia account
$response = array("error"=>false, "data"=>null);
$loc = "";

$assets_base = "http://developer.tmsimg.com/";

if(isset($_GET['lat']) && isset($_GET['lng'])){
	$lat = urlencode($_GET['lat']);
	$lng = urlencode($_GET['lng']);
	$loc = "lat=$lat&lng=$lng";
}elseif (isset($_GET['zip'])) {
	$zip = urlencode($_GET['zip']);
	$loc = "zip=$zip";
}else{
	$response['error'] = "no params set";
}

	$date = isset($_GET['date']) ? $_GET['date'] : "2015-09-22";
	$radius = "30";	//miles from location


if($response['error'] == false){
	$showtimes = array();
	$url = "https://data.tmsapi.com/v1.1/movies/showings?startDate=$date&$loc&radius=$radius&imageSize=Md&api_key=$apikey";
	$url_key = urlencode($url);

	// die($url_key);

	if(!$Psr16Adapter->has($url_key)){
		$response['cached']=false;
		getData();	//call the recusive search function
		$response['data'] = $showtimes;
	    // Write products to Cache in 10 minutes with same keyword
	    $Psr16Adapter->set($url_key, $showtimes , 3600);

	}else{
		// Getter action
		$response['data'] = $Psr16Adapter->get($url_key);
		$response['cached']=true;
	}


	// require_once("phpfastcache/phpfastcache.php");
	// phpFastCache::setup("storage","auto");
	// $cache = phpFastCache();

	// $response['data'] = $cache->get($url);
	// if($response['data'] == null || isset($_GET['refresh'])) {
	// 	$response['cached']=false;
	// 	getData();	//call the recusive search function
	// 	$response['data'] = $showtimes;
	//     // Write products to Cache in 10 minutes with same keyword
	//     $cache->set($url, $showtimes , 3600);
	// }else{
	// 	$response['cached']=true;
	// }
}

header('Content-Type: application/json');
echo json_encode($response);



function getData(){

	global $response, $showtimes, $url, $assets_base, $apikey;
	$max_entries = 40;
  if(!isset($_GET['test'])){
  	$curl = curl_init();
  	curl_setopt($curl, CURLOPT_URL, $url);
  	curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
  	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
  	$str = curl_exec($curl);
    // die($str);
  	if(curl_errno($curl))
  	{
  	    $response['error'] = curl_error($curl);
  	}
  	curl_close($curl);
  }
  else{  //use the test data
      $str = file_get_contents("sampledata.json");
  }

	if($response['error'] == false){
		// echo $str;
		$data = json_decode($str, true);
		if(is_array($data)){
			foreach ($data as $key => $i) {
				if($i['subType']=="Feature Film"){	//only list films
					$m = array();
					$m['id'] = $i['tmsId'];
					$m['title'] = $i['title'];
					$m['releaseDate'] = $i['releaseDate'];
					$m['runTime'] = $i['runTime'];
					$m['shortDescription'] = $i['shortDescription'];
					$m['longDescription'] = $i['longDescription'];
					$m['topCast'] = $i['topCast'];
					$m['directors'] = $i['directors'];
					$m['advisories'] = $i['advisories'];
					$m['officialUrl'] = $i['officialUrl'];
					$m['qualityRating'] = $i['qualityRating']['value'];
					$m['ratings'] = $i['ratings'][0]['code'];
					$m['advisories'] = $i['advisories'];
					$m['image'] = $assets_base . $i['preferredImage']['uri'] . "?api_key=$apikey";
					$shows = array();
					foreach ($i['showtimes'] as $key => $s) {
						$shows[] = array(
							"theatre" => $s['theatre']['name'],
							"dateTime" => $s['dateTime'],
							"ticketURI" => $s['ticketURI'],
							"quals" => $s['quals'],
						);
					}
					$m['showtimes'] = $shows;



					$showtimes[] = $m;

				}
			}
		}else{
      $response['error'] == "not array data";
    }
	}
}

?>
