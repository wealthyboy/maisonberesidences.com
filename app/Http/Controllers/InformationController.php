<?php

namespace App\Http\Controllers;

use App\Models\Information;
use Illuminate\View\View;

class InformationController extends Controller
{
    public function show(Information $information): View
    {
        return view('information.show', compact('information'));
    }
}
