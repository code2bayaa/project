<?php

use Modules\Finance\Http\Controllers\FinanceController;

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

Route::prefix('applications')->group(function() {
//    Route::get('/', 'FinanceController@index');
    Route::group(['middleware' => 'finance'], function (){
        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.dashboard');
        Route::get('/finance/applications', [FinanceController::class, 'applications'])->name('finance.applications');
        Route::get('/finance/viewApplication/{id}', [FinanceController::class, 'viewApplication'])->name('finance.viewApplication');
        Route::get('/finance/previewApplication/{id}', [FinanceController::class, 'previewApplication'])->name('finance.previewApplication');
        Route::get('/finance/batch', [FinanceController::class, 'batch'])->name('finance.batch');
        Route::post('/finance/batchSubmit', [FinanceController::class, 'batchSubmit'])->name('finance.batchSubmit');
        Route::get('/finance/acceptApplication/{id}', [FinanceController::class, 'acceptApplication'])->name('finance.acceptApplication');
        Route::post('/finance/rejectApplication/{id}', [FinanceController::class, 'rejectApplication'])->name('finance.rejectApplication');


        Route::get('/admission', [FinanceController::class, 'admissions'])->name('finance.admissions');
        Route::get('/admissionJab', [FinanceController::class, 'admissionsJab'])->name('finance.admissionsJab');
        Route::get('/review{id}', [FinanceController::class, 'reviewAdmission'])->name('finance.reviewAdmission');
        Route::get('/accept{id}', [FinanceController::class, 'acceptAdmission'])->name('finance.acceptAdmission');
        Route::get('/acceptJab/{id}', [FinanceController::class, 'acceptAdmissionJab'])->name('finance.acceptAdmJab');
        Route::post('/reject{id}', [FinanceController::class, 'rejectAdmission'])->name('finance.rejectAdmission');
        Route::get('/submit{id}', [FinanceController::class, 'submitAdmission'])->name('finance.submitAdmission');
        Route::get('/submitJab/{id}', [FinanceController::class, 'submitAdmissionJab'])->name('finance.submitAdmJab');
    });
});
