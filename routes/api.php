<?php

use App\Http\Controllers\Api\ApiController;
use Illuminate\Support\Facades\Route;

Route::post('/orientation', [ApiController::class, 'orientation']);          // F1/F2
Route::get('/structures', [ApiController::class, 'structures']);             // F3
Route::get('/medecin/{medecin}/creneaux', [ApiController::class, 'creneaux']); // F4

Route::middleware('auth:sanctum')->get('/me', fn () => request()->user());   // exemple protégé
