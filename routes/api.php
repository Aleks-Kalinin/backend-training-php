<?php

use App\Http\Controllers\Api\V1\PingController;
use Illuminate\Support\Facades\Route;

// Every route in this file is prefixed with /api/v1 (see bootstrap/app.php).

Route::get('/ping', PingController::class);
