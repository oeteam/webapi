<?php
declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use \Firebase\JWT\JWT;
require_once 'QueryHandler.php';

return function (App $app) {

    $container = $app->getContainer();
    // $app->get('/', function (Request $request, Response $response) {
    //     $response->getBody()->write('Hello world!');
    //     return $response;
    // });

    $app->group('/users', function (Group $group) use ($container) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->post('/auth', function (Request $request, Response $response, array $args) {
    	if (!$request->getHeaderLine('provider_id') && !$request->getHeaderLine('username') && !$request->getHeaderLine('password')) {
    		$data = array('error' => true, 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			return $response
					 ->withHeader('Content-Type', 'application/json');
    	} else if(!$request->getHeaderLine('provider_id')) {
    		$data = array('error' => true, 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			return $response
					 ->withHeader('Content-Type', 'application/json');
	 	} else if(!$request->getHeaderLine('username')) {
    		$data = array('error' => true, 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			return $response
					 ->withHeader('Content-Type', 'application/json');
	    } else if(!$request->getHeaderLine('password')) {
    		$data = array('error' => true, 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			return $response
					 ->withHeader('Content-Type', 'application/json');
    	}

	    $input = $request->getParsedBody();
	    $db = $this->get('db');
	    $header['provider_id'] = $request->getHeaderLine('provider_id');
	    $header['username'] = $request->getHeaderLine('username');
	    $header['password'] = $request->getHeaderLine('password');
	    $sql = "SELECT * FROM api_tbl_users WHERE provider_id = :provider_id AND username= :username limit 1";
	    $sth = $db->prepare($sql);
	    $sth->bindParam("provider_id", $header['provider_id']);
	    $sth->bindParam("username", $header['username']);
	    $sth->execute();
	    $user = $sth->fetchObject();
	    // verify email address.

	    if(!$user) {
	        $data = array('error' => true, 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			return $response
					 ->withHeader('Content-Type', 'application/json');
	    }
	    // verify password.
	    if (md5($header['password']) != $user->password) {
	    	$data = array('error' => true, 'message' => 'These credentials do not match our records.');
			$payload = json_encode($data);

			$response->getBody()->write($payload);
			return $response
					 ->withHeader('Content-Type', 'application/json');
	    }
	    $jwt = $this->get('jwt'); // get settings array.
    	
	    $token = JWT::encode(['id' => $user->id], $jwt['secret'], "HS256");
	    $data = array('status'=>true,'message'=>'Successfull','token' => $token);
		$payload = json_encode($data);

		$response->getBody()->write($payload);
		return $response
				 ->withHeader('Content-Type', 'application/json');
	});


    $app->group('/api', function (Group $group) use ($container) {
        $group->post('/HotelSearch',function(Request $request, Response $response, array $args) {
        	$input = $request->getAttribute('decoded_token_data');
        	$query = new QueryHandler($this->get('db'));

        	$validation = $query->validateparameters($request->getParsedBody());
        	if ($validation['status']!='true') {
        		$response->getBody()->write(json_encode($validation));
        		return $response;
        	} else {
				$data = $request->getParsedBody();
				$checkin_date=date_create($data['check_in']);
				$checkout_date=date_create($data['check_out']);
				$no_of_days=date_diff($checkin_date,$checkout_date);
				$list = $query->getHotelList($request->getParsedBody());
				if (count($list)!=0) {
					$data = array('status' => true, 'message' => 'Successfull','HotelResult'=>$list);
				} else {
					$data = array('status' => false, 'message' => 'Failed');
				}
				$response->getBody()->write(json_encode($data));
        		return $response;
        	}
	    });


	    $group->post('/AvailableHotelRooms',function(Request $request, Response $response, array $args) {
        	$input = $request->getAttribute('decoded_token_data');
        	print_r($input['id']);
        	exit();
        	$data = array($input);
			$payload = json_encode($data);

            $response->getBody()->write($payload);
        	return $response;
	    });

    });
};
