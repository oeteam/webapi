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

  // fetch task
  $result = $db->getHotelDetails($args['hotel_id']);
  if ($result != NULL) {
      $response["error"] = false;
      $response["id"] = $result["id"];
      $response["hotel_name"] = $result["hotel_name"];
      $response["location"] = $result["location"];
      echoResponse($response);
  } else {
      $response["error"] = true;
      $response["message"] = "The requested resource doesn't exists";
      echoResponse($response);
  }
});
$app->run();
?>