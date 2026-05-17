<?php

namespace App\Http\Controllers;


class HomeController extends Controller
{
    /**
     * Display the homepage with pricing plans.
     */
    public function index()
    {
        return view('home');
    }
}