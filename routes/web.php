<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'PPA WAR API Server', 'status' => 'running']);
});
