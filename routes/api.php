<?php

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\DespatchController;
use App\Http\Controllers\Api\RegisterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('register',[RegisterController::class, 'store']);

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout']);
Route::post('refresh', [AuthController::class, 'refresh']);
Route::post('me', [AuthController::class, 'me']);

Route::apiResource('companies', CompanyController::class)->middleware('auth:api');//se pone auth:api  porque por defecto es el guard web

Route::post('invoices/send',[InvoiceController::class, 'send'])->middleware('auth:api');//poner el middleware para retornar el usurio autentificado por token

Route::post('invoices/xml', [InvoiceController::class, 'xml'])->middleware('auth:api');

Route::post('invoices/pdf', [InvoiceController::class, 'pdf'])->middleware('auth:api');



//Notes
Route::post('notes/send', [NoteController::class, 'send'])->middleware('auth:api');
Route::post('notes/xml', [NoteController::class, 'xml'])->middleware('auth:api');
Route::post('notes/pdf', [NoteController::class, 'pdf'])->middleware('auth:api');



//Despatches
Route::post('despatches/send', [DespatchController::class, 'send'])->middleware('auth:api');
Route::post('despatches/xml', [DespatchController::class, 'xml'])->middleware('auth:api');
Route::post('despatches/pdf', [DespatchController::class, 'pdf'])->middleware('auth:api');


Route::get('prueba', function(){
    $company = Company::first();
    //return $company;
    return Storage::get($company->logo_path);

});
