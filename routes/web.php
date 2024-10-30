<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\DriveController;
use App\Http\Controllers\SharedController;
use App\Http\Controllers\AccountController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect(route('login'));
});

Route::group([], function() {
    Auth::routes();
    Route::middleware('auth')->group(function() {
        Route::controller(HomeController::class)->group(function () {
            Route::prefix('home')->group(function() {
                Route::get('/', 'index')->name('home');
            });
        });

        Route::controller(FolderController::class)->group(function () {
            Route::prefix('folder')->group(function() {
                Route::post('/store', 'store')->name('folder.store');
                Route::post('/update', 'update')->name('folder.update');
                Route::get('/show/{id}', 'show')->name('folder.show');
                Route::get('/destroy/{id}', 'destroy')->name('folder.destroy');
            });
        });

        Route::controller(FilesController::class)->group(function () {
            Route::prefix('files')->group(function() {
                Route::post('/files/create','create')->name('files.create');
                Route::post('/store', 'store')->name('files.store');
                Route::post('/store/decrypt', 'decryptStore')->name('files.decrypt.store');
                Route::post('/update', 'update')->name('files.update');
                Route::get('/destroy/{id}', 'destroy')->name('files.destroy');
            });
        });

        Route::controller(DriveController::class)->group(function () {
            Route::prefix('drive')->group(function() {
                Route::get('/', 'index')->name('drive');
                Route::get('/show/{id}', 'show')->name('drive.show');
                Route::get('/download/{id}', 'download')->name('drive.download');
                Route::get('/destroy/{id}', 'destroy')->name('drive.destroy');
                Route::get('/display/{title}/{content}', 'display_pdf')->name('drive.pdf.display');
                Route::get('/sharedShow/{id}', 'sharedShow')->name('drive.sharedShow');
                Route::get('/edit/{id}', 'edit')->name('drive.edit');
                Route::post('/update/{id}', 'update')->name('drive.update');
            });
        });

        Route::controller(SharedController::class)->group(function () {
            Route::prefix('shared')->group(function() {
                Route::get('/', 'index')->name('shared');
                Route::post('/store', 'store')->name('shared.store');
            });
        });

        Route::controller(AccountController::class)->group(function () {
            Route::prefix('account')->group(function() {
                Route::get('/', 'index')->name('account');
                Route::get('/profile', 'profile')->name('account.profile');
                Route::post('/update/profile', 'update_profile')->name('account.profile.update');

                Route::post('/store', 'store')->name('account.store');
                Route::post('/update', 'update')->name('account.update');
                Route::get('/destroy/{id}', 'destroy')->name('account.destroy');
            });
        });
    });
});
