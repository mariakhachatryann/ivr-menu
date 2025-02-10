<?php

use App\Http\Controllers\IVRController;
use Illuminate\Support\Facades\Route;

Route::post('/ivr', [IVRController::class, 'handleIVR']);
