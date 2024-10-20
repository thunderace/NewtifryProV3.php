<?php
// how to use :
// install send2NP.php, NewtifryPro.php and serviceAccount.json on a web server
// call http://YOUWEBSERVERPATH/send2NP.php?source=The+Source&title=The+Title&message=The+Message&priority=1 etc...

include ("NewtifryPro.php");

$deviceIds = array();
// get params from url
$deviceIds [] = "YOUR_FIRST_DEVICEID";
// For multiple device
//$deviceIds [] = "YOUR_SECOND_DEVICEID";
$source = isset($_GET["source"]) ? $_GET["source"] : NULL;
$title = isset($_GET["title"]) ? $_GET["title"] : NULL;
$message = isset($_GET["message"]) ? $_GET["message"] : NULL;
$priority = isset($_GET["priority"]) ? $_GET["priority"] : 0;
$url = isset($_GET["url"]) ? $_GET["url"] : NULL;
$image = isset($_GET["image"]) ? $_GET["image"] : NULL;
$speak = isset($_GET["speak"]) ? $_GET["speak"] : -1;
$noCache = isset($_GET["noCache"]) ? $_GET["noCache"] : false;
$state = isset($_GET["state"]) ? $_GET["state"] : 0;
$notify = isset($_GET["notify"]) ? $_GET["notify"] : -1;
$tag = isset($_GET["notify"]) ? $_GET["notify"] : NULL;

if ($title == NULL) {
  echo("KO : Title is mandatory");
  return;
}

$result = newtifryProPush(  "./serviceAccount.json",
                            $deviceIds, 
                            $title, 
                            $source, 
                            $message, 
                            $priority, 
                            $url, 
                            $image, 
                            $speak,	
                            $noCache, 
                            $state, 
                            $notify,
                            $tag);
                            
print_r($result);
?>