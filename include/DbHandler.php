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
        $response = true;
        if(!isset($data['location']) || $data['location'] == '') {
            $response['location_error'] = 'Location is mandatory';
        }
        if(!isset($data['citycode']) || $data['citycode'] == '') {
            $response['citycode_error'] = 'City Code is mandatory';
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
        return $response;
    }
}
?>