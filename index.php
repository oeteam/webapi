<?php
set_time_limit(0);
ini_set('maxdb_execution_time',900000);
require_once 'include/DbHandler.php';
require_once 'include/Utils.php';
require('vendor/autoload.php');
use \Firebase\JWT\JWT;
error_reporting(0);
$app = new \Slim\App;
// //slim application routes
// if(SLIM_DEBUG){
//   $app->config('debug',true);
// }

/**
* route test block
*/
$app->get('/', function () {
  $token = array(
      
          "id" => '1234',
          "firstname" => 'neethu',
          "lastname" => 'johnso',
  );
  $jwt = JWT::encode($token, 'supersecretkeyyoushouldnotcommittogithub');
  echo $jwt;
  //echo "Hello World";
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
       $res = $db->addSearchDetails($data);
       $list = $db->getHotelList($request->getParsedBody());
       $response['status'] = "success";
       $response['description'] = "Hotel Search is Successfull";
       $response['Session ID'] = $session_id;
       $response['result'] = $list;
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
          $rooms = array();
          if (!empty($contracts)) {
            for ($i=0; $i < $searchdet['noRooms']; $i++) { 
              $roomdet[$i] = $db->roomwisepaxdata($details['hotelcode'],$i,$searchdet,$contracts['contract_id']);
              if(!empty($roomdet[$i])) {
                $rooms[$i] = $roomdet[$i];
              }
            }
          }
          if(count($rooms)==$searchdet['noRooms']) {
            $response['status']['status'] = "success";
            for ($i=0; $i < $searchdet['noRooms']; $i++) {
              $response['result']['room'.($i+1)] = $rooms[$i];
            }
          } else {
            $response['status']['status'] = "failed";
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
          $response['result']['Checkin']= $viwedate1;
          $response['result']['Checkout']= $viwedate2;
          $response['result']['Adults']= $searchdet['adults'];
          $response['result']['Child']= $searchdet['child'];
          $response['result']['no_of_rooms']= $searchdet['noRooms'];
          $response['result']['no_of_days']=  $tot_days;

          for ($i=0; $i < $searchdet['noRooms']; $i++) { 
            $data = array();
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

            $data['Cancellation_policy'][$i] = $db->get_CancellationPolicy_table($searchdet,$contractid[$i],$roomid[$i],$details['hotelcode']);
            $data['remarks_and_policies']= $db->get_policy_contract($details['hotelcode'],$contractid[$i]);
            $contractBoardCheck = $db->contractBoardCheck($contractid[$i]);
           
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
              $GAamount[$j-1] = 0;
              $GCamount[$j-1] = 0;
              $BBAamount[$j-1] = 0;
              $BBCamount[$j-1] = 0;
              $LAamount[$j-1] = 0;
              $LCamount[$j-1] = 0;
              $DAamount[$j-1] = 0;
              $DCamount[$j-1] = 0;
              $TGAamount[$j-1] = 0;
              $TGCamount[$j-1] = 0;
              //$data['roomname'] =   $result[$j]['roomName'];
              //$data['per-day-amount'] = $result[$j]['amount'];
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
              $data['amount_breakup-room'.($i+1)][$j]['date']= date('d/m/Y' ,strtotime($result[$j]['date']));
              $data['amount_breakup-room'.($i+1)][$j]['room'] = $result[$j]['roomName'];
              $data['amount_breakup-room'.($i+1)][$j]['boardname'] = $contractBoardCheck['board'];
              $rmamount = 0;
              $total_markup = 0;
              $roomAmount[$j]  = (($result[$j]['amount']*$total_markup)/100)+$result[$j]['amount']+$rmamount;
              $DisroomAmount[$j] = $roomAmount[$j]-($roomAmount[$j]*$RMdiscount['discount'])/100;
              $WiDisroomAmount[$j] = $roomAmount[$j];

              if ($RMdiscount['discount']!=0) { 
                $data['amount_breakup-room'.($i+1)][$j]['roomamount'] = $roomAmount[$j];
              }
              $data['amount_breakup-room'.($i+1)][$j]['discountroomamount'] = $DisroomAmount[$j];
              // General Supplement breakup start 
              if($general['gnlCount']!=0) {
                //General Supplement adult breakup start
                foreach ($general['date'] as $GAkey => $GAvalue) {
                  if ($GAvalue==date('d/m/Y' ,strtotime($result[$j]['date']))) {
                    foreach ($general['general'][$GAkey] as $GSNkey => $GSNvalue) {
                      if (isset($general['RWadultamount'][$GAkey][$GSNvalue])) {
                        $GSAmamount = 0;
                        $total_markup = 0;
                        $GAamount[$j-1] = ($general['RWadultamount'][$GAkey][$GSNvalue][$i+1]*$total_markup)/100+$general['RWadultamount'][$GAkey][$GSNvalue][$i+1]+$GSAmamount;
                        $TGAamount[$j-1] += $GAamount[$j-1]-($GAamount[$j-1]*$GDis)/100;
                        if ($RMdiscount['discount']!=0 && $RMdiscount['General']!=0) { 
                          $data['generalSupplement']['adult'.$GAkey]['old-price'][]=$GAamount[$j-1];
                        }
                        $data['generalSupplement']['adult'.$GAkey]['price'][]=$GAamount[$j-1]-($GAamount[$j-1]*$GDis)/100; 
                      }
                    }
                  }
                }
                //General Supplement child breakup start -->
                foreach ($general['date'] as $GCkey => $GCvalue) {
                  if ($GCvalue==date('d/m/Y' ,strtotime($result[$j]['date']))) {
                    foreach ($general['general'][$GCkey] as $GSNkey => $GSNvalue) {
                      if (isset($general['RWchildAmount'][$GCkey]) && isset($general['RWchildAmount'][$GCkey][$GSNvalue][$i+1])) {
                        $GSCmamount = 0;
                        $total_markup = 0;
                        $GCamount[$j-1] = (array_sum($general['RWchildAmount'][$GCkey][$GSNvalue][$i+1])*$total_markup)/100+array_sum($general['RWchildAmount'][$GCkey][$GSNvalue][$i+1])+$GSCmamount;
                        $TGCamount[$j-1] = $GCamount[$j-1]-($GCamount[$j-1]*$GDis)/100;
                        if ($RMdiscount['discount']!=0 && $RMdiscount['General']!=0) { 
                          $data['generalSupplement']['child'.$GCkey]['old-price'][]= $GCamount[$j-1];
                        }
                        $data['generalSupplement']['child'.$GCkey]['price'][]=  $GCamount[$j-1]-($GCamount[$j-1]*$GDis)/100;
                      }
                    }
                  }
                }
              }
              //Extra bed breakup start
              if (isset($extrabed['date'][$j-1]) && isset($extrabed['RwextrabedAmount'][$j-1][$i])) {
                foreach ($extrabed['RwextrabedAmount'][$j-1][$i] as $exMkey => $exMvalue) {
                  $EXamount = 0;
                  $total_markup = 0;                          
                  $FextrabedAmount[$j-1] =  ($extrabed['RwextrabedAmount'][$j-1][$i][$exMkey]*$total_markup)/100+$extrabed['RwextrabedAmount'][$j-1][$i][$exMkey]+$EXamount;          
                  $TFextrabedAmount[$j-1] += $FextrabedAmount[$i-1]-($FextrabedAmount[$j-1]*$exDis)/100; 
                  if ($RMdiscount['discount']!=0 && $RMdiscount['Extrabed']!=0) {   
                    $data[$extrabed['extrabedType'][$j-1][$i][$exMkey]]['old-price'][] = $FextrabedAmount[$j-1];
                  }
                  $data[$extrabed['extrabedType'][$j-1][$i][$exMkey]]['price'][] = $FextrabedAmount[$j-1]-($FextrabedAmount[$j-1]*$exDis)/100;
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
            if($data['amount_breakup-room'.($i+1)]=="" || $data['totalroomamount']['price']==0) {
              $response['result']['room'.($i+1)]['status'][] = 'Error'; 
              $response['result']['room'.($i+1)]['status']['description'] = 'Invalid Room Combination'; 
            } else {
              $response['room'.($i+1)]['status'] = 'Success'; 
              $response['room'.($i+1)] =$data; 
            }
             
          }   
          $tax = $db->general_tax($details['hotelcode']);
          $response['result']['tax'] = $tax.'%';
          $finalAmount = array_sum($total);
          $finalAmount = ($finalAmount*$tax)/100+$finalAmount;
          $grandTotal = ($finalAmount*$tax)/100+$finalAmount;
          $response['result']['grandtotal'] = $grandTotal; 
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
$app->post('/HotelBook',function($request,$response) {
  $response = array();
  $db = new DbHandler();
  $log = $db->insertLog($request,'HotelBook');
  $result = authenticate_user($request);
  if($result['success']==true) {
    // validating post params
    $validation = $db->validateparametershotelbook($request->getParsedBody());
    if($validation['status']=="success") {
        $details = $request->getParsedBody();
        $validate_session = $db->validatesession($details,$result['provider_id']);
        if($validate_session == "success") {
          $searchdet = $db->getSearchDetails($details['session_id']);
          // Get Max booking Id start 
          $max_id = $db->max_booking_id();
          if ($max_id['id']=="") {
            $max_booking_id = "HAB01";
          } else {
            $booking_id = $max_id['id']+1;
            $max_booking_id = "HAB0".$booking_id;
          }
          // Get Max booking Id end 
          // Roomwise data finding start
          $booking_flag = 2;
          $BookingDate = date('Y-m-d');
          $checkin_date=date_create($searchdet['check_in']);
          $checkout_date=date_create($searchdet['check_out']);
          $no_of_days=date_diff($checkin_date,$checkout_date);
          $tot_days = $no_of_days->format("%a");
          // Default variable declaration
          for ($i=0; $i < $searchdet['noRooms']; $i++) {         
            $arrRoomIndex = explode("-", $details['roomindex'][$i]);
            $RoomID[$i] = $arrRoomIndex[1]; 
            $ContractID[$i] = $arrRoomIndex[0]; 
            $contract_board = $db->getBoard($ContractID[$i]);
            $board[$i] = $contract_board['board'];
            // Dicount value declaration start
            $discountGet = $db->Alldiscount(date('Y-m-d',strtotime($searchdet['check_in'])),date('Y-m-d',strtotime($searchdet['check_out'])),$details['hotelcode'],$RoomID[$i],$ContractID[$i],'Room');
            $DiscountType[$i] = 'Null';
            $discountStay[$i] = 0;
            $discountPay[$i] = 0;
            $vardecDis = 'Room'.($i+1).'Discount';
            $$vardecDis = 0;
            $ExDis[$i] = 0;
            $GSDis[$i] = 0;
            $BSDis[$i] = 0;
            if ($discountGet['dis']=="true") {
              $DiscountType[$i] = $discountGet['type'];
              $discountCode[$i] = $discountGet['discountCode'];
              $discountStay[$i] = $discountGet['stay'];
              $discountPay[$i] = $discountGet['pay'];
              if ($discountGet['Extrabed']==1) {
                $ExDis[$i] = 1;
              }
              if ($discountGet['General']==1) {
                $GSDis[$i] = 1;
              }
              if ($discountGet['Board']==1) {
                $BSDis[$i] = 1;
              }
            } else {
              $discountCodes[$i] = array();
              $discountTypes[$i] = array();
              $ExArr[$i] = array();
              $GsArr[$i] = array();
              $BsArr[$i] = array();
              for ($j=0; $j < $tot_days ; $j++) {
                $dateOut = date('Y-m-d', strtotime($searchdet['check_in']. ' + '.$j.'  days'));
                $DateWisediscount[$j] = $db->DateWisediscount($dateOut,$details['hotelcode'],$RoomID[$i],$ContractID[$i],'Room',$searchdet['check_in'],$searchdet['check_out']);
                $discount[$i][$j]  = 0;
                if (isset($DateWisediscount[$j]['discountCode']) && $DateWisediscount[$j]['discountCode']!="") {
                  $discountCodes[$i][$j]= $DateWisediscount[$j]['discountCode'];
                  $discountTypes[$i][$j] = $DateWisediscount[$j]['discountType'];
                  $discount[$i][$j] = $DateWisediscount[$j]['discount'];
                  if ($DateWisediscount[$j]['Extrabed']==1) {
                    $ExArr[$i][$j] = 1;
                  }
                  if ($DateWisediscount[$j]['General']==1) {
                    $GsArr[$i][$j] = 1;
                  }
                  if ($DateWisediscount[$j]['Board']==1) {
                    $BsArr[$i][$j] = 1;
                  }
                } 
              }
              $ExDis[$i] = array_sum($ExArr[$i])==0 ? 0 : 1;
              $GSDis[$i] = array_sum($GsArr[$i])==0 ? 0 : 1;
              $BSDis[$i] = array_sum($BsArr[$i])==0 ? 0 : 1;
              $$vardecDis = implode(",", $discount[$i]);
              $discountCode[$i] = implode(",", array_unique($discountCodes[$i]));
              $DiscountType[$i] = implode(",", array_unique($discountTypes[$i]));
            }
            // Dicount value declaration start
          }
          $discountStay= implode(",", $discountStay);
          $discountPay= implode(",", $discountPay);
          $discountType = implode(",", $DiscountType);
          $discountCode = implode(",", $discountCode);
          // Roomwise data finding start
          // Traveller details declaration start
          $Rwadults = $searchdet['Room1Adults'].','.$searchdet['Room2Adults'].','.$searchdet['Room3Adults'].','.$searchdet['Room4Adults'].','.$searchdet['Room5Adults'].','.$searchdet['Room6Adults'];
          $RwChild = $searchdet['Room1Child'].','.$searchdet['Room2Child'].','.$searchdet['Room3Child'].','.$searchdet['Room4Child'].','.$searchdet['Room5Child'].','.$searchdet['Room6Child'];
          $reqroom1childAge = "";
          $reqroom2childAge = "";
          $reqroom3childAge = "";
          $reqroom4childAge = "";
          $reqroom5childAge = "";
          $reqroom6childAge = "";
          if (isset($details['Room1ChildAge'])) {
            $reqroom1childAge = implode(",", $details['Room1ChildAge']);
          }
          if (isset($details['Room2ChildAge'])) {
            $reqroom2childAge = implode(",", $details['Room2ChildAge']);
          }
          if (isset($details['Room3ChildAge'])) {
            $reqroom3childAge = implode(",", $details['Room3ChildAge']);
          }
          if (isset($details['Room4ChildAge'])) {
            $reqroom4childAge = implode(",", $details['Room4ChildAge']);
          }
          if (isset($details['Room5ChildAge'])) {
            $reqroom5childAge = implode(",", $details['Room5ChildAge']);
          }
          if (isset($details['Room6ChildAge'])) {
            $reqroom6childAge = implode(",", $details['Room6ChildAge']);
          }
          $reqroom1adultFirstname = "";
          $reqroom2adultFirstname = "";
          $reqroom3adultFirstname = "";
          $reqroom4adultFirstname = "";
          $reqroom5adultFirstname = "";
          $reqroom6adultFirstname = "";
          if (isset($details['Room1AdultFirstname'])) {
            $reqroom1adultFirstname = implode(",", $details['Room1AdultFirstname']);
          }
          if (isset($details['Room2AdultFirstname'])) {
            $reqroom2adultFirstname = implode(",", $details['Room2AdultFirstname']);
          }
          if (isset($details['Room3AdultFirstname'])) {
            $reqroom3adultFirstname = implode(",", $details['Room3AdultFirstname']);
          }
          if (isset($details['Room4AdultFirstname'])) {
            $reqroom4adultFirstname = implode(",", $details['Room4AdultFirstname']);
          }
          if (isset($details['Room5AdultFirstname'])) {
            $reqroom5adultFirstname = implode(",", $details['Room5AdultFirstname']);
          }
          if (isset($details['Room6AdultFirstname'])) {
            $reqroom6adultFirstname = implode(",", $details['Room6AdultFirstname']);
          }
          $reqroom1adultLastname = "";
          $reqroom2adultLastname = "";
          $reqroom3adultLastname = "";
          $reqroom4adultLastname = "";
          $reqroom5adultLastname = "";
          $reqroom6adultLastname = "";
          if (isset($details['Room1AdultLastname'])) {
            $reqroom1adultLastname = implode(",", $details['Room1AdultLastname']);
          }
          if (isset($details['Room2AdultLastname'])) {
            $reqroom2adultLastname = implode(",", $details['Room2AdultLastname']);
          }
          if (isset($details['Room3AdultLastname'])) {
            $reqroom3adultLastname = implode(",", $details['Room3AdultLastname']);
          }
          if (isset($details['Room4AdultLastname'])) {
            $reqroom4adultLastname = implode(",", $details['Room4AdultLastname']);
          }
          if (isset($details['Room5AdultLastname'])) {
            $reqroom5adultLastname = implode(",", $details['Room4AdultLastname']);
          }
          if (isset($details['Room6AdultLastname'])) {
            $reqroom6adultLastname = implode(",", $details['Room6AdultLastname']);
          }
          // Traveller details declaration end
          $datas= array(
              'Room1Discount' => isset($Room1Discount) ? $Room1Discount : 0,
              'Room2Discount' => isset($Room2Discount) ? $Room2Discount : 0,
              'Room3Discount' => isset($Room3Discount) ? $Room3Discount  : 0,
              'Room4Discount' => isset($Room4Discount) ? $Room4Discount : 0,
              'Room5Discount' => isset($Room5Discount) ? $Room5Discount  : 0,
              'Room6Discount' => isset($Room6DiscountPercentage) ? $Room6DiscountPercentage : 0,
              'revenueMarkupType' => '',
              'revenueMarkup' => '',
              'revenueExtrabedMarkup' => '',
              'revenueExtrabedMarkupType' => '',
              'revenueGeneralMarkup' => '',
              'revenueGeneralMarkupType' => '',
              'revenueBoardMarkup' => '',
              'revenueBoardMarkupType' => '',
              'Room1individual_amount' => 0,
              'Room2individual_amount' => 0,
              'Room3individual_amount' => 0,
              'Room4individual_amount' => 0,
              'Room5individual_amount' => 0,
              'Room6individual_amount' => 0,
              'ExtrabedDiscount' => implode(",", $ExDis),
              'GeneralDiscount' => implode(",", $GSDis),
              'BoardDiscount' => implode(",", $BSDis),
              'booking_flag' => $booking_flag,
              'booking_id' =>$max_booking_id,
              'hotel_id' =>$details['hotelcode'],
              'room_id' => implode(",", $RoomID),
              'normal_price' =>0,
              'per_room_amount' =>0,
              'tax' =>0,
              'tax_amount' => '',
              'total_amount' =>$details['amount'],
              'currency_type' =>'AED',
              'adults_count' =>$searchdet['adults'],
              'childs_count' =>$searchdet['child'],
              'agent_markup' =>0,
              'admin_markup' =>0,
              'check_in' => $searchdet['check_in'],
              'check_out' => $searchdet['check_out'],
              'no_of_days' => $tot_days,
              'book_room_count' => $searchdet['noRooms'],
              'providerId' => $result['provider_id'],
              'search_markup' =>  0,
              'bk_contact_fname' => $details['Room1AdultFirstname'][0],
              'bk_contact_lname' => $details['Room1AdultLastname'][0],
              'bk_contact_email' => $details['email'],
              'bk_contact_number' => $details['phoneno'],
              'contract_id' => implode(",", $ContractID),
              'board' => implode(",", $board),
              'Rwadults' => $Rwadults,
              'Rwchild' => $RwChild,
              'Room1ChildAge' => $reqroom1childAge,
              'Room2ChildAge' => $reqroom2childAge,
              'Room3ChildAge' => $reqroom3childAge,
              'Room4ChildAge' => $reqroom4childAge,
              'Room5ChildAge' => $reqroom5childAge,
              'Room6ChildAge' => $reqroom6childAge,
              'individual_amount' => 0,
              'individual_discount' => '',
              'SpecialRequest' => '',
              'Room1-FName' => $reqroom1adultFirstname,
              'Room2-FName' => $reqroom2adultFirstname,
              'Room3-FName' => $reqroom3adultFirstname,
              'Room4-FName' => $reqroom4adultFirstname,
              'Room5-FName' => $reqroom5adultFirstname,
              'Room6-FName' => $reqroom6adultFirstname,
              'Room1-LName' => $reqroom1adultLastname,
              'Room2-LName' => $reqroom2adultLastname,
              'Room3-LName' => $reqroom3adultLastname,
              'Room4-LName' => $reqroom4adultLastname,
              'Room5-LName' => $reqroom5adultLastname,
              'Room6-LName' => $reqroom6adultLastname,
              'discount' => "",
              'discountCode' => $discountCode,
              'discountType' => $discountType,
              'discountStay' => $discountStay,
              'discountPay' => $discountPay,
              'nationality' => $searchdet['nationality'],
              'Created_Date' => date('Y-m-d H:i:s'),
              'Created_By' => $result['provider_id']
            );
          $booking_id = $db->addBooking($datas);
          for ($i=0; $i < $searchdet['noRooms']; $i++) { 
            $IndexSplit = explode("-", $details['roomindex'][$i]);
            // Cancellation Process start 
            $Cancellation[$i] = $db->get_CancellationPolicy_contractConfirm($searchdet,$details['hotelcode'],$IndexSplit[0],$IndexSplit[1]);
            if (count($Cancellation[$i])!=0) {
              foreach ($Cancellation[$i] as $Cpkey => $Cpvalue) {
                $st =$db->addCancellationBooking($booking_id['insertid'],$Cpvalue['msg'],$Cpvalue['percentage'],$Cpvalue['daysFrom'],$Cpvalue['daysTo'],$Cpvalue['application'],$IndexSplit[1],$IndexSplit[0],($i+1),$result['provider_id']);
              }
            }

            // Cancellation Process end 
            //  Extrabed process start
            $ExtrabedAmount[$i] =$db->get_PaymentConfirmextrabedAllotment($searchdet,$details['hotelcode'],$IndexSplit[0],$IndexSplit[1],$i);
            $amount = array();
            if ($ExtrabedAmount[$i]['count']!=0) {
              foreach ($ExtrabedAmount[$i]['date'] as $key => $value){
                  $date=$value;
                  $amount[$key]= $ExtrabedAmount[$i]['extrabedAmount'][$key];
                  
                  foreach ($ExtrabedAmount[$i]['RwextrabedAmount'][$key] as $Rwexamtarrkey => $Rwexamtarrvalue) {
                    $RwexamtarrAmount[$Rwexamtarrkey] = implode(",", $Rwexamtarrvalue);
                  }
                  $Exrwamount[$key] = implode(",", $RwexamtarrAmount);
                 
                  foreach ($ExtrabedAmount[$i]['Exrooms'][$key] as $Rwexroomarrkey => $Rwexroomarrvalue) {
                    $RwexamtarrRoom[$Rwexroomarrkey] = implode(",", $Rwexroomarrvalue);
                  }
                  $Exrooms[$key] = implode(",", $RwexamtarrRoom);

                  foreach ($ExtrabedAmount[$i]['extrabedType'][$key] as $Rwextypearrkey => $Rwextypearrvalue) {
                    $RwexamtarrType[$Rwextypearrkey] = implode(",", $Rwextypearrvalue);
                  }
                  $ExrwType[$key] = implode(",", $RwexamtarrType);
                  
                  $InsertExtrabedAmount=$db->AddPaymentConfirmExtrabed($date,$amount[$key],$booking_id['insertid'],$Exrooms[$key],$Exrwamount[$key],$ExrwType[$key],$IndexSplit[1],$IndexSplit[0],($i+1));
              }
            }
            //  Extrabed process end
            // General Supplement details Add start
            $gadultamount = array();
            $tgadultamount = array();
            $gchildamount = array();
            $tgchildamount = array();
            $general[$i] = $db->get_Confirmgeneral_supplement($searchdet,$IndexSplit[0],$IndexSplit[1],($i+1),$details['hotelcode']);
            if ($general[$i]['gnlCount']!=0) {
              foreach ($general[$i]['date'] as $key3 => $value3) {
                foreach ($general[$i]['general'][$key3] as $key4 => $value4) {
                  $gstayDate = $value3;
                  $gBookingDate = date('Y-m-d');
                  $generalType = $value4;
                  if (isset($general[$i]['adultamount'][$key3][$value4])) {
                    $gadultamount[$key4] = $general[$i]['adultamount'][$key3][$value4];
                    if ($searchdet['child']!=0 && isset($general['childamount'][$key3][$value4])) {
                      $gchildamount[$key4] = $general[$i]['childamount'][$key3][$value4];
                    } else {
                      $gchildamount[$key4] = 0;
                    }
                    $tgadultamount[] = $general[$i]['adultamount'][$key3][$value4];
                    $tgchildamount[] = $gchildamount[$key4];
                    $Rwgadult[$key4] = implode(",", $general[$i]['RWadult'][$key3][$value4]);
                    if (isset($general[$i]['RWchild'][$key3][$value4])) {
                      $Rwgchild[$key4] = implode(",", $general[$i]['RWchild'][$key3][$value4]);
                    } else {
                      $Rwgchild[$key4] = "";
                    }
                    $RwgAdultamount[$key4] = implode(",", $general[$i]['RWadultamount'][$key3][$value4]);
                    if (isset($general[$i]['RWchildAmount'][$key3][$value4])) {
                      foreach ($general[$i]['RWchildAmount'][$key3][$value4] as $gscarkey => $gscarvalue) {
                          $gscarr[] =array_sum($gscarvalue);
                      }
                      $RwgChildamount[$key4] = implode(",", $gscarr);
                    } else {
                      $RwgChildamount[$key4] = "";
                    }
                    $Rwgsapplication[$key4] = $general[$i]['application'][$key3][$key4];
                    $bkgeneralSupplementConfirm = $db->bkgeneralSupplementConfirm($gstayDate, $gBookingDate, $generalType, $gadultamount[$key4] , $gchildamount[$key4],$booking_id['insertid'],$searchdet['adults'],$searchdet['child'],1,$Rwgadult[$key4],$Rwgchild[$key4],$RwgAdultamount[$key4],$RwgChildamount[$key4],$Rwgsapplication[$key4],$IndexSplit[1],$IndexSplit[0],($i+1),$result['provider_id']);
                  }
                }
              }
            }
          // General Supplement details Add end
          }
          $response['status']['status'] = "success";
          $response['status']['description'] = "Hotel booked successfully";
          $response['status']['bookingId'] = 'HAB0'.$booking_id['insertid'];
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
$app->post('/BookingDetail',function($request,$response) {
  $response = array();
  $db = new DbHandler();
  $log = $db->insertLog($request,'BookingDetail');
  $result = authenticate_user($request);
  if($result['success']==true) {
    // validating post params
    $validation = $db->validateparametersbookingdetail($request->getParsedBody());
    if($validation['status']=="success") {
       $data = $request->getParsedBody();
       $bookingdetails = $db->getBookingDetail($data['booking_id']);
       //print_r($bookingdetails);exit;
       $response['status']['status'] = "success";
       $response['result']['Booking_Id'] =  $bookingdetails['booking_id'];
       $response['result']['Hotel Name'] =  $bookingdetails['hotel_name'];
       $response['result']['Check_in'] =  $bookingdetails['check_in'];
       $response['result']['Check_out'] =  $bookingdetails['check_out'];
       $response['result']['No_of_rooms'] =  $bookingdetails['book_room_count'];
       $response['result']['No_of_days'] =  $bookingdetails['no_of_days'];
       $response['result']['Adult_count'] =  $bookingdetails['adults_count'];
       $response['result']['Child_count'] =  $bookingdetails['childs_count'];
       $Rwadults = explode(',',$bookingdetails['Rwadults']);
       $Rwchild = explode(',',$bookingdetails['Rwchild']);
       $response['result']['Booking_date'] =  $bookingdetails['Created_Date'];
       for($i=0;$i<$bookingdetails['book_room_count'];$i++) {
         $response['result']['RoomDetails']['Room'.($i+1)]['Adults'] = $Rwadults[$i];
         $response['result']['RoomDetails']['Room'.($i+1)]['Child'] = $Rwchild[$i];
         $adultfirstname = explode(',',$bookingdetails['Room'.($i+1).'-FName']);
         $adultlastname = explode(',',$bookingdetails['Room'.($i+1).'-LName']);
         $childage = explode(',',$bookingdetails['Room'.($i+1).'ChildAge']);
         for($j=0;$j<$Rwadults[$i];$j++) {
           $response['result']['RoomDetails']['Room'.($i+1)]['Adult_details '.($j+1)]['name'] =  $adultfirstname[$j].' '. $adultlastname[$j];
         }
         for($j=0;$j<$Rwchild[$i];$j++) {
           $response['result']['RoomDetails']['Room'.($i+1)]['Child_details '.($j+1)]['age'] =  $childage[$j];
         }
       }
       if($bookingdetails['booking_flag'] == 1) {
          $response['result']['booking_status']['status'] = "Accepted";
          $response['result']['booking_status']['confirmation_no']= $bookingdetails['confirmationNumber'];
       } else if($bookingdetails['booking_flag'] == 2) {
          $response['result']['booking_status']['status'] = "Pending";
       } else if($bookingdetails['booking_flag'] == 3) {
          $response['result']['booking_status']['status'] = "Cancelled";
       } else if($bookingdetails['booking_flag'] == 4) {
          $response['result']['booking_status']['status'] = "Hotel Approved";
       } else if($bookingdetails['booking_flag'] == 5) {
          $response['result']['booking_status']['status'] = "Cancellation Pending";
       } else if($bookingdetails['booking_flag'] == 8) {
          $response['result']['booking_status']['status'] = "On Request";
       } else if($bookingdetails['booking_flag'] == 9) {
          $response['result']['booking_status']['status'] = "Amendmemt";
       }
       echoResponse($response);
    } else {
      echoResponse($validation);
    }
  } else {
    echoResponse($result);
  }
});
$app->post('/BookingCancel',function($request,$response) {
  $response = array();
  $db = new DbHandler();
  $log = $db->insertLog($request,'BookingCancel');
  $result = authenticate_user($request);
  if($result['success']==true) {
    // validating post params
    $validation = $db->validateparametersbookingcancel($request->getParsedBody());
    if($validation['status']=="success") {
       $data = $request->getParsedBody();
       $result = $db->cancellationrequest($data);
       if($result=='process') {
          $response['status']['status'] = 'Success';
          $response['status']['description'] = 'Your request is processing. Will contact you shortly.';
       } else if ($result=='send') {
          $response['status']['status'] = 'Failed';
          $response['status']['description'] = 'Cancellation request already send for this booking';
       } else {
          $response['status']['status'] = 'Error';
          $response['status']['description'] = 'Request failed';
       }
       echoResponse($response);
    } else {
      echoResponse($validation);
    }
  } else {
    echoResponse($result);
  }
});
$app->post('/BookingCancelStatus',function($request,$response) {
  $response = array();
  $db = new DbHandler();
  $log = $db->insertLog($request,'BookingCancelStatus');
  $result = authenticate_user($request);
  if($result['success']==true) {
    // validating post params
    $validation = $db->validateparametersbookingcancelstatus($request->getParsedBody());
    if($validation['status']=="success") {
       $data = $request->getParsedBody();
       $result = $db->cancellationstatus($data);
       if($result=='process') {
          $response['status']['status'] = 'Success';
          $response['status']['description'] = 'Your request is in processing.';
       } else if ($result=='cancelled') {
          $response['status']['status'] = 'Success';
          $response['status']['description'] = 'Your booking have been cancelled';
       } else if ($result=='notsend') {
          $response['status']['status'] = 'Success';
          $response['status']['description'] = 'You havent request any cancellation for this booking.';
       } else {
          $response['status']['status'] = 'Error';
          $response['status']['description'] = 'Request failed';
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