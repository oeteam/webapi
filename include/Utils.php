<?php
require('vendor/autoload.php');
require_once 'Config.php';

// if(PHP_DEBUG_MODE){
//   error_reporting(-1);
//   ini_set('display_errors', 'On');
// }
/**
* Echo json response
* @param String $status_code http response code
* @param Int $response Json response
*/
function echoResponse($response) {
  // setting response content type to json
  echo json_encode($response);
}
function authenticate($request) {
  // getting request header
  $username = $request->getHeaderLine('username');
  $password = $request->getHeaderLine('password');
  $response = array();
  $app = new \Slim\App();
  // verifying authorization header
  if(isset($username) && $username!='' && isset($password) && $password!='') {
    $db = new DbHandler();
    // validating user
    if (!$db->isValidUser($username,$password)) {
      //user is not present in users table
      $response['error'] = true;
      $response['message'] = 'Access denied. Invalid User';
      echoResponse($response);
      //$app->stop();
    } else {
      global $session_id;
      // get user details
      $session_id = 1234788;
    }
  } else if(!isset($username) || $username=='') {
      $response['message'] = "Username is mandatory";
      echoResponse($response);
      //$app->stop();
  } else if($password=='') {
      $response['message'] = "Password is mandatory";
      echoResponse($response);
      //$app->stop();
  }
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
