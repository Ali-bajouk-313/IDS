<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);



Route::middleware('auth:api')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

});



Route::middleware(['auth:api', 'role:Admin'])->group(function () {

    Route::get('/admin/dashboard', function () {

        return response()->json([
            'message' => 'Welcome Admin',
            'access' => 'Full system access'
        ]);

    });

});



Route::middleware(['auth:api','role:Employee'])->group(function () {

    Route::get('/employee/dashboard', function () {

        return response()->json([
            'message' => 'Welcome Employee'
        ]);

    });

});

Route::middleware(['auth:api','role:IT Support'])->group(function () {

    Route::get('/support/dashboard', function () {

        return response()->json([
            'message' => 'Welcome IT Support',
            'access' => [
                'View assigned tickets',
                'Update ticket status',
                'Add comments',
                'Resolve tickets'
            ]
        ]);

    });

});


Route::middleware(['auth:api','role:Manager'])->group(function () {

    Route::get('/manager/dashboard', function () {

        return response()->json([
            'message' => 'Welcome Manager',
            'access' => [
                'View reports',
                'Monitor team performance',
                'View ticket statistics'
            ]
        ]);

    });

});