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
  if($result['success']==true) {
     $session_id = md5(date('YmdHis').$result['username'].$result['provider_id']);
    // validating post params
    $validation = $db->validateparameters($request->getParsedBody());
    if($validation['status']=="success") {
       $data = $request->getParsedBody();
       $checkin_date=date_create($data['check_in']);
       $checkout_date=date_create($data['check_out']);
       $no_of_days=date_diff($checkin_date,$checkout_date);
       $data['nights'] = $no_of_days->format("%a");
       $data['session_id'] = $session_id;
       $data['provider_id'] = $result['provider_id'];
       $res = $db->addSearchDetails($data);
       $response['status']['status'] = "success";
       $response['status']['description'] = "Hotel Search is Successfull";
       $response['status']['Session ID'] = $session_id;
       echoResponse($response);
    } else {
      echoResponse($validation);
    }
  } else {
    echoResponse($result);
  }
});
$app->post('/AvailableHotelRooms',function($request,$response) {
  $response = array();
  $db = new DbHandler();
  $result = authenticate_user($request);
  if($result['success']==true) {
    // validating post params
    $validation = $db->validateparametersavailablerooms($request->getParsedBody());
    if($validation['status']=="success") {
        $details = $request->getParsedBody();
        $data['view'] = $db->getHotelDetails($details['hotelcode']);
        $hotel_facilities = explode(",",$data['view']['hotel_facilities']); 
        foreach ($hotel_facilities as $key => $value) {
          $data['hotel_facilities'][$key] = $db->hotel_facilities_data($value);
        }
        $room_facilities = explode(",",$data['view']['room_facilities']); 
        foreach ($room_facilities as $key => $value) {
          $data['room_facilities'][$key] = $db->room_facilities_data($value);
        } 
        $searchdet = $db->getSearchDetails($details['session_id']);
        $contracts = $db->contractChecking($searchdet,$details['hotelcode']);
        if ($contracts!=false) {
          for ($i=0; $i < $searchdet['noRooms']; $i++) { 
            $rooms[$i] = $db->roomwisepaxdata($details['hotelcode'],$i,$searchdet,$contracts['contract_id']);
          }
        }
       $response['status']['status'] = "success";
       $response['status']['result'] = $rooms;
       echoResponse($response);
    } else {
      echoResponse($validation);
    }
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
      $response['username'] = $username;
      $response['provider_id'] = $userdetails['provider_id'];
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