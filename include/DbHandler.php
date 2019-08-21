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

        //$stmt->close();
        // Check for successful insertion
        if ($result) {
            // data successfully inserted
            return true;
        } else {
            // Failed to insert
            return false;
        }
    }
}
?>