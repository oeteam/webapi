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
     $response["success"] = true;
     $response["message"] = "The requested resource doesn't exists";
     echoResponse($response);
     exit();
  }
  
  // fetch task

  $result = $db->getHotelDetails($args['hotel_id']);
  if ($result != NULL) {
      $response["success"] = false;
      $response["id"] = $result["id"];
      $response["hotel_name"] = $result["hotel_name"];
      $response["location"] = $result["location"];
      echoResponse($response);
  } else {
      $response["success"] = true;
      $response["message"] = "The requested resource doesn't exists";
      echoResponse($response);
  }
});
$app->post('/HotelSearch', 'authenticate',function($request,$response) use ($app) {

  $response = array();
  // reading post params
  $input = $request->getParsedBody()['name'];
  $db = new DbHandler();
  echoResponse($input);
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
$app->run();
?>