<?php namespace App\Controllers;

use App\Models\User;

class IndexController extends Controller {

    function index() {
    }

    function users() {
        $users = User::all();
        print_r($users);
        exit();
        return view('users', compact('users'));
    }

}