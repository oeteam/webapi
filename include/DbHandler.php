<?php
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
    public function getHotelDetails($id) {
        $stmt = $this->conn->prepare("SELECT * FROM hotel_tbl_hotels WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $details = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $details;
        } else {
            return NULL;
        }
    }
    public function isValidUser($user,$pass) {
        $stmt = $this->conn->prepare("SELECT * from api_tbl_users WHERE username = ? and password= ? and status=1");
        $stmt->bind_param("ss", $user,$pass);
        $stmt->execute();
        $num_rows = $stmt->get_result()->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    public function getuserdetails($user,$pass) {
        $stmt = $this->conn->prepare("SELECT * FROM api_tbl_users WHERE username = ? and password= ? and status=1");
        $stmt->bind_param("ss", $user, $pass);
        if ($stmt->execute()) {
            $details = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $details;
        } else {
            return NULL;
        }
    }
    public function validateparameters($data) {
        $response = array();
        if(!isset($data['location']) || $data['location'] == '') {
            $response['location_error'] = 'Location is mandatory';
        }
        if(!isset($data['cityname']) || $data['cityname'] == '') {
            $response['cityname_error'] = 'City name is mandatory';
        }
        if(!isset($data['countryname']) || $data['countryname'] == '') {
            $response['country_error'] = 'Country name is mandatory';
        }
        if(!isset($data['nationality']) || $data['nationality'] == '') {
            $response['nationality_error'] = 'Nationality is mandatory';
        }
        if(!isset($data['check_in']) || $data['check_in'] == '') {
            $response['CheckIn_error'] = 'Check in is mandatory';
        }
        if(!isset($data['check_out']) || $data['check_out'] == '') {
            $response['CheckOut_error'] = 'Check Out is mandatory';
        }
        if(!isset($data['no_of_rooms']) || $data['no_of_rooms'] == '') {
            $response['rooms_error'] = 'Number of rooms is mandatory';
        }
        if(isset($data['no_of_rooms']) && $data['no_of_rooms'] != '') {
            for($i=0;$i<$data['no_of_rooms'];$i++) {
                if(!isset($data['adults']) || !isset($data['adults'][$i]) || $data['adults'][$i] == '') {
                  $response['room'.($i+1).'_adult_error'] = 'Room'.($i+1).' adult count is missing';
                }
                if(!isset($data['child']) || !isset($data['child'][$i]) || $data['child'][$i] == '') {
                  $response['room'.($i+1).'_child_error'] = 'Room'.($i+1).' child count is missing';
                }
                if(isset($data['child'][$i]) && $data['child'][$i]!=0) {
                    for($j=0;$j<$data['child'][$i];$j++) {
                        if(!isset($data['room'.($i+1).'-childAge']) || !isset($data['room'.($i+1).'-childAge'][$j]) || $data['room'.($i+1).'-childAge'][$j] == '') {
                          $response['room'.($i+1).'_child_age_error'] = 'Room'.($i+1).' child age is missing';
                        }
                    }
                }
            }
        }
        if(empty($response)) {
            $response['status'] = "success";
        } else {
            $response['status'] = "error";
        }
        return $response;
    }
    public function addSearchDetails($data) {
        $room1childAge = "";
        $room2childAge = "";
        $room3childAge = "";
        $room4childAge = "";
        $room5childAge = "";
        $room6childAge = "";
        $room7childAge = "";
        $room8childAge = "";
        $room9childAge = "";
        $room10childAge = "";
        $Room1Adults = "";
        $Room2Adults = "";
        $Room3Adults = "";
        $Room4Adults = "";
        $Room5Adults = "";
        $Room6Adults = "";
        $Room1Child = "";
        $Room2Child = "";
        $Room3Child = "";
        $Room4Child = "";
        $Room5Child = "";
        $Room6Child = "";
        if (isset($data['room1-childAge'])) {
          $room1childAge = implode(",", $data['room1-childAge']);
        }
        if (isset($data['room2-childAge'])) {
          $room2childAge = implode(",", $data['room2-childAge']);
        }
        if (isset($data['room3-childAge'])) {
          $room3childAge = implode(",", $data['room3-childAge']);
        }
        if (isset($data['room4-childAge'])) {
          $room4childAge = implode(",", $data['room4-childAge']);
        }
        if (isset($data['room5-childAge'])) {
          $room5childAge = implode(",", $data['room5-childAge']);
        }
        if (isset($data['room6-childAge'])) {
          $room6childAge = implode(",", $data['room6-childAge']);
        }
        if (isset($data['adults'][0])) {
          $Room1Adults = $data['adults'][0];
        }
        if (isset($data['adults'][1])) {
          $Room2Adults = $data['adults'][1];
        }
        if (isset($data['adults'][2])) {
          $Room3Adults = $data['adults'][2];
        }
        if (isset($data['adults'][3])) {
          $Room4Adults = $data['adults'][3];
        }
        if (isset($data['adults'][4])) {
          $Room5Adults = $data['adults'][4];
        }
        if (isset($data['adults'][5])) {
          $Room6Adults = $data['adults'][5];
        }
        if (isset($data['child'][0])) {
          $Room1Child = $data['child'][0];
        }
        if (isset($data['child'][1])) {
          $Room2Child = $data['child'][1];
        }
        if (isset($data['child'][2])) {
          $Room3Child = $data['child'][2];
        }
        if (isset($data['child'][3])) {
          $Room4Child = $data['child'][3];
        }
        if (isset($data['child'][4])) {
          $Room5Child = $data['child'][4];
        }
        if (isset($data['child'][5])) {
          $Room4Child = $data['child'][5];
        }
        if (isset($data['hotel_name'])) {
          $hotelname = $data['hotel_name'];
        } else {
            $hotelname = "";
        }
        $stmt = $this->conn->prepare("INSERT INTO api_tbl_search(location,city,country,nationality,check_in,check_out,nights,hotel_name,adults,child,searchDate,providerId,Room1ChildAge,Room2ChildAge,Room3ChildAge,Room4ChildAge,Room5ChildAge,Room6ChildAge,noRooms,Room1Adults,Room2Adults,Room3Adults,Room4Adults,Room5Adults,Room6Adults,Room1Child,Room2Child,Room3Child,Room4Child,Room5Child,Room6Child,sessionId)values('".$data['location']."', '".$data['cityname']."', '".$data['countryname']."', '".$data['nationality']."','".$data['check_in']."', '".$data['check_out']."','".$data['nights']."', '".$hotelname."', '".array_sum($data['adults'])."', '".array_sum($data['child'])."', '".date('Y-m-d')."', '".$data['provider_id']."', '".$room1childAge."', '".$room2childAge."', '".$room3childAge."', '".$room4childAge."', '".$room5childAge."', '".$room6childAge."', '".$data['no_of_rooms']."', '".$Room1Adults."','".$Room2Adults."','".$Room3Adults."', '".$Room4Adults."', '".$Room5Adults."','".$Room6Adults."','".$Room1Child."', '".$Room2Child."', '".$Room3Child."','".$Room4Child."', '".$Room5Child."', '".$Room6Child."','".$data['session_id']."')");
        $result = $stmt->execute();
        // Check for successful insertion
        if ($result) {
            // data successfully inserted
            return true;
        } else {
            // Failed to insert
            return false;
        }
    }
    public function validateparametersavailablerooms($data) {
        $response = array();
        if(!isset($data['session_id']) || $data['session_id'] == '') {
            $response['session_error'] = 'Session ID is mandatory';
        }
        if(!isset($data['hotelcode']) || $data['hotelcode'] == '') {
            $response['hotel_error'] = 'Hotel Code is mandatory';
        }
        if(empty($response)) {
            $response['status'] = "success";
        } else {
            $response['status'] = "error";
        }
        return $response;
    }
    public function hotel_facilities_data($id) {
        $stmt = $this->conn->prepare("SELECT Hotel_Facility FROM hotel_tbl_hotel_facility WHERE id = ?");
        $stmt->bind_param("i", $value);
        if ($stmt->execute()) {
            $details = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $details;
        } else {
            return null;
        }
    }
    public function room_facilities_data($id) {
        $stmt = $this->conn->prepare("SELECT Room_Facility FROM hotel_tbl_room_facility WHERE id = ?");
        $stmt->bind_param("i", $value);
        if ($stmt->execute()) {
            $details = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $details;
        } else {
            return null;
        }
    }
    public function getSearchDetails($sessionid) {
        $stmt = $this->conn->prepare("SELECT * FROM api_tbl_search WHERE sessionId = ?");
        $stmt->bind_param("s", $sessionid);
        if ($stmt->execute()) {
            $details = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $details;
        } else {
            return null;
        }
    }
    public function contractChecking($searchdet,$hotelid) {
        $start = $searchdet['check_in'];
        $end = $searchdet['check_out'];
        $checkin_date=date_create($searchdet['check_in']);
        $checkout_date=date_create($searchdet['check_out']);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");
        // Contract Check start
        $contract_id = array();
        $count = array();
        $stmt = $this->conn->prepare("SELECT contract_id FROM hotel_tbl_contract a WHERE  FIND_IN_SET('".$searchdet['nationality']."', IFNULL(nationalityPermission,'')) = 0 AND from_date <= '".date('Y-m-d',strtotime($searchdet['check_in']))."' AND to_date >= '".date('Y-m-d',strtotime($searchdet['check_in']))."' AND  from_date < '".date('Y-m-d',strtotime($searchdet['check_out']. ' -1 days'))."' AND to_date >= '".date('Y-m-d',strtotime($searchdet['check_out']. ' -1 days'))."'  AND hotel_id = '".$hotelid."' AND contract_flg  = 1");
        if ($stmt->execute()) {
            $tmp = $stmt->get_result();
            $stmt->close();
        }
        while($ot = $tmp->fetch_assoc()) {
            $dt[] = $ot;
        }
        foreach ($dt as $key5 => $value5) {
            $contract_id[] =  $value5['contract_id'];
        }
        $count[] =  count($dt);
        if (count($count)!=0) {
            $array_uniquecon = array_unique($contract_id);
            foreach ($array_uniquecon as $key10 => $value10) {
                $contracts['contract_id'][] = $value10;
                $stmt = $this->conn->prepare("SELECT * FROM hotel_tbl_contract WHERE contract_id ='".$value10."'");
                if ($stmt->execute()) {
                    $det = $stmt->get_result()->fetch_assoc();
                    $contracts['max_child_age'][] = $det['max_child_age'];
                    $stmt->close();
                }  
            }
            return $contracts;
        } else {
            return false;
        }
    }
    public function roomwisepaxdata($hotel_id,$key,$data,$contract) {
      $start_date = $data['check_in'];
      $end_date = $data['check_out'];
      $first_date = strtotime($start_date);
      $second_date = strtotime($end_date);
      $offset = $second_date-$first_date; 
      $result = array();
      $checkin_date=date_create($data['check_in']);
      $checkout_date=date_create($data['check_out']);
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");
      $bookDate = date_create(date('Y-m-d'));

      for($i = 0; $i < $tot_days; $i++) {
        $dateAlt[$i] = date('Y-m-d', strtotime($start_date. ' + '.$i.'  days'));
      }
      $implode_data = implode("','", $dateAlt);

      $implode_data2 = implode("','", array_unique($contract));
      $RoomChildAge1 = 0; 
      $RoomChildAge2 = 0; 
      $RoomChildAge3 = 0; 
      $RoomChildAge4 = 0; 

      if (isset($searchdet['Room'.($key+1).'ChildAge'][0])) {
        $RoomChildAge1 = $searchdet['Room'.($key+1).'ChildAge'][0]; 
      }
      if (isset($searchdet['Room'.($key+1).'ChildAge'][1])) {
        $RoomChildAge2 = $searchdet['Room'.($key+1).'ChildAge'][1]; 
      }
      if (isset($searchdet['Room'.($key+1).'ChildAge'][2])) {
        $RoomChildAge3 = $searchdet['Room'.($key+1).'ChildAge'][2]; 
      }
      if (isset($searchdet['Room'.($key+1).'ChildAge'][3])) {
        $RoomChildAge4 = $searchdet['Room'.($key+1).'ChildAge'][3]; 
      }

      $markup = 0;
      $general_markup = 0;

      $stmt = $this->conn->prepare("SELECT *,TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday)) as Price 
      FROM (
        SELECT *,sum(TtlPrice) as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType,sum(exAmount) as exAmountTot,sum(exChildAmount) as exChildAmountTot
        ,sum(boardChildAmount) as boardChildAmountTot,sum(generalsubAmount) as generalsubAmountTot,IF(sum(exAmount)!=0,'Adult Extrabed','') as extraLabel,
        IF(sum(exChildAmount)!=0,'Child Extrabed','') as extraChildLabel,IF(sum(boardChildAmount)!=0,'Child supplements','') as boardChildLabel 
         FROM (
         SELECT *,
      IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
      IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
      IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100)) as boardChildAmount,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount

      FROM (select con.board,CONCAT(f.room_name,' ',g.Room_Type) as RoomName,a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, a.amount as TtlPrice1,dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,CONCAT(con.contract_id,'-',a.room_id) as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,

        ((a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

         ((SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where
        Discount_flag = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0 
         AND FIND_IN_SET(a.room_id,room) > 0 
         AND FIND_IN_SET(a.contract_id,contract) > 0 
         AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' 
          AND Bkbefore <  DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' 
          AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB')
          OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' 
          AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB') 
        ) limit 1)/100)
         ) as TtlPrice,
        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['Room'.($key+1).'Adults']." > f.standard_capacity ) as extrabed, 

        (select IF(count(*)=0,'',IF(0=".$RoomChildAge1.",0,IF(ChildAgeFrom < ".$RoomChildAge1." && ChildAgeTo >= ".$RoomChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['Room'.($key+1).'Adults']+$data['Room'.($key+1).'Child'])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=".$RoomChildAge1.",0,IF(startAge <= ".$RoomChildAge1." && finalAge >= ".$RoomChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',sum(amount)+(sum(amount)*BoardSupMarkup/100)+(sum(amount)*".$markup."/100),sum(amount)+(count(amount)*BoardSupMarkup)+(sum(amount)*".$markup."/100)),sum(amount)+(sum(amount)*".($markup+$general_markup)."/100)),0))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['Room'.($key+1).'Adults'].")+(adultAmount*".$data['Room'.($key+1).'Adults'].")*GeneralSupMarkup/100,(adultAmount*".$data['Room'.($key+1).'Adults'].")+(GeneralSupMarkup*".$data['Room'.($key+1).'Adults'].")),(adultAmount*".$data['Room'.($key+1).'Adults'].")+((adultAmount*".$data['Room'.($key+1).'Adults'].")*".$general_markup."/100)) + ((adultAmount*".$data['Room'.($key+1).'Adults'].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$RoomChildAge1." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$RoomChildAge2." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$RoomChildAge3." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$RoomChildAge4." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

      (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0 AND rev.FromDate <= '".date('Y-m-d',strtotime($data['check_in']))."' AND  rev.ToDate >= '".date('Y-m-d',strtotime($data['check_out']))."'

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id INNER JOIN hotel_tbl_room_type g ON g.id = f.room_type  where (f.max_total >= ".($data['Room'.($key+1).'Adults']+$data['Room'.($key+1).'Child'])." AND f.occupancy >= ".$data['Room'.($key+1).'Adults']." AND f.occupancy_child >= ".$data['Room'.($key+1).'Child'].") AND f.delflg = 1 AND a.allotement_date IN ('".$implode_data."') AND a.contract_id IN ('".$implode_data2."') AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0 AND (SELECT count(*) FROM hotel_tbl_closeout_period WHERE closedDate IN ('".$implode_data."') AND FIND_IN_SET(a.room_id,roomType)>0 AND contract_id = a.contract_id AND hotel_id = a.hotel_id) =0 AND a.hotel_id = ".$hotel_id." AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) extra) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x order by price asc");
         if ($stmt->execute()) {
            $tmp = $stmt->get_result();
            $stmt->close();
        }
        while($ot = $tmp->fetch_assoc()) {
            $rooms[] = $ot;
        }
        return $rooms;
    }
    public function validateparametersbookingreview($data) {
        $response = array();
        if(!isset($data['session_id']) || $data['session_id'] == '') {
            $response['session_error'] = 'Session ID is mandatory';
        }
        if(!isset($data['hotelcode']) || $data['hotelcode'] == '') {
            $response['hotel_error'] = 'Hotel Code is mandatory';
        }
        $stmt = $this->conn->prepare("SELECT * FROM api_tbl_search WHERE sessionId = '".$data['session_id']."'");
        if ($stmt->execute()) {
            $details = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } 
        for($i=0;$i<$details['noRooms'];$i++) {
            if(!isset($data['room'][$i]) || $data['room'][$i] == '') {

                $response['room'.($i+1).'_error'] = 'Room['.$i.'] is required';
            }
        }
        if(empty($response)) {
            $response['status'] = "success";
        } else {
            $response['status'] = "error";
        }
        return $response;
    }
    public function get_policy_contract($hotel_id,$contract_id){
        $stmt = $this->conn->prepare("SELECT Important_Remarks_Policies,Important_Notes_Conditions,cancelation_policy FROM hotel_tbl_policies WHERE hotel_id ='".$hotel_id."' and contract_id = '".$contract_id."'");
        if ($stmt->execute()) {
            $details = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $details;
        } else {
            return null;
        }
    }
    public function contractBoardCheck($contract_id) {
        $stmt = $this->conn->prepare("SELECT board FROM hotel_tbl_contract WHERE contract_id = '".$contract_id."'");
        if ($stmt->execute()) {
            $details = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $details;
        } else {
            return null;
        }
    }
    public function additionalfoodrequest($hotelid,$contractid,$roomid,$request,$boardRequest) {
        $adultBoardAmount = array();
        $childBoardAmount = array();
        $childarrayBoardSumData = array();
        $bsCount = array();
        $BoardsupplementType = array();
        $start_date = $request['check_in'];
        $end_date = $request['check_out'];
        $checkin_date=date_create($start_date);
        $checkout_date=date_create($end_date);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");
        for($i = 0; $i < $tot_days; $i++) {
            $date[$i] = date('Y-m-d', strtotime($start_date. ' + '.$i.'  days'));
            $stmt = $this->conn->prepare("SELECT * FROM hotel_tbl_boardsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contractid."'  AND FIND_IN_SET('".$roomid."', IFNULL(roomType,'')) > 0 AND board = '".$boardRequest."' ");
            if ($stmt->execute()) {
                $boardSplmntCheck[$i][] = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } 
            foreach ($boardSplmntCheck[$i] as $key7 => $value7) {
              $BoardsupplementType[] = $value7['board'];
            }
        }
        if (count($BoardsupplementType)!=0) {
          return true;
        } else {
          return false;
        }
    }
    public function get_PaymentConfirmextrabedAllotment($request,$hotel_id,$contract_id,$room_id,$index) {
        $extrabedAmount  = array();
        $extraBedtotal  = array();
        $exrooms = array();
        $extrabedType = array();
        $stmt = $this->conn->prepare("SELECT tax_percentage,max_child_age,board FROM hotel_tbl_contract WHERE hotel_id= '".$hotel_id."' and contract_id = '".$contract_id."'");
        if ($stmt->execute()) {
            $row_values = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } 
        $tax = $row_values['tax_percentage'];
        $max_child_age = $row_values['max_child_age'];
        $contract_board = $row_values['board'];
        $stmt = $this->conn->prepare("SELECT occupancy,occupancy_child,standard_capacity,max_total FROM hotel_tbl_hotel_room_type WHERE hotel_id= '".$hotel_id."' and id = '".$room_id."'");
        if ($stmt->execute()) {
            $Rmrow_values = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } 
        $occupancyAdult = $Rmrow_values['occupancy'];
        $occupancyChild = $Rmrow_values['occupancy_child'];
        $standard_capacity = $Rmrow_values['standard_capacity'];
        $max_capacity = $Rmrow_values['max_total'];
        $Room_Type = $room_id;
        $start_date = $request['check_in'];
        $end_date = $request['check_out'];
        $checkin_date=date_create($start_date);
        $checkout_date=date_create($end_date);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");
        for($i = 0; $i < $tot_days; $i++) {
            /*Extrabed allotment start*/
            $date[$i] = date('Y-m-d', strtotime($start_date. ' + '.$i.'  days'));
            if ($contract_board=="BB") {
                $contract_boardRequest = array('Breakfast');
            } else if($contract_board=="HB") {
                $contract_boardRequest = array('Breakfast','Dinner');
            } else if($contract_board=="FB") {
                $contract_boardRequest = array('Breakfast','Dinner','Lunch');
            } else {
                $contract_boardRequest = array();
            }
            $implodeboardRequest = implode("','", $contract_boardRequest);
            $stmt = $this->conn->prepare("SELECT * FROM hotel_tbl_extrabed WHERE '".$date[$i]."' BETWEEN from_date AND to_date AND contract_id = '".$contract_id."' AND  hotel_id = '".$hotel_id."' AND FIND_IN_SET('".$Room_Type."', IFNULL(roomType,'')) > 0");
            if ($stmt->execute()) {
                $tmp = $stmt->get_result();
                $stmt->close();
                $extrabedallotment[$i] = array();
                while($res[] = $tmp->fetch_assoc()) {
                    $extrabedallotment[$i] = $res;
                }
            }
            $boardalt[$i] = array();
            if (count($extrabedallotment[$i])!=0) {
                foreach ($extrabedallotment[$i] as $key15 => $value15) {
                    if (($request['Room'.($index+1).'Adults']+$request['Room'.($index+1).'Child']) > $standard_capacity) {
                        $RoomChildAge = explode(",",$request['Room'.($index+1).'ChildAge']);
                        if (isset($RoomChildAge)) {
                            foreach ($RoomChildAge as $key18 => $value18) {
                              if ($max_child_age < $value18) {
                                $extrabedAmount[$i][$index][] =  $value15['amount'];
                                $exrooms[$i][$index][] = $index+1;
                                $extrabedType[$i][$index][] =  'Adult Extrabed';
                              } else {
                                if ($value15['ChildAmount']!=0 && $value15['ChildAmount']!="") {
                                    if ($value15['ChildAgeFrom'] <= $value18 && $value15['ChildAgeTo'] >= $value18) {
                                      $extrabedAmount[$i][$index][$key18] =  $value15['ChildAmount'];
                                      $extrabedType[$i][$index][$key18] =  'Child Extrabed';
                                      $exrooms[$i][$index][$key18] = $index+1;
                                    }
                                } else {
                                    $stmt = $this->conn->prepare("SELECT * FROM hotel_tbl_boardsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."' AND board IN ('".$implodeboardRequest."') AND FIND_IN_SET('".$Room_Type."', IFNULL(roomType,'')) > 0");
                                    if($stmt->execute()) {
                                        $tmp = $stmt->get_result();
                                        $stmt->close();
                                        while($res = $tmp->fetch_assoc()) {
                                            $boardalt[$i] = $res;
                                        }
                                    }
                                    if (count($boardalt[$i])!=0) {
                                        foreach ($boardalt[$i] as $key21 => $value21) {
                                          if ($value21['startAge'] <= $value18 && $value21['finalAge'] >= $value18) {
                                            $extrabedAmount[$i][$index][$key21] =  $value21['amount'];
                                            $exrooms[$i][$index][$key18] = $index+1;
                                            $extrabedType[$i][$index][$key21] =  'Child '.$value21['board'];
                                          }
                                        }
                                    }
                                } 
                              }
                            } 
                        }
                        if ($request['Room'.($index+1).'Adults'] > $standard_capacity) {
                            $extrabedAmount[$i][$index][] =  $value15['amount'];
                            $exrooms[$i][$index][] = $index+1;
                            $extrabedType[$i][$index][] =  'Adult Extrabed';
                        }
                    }
                }
            }
            if (count($extrabedallotment[$i])==0) {
                $stmt = $this->conn->prepare("SELECT * FROM hotel_tbl_boardsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."' AND board IN ('".$implodeboardRequest."') AND FIND_IN_SET('".$Room_Type."', IFNULL(roomType,'')) > 0");
                if($stmt->execute()) {
                    $tmp = $stmt->get_result();
                    $stmt->close();
                    while($res = $tmp->fetch_assoc()) {
                        $boardalt[$i] = $res;
                    }
                }
                if (($request['Room'.($index+1).'Adults']+$request['Room'.($index+1).'Child']) > $standard_capacity) {
                    $RoomChildAge = explode(",",$request['Room'.($index+1).'ChildAge']);
                    if (isset($RoomChildAge)) {
                        foreach ($RoomChildAge as $key18 => $value18) {
                            if (count($boardalt[$i])!=0) {
                              foreach ($boardalt[$i] as $key21 => $value21) {
                                if ($value21['startAge'] <= $value18 && $value21['finalAge'] >= $value18) {
                                  $extrabedAmount[$i][$index][$key21] =  $value21['amount'];
                                  $exrooms[$i][$index][$key18] = $index+1;
                                  $extrabedType[$i][$index][$key21] =  'Child '.$value21['board'];
                                }
                              }
                            }
                        }
                    }
                }
            }
            /* Board wise supplement check start */
            $boardSp[$i] = array();
            if($contract_board=="HB") {
                $stmt = $this->conn->prepare("SELECT startAge,finalAge,amount,board FROM hotel_tbl_boardsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."' AND board = 'Half Board' AND FIND_IN_SET('".$Room_Type."', IFNULL(roomType,'')) > 0");
                if($stmt->execute()) {
                    $tmp = $stmt->get_result();
                    $stmt->close();
                    while($res = $tmp->fetch_assoc()) {
                        $boardSp[$i] = $res;
                    }
                }
            } else if($contract_board=="FB") {
                $stmt = $this->conn->prepare("SELECT startAge,finalAge,amount,board FROM hotel_tbl_boardsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."' AND board = 'Full Board' AND FIND_IN_SET('".$Room_Type."', IFNULL(roomType,'')) > 0");
                if($stmt->execute()) {
                    $tmp = $stmt->get_result();
                    $stmt->close();
                    while($res = $tmp->fetch_assoc()) {
                        $boardSp[$i] = $res;
                    }
                }
            }
            if (count($boardSp[$i])!=0) {
                foreach ($boardSp[$i] as $key21 => $value21) {
                  if (($request['Room'.($index+1).'Adults']+$request['Room'.($index+1).'Child']) > $standard_capacity) {
                    $RoomChildAge = explode(",",$request['Room'.($index+1).'ChildAge']);
                    if (isset($RoomChildAge)) {
                      foreach ($RoomChildAge as $key18 => $value18) {
                        if ($value21['startAge'] <= $value18 && $value21['finalAge'] >= $value18) {
                          if (round($value21['amount'])!=0) {
                            $extrabedAmount[$i][$index][] =  $value21['amount'];
                            $extrabedType[$i][$index][] =  'Child '.$value21['board'];
                          }
                        }
                      }
                    }
                  }
                  if ($value21['startAge'] >= 18) {
                    if (round($value21['amount'])!=0) {
                      $extrabedAmount[$i][$index][] =  $value21['amount'];
                      $extrabedType[$i][$index][] =  'Adult '.$value21['board'];
                    }
                  }
                }
            }
            /* Board wise supplement check end */
            if (isset($extrabedAmount[$i])) {
              $Texamount[$i] = array();
              foreach ($extrabedAmount[$i] as $Texamkey => $Texam) {
                  $Texamount[$i][] = array_sum($Texam);
              }
              $extraBedtotal[$i] = array_sum($Texamount[$i]);
            }
        }
        if (count($extraBedtotal)!=0) {
            $return['date'] = $date;
            $return['extrabedAmount'] = $extraBedtotal;
            $return['extrabedType'] = $extrabedType;
            $return['RwextrabedAmount'] = $extrabedAmount;
            $return['Exrooms'] = $exrooms;
            $return['count'] = count($extraBedtotal);
        } else {
            $return['count'] = 0;
        }
        return $return;
    }
     public function get_Confirmgeneral_supplement($request,$contract_id,$room_id,$j,$hotel_id) {
        /*Standard capacity get from rooms start*/
        $stmt = $this->conn->prepare("SELECT occupancy,occupancy_child,standard_capacity FROM hotel_tbl_hotel_room_type WHERE hotel_id = '".$hotel_id."' AND id = '".$room_id."'");
        if($stmt->execute()) {
            $Rmrow_values = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        $occupancyAdult = $Rmrow_values['occupancy'];
        $occupancyChild = $Rmrow_values['occupancy_child'];
        $standard_capacity = $Rmrow_values['standard_capacity'];

        /*Standard capacity get from rooms end*/

        $return = array();
        $adultAmount =array();
        $RWadultAmount = array();
        $RWadult = array();
        $RWchild = array();
        $childAmount =array();
        $RWchildAmount = array();
        $generalsupplementType = array();
        $generalsupplementapplication = array();
        $boardSplmntCheck  = array();
        $gsarraySum = array();
        $mangsarraySum = array();
        $ManadultAmount  = array();
        $MangeneralsupplementforAdults = array();
        $ManchildAmount = array();
        $MangeneralsupplementforChilds = array();
        $MangeneralsupplementType = array();
        //$generalSplmntCheck[] = array();
        $stmt = $this->conn->prepare("SELECT * FROM hotel_tbl_hotel_room_type WHERE id = '".$room_id."'");
        if($stmt->execute()) {
            $roomType = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        $checkin_date=date_create($request['check_in']);
        $checkout_date=date_create($request['check_out']);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");
        for($i = 0; $i < $tot_days; $i++) {
            $date[$i] = date('Y-m-d', strtotime($request['check_in']. ' + '.$i.'  days'));
            $dateFormatdate[$i] = date('d/m/Y', strtotime($request['check_in']. ' + '.$i.'  days'));
            $dateFormatday[$i] = date('D', strtotime($request['check_in']. ' + '.$i.'  days'));
            /*Mandatory General Supplement start*/
            $adultAmount =array();
            $RWadultAmount = array();
            $stmt = $this->conn->prepare("SELECT * FROM hotel_tbl_generalsupplement WHERE '".$date[$i]."' BETWEEN fromDate AND toDate AND contract_id = '".$contract_id."'  AND hotel_id = '".$hotel_id."'  AND mandatory = 1 AND FIND_IN_SET('".$room_id."', IFNULL(roomType,'')) > 0");
            if($stmt->execute()) {
                $tmp = $stmt->get_result();
                $stmt->close();
                $generalSplmntCheck[$i] = array();
                while($res = $tmp->fetch_assoc()) {
                    $generalSplmntCheck[$i] = $res;
                }
            }
            $gsarraySum[$i] = count($generalSplmntCheck[$i]);
          // print_r($gsarraySum[$i]);exit;
            if (count($generalSplmntCheck[$i])!=0) {
                foreach ($generalSplmntCheck[$i] as $key1 => $value1) {
            if ($value1['application']=="Per Person") {
              if (round($value1['adultAmount'])!=0) {
                $adultAmount[$value1['type']] = $value1['adultAmount']*$request['Room'.$j.'Adults'];
              }
              if (round($value1['adultAmount'])!=0) {
                $RWadultAmount[$value1['type']][$j] = $value1['adultAmount']*$request['Room'.$j.'Adults'];
                $RWadult[$value1['type']][$j] = $j;
              }
              if (isset($request['Room'.$j.'ChildAge'])) {
                foreach ($request['Room'.$j.'ChildAge'] as $key44 => $value44) {
                  if ($value1['MinChildAge'] < $value44) {
                    if (round($value1['childAmount'])!=0) {
                      $childAmount[$value1['type']] = $value1['childAmount'];
                      $RWchildAmount[$value1['type']][$j][$key44] = $value1['childAmount'];
                      $RWchild[$value1->type][$j] = $j;
                    }
                    // $childAmount[$value1->type] = $value1->childAmount;
                  } 
                }

              }
            } else {
              if (round($value1['adultAmount'])!=0) {
                $adultAmount[$value1['type']] = $value1['adultAmount'];
                $childAmount[$value1['type']] = 0;
                $RWadultAmount[$value1['type']][1] = $value1['adultAmount'];
                $RWadult[$value1['type']][1] = 1;
              }
            }
            $generalsupplementType[$key1] = $value1['type'];
            $generalsupplementapplication[$key1] = $value1['application'];
                
          }
        }

        $return['date'][$i] = $dateFormatdate[$i];
        $return['day'][$i] = $dateFormatday[$i];
        $return['adultamount'][$i] = $adultAmount;
        $return['RWadultamount'][$i] = $RWadultAmount;
        $return['RWadult'][$i] = $RWadult;
        $return['RWchild'][$i] = $RWchild;
        $return['childamount'][$i] = $childAmount;
        $return['RWchildAmount'][$i] = $RWchildAmount;
        $return['general'][$i] = array_unique($generalsupplementType);
        $return['application'][$i] = array_unique($generalsupplementapplication);
        $return['ManadultAmount'][$i] = $ManadultAmount;
        $return['ManchildAmount'][$i] = $ManchildAmount;
        $return['ManchildAmount'][$i] = $ManchildAmount;
        $return['Manadultcount'][$i] = $MangeneralsupplementforAdults;
        $return['Manchildcount'][$i] = $MangeneralsupplementforChilds;
        $return['mangeneral'][$i] = array_unique($MangeneralsupplementType);
      }
      $return['gnlCount'] = array_sum($gsarraySum)+array_sum($mangsarraySum);
      return $return;
    }
    public function Alldiscount($startdate,$enddate,$hotel_id,$room_id,$contract_id,$type) {
      $checkin_date=date_create($startdate);
      $checkout_date=date_create($enddate);
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");
      $discount['stay'] = 0;
      $discount['pay'] = 0;
      $discount['dis'] = 'false';
      $hotelidCheck = array();
      $contractCheck = array();
      $roomCheck = array();
      $BlackoutDateCheck = array();
      $query = array();
      if($room_id!="" && $contract_id!="") {
          $stmt = $this->conn->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1  AND
        FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$startdate.'" AND Styto >= "'.$startdate.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND stay_night <= '.$tot_days.'  AND discount_type = "stay&pay") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$startdate.'" AND Styto >= "'.$startdate.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND stay_night <= '.$tot_days.' AND discount_type = "stay&pay" order by stay_night desc) order by stay_night desc');
          if($stmt->execute()) {
            $tmp = $stmt->get_result();
            $stmt->close();
            while($res = $tmp->fetch_assoc()) {
                $query[] = $res;
            }
          }
          if (count($query)!=0) {
            if($query['BlackOut']!="")  {
              $BlackoutDate = explode(",", $query[0]['BlackOut']);
              for ($j=0; $j < $tot_days ; $j++) { 
                $dates[$j] =  date('Y-m-d', strtotime($startdate. ' + '.$j.'  days'));
                  if (is_numeric(array_search($dates[$j],$BlackoutDate))) {
                      $BlackoutDateCheck[] = 1;              
                  }
              }
            }
            if (array_sum($BlackoutDateCheck)==0) {
              $discount['stay'] = $query['stay_night'];
              $discount['pay'] = $query['pay_night'];
              $discount['dis'] = 'true';
              $discount['type'] = $query['discount_type'];
              $discount['discountCode'] = $query['discountCode'];
              $discount['Extrabed'] = $query['Extrabed'];
              $discount['General'] = $query['General'];
              $discount['Board'] = $query['Board'];
            }
          }
        }
      return $discount;
    }
    public function special_offer_amount($date,$room_id,$hotel_id,$contract_id) {
      $date = date('Y-m-d', strtotime($date));
      $stmt = $this->conn->prepare('SELECT amount FROM hotel_tbl_allotement WHERE room_id ="'.$room_id.'" and hotel_id ="'.$hotel_id.'" and allotement_date="'.$date.'" and contract_id="'.$contract_id.'"');
      if($stmt->execute()) {
        $tmp = $stmt->get_result();
        $stmt->close();
        $result = array();
        while($res = $tmp->fetch_assoc()) {
            $result = $res;
        }
      }
      if (count($result)!=0) {
        $amount = $result['amount'];
      } else {
        $stmt = $this->conn->prepare('SELECT price FROM hotel_tbl_hotel_room_type WHERE id ="'.$room_id.'"');
        if($stmt->execute()) {
            $tmp = $stmt->get_result();
            $stmt->close();
            $result1 = array();
            while($res = $tmp->fetch_assoc()) {
                $result1 = $res;
            }
        }
        if (count($result1)!=0) {
            $amount = $result1['price'];    
        } else {
           $amount = 0; 
        } 
      }
      return $amount;
    }
    public function roomnameGET($room_id,$hotel_id) {
        $stmt = $this->conn->prepare('SELECT CONCAT(a.room_name," ",b.Room_Type) as name FROM hotel_tbl_hotel_room_type a inner join  hotel_tbl_room_type b on b.id=a.room_type WHERE a.id ="'.$room_id.'" and a.hotel_id ="'.$hotel_id.'"');
        if($stmt->execute()) {
            $tmp = $stmt->get_result();
            $stmt->close();
            $result1 = array();
            while($res = $tmp->fetch_assoc()) {
                $result1 = $res;
            }
        }
        if (count($result1)!=0) {
            $name = $result1['name'];   
        } else {
           $name = ''; 
        } 
        return $name;
    }
    public function DateWisediscount($date,$hotel_id,$room_id,$contract_id,$type,$checkIn,$checkOut,$status='false') {
      $chIn = date_create($checkIn);
      $chOut = date_create($checkOut);
      $noOfDays=date_diff($chIn,$chOut);
      $totalDays = $noOfDays->format("%a");
      $checkin_date=date_create($date);
      $checkout_date=date_create(date('Y-m-d'));
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");
      $return['discount'] = 0;
      $return['Extrabed'] = 0;
      $return['General'] = 0;
      $return['Board'] = 0;
      $hotelidCheck = array();
      $contractCheck = array();
      $roomCheck = array();
      $BlackoutDateCheck = array();
      $query = array();
      if ($status=='false' && $room_id!="" && $contract_id!="") {
        $stmt = $this->conn->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND
        FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "MLOS" AND numofnights <= '.$totalDays.') AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "MLOS" AND numofnights <= '.$totalDays.')');
        if($stmt->execute()) {
            $tmp = $stmt->get_result();
            $stmt->close();
            while($res = $tmp->fetch_assoc()) {
                $query = $res;
            }
        }
        if (count($query)==0) {
            $stmt = $this->conn->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND
            FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND Bkbefore < '.$tot_days.' AND discount_type = "")');
            if($stmt->execute()) {
                $tmp = $stmt->get_result();
                $stmt->close();
                while($res = $tmp->fetch_assoc()) {
                    $query = $res;
                }
            }
        }
        if (count($query)==0) {
            $stmt = $this->conn->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND
            FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND discount_type = "EB") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'" AND  BkFrom <= "'.date("Y-m-d").'" AND BkTo >= "'.date("Y-m-d").'") AND discount_type = "EB")');
            if($stmt->execute()) {
                $tmp = $stmt->get_result();
                $stmt->close();
                while($res = $tmp->fetch_assoc()) {
                    $query = $res;
                }
            }
        }
        if (count($query)==0) {
            $stmt = $this->conn->prepare('SELECT * FROM hoteldiscount WHERE Discount_flag = 1 AND
                FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND ((Styfrom <= "'.$date.'" AND Styto >= "'.$date.'") AND Bkbefore < '.$tot_days.' AND discount_type = "REB") AND discount  = (SELECT MIN(discount) FROM hoteldiscount  WHERE Discount_flag = 1 AND FIND_IN_SET('.$hotel_id.' ,hotelid) > 0 AND FIND_IN_SET('.$room_id.',room) > 0 AND FIND_IN_SET("'.$contract_id.'",contract) > 0 AND (Styfrom <= "'.$date.'" AND Styto >= "'.$date.'") AND Bkbefore < '.$tot_days.' AND discount_type = "REB")');
            if($stmt->execute()) {
                $tmp = $stmt->get_result();
                $stmt->close();
                while($res = $tmp->fetch_assoc()) {
                    $query = $res;
                }
            }
        }
        if (count($query)!=0) {
            $BlackoutDate = explode(",", $query['BlackOut']); 
            if($query['BlackOut']!="")  {
              foreach ($BlackoutDate as $key0 => $value0) {
                  if ($value0==$date) {
                      $BlackoutDateCheck[$key0] = 1;
                  }
              }
            }
            if (array_sum($BlackoutDateCheck)==0) {
                $return['discountType'] = 'discount';
                if ($query['discount_type']!="") {
                    $return['discountType'] = $query['discount_type'];
                }
                $return['discountCode'] = $query['discountCode'];
                $return['discount'] = $query['discount'];
                $return['Extrabed'] = $query['Extrabed'];
                $return['General'] = $query['General'];
                $return['Board'] = $query['Board'];
             }
        }
      }
      return $return;
    }
    public function general_tax($id) {
        $stmt = $this->conn->prepare('SELECT tax_percentage FROM hotel_tbl_contract WHERE hotel_id = "'.$id.'"');
        if($stmt->execute()) {
            $tmp = $stmt->get_result();
            $stmt->close();
            while($res = $tmp->fetch_assoc()) {
                $result = $res;
            }
        }
        if (count($result)!=0) {
            return $result['tax_percentage'];
        }
        return 0;
    }
     public function insertLog($data,$method) {
        $stmt = $this->conn->prepare("SELECT * FROM api_tbl_users WHERE username = '".$data->getHeaderLine('username')."' and password= '".$data->getHeaderLine('password')."' and status=1");
        if ($stmt->execute()) {
            $details = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } 
        if(isset($details['provider_id'])) {
            $provider_id = $details['provider_id'];
        } else {
            $provider_id = "";
        }
        $arr['username'] = $data->getHeaderLine('username');
        $arr['password'] = $data->getHeaderLine('password');
        $stmt = $this->conn->prepare("INSERT INTO api_tbl_log(request_date,method,providerId,header_parameters,parameters)values('".date('Y-m-d H:i:s')."', '".$method."', '".$provider_id."', '".json_encode($arr)."','".json_encode($data->getParsedBody())."')");
        $result = $stmt->execute();
        // Check for successful insertion
        if ($result) {
            // data successfully inserted
            return true;
        } else {
            // Failed to insert
            return false;
        }
    }
    public function validatesession($data,$providerid) {
        $stmt = $this->conn->prepare("SELECT * FROM api_tbl_search WHERE sessionid = '".$data['session_id']."' and providerId= '".$providerid."' and searchDate='".date('Y-m-d')."'");
        if($stmt->execute()) {
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
        if (!empty($result)) {
            return "success";
        } else {
            return "error";
        }
    }
}
?>