<?php
require_once 'include/DbHandler.php';
require_once 'include/Utils.php';
require('vendor/autoload.php');

$app = new \Slim\App;
// //slim application routes
// if(SLIM_DEBUG){
//   $app->config('debug',true);
// }

/**
* route test block
*/
$app->get('/', function () {
    echo "Hello World";
});
$app->get('/hotels/{hotel_id}', function($request,$response,$args){

  $response = array();
  $db = new DbHandler();
  $headers = $request->getHeaders();
  if (!isset($headers['HTTP_USERNAME'][0])) {
     $response["success"] = false;
     $response["message"] = "The requested resource doesn't exists";
     echoResponse($response);
     exit();
  }
  
  // fetch task

  $result = $db->getHotelDetails($args['hotel_id']);
  if ($result != NULL) {
      $response["success"] = true;
      $response["id"] = $result["id"];
      $response["hotel_name"] = $result["hotel_name"];
      $response["location"] = $result["location"];
      echoResponse($response);
  } else {
      $response["success"] = false;
      $response["message"] = "The requested resource doesn't exists";
      echoResponse($response);
  }
});
$app->post('/HotelSearch', 'authenticate',function($request,$response) use ($app) {
  $response = array();
  // reading post params
  $input = $request->getParsedBody()['name'];
  $db = new DbHandler();
  echoResponse($response);
 // $res = $db->createUser($name, $email, $password);

  // if ($res == USER_CREATED_SUCCESSFULLY) {
  //     $response["error"] = false;
  //     $response["message"] = "You are successfully registered";
  //     echoResponse(201, $response);
  // } else if ($res == USER_CREATE_FAILED) {
  //     $response["error"] = true;
  //     $response["message"] = "Oops! An error occurred while registereing";
  //     echoResponse(200, $response);
  // } else if ($res == USER_ALREADY_EXISTED) {
  //     $response["error"] = true;
  //     $response["message"] = "Sorry, this email already existed";
  //     echoResponse(200, $response);
  // }
});
$app->post('/HotelSearch2',function($request,$response) {
  $response = array();
  $result = authenticate_user($request);
  if($result=="true") {
    // reading post params
    $input = $request->getParsedBody()['name'];
    $db = new DbHandler();
    echoResponse($input);
  } else {
    echoResponse($result);
  }
});
function authenticate_user($request) {
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
      $response['success'] = true;
      $response['message'] = 'Access denied. Invalid User';
      return $response;
     // echoResponse($response);
      //$app->stop();
    } else {
      return true;
    }
  } else if($username=='' && $password=='') {
      $response["success"] = false;
      $response['message'] = "Username & Password is mandatory";
      return $response;
      //$app->stop();
  } else if(isset($username) || $username=='') {
      $response["success"] = false;
      $response['message'] = "Username is mandatory";
      return $response;
      //$app->stop();
  } else if(isset($password) || $password=='') {
      $response["success"] = false;
      $response['message'] = "Password is mandatory";
      return $response;
      //$app->stop();
  }
}
$app->run();
?>