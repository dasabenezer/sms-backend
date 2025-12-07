<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'School Management System Web',
        'version' => '1.0',
        'status' => 'running'
    ]);
});
