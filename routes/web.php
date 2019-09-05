<?php

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

// Carga de clases

use App\Http\Middleware\ApiAuthMiddleware;

Route::get('/', function () {
    return view('welcome');
});
// Rutas de prueba
/*
Route::get('/test-orm', 'PruebasController@testOrm');
Route::get('/usuario/pruebas', 'UserController@pruebas');
Route::get('/categoria/pruebas', 'CategoryController@pruebas');
Route::get('/entrada/pruebas', 'PostController@pruebas');
*/

// Rutas de la API

    // Rutas de UserController
    Route::post('/api/register', 'UserController@register');
    Route::post('/api/login', 'UserController@login');
    Route::put('/api/user/update', 'UserController@update');
    Route::post('/api/user/upload', 'UserController@upload')->middleware(ApiAuthMiddleware::class);
    Route::get('/api/user/avatar/{filename}', 'UserController@getImage');
    Route::get('/api/user/profile/{id}', 'UserController@details');

    // Rutas de CategoryController
    Route::resource('/api/category', 'CategoryController');

    // Rutas de PostController
    Route::resource('/api/post', 'PostController');
    Route::post('/api/post/upload', 'PostController@upload');
    Route::get('/api/post/image/{filename}', 'PostController@getImage');
    Route::get('/api/post/category/{id}', 'PostController@getByCategory');
    Route::get('/api/post/user/{id}', 'PostController@getByUser');