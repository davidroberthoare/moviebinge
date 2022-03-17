<?php
/**
 * Google Showtime grabber
 * 
 * This file will grab the last showtimes of theatres nearby your zipcode.
 * Please make the URL your own! You can also add parameters to this URL: 
 * &date=0|1|2|3 => today|1 day|2 days|etc.. 
 * &start=10 gets the second page etc...
 * 
 * Please download the latest version of simple_html_dom.php on sourceForge:
 * http://sourceforge.net/projects/simplehtmldom/files/
 * 
 * @author Bas van Dorst <info@basvandorst.nl>
 * @version 0.1 
 * @package GoogleShowtime
 *
 * @modifyed by stephen byrne <gold.mine.labs@gmail.com>
 * @GoldMinelabs.com 
 */

$response = array("error"=>false, "data"=>null);

if(isset($_GET['loc'])){
	$location = urlencode($_GET['loc']);
	$date = isset($_GET['date']) ? $_GET['date'] : "0";
	$start = 0;	
} else{
	$response['error'] = "no params set";
}


if($response['error'] == false){
	$showtimes = array();
	$request_url = "http://www.google.com/movies?date=$date&near=$location&start=$start";
	
	require_once("phpfastcache/phpfastcache.php");
	phpFastCache::setup("storage","auto");
	$cache = phpFastCache();
	
	$response['data'] = $cache->get($request_url);
	if($response['data'] == null || isset($_GET['refresh'])) {
		getData();	//call the recusive search function
		$response['data'] = $showtimes;
	    
	    // Write products to Cache in 10 minutes with same keyword
	    $cache->set($request_url, $showtimes , 3600);
	}
}

header('Content-Type: application/json');
echo json_encode($response);



function getData(){
	
	global $response, $showtimes, $url, $date, $location, $start;
	$max_entries = 40;
	
	$url = "http://www.google.com/movies?date=$date&near=$location&start=$start";
	require_once('simple_html_dom.php');
	$curl = curl_init(); 
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');  
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);  
	$str = curl_exec($curl);  
	
	if(curl_errno($curl))
	{
	    $response['error'] = curl_error($curl);
	}
	curl_close($curl);  


	if($response['error'] == false){
		$html = str_get_html($str);
		
		
		// print '<pre>';
		foreach($html->find('#movie_results .theater') as $div) {
		    if($div->find('.movie .times span')){	//make sure there are times for at least one movie
			    // print theater and address info
			    // print "Theater:  ".$div->find('h2.name',0)->plaintext."\n";
			    // print "Address: ". $div->find('.info',0)->plaintext."\n";
			
				$theatre = array(
					"name" => trim($div->find('h2.name',0)->plaintext), 
					"address" => trim($div->find('.info',0)->plaintext),
					"movies" => array()
				);
			
			    // print all the movies with showtimes
			    foreach($div->find('.movie') as $movie) {
			        // print "Movie:    ".$movie->find('.name a',0)->innertext.'<br />';
					$info = trim($movie->find('.info',0)->plaintext);
					$info = html_entity_decode($info, ENT_QUOTES, "UTF-8");
				    $info = trim($info);
			        $info = preg_replace('/[^0-9a-z: ]/iu', '', $info);
					preg_match("/^([0-9])hr ([0-9][0-9])min/", $info, $matches);
					// print_r($matches);
					// die();
					$length = isset($matches[2]) ? array($matches[0], ($matches[1]*60) + $matches[2]) : array("2hr 00min", 120);

					$movie_data = array(
					"title" => trim($movie->find('.name a',0)->innertext), 
					// "info" => $info, 
					"length" => $length, 
					"times"=>array());
					
				    foreach($movie->find('.times span[style="color:"]') as $time) {
				        $timestring = $time->plaintext;
				        $timestring = html_entity_decode($timestring, ENT_QUOTES, "UTF-8");
				        $timestring = trim($timestring);
						// print $timestring;
						// $timestring = preg_replace("/&#?[a-z0-9]{2,8};/i","",$timestring);
				        $timestring = preg_replace('/[^0-9:]/i', '', $timestring);
						// print "$timestring" . ", ";
						$movie_data['times'][] = $timestring;
				    }
					// print "<br />";
					$theatre['movies'][] = $movie_data;
			    }
			    // print "<br /><br />";
				$showtimes[] = $theatre;
			}
		}
		
		if($html->find('#navbar img[src="//www.google.com/nav_next.gif"]') && $start < $max_entries) {
			$start += 10;
			sleep(1);	//before getting second page, etc...
			getData();	
		}
		// print_r($showtimes);
		
		// clean up memory
		$html->clear();
	}
}

?>