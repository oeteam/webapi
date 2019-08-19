<?php
namespace App\Controllers;
use Illuminate\Database\Query\Builder;
class searchController{
    public function index(){
        echo "This is from message from searchController";
    }
    public function home($request, $response, $args) {
      // your code here
      // use $this->view to render the HTML
    	print_r($_GET['name']);
    	exit();
      return $response;
    }
}