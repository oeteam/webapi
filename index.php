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
  $log = $db->insertLog($request,'HotelSearch');
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
       // $res = $db->addSearchDetails($data);
       $data['view'] = $db->getHotelList($request->getParsedBody());
       $response['status']['status'] = "success";
       $response['status']['description'] = "Hotel Search is Successfull";
       $response['status']['Session ID'] = $session_id;
       $response['result'] = $data['view'];
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
  $log = $db->insertLog($request,'AvailableHotelRooms');
  $result = authenticate_user($request);
  if($result['success']==true) {
    // validating post params
    $validation = $db->validateparametersavailablerooms($request->getParsedBody());
    if($validation['status']=="success") {
        $details = $request->getParsedBody();
        $validate_session = $db->validatesession($details,$result['provider_id']);
        if($validate_session == "success") {
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
          for ($i=0; $i < $searchdet['noRooms']; $i++) {
            $response['status']['result']['room'.($i+1)] = $rooms[$i];
          }
        } else {
          $response['status']['status'] = "error";
          $response['status']['session_error'] = "Session invalid";
        }
        echoResponse($response);
    } else {
      echoResponse($validation);
    }
  } else {
    echoResponse($result);
  }
});
$app->post('/BookingReview',function($request,$response) {
  $response = array();
  $db = new DbHandler();
  $log = $db->insertLog($request,'BookingReview');
  $result = authenticate_user($request);
  if($result['success']==true) {
    // validating post params
    $validation = $db->validateparametersbookingreview($request->getParsedBody());
    if($validation['status']=="success") {
        $details = $request->getParsedBody();
        $validate_session = $db->validatesession($details,$result['provider_id']);
        if($validate_session == "success") {
          $searchdet = $db->getSearchDetails($details['session_id']);
          $start = $searchdet['check_in'];
          $end = $searchdet['check_out'];
          $first_date = strtotime($start);
          $second_date = strtotime($end);
          $offset = $second_date-$first_date; 
          $result = array();
          $checkin_date=date_create($searchdet['check_in']);
          $checkout_date=date_create($searchdet['check_out']);
          $no_of_days=date_diff($checkin_date,$checkout_date);
          $tot_days = $no_of_days->format("%a");
          $response['status']['status'] = "success";
          for($i = 0; $i <= floor($offset/24/60/60); $i++) {
            $result[1+$i]['day'] = date('l', strtotime($start. ' + '.$i.' days'));
            $result[1+$i]['date'] = date('m/d/Y', strtotime($start. ' + '.$i.'  days'));
          }
          $viwedate1 = date("d/m/Y", strtotime(isset($searchdet['check_in']) ? $searchdet['check_in'] : ''));
          $viwedate2 = date("d/m/Y", strtotime(isset($searchdet['check_out']) ? $searchdet['check_out'] : ''));
          $response['status']['result']['Checkin']= $viwedate1;
          $response['status']['result']['Checkout']= $viwedate2;
          $response['status']['result']['Adults']= $searchdet['adults'];
          $response['status']['result']['Child']= $searchdet['child'];
          $response['status']['result']['no_of_rooms']= $searchdet['noRooms'];
          $response['status']['result']['no_of_days']=  $tot_days;
          for ($i=0; $i < $searchdet['noRooms']; $i++) { 
            $roomindex = explode('-',$details['room'][$i]);
            if(isset($roomindex[1])) {
              $roomid[$i] = $roomindex[1];
            } else {
              $roomid[$i] = "";
            }
            if(isset($roomindex[0])) {
              $contractid[$i] = $roomindex[0];
            } else {
               $contractid[$i] = "";
            }
            $data['additionalfoodrequest'] = array();
            $data['cancellation_policy'] = $db->get_policy_contract($details['hotelcode'],$contractid[$i]);
            $contractBoardCheck = $db->contractBoardCheck($contractid[$i]);
            if ($contractBoardCheck['board']=="RO") {
              $Breakfast = $db->additionalfoodrequest($details['hotelcode'],$contractid[$i],$roomid[$i],$searchdet,'Breakfast');
              if ($Breakfast!=false) {
                $data['additionalfoodrequest']['board'][] = 'Breakfast';
              }
              $Lunch =  $db->additionalfoodrequest($details['hotelcode'],$contractid[$i],$roomid[$i],$searchdet,'Lunch');
              if ($Lunch!=false) {
                $data['additionalfoodrequest']['board'][] = 'Lunch';
              }
              $Dinner =  $db->additionalfoodrequest($details['hotelcode'],$contractid[$i],$roomid[$i],$searchdet,'Dinner');
              if ($Dinner!=false) {
                $data['additionalfoodrequest']['board'][] = 'Dinner';
              }
            } else if ($contractBoardCheck['board']=="BB") {
              $Lunch = $db->additionalfoodrequest($details['hotelcode'],$contractid[$i],$roomid[$i],$searchdet,'Lunch');
              if ($Lunch!=false) {
                $data['additionalfoodrequest']['board'][] = 'Lunch';
              }
              $Dinner = $db->additionalfoodrequest($details['hotelcode'],$contractid[$i],$roomid[$i],$searchdet,'Dinner');
              if ($Dinner!=false) {
                $data['additionalfoodrequest']['board'][] = 'Dinner';
              }
            } else if ($contractBoardCheck['board']=="HB") {
              $Lunch = $db->additionalfoodrequest($details['hotelcode'],$contractid[$i],$roomid[$i],$searchdet,'Lunch');
              if ($Lunch!=false) {
                $data['additionalfoodrequest']['board'][] = 'Lunch';
              }
            }
            $extrabed = $db->get_PaymentConfirmextrabedAllotment($searchdet,$details['hotelcode'],$contractid[$i],$roomid[$i],$i); 
            $general = $db->get_Confirmgeneral_supplement($searchdet,$contractid[$i],$roomid[$i],$i+1,$details['hotelcode']); 
            // stay and pay dicount start 
            $Fdays = 0;
            $discountGet = $db->Alldiscount(date('Y-m-d',strtotime($searchdet['check_in'])),date('Y-m-d',strtotime($searchdet['check_out'])),$details['hotelcode'],$roomid[$i],$contractid[$i],'Room'); 
            if ($discountGet['dis']=="true") {
              $Cdays = $tot_days/$discountGet['stay'];
              $parts = explode('.', $Cdays);
              $Cdays = $parts[0];
              $Sdays = $discountGet['stay']*$Cdays;
              $Pdays = $discountGet['pay']*$Cdays;
              $Tdays = $tot_days-$Sdays;
              $Fdays = $Pdays+$Tdays;
              $discountGet['stay'];
              $discountGet['pay'];
            }
            if($discountGet['dis']=="true") { 
              $data['discount']['Stay'] =  $discountGet['stay'].'nights';
              $data['discount']['Pay'] = $discountGet['pay'].'nights';
            }
            for ($j=1; $j <=$tot_days ; $j++) {
              $result[$j]['amount'] = $db->special_offer_amount($result[$j]['date'],$roomid[$i],$details['hotelcode'],$contractid[$i]);
              $result[$j]['roomName'] = $db->roomnameGET($roomid[$i],$details['hotelcode']);
              $FextrabedAmount[$i-1]  = 0;
              $TFextrabedAmount[$i-1]  = 0;
              $GAamount[$i-1] = 0;
              $GCamount[$i-1] = 0;
              $BBAamount[$i-1] = 0;
              $BBCamount[$i-1] = 0;
              $LAamount[$i-1] = 0;
              $LCamount[$i-1] = 0;
              $DAamount[$i-1] = 0;
              $DCamount[$i-1] = 0;
              $TGAamount[$i-1] = 0;
              $TGCamount[$i-1] = 0;
              $data['roomname'] =   $result[$j]['roomName'];
              $data['per-day-amount'] = $result[$j]['amount'];
              $RMdiscount = $db->DateWisediscount(date('Y-m-d' ,strtotime($result[$j]['date'])),$details['hotelcode'],$roomid[$i],$contractid[$i],'Room',date('Y-m-d',strtotime($searchdet['check_in'])),date('Y-m-d',strtotime($searchdet['check_out'])),$discountGet['dis']);
              $RMdiscountval[$i] = $RMdiscount['discount'];
              $GDis = 0;
              if ($RMdiscount['discount']!=0 && $RMdiscount['General']!=0) { 
                $GDis = $RMdiscount['discount'];
              }
              $exDis = 0;
              if ($RMdiscount['discount']!=0 && $RMdiscount['Extrabed']!=0) { 
                $exDis = $RMdiscount['discount'];
              }
              $BDis = 0;
              if ($RMdiscount['discount']!=0 && $RMdiscount['Board']!=0) { 
                $BDis = $RMdiscount['discount'];
              }
              $rmamount = 0;
              $total_markup = 0;
              $roomAmount[$j]  = (($result[$j]['amount']*$total_markup)/100)+$result[$j]['amount']+$rmamount;
              $DisroomAmount[$j] = $roomAmount[$j]-($roomAmount[$j]*$RMdiscount['discount'])/100;
              $WiDisroomAmount[$j] = $roomAmount[$j];
              if ($RMdiscount['discount']!=0) { 
                $data['roomamount'] = $roomAmount[$j];
              }
              $data['discountroomamount'] = $DisroomAmount[$j];
              // General Supplement breakup start 
              if($general['gnlCount']!=0) {
                //General Supplement adult breakup start
                foreach ($general['date'] as $GAkey => $GAvalue) {
                  if ($GAvalue==date('d/m/Y' ,strtotime($result[$i]['date']))) {
                    foreach ($general['general'][$GAkey] as $GSNkey => $GSNvalue) {
                      if (isset($general['RWadultamount'][$GAkey][$GSNvalue])) {
                        $GSAmamount = 0;
                        $total_markup = 0;
                        $GAamount[$i-1] = ($general['RWadultamount'][$GAkey][$GSNvalue][$RAkey+1]*$total_markup)/100+$general['RWadultamount'][$GAkey][$GSNvalue][$RAkey+1]+$GSAmamount;
                        $TGAamount[$i-1] += $GAamount[$i-1]-($GAamount[$i-1]*$GDis)/100;
                        if ($RMdiscount['discount']!=0 && $RMdiscount['General']!=0) { 
                          $data['generalSupplement']['adult'.$GAkey]['old-price']=$GAamount[$i-1];
                        }
                        $data['generalSupplement']['adult'.$GAkey]['price']=$GAamount[$i-1]-($GAamount[$i-1]*$GDis)/100; 
                      }
                    }
                  }
                }
                //General Supplement child breakup start -->
                foreach ($general['date'] as $GCkey => $GCvalue) {
                  if ($GCvalue==date('d/m/Y' ,strtotime($result[$i]['date']))) {
                    foreach ($general['general'][$GCkey] as $GSNkey => $GSNvalue) {
                      if (isset($general['RWchildAmount'][$GCkey]) && isset($general['RWchildAmount'][$GCkey][$GSNvalue][$RAkey+1])) {
                        $GSCmamount = 0;
                        $total_markup = 0;
                        $GCamount[$i-1] = (array_sum($general['RWchildAmount'][$GCkey][$GSNvalue][$RAkey+1])*$total_markup)/100+array_sum($general['RWchildAmount'][$GCkey][$GSNvalue][$RAkey+1])+$GSCmamount;
                        $TGCamount[$i-1] = $GCamount[$i-1]-($GCamount[$i-1]*$GDis)/100;
                        if ($RMdiscount['discount']!=0 && $RMdiscount['General']!=0) { 
                          $data['generalSupplement']['child'.$GCkey]['old-price']= $GCamount[$i-1];
                        }
                        $data['generalSupplement']['child'.$GCkey]['price']=  $GCamount[$i-1]-($GCamount[$i-1]*$GDis)/100;
                      }
                    }
                  }
                }
              }
              //Extra bed breakup start
              if (isset($extrabed['date'][$i-1]) && isset($extrabed['RwextrabedAmount'][$i-1][$RAkey])) {
                foreach ($extrabed['RwextrabedAmount'][$i-1][$RAkey] as $exMkey => $exMvalue) {
                  $EXamount = 0;
                  $total_markup = 0;                          
                  $FextrabedAmount[$i-1] =  ($extrabed['RwextrabedAmount'][$i-1][$RAkey][$exMkey]*$total_markup)/100+$extrabed['RwextrabedAmount'][$i-1][$RAkey][$exMkey]+$EXamount;          
                  $TFextrabedAmount[$i-1] += $FextrabedAmount[$i-1]-($FextrabedAmount[$i-1]*$exDis)/100; 
                  if ($RMdiscount['discount']!=0 && $RMdiscount['Extrabed']!=0) {   
                    $data[$extrabed['extrabedType'][$i-1][$RAkey][$exMkey]]['old-price'] = $FextrabedAmount[$i-1];
                  }
                  $data[$extrabed['extrabedType'][$i-1][$RAkey][$exMkey]]['price'] = $FextrabedAmount[$i-1]-($FextrabedAmount[$i-1]*$exDis)/100;
                }
              }
            }
            $witotal[$i] = array_sum($WiDisroomAmount)+array_sum($TFextrabedAmount)+array_sum($BBAamount)+array_sum($BBCamount)+array_sum($LAamount)+array_sum($LCamount)+array_sum($DAamount)+array_sum($DCamount)+array_sum($TGAamount)+array_sum($TGCamount);  
            $total[$i] = array_sum($DisroomAmount)+array_sum($TFextrabedAmount)+array_sum($BBAamount)+array_sum($BBCamount)+array_sum($LAamount)+array_sum($LCamount)+array_sum($DAamount)+array_sum($DCamount)+array_sum($TGAamount)+array_sum($TGCamount); 
            if ($discountGet['dis']=="true") {
              if ($discountGet['Extrabed']==1) {
                array_splice($TFextrabedAmount,$Fdays);
              }
              if ($discountGet['General']==1) {
                array_splice($TGAamount,$Fdays);
                array_splice($TGCamount,$Fdays);
              }
              if ($discountGet['Board']==1) {
                array_splice($BBAamount,$Fdays);
                array_splice($BBCamount,$Fdays);

                array_splice($LAamount,$Fdays);
                array_splice($LCamount,$Fdays);

                array_splice($DAamount,$Fdays);
                array_splice($DCamount,$Fdays);
              }
            }
            $totRmAmt[$i] = array_sum(array_splice($DisroomAmount, 1,$Fdays))+array_sum($TFextrabedAmount)+array_sum($BBAamount)+array_sum($BBCamount)+array_sum($LAamount)+array_sum($LCamount)+array_sum($DAamount)+array_sum($DCamount)+array_sum($TGAamount)+array_sum($TGCamount); 
            unset($DisroomAmount);
            unset($WiDisroomAmount);       
            if ($discountGet['dis']=="true") {
              $data['totalroomamount']['oldprice'] = $total[$i];
              $data['totalroomamount']['price'] = $totRmAmt[$i];
              $total[$i] = $totRmAmt[$i];
            } else {
               $data['totalroomamount']['price'] = $total[$i];  
            }
            
            //print_r($data['cancellation_policy']);exit;
            if(empty($data['cancellation_policy']) || $data['roomname']=="" || $data['per-day-amount']==0 || $data['discountroomamount']==0 || $data['totalroomamount']['price']==0) {
              $response['status']['result']['room'.($i+1)]['status'][] = 'Error'; 
              $response['status']['result']['room'.($i+1)]['status']['description'] = 'Invalid Room Combination'; 
            } else {
              $response['status']['result']['room'.($i+1)]['status'] = 'Success'; 
              $response['status']['result']['room'.($i+1)] =$data; 
            }
             
          }   
          $tax = $db->general_tax($details['hotelcode']);
          $response['status']['result']['tax'] = $tax.'%';
          $finalAmount = array_sum($total);
          $finalAmount = ($finalAmount*$tax)/100+$finalAmount;
          $grandTotal = ($finalAmount*$tax)/100+$finalAmount;
          $response['status']['result']['grandtotal'] = $grandTotal; 
        } else {
            $response['status']['status'] = "error";
            $response['status']['session_error'] = "Session invalid";
        }  
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