<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/bulk', 'BulkSmsController@index');
Route::get('/bulk-recode', 'BulkSmsController@index_recode');
Route::get('/bulk/loadtest', 'BulkSmsController@loadTest');
// Route::get('/bulk/asyncload','BulkSmsController@asyncLoad');
Route::get('/bulk/asyncload', 'BulkSmsController@asyncLoad');
Route::get('/bulk/asyncload/test', 'BulkSmsController@asyncLoadTest');
Route::get('/notification', 'BulkSmsController@smsNotification');
