<?php
/**********************************************************************************
 *
 *  Purpose: This script screenscrapes the names and cities from every page of the website
 *           theukdatabase.net in order to try to get a list of Bad Actors from the UK
 * 	         This data is captured into a flatfile allowing for later comparison to Supporters of Compassion UK in an attempt to 
 *           fulfill the requirements of PATCh (Protect All The Children)  which is to remove any Bad Actors from our sponsor pool
 *
 *	Programmer:  Jonathan Wagner, jwagner@us.ci.org
 *  Phone:  864-567-9302 (leave a message)
 *  Date:  December 17, 2019
 *  
 *  License: Code is licensed under the GNU GENERAL PUBLIC LICENSE (see file GN?U_GEN_PUB_License.txt for details)
 *
 */


 ini_set(‘memory_limit’,’1G’);
require('simple_html_dom.php');

function LogIt($message) {
	global $LogMessage;
	$numargs = func_num_args();
	if ($LogMessage) {
	   if ($numargs >1){
		$logToFile = func_get_arg(1);
		if ($logToFile=="t" OR $logToFile=="T" OR $logToFile === true) {
			if (null !== func_get_arg(2)){
				$fname=func_get_arg(2);
			} else {
				$fname="default.log";
			}
			$fn=fopen($fname,"a+");
			$now=time();
		}
		if (is_array($message)){
			foreach($message as $m){
				$m1=$now.", ".$m.PHP_EOL;
				if (isset($fn)) { fwrite($fn, $m1); }
			}
			var_dump($message);
		} else {
			$m1=$now.", ".$message.PHP_EOL;
				if (isset($fn)) { fwrite($fn, $m1); }
			echo $message.PHP_EOL;
		}
	   } else {
		if (is_array($message)){
			var_dump($message);
		} else {
			echo $message.PHP_EOL;
		}
	   }
	}
}

function fetchDOM($url="")
{
	global $LogMessage;
	global $output;
	$url=rtrim(strtolower($url));
//	unset($html);
//	unset($headers);
        $html = new simple_html_dom();
        $html->load_file($url);
    	$headers = @get_headers($url);
    if (in_array("HTTP/1.1 200 OK", $headers)) {
        foreach($html->find('article') as $element)
        {
		$href = $element->find('href'); 
		$name = $element->find('a');
		//LogIt($name,"t","finalloop.log");
		for($i=0; $i<sizeof($name); $i++){
			$n=$name[$i]->innertext;
			LogIt($n,"t","finalloop.log");
			$x=explode('&',$n);
			$y=explode(' ',$x[0]);
			if ($y[0] == "Continue"){
			} else {
				$c1=array_pop($x);
				$c=explode(';',$c1);
				$city=array_pop($c);
				$nameOut=$x[0];
				$writeout=$nameOut."| ".$city."| ".$name[$i]->href.PHP_EOL;
				fwrite($output, $writeout);
			}
	    	}
	}
   }
}

function fetchUrls($location, $page) {
	global $LogMessage;
        global $urlList;
	global $urlFP;
	if (strpos($location," - ")) {
		$location=str_replace(" - ","-",$location);
	}
	if (strpos($location," ")) {
		$location=str_replace(" ","-",$location);
	}
        if (strpos($location,"/")){
		$location=str_replace("/","",$location);
	}
	
    $url = "https://theukdatabase.net/category/$location/page/$page"; 
	// some of their url's don't follow their own standards

        if (strpos($url,"Avon")) {
		$url="https://theukdatabase.net/category/avon-and-somerset-bristol/page/$page";
        }

        if (strpos($url,"Boys")) {
                $url="https://theukdatabase.net/category/dyfed-powys/page/$page";
        }

        if (strpos($url,"Dyfed")) {
                $url="https://theukdatabase.net/category/dyfed-powys/page/$page";
        }

	if (strpos($url,"Childrens-homes/Boarding-schools")) {
		$url="https://theukdatabase.net/category/childrens-homesboarding-schools/page/$page";
	}
	if (strpos($url,"Company-Director")) {
		$url="https://theukdatabase.net/category/company-directorowner/page/$page";
	}
	if (strpos($url,"Councillor")) {
		$url="https://theukdatabase.net/category/councillor-political-party/page/$page";
        }

	if (strpos($url,"Dumfries")) {
                $url="https://theukdatabase.net/category/dumfries-galloway/page/$page";
        }

	if (strpos($url,"Jehovah")) {
                $url="https://theukdatabase.net/category/jehovahs-witnesses/page/$page";
        }

	if (strpos($url,"Overseas")) {
                $url="https://theukdatabase.net/category/overseas-linked-to-uk/page/$page";
        }

	if (strpos($url,"Ross")) {
                $url="https://theukdatabase.net/category/ross-shire/page/$page";
        }

    $page+=1;                          
    $html = new simple_html_dom();

    $headers = @get_headers($url); 
    if (in_array("HTTP/1.1 200 OK", $headers)) {
	logIt("valid url: $url".PHP_EOL, "t", "finalloop.log");;
	fwrite($urlFP, $url.PHP_EOL);
	$urlList[] = $url;	
	fetchUrls($location, $page);
    } else {
	return($urlList);
    }
}

function fetchCat() 
{
	global $LogMessage;
   global $categories;
   global $catlist;
   $url = "https://theukdatabase.net/the-uk-database/";
   $html = new simple_html_dom();
   $html->load_file($url);
$opt = $html->find('option');
for ($i = 0; $i < count($opt); $i++) {
        $element = $opt[$i];
        $value = $element->value;
        $content = $element->innertext;
	$x=explode('&', $content);
	$catlist[]=$x[0];
    }
   return($catlist);
}
// Program Start

$catlist = array();
$categories = array();
$namelist = array();
$count=0;
$names = array();
$items = array();
$urlList = array();
$theUrl = array();
//$LogMessage = True;	// set to FALSE to disable Logging TRUE to enable logging
$LogMessage = False;	// set to FALSE to disable Logging TRUE to enable logging

$output=fopen('list.csv',"a+");
if (file_exists('url-list.txt')) {
$lastUpdatedUrlList=filemtime('url-list.txt');
} else {
$lastUpdatedUrlList=0;
}
$urlFP=fopen('url-list.txt',"a+");

$catlist = fetchCat();

$now=time();
LogIt("<<=============================================================>>","t","finalloop.log");
LogIt($lastUpdatedUrlList,"t","finalloop.log");
LogIt($now,"t","finalloop.log");
LogIt($now-$lastUpdatedUrlList,"t","finalloop.log");
if ( $now-$lastUpdatedUrlList >=86400 ) {
foreach($catlist as $cat){
	LogIt("cat: -> ".$cat,FALSE);
	$theUrl=fetchUrls($cat, 1);
	//LogIt($theUrl,"t","theUrl.log");
}
        fclose($urlFP);
} 
$theUrl=file("url-list.txt");
	LogIt("Size of file ".sizeof($theUrl).PHP_EOL,"t","finalloop.log");
foreach($theUrl as $url){
	if (isset($url)) {
		LogIt($url,"t","finalloop.log");
		fetchDOM($url);
	}
}

fclose($output);
?>
