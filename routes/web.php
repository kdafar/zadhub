<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

Route::get('/', function (Request $request) {
    $qs = $request->getQueryString();
    return redirect('/en' . ($qs ? ('?' . $qs) : ''), 302);
});
