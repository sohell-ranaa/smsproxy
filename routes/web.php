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

Route::get('/ip',function(){
    echo $_SERVER['SERVER_ADDR'].'<br>';
    echo $_SERVER['REMOTE_ADDR'];
});

Route::get('/bulk', function (){
    return 'Please use new url given';
});
Route::get('/bulk/dlr', 'BulkSmsController@dlrReport');
Route::get('/bulk/dlr/report/{number?}', 'BulkSmsController@dlrReportAll');
//Route::get('/bulk/client', 'BulkSmsController@dlrReportFromClient');
Route::get('/ekshop', 'BulkSmsController@ekShopSms');
//Route::get('/bulk/loadtest', 'BulkSmsController@loadTest');
//// Route::get('/bulk/asyncload','BulkSmsController@asyncLoad');
//Route::get('/bulk/asyncload', 'BulkSmsController@asyncLoad');
//Route::get('/bulk/asyncload/test', 'BulkSmsController@asyncLoadTest');
//Route::get('/notification', 'BulkSmsController@smsNotification');
