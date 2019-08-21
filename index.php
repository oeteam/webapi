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
$app->post('/HotelSearch',function($request,$response) {
  $response = array();
  $db = new DbHandler();
  $result = authenticate_user($request);
  if(isset($result['session_id']) && $result['success']==true) {
    // validating post params
    $validation = $db->validateparameters($request->getParsedBody());
    echoResponse($validation);
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
  $db = new DbHandler();
  // verifying authorization header
  if(isset($username) && $username!='' && isset($password) && $password!='') {
    // validating user
    if (!$db->isValidUser($username,$password)) {
      //user is not present in users table
      $response['success'] = false;
      $response['message'] = 'Access denied. Invalid User';
      return $response;
    } else {
      $userdetails = $db->getuserdetails($username,$password);
      $response['success'] = true;
      $response['session_id'] = date('YmdHis').$username.$userdetails['provider_id'];
      return $response;
    }
  } else if($username=='' && $password=='') {
      $response["success"] = false;
      $response['message'] = "Username & Password is mandatory";
      return $response;
  } else if(isset($username) || $username=='') {
      $response["success"] = false;
      $response['message'] = "Username is mandatory";
      return $response;
  } else if(isset($password) || $password=='') {
      $response["success"] = false;
      $response['message'] = "Password is mandatory";
      return $response;
  }
}
$app->run();
?>