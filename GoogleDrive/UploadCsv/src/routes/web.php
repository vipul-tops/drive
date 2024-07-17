<?php

use Illuminate\Support\Facades\Route;
use Googledrive\Uploadcsv\Controllers\GdriveApiController;

//Route::get('/helllo', [GdriveApiController::class, 'hello']);
//Route::get('/', [GdriveApiController::class, 'index']);

Route::get('/gindex', [GdriveApiController::class, 'googleIndex']);
Route::get('/refreshtoken', [GdriveApiController::class, 'refreshToken']);
Route::get('/handlecallback', [GdriveApiController::class, 'handlecallback']);
Route::get('/uploadlargefile', [GdriveApiController::class, 'uploadLargeFile']);
Route::get('/authenticatee', [GdriveApiController::class, 'authenticate']);
?>