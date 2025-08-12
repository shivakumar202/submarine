<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ActivityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = $request->user();
    $user->load('roles', 'permissions'); // eager load relationships

    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'role' => $user->getRoleNames()->first(), // single string role
        'permissions' => $user->getAllPermissions()->pluck('name'), // array of permissions
    ]);

});


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Activities CRUD (protected)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/activities', [ActivityController::class, 'index']);
    Route::post('/activities', [ActivityController::class, 'store']);
    Route::get('/activities/{activity}', [ActivityController::class, 'show']);
    Route::put('/activities/{activity}', [ActivityController::class, 'update']);
    Route::delete('/activities/{activity}', [ActivityController::class, 'destroy']);
    Route::post('/activities/{activity}/toggle', [ActivityController::class, 'toggle']);
});