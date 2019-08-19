<?php
namespace App\Controllers;
class loginController{
    public function index(){
        echo "This is from message from forumController";
    }
    public function home($request, $response, $args) {
      // your code here
      // use $this->view to render the HTML
    	print_r($request);
    	exit();
      return $response;
    }
}