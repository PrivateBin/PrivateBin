<?php

// change this, if your php files and data is outside of your webservers document root
define('PATH', '');

define('PUBLIC_PATH', __DIR__);
require PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$link = $_SERVER['REQUEST_URI'];
$response = getGetData();

$arr = explode('=',$response);
$c = count ($arr);

$opSuccess = FALSE;
$errCode = 0;

$shortenedUrl = "";
$originalUrl = "";

if(($c == 2) && ($arr[0] == "link") && (strlen($arr[1]) < 256)) {
   // read in configuration values
   $conf = new PrivateBin\Configuration;

   $originalUrl = urldecode($arr[1]);
   if (startsWith($originalUrl, $conf->getKey( "basepath") . "/?")) {

      // Init the CURL session
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $conf->getKey( "apiurl", "yourls"));
      curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result
      curl_setopt($ch, CURLOPT_POST, 1);              // This is a POST request
      curl_setopt($ch, CURLOPT_POSTFIELDS, array(     // Data to POST
                        'signature' => $conf->getKey( "signature", "yourls"),
                        'format'   => 'json',
                        'action'   => 'shorturl',
                        'url' =>  $originalUrl
      ));
      // Fetch and return content
      $data = curl_exec($ch);
      curl_close($ch);
      if (!($data === FALSE) && is_string($data))
      {
          $data = json_decode( $data, true);

          if (!is_null($data) && array_key_exists('statusCode', $data)
                && array_key_exists('shorturl', $data) &&  ($data['statusCode'] == 200))
          {
              $shortenedUrl = $data['shorturl'];
              $opSuccess = TRUE;
          } else {
             // error with contents of YOURLS response.
             $errCode = 3;
          }
      } else {
         // error when calling YOURLS - probably a PrivateBin configuration issue, like wrong/missing apiurl or signature
         $errCode = 2;
      }
   } else {
      // trying to shorten a URL not pointing to our PrivateBin instance.
      $errCode = 1;
   }
}

if ($opSuccess)
{
   print("<br>Your shortened paste is <span class=\"shortensuccess\"><a href=\"$shortenedUrl\">$shortenedUrl</a></span>");
}
else
{
   print("<br><span class=\"shortenerror\">Error: An error occured while trying to shorten the given URL (error code $errCode)</span>");
}
 
function getGetData() {
   $data = http_build_query($_GET);
   return $data;
}

function startsWith($haystack, $needle)
{
   $length = strlen($needle);
   return (substr($haystack, 0, $length) === $needle);
}
?>
