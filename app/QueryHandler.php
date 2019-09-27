<?php

class QueryHandler {

    protected $db;
    function __construct($db){ 
      $this->db = $db;
    }
    public function getHotelDetails($id) {
        $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_hotels WHERE id = ".$id."");
        // $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->fetch();
        return $user;
        
    }
    public function validateparameters($data) {
        $return = array();
        if(!isset($data['location']) || $data['location'] == '') {
            $return['location'] = 'Location is mandatory';
        }
        if(!isset($data['cityname']) || $data['cityname'] == '') {
            $return['cityname'] = 'City name is mandatory';
        }
        if(!isset($data['countryname']) || $data['countryname'] == '') {
            $return['countryname'] = 'Country name is mandatory';
        }
        if(!isset($data['nationality']) || $data['nationality'] == '') {
            $return['nationality'] = 'Nationality is mandatory';
        }
        if(!isset($data['check_in']) || $data['check_in'] == '') {
            $return['check_in'] = 'Check in is mandatory';
        }
        if(!isset($data['check_out']) || $data['check_out'] == '') {
            $return['check_out'] = 'Check Out is mandatory';
        }
        if(!isset($data['no_of_rooms']) || $data['no_of_rooms'] == '') {
            $return['no_of_rooms'] = 'Number of rooms is mandatory';
        }
        if(isset($data['no_of_rooms']) && $data['no_of_rooms'] != '') {
            for($i=0;$i<$data['no_of_rooms'];$i++) {
                if(!isset($data['adults']) || !isset($data['adults'][$i]) || $data['adults'][$i] == '') {
                  $return['adults['.($i).']'] = 'adults['.($i).'] is missing';
                }
                if(!isset($data['child']) || !isset($data['child'][$i]) || $data['child'][$i] == '') {
                  $return['child['.($i).']'] = 'child['.($i).'] is missing';
                }
                if(isset($data['child'][$i]) && $data['child'][$i]!=0) {
                    for($j=0;$j<$data['child'][$i];$j++) {
                        if(!isset($data['room'.($i+1).'-childAge']) || !isset($data['room'.($i+1).'-childAge'][$j]) || $data['room'.($i+1).'-childAge'][$j] == '') {
                          $return['room'.($i+1).'_child_age'] = 'Room'.($i+1).' child age is missing';
                        }
                    }
                }
            }
        }
        if(empty($return)) {
            $response['status'] = "true";
            $response['message'] = "success";
        } else {
            $response['status'] = "false";
            $response['message'] = "failed";
            $response['error'] = $return;
        }
        return $response;
    }
    function getHotelList($data) {
      $outData = array();
      $search = '';
      $hotelName = '';

      $checkin_date=date_create($data['check_in']);
      $checkout_date=date_create($data['check_out']);
      $no_of_days=date_diff($checkin_date,$checkout_date);
      $tot_days = $no_of_days->format("%a");


      if (!empty($data['location'])) {
        $str = explode('-',$data['location']);
        if (!isset($str[1])) {
          $str = explode(',',$data['location']);
        }
        $data['location'] = $str[0];
        $search = "a.location LIKE '%".$data['location']."%' OR a.keywords LIKE '%".$data['location']."%' OR a.city LIKE '%".$data['location']."%' OR b.name  = '".$data['location']."' ";
      }
    
      if ($search!='') {
        $search = '('.$search.') AND ';
      }

      if (isset($data['HotelName']) && $data['HotelName']!="") {
        $hotelName = " hotel_name  LIKE '%".$this->db->escape_like_str($data['HotelName'])."%' AND ";
      }

      $room2 = "";
      $room3 = "";
      $room4 = "";
      $room5 = "";
      $room6 = "";

      if (isset($data['adults'][1])) {
        $room2 =" OR (c.max_total >= ".($data['adults'][1]+$data['child'][1])." AND c.occupancy >= ".$data['adults'][1]." AND c.occupancy_child >= ".$data['child'][1].")";
      }
      if (isset($data['adults'][2])) {
        $room3 =" OR (c.max_total >= ".($data['adults'][2]+$data['child'][2])." AND c.occupancy >= ".$data['adults'][2]." AND c.occupancy_child >= ".$data['child'][2].")";
      }
      if (isset($data['adults'][3])) {
        $room4 =" OR (c.max_total >= ".($data['adults'][3]+$data['child'][3])." AND c.occupancy >= ".$data['adults'][3]." AND c.occupancy_child >= ".$data['child'][3].")";
      }
      if (isset($data['adults'][4])) {
        $room5 =" OR (c.max_total >= ".($data['adults'][4]+$data['child'][4])." AND c.occupancy >= ".$data['adults'][4]." AND c.occupancy_child >= ".$data['child'][4].")";
      }
      if (isset($data['adults'][5])) {
        $room6 =" OR (c.max_total >= ".($data['adults'][5]+$data['child'][5])." AND c.occupancy >= ".$data['adults'][5]." AND c.occupancy_child >= ".$data['child'][5].")";
      }
    
      $stmt = $this->db->prepare("SELECT a.id FROM hotel_tbl_hotels a INNER JOIN states b ON  b.id = IF(a.state!='',a.state,3798) INNER JOIN hotel_tbl_hotel_room_type c ON c.hotel_id = a.id WHERE ".$search.$hotelName."  a.delflg = 1 and ((c.max_total >= ".($data['adults'][0]+$data['child'][0])." AND c.occupancy >= ".$data['adults'][0]." AND c.occupancy_child >= ".$data['child'][0].") ".$room2.$room3.$room4.$room5.$room6.")  and c.delflg = 1");
        if ($stmt->execute()) {
            $tmp = $stmt->fetch();
        }
        $hotelidArr = array();

        foreach ($stmt->fetchAll() as $key => $value) {
          $hotelidArr[$key] = $value['id'];
        }
        if (count($hotelidArr)!=0) {
          $searchHotel_id = implode(",", array_unique($hotelidArr));
        } else {
          $searchHotel_id = 0;
        }


      $ot = $this->db->prepare("SELECT contract_id,hotel_id,contract_type,linkedContract FROM hotel_tbl_contract a WHERE not exists (select 1 from  hotel_agent_permission b where   a.contract_id = b.contract_id  AND FIND_IN_SET('".$data['nationality']."', IFNULL(nationalityPermission,'')) = 0)
     AND not exists (select 1 from hotel_country_permission c where a.contract_id = c.contract_id and FIND_IN_SET('AED', IFNULL(permission,'')) > 0) AND hotel_id IN (".$searchHotel_id.") AND from_date <= '".date('Y-m-d',strtotime($data['check_in']))."' AND to_date >= '".date('Y-m-d',strtotime($data['check_in']))."' AND  from_date < '".date('Y-m-d',strtotime($data['check_out']. ' -1 days'))."' AND to_date >= '".date('Y-m-d',strtotime($data['check_out']. ' -1 days'))."' AND contract_flg  = 1");

      if ($ot->execute()) {
          $tmp1 = $ot->fetch();
      }

      foreach ($ot->fetchAll() as $key5 => $value5) {
        if ($value5['contract_type']=="Sub") {
          $enablecon = $this->db->prepare('SELECT id FROM hotel_tbl_contract WHERE contract_id = "CON0'.$value5['linkedContract'].'" AND contract_flg = 1');
          if ($enablecon->execute()) {
              $tmp2 = $enablecon->fetch();
          }
          if (count($enablecon->fetchAll())!=0) {
            $outData['hotel_id'][$key5] = $value5['hotel_id'];
            $outData['contract_id'][$key5] = $value5['contract_id'];
          }
        } else {
          $outData['hotel_id'][$key5] = $value5['hotel_id'];
          $outData['contract_id'][$key5] = $value5['contract_id'];
        }
      }

      $dateAlt = array();
      for($i = 0; $i < $tot_days; $i++) {
        $dateAlt[$i] = date('Y-m-d', strtotime($data['check_in']. ' + '.$i.'  days'));
      }

      $implode_data = implode("','", $dateAlt);

      if (isset($outData['hotel_id'][0]) && $outData['hotel_id'][0]!='') {
        $implode_data1 = implode(",", array_unique($outData['hotel_id']));
      } else {
        $implode_data1 = "0";
      }
      $implode_data2 = implode("','", array_unique($outData['contract_id']));

      $room1 = "";
      $room2 = "";
      $room3 = "";
      $room4 = "";
      $room5 = "";
      $room6 = "";
      // Room 1
      $markup = 0;
      $general_markup = 0;
      if (isset($data['adults'][0])) {
        $Room1ChildAge1 = 0; 
        $Room1ChildAge2 = 0; 
        $Room1ChildAge3 = 0; 
        $Room1ChildAge4 = 0; 
        if (isset($_REQUEST['room1-childAge'][0])) {
          $Room1ChildAge1 = $_REQUEST['room1-childAge'][0]; 
        }
        if (isset($_REQUEST['room1-childAge'][1])) {
          $Room1ChildAge2 = $_REQUEST['room1-childAge'][1]; 
        }
        if (isset($_REQUEST['room1-childAge'][2])) {
          $Room1ChildAge3 = $_REQUEST['room1-childAge'][2]; 
        }
        if (isset($_REQUEST['room1-childAge'][3])) {
          $Room1ChildAge4 = $_REQUEST['room1-childAge'][3]; 
        }

        $room1 = "SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,sum(TtlPrice) as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,1 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       (((SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where
         Discount_flag = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0 
 AND FIND_IN_SET(a.contract_id,contract) > 0  AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1)/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][0]." > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room1ChildAge1.",0,IF(ChildAgeFrom < ".$Room1ChildAge1." && ChildAgeTo >= ".$Room1ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][0]+$data['child'][0])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=".$Room1ChildAge1.",0,IF(startAge <= ".$Room1ChildAge1." && finalAge >= ".$Room1ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',sum(amount)+(sum(amount)*BoardSupMarkup/100)+(sum(amount)*".$markup."/100),sum(amount)+(count(amount)*BoardSupMarkup)+(sum(amount)*".$markup."/100)),sum(amount)+(sum(amount)*".($markup+$general_markup)."/100)),0))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][0].")+(adultAmount*".$data['adults'][0].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][0].")+(GeneralSupMarkup*".$data['adults'][0].")),(adultAmount*".$data['adults'][0].")+((adultAmount*".$data['adults'][0].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][0].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room1ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room1ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room1ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room1ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room1ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room1ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room1ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room1ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0  AND rev.FromDate <= '".date('Y-m-d',strtotime($data['check_in']))."' AND  rev.ToDate >= '".date('Y-m-d',strtotime($data['check_out']))."'

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][0]+$data['child'][0])." AND f.occupancy >= ".$data['adults'][0]." AND f.occupancy_child >= ".$data['child'][0].") AND f.delflg = 1 AND a.allotement_date IN ('".$implode_data."') AND a.contract_id IN ('".$implode_data2."') AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0 AND (SELECT count(*) FROM hotel_tbl_closeout_period WHERE closedDate IN ('".$implode_data."') AND FIND_IN_SET(a.room_id,roomType)>0 AND contract_id = a.contract_id AND hotel_id = a.hotel_id) =0 AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x GROUP By x.hotel_id";

      }


      if (isset($data['adults'][1])) {
        $Room2ChildAge1 = 0; 
        $Room2ChildAge2 = 0; 
        $Room2ChildAge3 = 0; 
        $Room2ChildAge4 = 0; 
        if (isset($_REQUEST['room2-childAge'][0])) {
          $Room2ChildAge1 = $_REQUEST['room2-childAge'][0]; 
        }
        if (isset($_REQUEST['room2-childAge'][1])) {
          $Room2ChildAge2 = $_REQUEST['room2-childAge'][1]; 
        }
        if (isset($_REQUEST['room2-childAge'][2])) {
          $Room2ChildAge3 = $_REQUEST['room2-childAge'][2]; 
        }
        if (isset($_REQUEST['room2-childAge'][3])) {
          $Room2ChildAge4 = $_REQUEST['room2-childAge'][3]; 
        }

        $room2 = " UNION SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,sum(TtlPrice) as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,2 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       (((SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where
         Discount_flag = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0 
 AND FIND_IN_SET(a.contract_id,contract) > 0  AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1)/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][1]." > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room2ChildAge1.",0,IF(ChildAgeFrom < ".$Room2ChildAge1." && ChildAgeTo >= ".$Room2ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][1]+$data['child'][1])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=".$Room2ChildAge1.",0,IF(startAge <= ".$Room2ChildAge1." && finalAge >= ".$Room2ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',sum(amount)+(sum(amount)*BoardSupMarkup/100)+(sum(amount)*".$markup."/100),sum(amount)+(count(amount)*BoardSupMarkup)+(sum(amount)*".$markup."/100)),sum(amount)+(sum(amount)*".($markup+$general_markup)."/100)),0))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][1].")+(adultAmount*".$data['adults'][1].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][1].")+(GeneralSupMarkup*".$data['adults'][1].")),(adultAmount*".$data['adults'][1].")+((adultAmount*".$data['adults'][1].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][1].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room2ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room2ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room2ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room2ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room2ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0  AND rev.FromDate <= '".date('Y-m-d',strtotime($data['check_in']))."' AND  rev.ToDate >= '".date('Y-m-d',strtotime($data['check_out']))."'

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][1]+$data['child'][1])." AND f.occupancy >= ".$data['adults'][1]." AND f.occupancy_child >= ".$data['child'][1].") AND f.delflg = 1 AND a.allotement_date IN ('".$implode_data."') AND a.contract_id IN ('".$implode_data2."') AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0 AND (SELECT count(*) FROM hotel_tbl_closeout_period WHERE closedDate IN ('".$implode_data."') AND FIND_IN_SET(a.room_id,roomType)>0 AND contract_id = a.contract_id AND hotel_id = a.hotel_id) =0 AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x GROUP By x.hotel_id";

      }

      if (isset($data['adults'][2])) {
        $Room3ChildAge1 = 0; 
        $Room3ChildAge2 = 0; 
        $Room3ChildAge3 = 0; 
        $Room3ChildAge4 = 0; 
        if (isset($_REQUEST['room3-childAge'][0])) {
          $Room3ChildAge1 = $_REQUEST['room3-childAge'][0]; 
        }
        if (isset($_REQUEST['room3-childAge'][1])) {
          $Room3ChildAge2 = $_REQUEST['room3-childAge'][1]; 
        }
        if (isset($_REQUEST['room3-childAge'][2])) {
          $Room3ChildAge3 = $_REQUEST['room3-childAge'][2]; 
        }
        if (isset($_REQUEST['room3-childAge'][3])) {
          $Room3ChildAge4 = $_REQUEST['room3-childAge'][3]; 
        }

        $room3 = " UNION SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,sum(TtlPrice) as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,3 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       (((SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where
         Discount_flag = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0 
 AND FIND_IN_SET(a.contract_id,contract) > 0  AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1)/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][2]." > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room3ChildAge1.",0,IF(ChildAgeFrom < ".$Room3ChildAge1." && ChildAgeTo >= ".$Room3ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][2]+$data['child'][2])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=".$Room3ChildAge1.",0,IF(startAge <= ".$Room3ChildAge1." && finalAge >= ".$Room3ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',sum(amount)+(sum(amount)*BoardSupMarkup/100)+(sum(amount)*".$markup."/100),sum(amount)+(count(amount)*BoardSupMarkup)+(sum(amount)*".$markup."/100)),sum(amount)+(sum(amount)*".($markup+$general_markup)."/100)),0))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][2].")+(adultAmount*".$data['adults'][2].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][2].")+(GeneralSupMarkup*".$data['adults'][2].")),(adultAmount*".$data['adults'][2].")+((adultAmount*".$data['adults'][2].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][2].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room3ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room3ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room3ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room3ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room3ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room3ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room3ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room3ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0  AND rev.FromDate <= '".date('Y-m-d',strtotime($data['check_in']))."' AND  rev.ToDate >= '".date('Y-m-d',strtotime($data['check_out']))."'

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][2]+$data['child'][2])." AND f.occupancy >= ".$data['adults'][2]." AND f.occupancy_child >= ".$data['child'][2].") AND f.delflg = 1 AND a.allotement_date IN ('".$implode_data."') AND a.contract_id IN ('".$implode_data2."') AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0 AND (SELECT count(*) FROM hotel_tbl_closeout_period WHERE closedDate IN ('".$implode_data."') AND FIND_IN_SET(a.room_id,roomType)>0 AND contract_id = a.contract_id AND hotel_id = a.hotel_id) =0 AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x GROUP By x.hotel_id";

      }

      if (isset($data['adults'][3])) {
        $Room4ChildAge1 = 0; 
        $Room4ChildAge2 = 0; 
        $Room4ChildAge3 = 0; 
        $Room4ChildAge4 = 0; 
        if (isset($_REQUEST['room4-childAge'][0])) {
          $Room4ChildAge1 = $_REQUEST['room4-childAge'][0]; 
        }
        if (isset($_REQUEST['room4-childAge'][1])) {
          $Room4ChildAge2 = $_REQUEST['room4-childAge'][1]; 
        }
        if (isset($_REQUEST['room4-childAge'][2])) {
          $Room4ChildAge3 = $_REQUEST['room4-childAge'][2]; 
        }
        if (isset($_REQUEST['room4-childAge'][3])) {
          $Room4ChildAge4 = $_REQUEST['room4-childAge'][3]; 
        }

        $room4 = " UNION SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,sum(TtlPrice) as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,4 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       (((SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where
         Discount_flag = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0 
 AND FIND_IN_SET(a.contract_id,contract) > 0  AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1)/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][3]." > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room4ChildAge1.",0,IF(ChildAgeFrom < ".$Room4ChildAge1." && ChildAgeTo >= ".$Room4ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][3]+$data['child'][3])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=".$Room4ChildAge1.",0,IF(startAge <= ".$Room4ChildAge1." && finalAge >= ".$Room4ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',sum(amount)+(sum(amount)*BoardSupMarkup/100)+(sum(amount)*".$markup."/100),sum(amount)+(count(amount)*BoardSupMarkup)+(sum(amount)*".$markup."/100)),sum(amount)+(sum(amount)*".($markup+$general_markup)."/100)),0))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][3].")+(adultAmount*".$data['adults'][3].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][3].")+(GeneralSupMarkup*".$data['adults'][3].")),(adultAmount*".$data['adults'][3].")+((adultAmount*".$data['adults'][3].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][3].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room4ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room4ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room4ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room4ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room4ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room4ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room4ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room4ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0  AND rev.FromDate <= '".date('Y-m-d',strtotime($data['check_in']))."' AND  rev.ToDate >= '".date('Y-m-d',strtotime($data['check_out']))."'

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][3]+$data['child'][3])." AND f.occupancy >= ".$data['adults'][3]." AND f.occupancy_child >= ".$data['child'][3].") AND f.delflg = 1 AND a.allotement_date IN ('".$implode_data."') AND a.contract_id IN ('".$implode_data2."') AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0 AND (SELECT count(*) FROM hotel_tbl_closeout_period WHERE closedDate IN ('".$implode_data."') AND FIND_IN_SET(a.room_id,roomType)>0 AND contract_id = a.contract_id AND hotel_id = a.hotel_id) =0 AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x GROUP By x.hotel_id";

      }

      if (isset($data['adults'][4])) {
        $Room5ChildAge1 = 0; 
        $Room5ChildAge2 = 0; 
        $Room5ChildAge3 = 0; 
        $Room5ChildAge4 = 0; 
        if (isset($_REQUEST['room5-childAge'][0])) {
          $Room5ChildAge1 = $_REQUEST['room5-childAge'][0]; 
        }
        if (isset($_REQUEST['room5-childAge'][1])) {
          $Room5ChildAge2 = $_REQUEST['room5-childAge'][1]; 
        }
        if (isset($_REQUEST['room5-childAge'][2])) {
          $Room5ChildAge3 = $_REQUEST['room5-childAge'][2]; 
        }
        if (isset($_REQUEST['room5-childAge'][3])) {
          $Room5ChildAge4 = $_REQUEST['room5-childAge'][3]; 
        }

        $room5 = " UNION SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,sum(TtlPrice) as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,5 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       (((SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where
         Discount_flag = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0 
 AND FIND_IN_SET(a.contract_id,contract) > 0  AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1)/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][4]." > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room5ChildAge1.",0,IF(ChildAgeFrom < ".$Room5ChildAge1." && ChildAgeTo >= ".$Room5ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][4]+$data['child'][4])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=".$Room5ChildAge1.",0,IF(startAge <= ".$Room5ChildAge1." && finalAge >= ".$Room5ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',sum(amount)+(sum(amount)*BoardSupMarkup/100)+(sum(amount)*".$markup."/100),sum(amount)+(count(amount)*BoardSupMarkup)+(sum(amount)*".$markup."/100)),sum(amount)+(sum(amount)*".($markup+$general_markup)."/100)),0))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][4].")+(adultAmount*".$data['adults'][4].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][4].")+(GeneralSupMarkup*".$data['adults'][4].")),(adultAmount*".$data['adults'][4].")+((adultAmount*".$data['adults'][4].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][4].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room5ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room5ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room5ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room5ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room5ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room5ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room5ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room5ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0  AND rev.FromDate <= '".date('Y-m-d',strtotime($data['check_in']))."' AND  rev.ToDate >= '".date('Y-m-d',strtotime($data['check_out']))."'

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][4]+$data['child'][4])." AND f.occupancy >= ".$data['adults'][4]." AND f.occupancy_child >= ".$data['child'][4].") AND f.delflg = 1 AND a.allotement_date IN ('".$implode_data."') AND a.contract_id IN ('".$implode_data2."') AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0 AND (SELECT count(*) FROM hotel_tbl_closeout_period WHERE closedDate IN ('".$implode_data."') AND FIND_IN_SET(a.room_id,roomType)>0 AND contract_id = a.contract_id AND hotel_id = a.hotel_id) =0 AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x GROUP By x.hotel_id";

      }

      if (isset($data['adults'][5])) {
        $Room6ChildAge1 = 0; 
        $Room6ChildAge2 = 0; 
        $Room6ChildAge3 = 0; 
        $Room6ChildAge4 = 0; 
        if (isset($_REQUEST['room6-childAge'][0])) {
          $Room6ChildAge1 = $_REQUEST['room6-childAge'][0]; 
        }
        if (isset($_REQUEST['room6-childAge'][1])) {
          $Room6ChildAge2 = $_REQUEST['room6-childAge'][1]; 
        }
        if (isset($_REQUEST['room6-childAge'][2])) {
          $Room6ChildAge3 = $_REQUEST['room6-childAge'][2]; 
        }
        if (isset($_REQUEST['room6-childAge'][3])) {
          $Room6ChildAge4 = $_REQUEST['room6-childAge'][3]; 
        }

        $room6 = " UNION SELECT *,min(TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday))) as dd FROM (
        SELECT *,sum(TtlPrice) as TotalPrice,count(*) as counts,IF(min(allotment)=0,'On Request','Book') as RequestType, IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0) as exAmount,
         sum(IF(extrabed!=0,IF(StayExbed=1,extrabed,extrabed-(extrabed*exdis/100)),0)) as exAmountTot
        ,IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100)) as exChildAmount ,
        sum(IF(StayExbed=1,
      IF(extrabedChild=0,0,extrabedChild) ,(IF(extrabedChild=0,0,extrabedChild)- IF(extrabedChild=0,0,extrabedChild)*exdis/100))) as exChildAmountTot ,
        IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))  as boardChildAmount
      ,sum(IF(StayBoard=1,
      IF(extrabedChild=0,extrabedChild1,0) ,(IF(extrabedChild=0,extrabedChild1,0)- IF(extrabedChild=0,extrabedChild1,0)*boarddis/100))) as boardChildAmountTot,
      IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0) as generalsubAmount,
     sum(IF(generalsub!=0,IF(StayGeneral=1, generalsub,generalsub-(generalsub*generaldis/100)),0)) as generalsubAmountTot

      FROM (select a.hotel_id,a.contract_id,a.room_id,a.allotement as allotment, dis.discount_type,dis.Extrabed as StayExbed,dis.General as StayGeneral,dis.Board as StayBoard,IF(dis.stay_night!='',(dis.pay_night*ceil(".$tot_days."/dis.stay_night))+(".$tot_days."-(dis.stay_night*ceil(".$tot_days."/dis.stay_night))),0) as fday ,6 as RoomIndex, rev.ExtrabedMarkup,rev.ExtrabedMarkuptype,

        (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))
        - (a.amount+(a.amount*".$markup."/100)+IF(rev.Markup!='',IF(rev.Markuptype='Percentage',(a.amount*rev.Markup/100),(rev.Markup)), (a.amount*".$general_markup."/100)))*

       (((SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where
         Discount_flag = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0 
 AND FIND_IN_SET(a.contract_id,contract) > 0  AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1)/100)) as TtlPrice,


        (select IF(count(*)!=0,IF(ExtrabedMarkup!='',IF(ExtrabedMarkuptype='Percentage',amount+(amount*ExtrabedMarkup/100)+(amount*".$markup."/100),amount+ExtrabedMarkup+(amount*".$markup."/100)),amount+(sum(amount)*".($markup+$general_markup)."/100)),0) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND 
            ".$data['adults'][5]." > f.standard_capacity ) as extrabed, 


        (select IF(count(*)=0,'',IF(0=".$Room6ChildAge1.",0,IF(ChildAgeFrom < ".$Room6ChildAge1." && ChildAgeTo >= ".$Room6ChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][5]+$data['child'][5])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=".$Room6ChildAge1.",0,IF(startAge <= ".$Room6ChildAge1." && finalAge >= ".$Room6ChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',sum(amount)+(sum(amount)*BoardSupMarkup/100)+(sum(amount)*".$markup."/100),sum(amount)+(count(amount)*BoardSupMarkup)+(sum(amount)*".$markup."/100)),sum(amount)+(sum(amount)*".($markup+$general_markup)."/100)),0))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][5].")+(adultAmount*".$data['adults'][5].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][5].")+(GeneralSupMarkup*".$data['adults'][5].")),(adultAmount*".$data['adults'][5].")+((adultAmount*".$data['adults'][5].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][5].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
          + 

           IF(count(*)=0,0, IF(0=".$Room6ChildAge1." && childAmount=0,0,IF(MinChildAge < ".$Room6ChildAge1.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) )) 

          + IF(count(*)=0,0, IF(0=".$Room6ChildAge2." && childAmount=0,0,IF(MinChildAge < ".$Room6ChildAge2.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room6ChildAge3." && childAmount=0,0,IF(MinChildAge < ".$Room6ChildAge3.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

          +  IF(count(*)=0,0, IF(0=".$Room6ChildAge4." && childAmount=0,0,IF(MinChildAge < ".$Room6ChildAge4.", IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+((childAmount)*GeneralSupMarkup/100),(childAmount)+GeneralSupMarkup),(childAmount+((childAmount)*".$general_markup."/100))),IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(childAmount)+(childAmount*GeneralSupMarkup/100),childAmount+GeneralSupMarkup) ,childAmount))+((childAmount)*".$markup."/100) ,0) ))

         from hotel_tbl_generalsupplement where a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND  mandatory = 1) as generalsub, 

        (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Extrabed = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as exdis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND Board = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as boarddis,

         (SELECT IF(min(discount)!='',discount,0) FROM `hoteldiscount` where Discount_flag = 1 AND General = 1 AND FIND_IN_SET(a.hotel_id ,hotelid) > 0  AND FIND_IN_SET(a.room_id,room) > 0  AND FIND_IN_SET(a.contract_id,contract) > 0 AND ((Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND numofnights <= ".$tot_days." AND discount_type = 'MLOS')  OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = '') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."' AND discount_type = 'EB') OR (Styfrom <= a.allotement_date AND Styto >= a.allotement_date  AND  BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."'  AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."')  AND discount_type = 'REB')) limit 1) as generaldis

      FROM hotel_tbl_allotement a INNER JOIN hotel_tbl_contract con ON con.contract_id = a.contract_id 

      LEFT JOIN hotel_tbl_revenue rev ON FIND_IN_SET(a.hotel_id, IFNULL(rev.hotels,'')) > 0 AND FIND_IN_SET(a.contract_id, IFNULL(rev.contracts,'')) > 0  AND rev.FromDate <= '".date('Y-m-d',strtotime($data['check_in']))."' AND  rev.ToDate >= '".date('Y-m-d',strtotime($data['check_out']))."'

      LEFT JOIN hoteldiscount dis ON FIND_IN_SET(a.hotel_id,dis.hotelid) > 0 AND FIND_IN_SET(a.contract_id,dis.contract) > 0 
      AND FIND_IN_SET(a.room_id,dis.room) > 0 AND Discount_flag = 1 AND (Styfrom <= '".date('Y-m-d',strtotime($data['check_in']))."' AND Styto >= '".date('Y-m-d',strtotime($data['check_in']))."' 
      AND BkFrom <= '".date('Y-m-d')."' AND BkTo >= '".date('Y-m-d')."') AND Bkbefore < DATEDIFF(a.allotement_date,'".date('Y-m-d')."') AND FIND_IN_SET(a.allotement_date,BlackOut)=0 
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id where (f.max_total >= ".($data['adults'][5]+$data['child'][5])." AND f.occupancy >= ".$data['adults'][5]." AND f.occupancy_child >= ".$data['child'][5].") AND f.delflg = 1 AND a.allotement_date IN ('".$implode_data."') AND a.contract_id IN ('".$implode_data2."') AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0 AND (SELECT count(*) FROM hotel_tbl_closeout_period WHERE closedDate IN ('".$implode_data."') AND FIND_IN_SET(a.room_id,roomType)>0 AND contract_id = a.contract_id AND hotel_id = a.hotel_id) =0 AND a.hotel_id IN (".$implode_data1.") AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x GROUP By x.hotel_id";

      }
      $imgurl = 'http://dev.otelseasy.com/';
      $agent_currency = 'AED';
     
      $OtelseasyHotels =  $this->db->prepare("SELECT hotel_id as HotelCode,min(amount) as TotalPrice,h.hotel_name as HotelName,h.location as HotelAddress,concat('".$imgurl."uploads/gallery/',n.hotel_id,'/',h.Image1) as HotelPicture,h.hotel_description as HotelDescription, h.rating as Rating,'".$agent_currency."' as Currency,h.starsrating as reviews ,'' as Inclusion  FROM (SELECT m.*,sum(dd) as amount ,count(*) as roomcount  FROM ( ".$room1.$room2.$room3.$room4.$room5.$room6.") m GROUP BY m.hotel_id HAVING roomcount >= ".count($data['adults']).") n INNER JOIN hotel_tbl_hotels h ON h.id = n.hotel_id GROUP BY n.hotel_id");

      if ($OtelseasyHotels->execute()) {
        $tmp4 = $OtelseasyHotels->fetch();
      }
      return $OtelseasyHotels->fetchAll();
    }
    public function validateparametersavailablerooms($data) {
        $return = array();
        if(!isset($data['location']) || $data['location'] == '') {
            $return['location'] = 'Location is mandatory';
        }
        if(!isset($data['cityname']) || $data['cityname'] == '') {
            $return['cityname'] = 'City name is mandatory';
        }
        if(!isset($data['countryname']) || $data['countryname'] == '') {
            $return['countryname'] = 'Country name is mandatory';
        }
        if(!isset($data['nationality']) || $data['nationality'] == '') {
            $return['nationality'] = 'Nationality is mandatory';
        }
        if(!isset($data['check_in']) || $data['check_in'] == '') {
            $return['check_in'] = 'Check in is mandatory';
        }
        if(!isset($data['check_out']) || $data['check_out'] == '') {
            $return['check_out'] = 'Check Out is mandatory';
        }
        if(!isset($data['no_of_rooms']) || $data['no_of_rooms'] == '') {
            $return['no_of_rooms'] = 'Number of rooms is mandatory';
        }
        if(isset($data['no_of_rooms']) && $data['no_of_rooms'] != '') {
            for($i=0;$i<$data['no_of_rooms'];$i++) {
                if(!isset($data['adults']) || !isset($data['adults'][$i]) || $data['adults'][$i] == '') {
                  $return['adults['.($i).']'] = 'adults['.($i).'] is missing';
                }
                if(!isset($data['child']) || !isset($data['child'][$i]) || $data['child'][$i] == '') {
                  $return['child['.($i).']'] = 'child['.($i).'] is missing';
                }
                if(isset($data['child'][$i]) && $data['child'][$i]!=0) {
                    for($j=0;$j<$data['child'][$i];$j++) {
                        if(!isset($data['room'.($i+1).'-childAge']) || !isset($data['room'.($i+1).'-childAge'][$j]) || $data['room'.($i+1).'-childAge'][$j] == '') {
                          $return['room'.($i+1).'_child_age'] = 'Room'.($i+1).' child age is missing';
                        }
                    }
                }
            }
        }
        if(!isset($data['hotelcode']) || $data['hotelcode'] == '') {
            $return['hotelcode'] = 'Hotel Code is mandatory';
        }
        if(empty($return)) {
            $response['status'] = "true";
            $response['message'] = "success";
        } else {
            $response['status'] = "false";
            $response['message'] = "failed";
            $response['error'] = $return;
        }
        return $response;
    }
    // public function hotel_facilities_data($id) {
    //     $stmt = $this->db->prepare("SELECT Hotel_Facility FROM hotel_tbl_hotel_facility WHERE id ='".$id."'");
    //     $stmt->execute();
    //     $details = $stmt->fetch();
    //     return $details;
    // }
    // public function room_facilities_data($id) {
    //     $stmt = $this->db->prepare("SELECT Room_Facility FROM hotel_tbl_room_facility WHERE id = '".$id."'");
    //     $stmt->execute();
    //     $details = $stmt->fetch();
    //     return $details;
    // }
    public function contractChecking($searchdet) {
        $start = $searchdet['check_in'];
        $end = $searchdet['check_out'];
        $checkin_date=date_create($searchdet['check_in']);
        $checkout_date=date_create($searchdet['check_out']);
        $no_of_days=date_diff($checkin_date,$checkout_date);
        $tot_days = $no_of_days->format("%a");
        // Contract Check start
        $contract_id = array();
        $count = array();
        $contracts = array();
        $stmt = $this->db->prepare("SELECT contract_id FROM hotel_tbl_contract a WHERE  FIND_IN_SET('".$searchdet['nationality']."', IFNULL(nationalityPermission,'')) = 0 AND from_date <= '".date('Y-m-d',strtotime($searchdet['check_in']))."' AND to_date >= '".date('Y-m-d',strtotime($searchdet['check_in']))."' AND  from_date < '".date('Y-m-d',strtotime($searchdet['check_out']. ' -1 days'))."' AND to_date >= '".date('Y-m-d',strtotime($searchdet['check_out']. ' -1 days'))."'  AND hotel_id = '".$searchdet['hotelcode']."' AND contract_flg  = 1");
        $stmt->execute();
        $contracts = $stmt->fetchAll();
        foreach ($contracts as $key5 => $value5) {
            $contract_id[] =  $value5['contract_id'];
        }
        $count[] =  count($contracts);
        $contractdet= array();
        if (count($count)!=0) {
            $array_uniquecon = array_unique($contract_id);
            foreach ($array_uniquecon as $key10 => $value10) {
                $contractdet['contract_id'][] = $value10;
                $stmt = $this->db->prepare("SELECT * FROM hotel_tbl_contract WHERE contract_id ='".$value10."'");
                $stmt->execute();
                $det = $stmt->fetch();
                $contractdet['max_child_age'][] = $det['max_child_age']; 
            }
            return $contractdet;
        } else {
            return $contractdet;
        }
    }
    public function roomwisepaxdata($key,$data,$contract) {
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
        $rooms = array();
        for($i = 0; $i < $tot_days; $i++) {
          $dateAlt[$i] = date('Y-m-d', strtotime($start_date. ' + '.$i.'  days'));
        }
        $implode_data = implode("','", $dateAlt);
        $implode_data2 = implode("','", array_unique($contract));
        $RoomChildAge1 = 0; 
        $RoomChildAge2 = 0; 
        $RoomChildAge3 = 0; 
        $RoomChildAge4 = 0; 
        if (isset($data['room'.($key).'-childAge'][0])) {
          $RoomChildAge1 = $data['room'.($key).'-childAge'][0]; 
        }
        if (isset($data['room'.($key).'-childAge'][1])) {
          $RoomChildAge2 = $data['room'.($key).'-childAge'][1]; 
        }
        if (isset($data['room'.($key).'-childAge'][2])) {
          $RoomChildAge3 = $data['room'.($key).'-childAge'][2]; 
        }
        if (isset($data['room'.($key).'-childAge'][3])) {
          $RoomChildAge4 = $data['room'.($key).'-childAge'][3]; 
        }
        $markup = 0;
        $general_markup = 0;
        $stmt = $this->db->prepare("SELECT RoomIndex,board,RoomName,RequestType,extraLabel,extraChildLabel,TotalPrice-(TtlPrice*fday)+(exAmountTot-(exAmount*fday))+(boardChildAmountTot-(boardChildAmount*fday))+(exChildAmountTot-(exChildAmount*fday))+(generalsubAmountTot-(generalsubAmount*fday)) as Price 
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
            ".$data['adults'][$key]." > f.standard_capacity ) as extrabed, 

        (select IF(count(*)=0,'',IF(0=".$RoomChildAge1.",0,IF(ChildAgeFrom < ".$RoomChildAge1." && ChildAgeTo >= ".$RoomChildAge1.",IF(ExtrabedMarkup!='' && ChildAmount!=0,IF(ExtrabedMarkuptype='Percentage',ChildAmount+(ChildAmount*ExtrabedMarkup/100), ChildAmount+ExtrabedMarkup) ,ChildAmount+(ChildAmount*".$general_markup."/100))+(ChildAmount*".$markup."/100),0))) from hotel_tbl_extrabed where a.allotement_date BETWEEN from_date AND to_date AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND ".($data['adults'][$key]+$data['child'][$key])." > f.standard_capacity) as extrabedChild, 

        (select IF(count(*)=0,0,IF(0=".$RoomChildAge1.",0,IF(startAge <= ".$RoomChildAge1." && finalAge >= ".$RoomChildAge1.",IF(BoardSupMarkup!='',IF(BoardSupMarkuptype='Percentage',sum(amount)+(sum(amount)*BoardSupMarkup/100)+(sum(amount)*".$markup."/100),sum(amount)+(count(amount)*BoardSupMarkup)+(sum(amount)*".$markup."/100)),sum(amount)+(sum(amount)*".($markup+$general_markup)."/100)),0))) from hotel_tbl_boardsupplement where a.allotement_date BETWEEN 
        fromDate AND toDate AND contract_id = a.contract_id AND hotel_id = a.hotel_id AND FIND_IN_SET(a.room_id, IFNULL(roomType,'')) > 0 AND IF(con.board='RO',board IN (''),IF(con.board='BB',board IN ('Breakfast'),IF(con.board='HB',board IN ('Breakfast','Dinner'),board IN ('Breakfast','Lunch','Dinner'))))) as extrabedChild1,

        (select IF(count(*)=0,0,IF(application='Per Person',IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount*".$data['adults'][$key].")+(adultAmount*".$data['adults'][$key].")*GeneralSupMarkup/100,(adultAmount*".$data['adults'][$key].")+(GeneralSupMarkup*".$data['adults'][$key].")),(adultAmount*".$data['adults'][$key].")+((adultAmount*".$data['adults'][$key].")*".$general_markup."/100)) + ((adultAmount*".$data['adults'][$key].")*".$markup."/100) ,IF(GeneralSupMarkup!='',IF(GeneralSupMarkuptype='Percentage',(adultAmount)+(adultAmount)*GeneralSupMarkup/100,adultAmount+GeneralSupMarkup) ,adultAmount+((adultAmount)*".$general_markup."/100))+((adultAmount)*".$markup."/100)))  
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
      AND discount_type = 'stay&pay' AND stay_night <= ".$tot_days." INNER JOIN hotel_tbl_hotel_room_type f ON f.id = a.room_id INNER JOIN hotel_tbl_room_type g ON g.id = f.room_type  where (f.max_total >= ".($data['adults'][$key]+$data['child'][$key])." AND f.occupancy >= ".$data['adults'][$key]." AND f.occupancy_child >= ".$data['child'][$key].") AND f.delflg = 1 AND a.allotement_date IN ('".$implode_data."') AND a.contract_id IN ('".$implode_data2."') AND a.amount !=0 AND (SELECT count(*) FROM hotel_tbl_minimumstay WHERE a.allotement_date BETWEEN fromDate AND toDate AND contract_id = a.contract_id AND minDay > ".$tot_days.") = 0 AND (SELECT count(*) FROM hotel_tbl_closeout_period WHERE closedDate IN ('".$implode_data."') AND FIND_IN_SET(a.room_id,roomType)>0 AND contract_id = a.contract_id AND hotel_id = a.hotel_id) =0 AND a.hotel_id = ".$data['hotelcode']." AND DATEDIFF(a.allotement_date,'".date('Y-m-d')."') >= a.cut_off ) extra) discal GROUP BY hotel_id,room_id,contract_id HAVING counts = ".$tot_days.") x order by price asc");
        $stmt->execute();
        $rooms = $stmt->fetchAll();
        if(empty($rooms)) {
            return null;
        } else {
            return $rooms;
        }   
    }
}