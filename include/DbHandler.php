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
        $stmt = $this->conn->prepare("SELECT contract_id,(select SUM(amount) from hotel_tbl_allotement where 
        contract_id = a.contract_id AND hotel_id = a.hotel_id AND from_date <= '".date('Y-m-d',strtotime($searchdet['check_in']))."' AND to_date > '".date('Y-m-d',strtotime($searchdet['check_in']))."' AND  from_date < '".date('Y-m-d',strtotime($searchdet['check_out']))."' AND to_date >= '".date('Y-m-d',strtotime($searchdet['check_out']))."') as price FROM hotel_tbl_contract a WHERE from_date <= '".date('Y-m-d',strtotime($searchdet['check_in']))."' AND to_date >= '".date('Y-m-d',strtotime($searchdet['check_in']))."' AND  from_date < '".date('Y-m-d',strtotime($searchdet['check_out']. ' -1 days'))."' AND to_date >= '".date('Y-m-d',strtotime($searchdet['check_out']. ' -1 days'))."'  AND hotel_id = '".$hotelid."' AND contract_flg  = 1 order by price asc");
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
      $Bkbeforeno_of_days=date_diff($bookDate,$checkin_date);
      $Bkbefore = $Bkbeforeno_of_days->format("%a");

      for($i = 0; $i < $tot_days; $i++) {
        $dateAlt[$i] = date('Y-m-d', strtotime($start_date. ' + '.$i.'  days'));
      }
      $implode_data = implode("','", $dateAlt);

      $implode_data2 = implode("','", array_unique($contract));
      $RoomChildAge1 = 0; 
      $RoomChildAge2 = 0; 
      $RoomChildAge3 = 0; 
      $RoomChildAge4 = 0; 

      if (isset($searchdet['Room'.($key+1).'-ChildAge'][0])) {
        $RoomChildAge1 = $searchdet['Room'.($key+1).'-ChildAge'][0]; 
      }
      if (isset($searchdet['Room'.($key+1).'-ChildAge'][1])) {
        $RoomChildAge2 = $searchdet['Room'.($key+1).'-ChildAge'][1]; 
      }
      if (isset($searchdet['Room'.($key+1).'-ChildAge'][2])) {
        $RoomChildAge3 = $searchdet['Room'.($key+1).'-ChildAge'][2]; 
      }
      if (isset($searchdet['Room'.($key+1).'-ChildAge'][3])) {
        $RoomChildAge4 = $searchdet['Room'.($key+1).'-ChildAge'][3]; 
      }

      $markup = 0;
      $general_markup = 0;

      $stmt = $this->conn->prepare("SELECT *,TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday)) as dd 
      FROM (
        SELECT *,sum(TtlPrice) as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType,sum(exAmount) as exAmountTot,sum(exChildAmount) as exChildAmountTot
        ,sum(boardChildAmount) as boardChildAmountTot,sum(generalsubAmount) as generalsubAmountTot,IF(sum(exAmount)!=0,'Adult Extrabed','') as extraLabel,
        IF(sum(exChildAmount)!=0,'Child Extrabed','') as extraChildLabel,IF(sum(boardChildAmount)!=0,'Child supplements','') as boardChildLabel 
         FROM (
         SELECT *,
      IF(extrabed!=0,IF(StayExbed=0,extrabed-(extrabed*exdis/100), extrabed),0) as exAmount,
      IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
      IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100)) as boardChildAmount,
      IF(generalsub!=0,IF(StayGeneral=0,generalsub-(generalsub*generaldis/100), generalsub),0) as generalsubAmount

      FROM (select con.board,CONCAT(f.room_name,' ',g.Room_Type) as RoomName,a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, a.amount as TtlPrice1,dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,CONCAT(con.contract_id,'-',a.room_id) as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,

        ((a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       ((select IF(min(discount)!='',discount,(select IF(min(discount)!='',discount,0) from hoteldiscount where Discount_flag = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0 
      AND FIND_IN_SET(a.room_id,room) > 0 AND FIND_IN_SET(a.contract_id,contract) > 0 AND (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND discount_type = 'REB') AND Bkbefore < ".$Bkbefore." AND FIND_IN_SET(a.allotement_date,BlackOut)=0 limit 1)) from hoteldiscount where Discount_flag = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0 
      AND FIND_IN_SET(a.room_id,room) > 0 AND FIND_IN_SET(a.contract_id,contract) > 0 AND (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < ".$Bkbefore." AND FIND_IN_SET(a.allotement_date,BlackOut)=0 limit 1)/100)
         ) as TtlPrice,
        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100),amount+ExtrabedMarkup),amount),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['Room'.($key+1).'Adults']." > f.standard_capacity ) as extrabed, 

        (select IF(count(*)=0,'',IF(0=".$RoomChildAge1.",0,IF(ChildAgeFrom < ".$RoomChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['Room'.($key+1).'Adults']+$data['Room'.($key+1).'Child'])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=".$RoomChildAge1.",0,IF(startAge <= ".$RoomChildAge1." && finalAge >= ".$RoomChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',sum(amount)+(sum(amount)*BoardSupMarkup/100)+(sum(amount)*".$markup."/100),sum(amount)+(count(amount)*BoardSupMarkup)+(sum(amount)*".$markup."/100)),sum(amount)+(sum(amount)*".($markup+$general_markup)."/100)),0))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['Room'.($key+1).'Adults'].")+(adultAmount*".$data['Room'.($key+1).'Adults'].")*GeneralSupMarkup/100,(adultAmount*".$data['Room'.($key+1).'Adults'].")+(GeneralSupMarkup*".$data['Room'.($key+1).'Adults'].")),(adultAmount*".$data['Room'.($key+1).'Adults'].")+((adultAmount*".$data['Room'.($key+1).'Adults'].")*".$general_markup."/100)) + ((adultAmount*".$data['Room'.($key+1).'Adults'].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$RoomChildAge1." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$RoomChildAge2." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$RoomChildAge3." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$RoomChildAge4." && childAmount=0,0,IF(MinChildAge < ".$RoomChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

      (select IF(min(discount)!='',discount,(select IF(min(discount)!='',discount,0) from hoteldiscount where Discount_flag = 1 
      AND FIND_IN_SET(a.hotel_id ,hotelid) > 0 AND FIND_IN_SET(a.room_id,room) > 0 AND FIND_IN_SET(a.contract_id,contract) > 0 AND (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND discount_type = 'REB') AND Extrabed = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0 limit 1)) from hoteldiscount where Discount_flag = 1 
      AND FIND_IN_SET(a.hotel_id ,hotelid) > 0 AND FIND_IN_SET(a.room_id,room) > 0 AND FIND_IN_SET(a.contract_id,contract) > 0 AND (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Extrabed = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0 limit 1) as exdis,

       (select IF(min(discount)!='',discount,(select IF(min(discount)!='',discount,0) from hoteldiscount where Discount_flag = 1 
      AND FIND_IN_SET(a.hotel_id ,hotelid) > 0 AND FIND_IN_SET(a.room_id,room) > 0 AND FIND_IN_SET(a.contract_id,contract) > 0 AND (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND discount_type = 'REB') AND Board = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0 limit 1)) from hoteldiscount where Discount_flag = 1 
      AND FIND_IN_SET(a.hotel_id ,hotelid) > 0 AND FIND_IN_SET(a.room_id,room) > 0 AND FIND_IN_SET(a.contract_id,contract) > 0 AND (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND discount_type = 'REB') AND Board = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0 limit 1) as boarddis,

      (select IF(min(discount)!='',discount,(select IF(min(discount)!='',discount,0) from hoteldiscount where Discount_flag = 1 
      AND FIND_IN_SET(a.hotel_id ,hotelid) > 0 AND FIND_IN_SET(a.room_id,room) > 0 AND FIND_IN_SET(a.contract_id,contract) > 0 AND (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND discount_type = 'REB') AND General = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0 limit 1)) from hoteldiscount where Discount_flag = 1 
      AND FIND_IN_SET(a.hotel_id ,hotelid) > 0 AND FIND_IN_SET(a.room_id,room) > 0 AND FIND_IN_SET(a.contract_id,contract) > 0 AND (Styfrom <= a.allotement_date AND Styto >= a.allotement_date AND discount_type = 'REB') AND General = 1 AND FIND_IN_SET(a.allotement_date,BlackOut)=0 limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0 AND  rev.FromDate <= '".date('Y-m-d',strtotime($data['check_in']))."' AND  rev.ToDate >= '".date('Y-m-d',strtotime($data['check_out']))."'

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < ".$Bkbefore." AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id INNER JOIN hotel_tbl_room_type g ON g.id = f.room_type  where (f.max_total >= ".($data['Room'.($key+1).'Adults']+$data['Room'.($key+1).'Child'])." AND f.occupancy >= ".$data['Room'.($key+1).'Adults']." AND f.occupancy_child >= ".$data['Room'.($key+1).'Child'].") AND f.delflg = 1 AND a.allotement_date IN ('".$implode_data."') AND a.contract_id IN ('".$implode_data2."') AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0 AND (SELECT count(*) FROM hotel_tbl_closeout_period WHERE closedDate IN ('".$implode_data."') AND FIND_IN_SET(a.room_id,roomType)>0 AND contract_id = a.contract_id AND hotel_id = a.hotel_id) =0 AND a.hotel_id = ".$hotel_id." AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) extra) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x");
         if ($stmt->execute()) {
            $tmp = $stmt->get_result();
            $stmt->close();
        }
        while($ot = $tmp->fetch_assoc()) {
            $rooms[] = $ot;
        }
        return $rooms;
    }
}
?>