<?php
require('vendor/autoload.php');
require_once 'Config.php';

if(PHP_DEBUG_MODE){
  error_reporting(-1);
  ini_set('display_errors', 'On');
}

/**
* Echo json response
* @param String $status_code http response code
* @param Int $response Json response
*/
function echoResponse($response) {
  // setting response content type to json
  echo json_encode($response);
}
/** Debugging utility */
function p($input, $exit=1) {
  echo '<pre>';
  print_r($input);
  echo '</pre>';
  if($exit) {
    exit;
  }
}

function j($input, $encode=true, $exit=1) {
  echo '<pre>';
  echo json_encode($input, JSON_PRETTY_PRINT | $encode);
  echo '</pre>';
  if($exit) {
    exit;
  }
}
?>
