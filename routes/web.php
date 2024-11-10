<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    HomeController,
    FilesController,
    FolderController,
    DriveController,
    SharedController,
    AccountController,
    SubfolderController,
    UsersFolderShareableController
};
use Illuminate\Support\Facades\Auth;

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

Route::get('/', fn() => redirect(route('login')));

// Auth Routes
Auth::routes();

// Authenticated Routes
Route::middleware('auth')->group(function () {

    // Home Routes
    Route::prefix('home')->controller(HomeController::class)->group(function () {
        Route::get('/', 'index')->name('home');
    });

    // Folder Routes
    Route::prefix('folder')->controller(FolderController::class)->group(function () {
        Route::post('/store', 'store')->name('folder.store');
        Route::post('/update', 'update')->name('folder.update');
        Route::get('/show/{id}', 'show')->name('folder.show');
        Route::delete('/destroy/{id}', 'destroy')->name('folder.destroy');
        Route::get('/download/{id}', 'download')->name('folder.download');
    });

    // Files Routes
    Route::prefix('files')->controller(FilesController::class)->group(function () {
        Route::post('/create', 'create')->name('files.create');
        Route::post('/store', 'store')->name('files.store');
        Route::post('/store/decrypt', 'decryptStore')->name('files.decrypt.store');
        Route::get('/file/download/{id}', [FilesController::class, 'download'])->name('file.download');
        Route::post('/rename', 'rename')->name('files.rename');
        Route::get('/destroy/{id}', 'destroy')->name('files.destroy');
        Route::get('/details/{id}', 'showFileDetails')->name('files.details'); // Adjusted URL and fixed controller name
    });

    // Drive Routes
    Route::prefix('drive')->controller(DriveController::class)->group(function () {
        Route::get('/', 'index')->name('drive');
        Route::get('/show/{id}', 'show')->name('drive.show');
        Route::get('/download/{id}', 'download')->name('drive.download');
        Route::delete('/destroy/{id}', 'destroy')->name('drive.destroy');
        Route::get('/display/{title}/{content}', 'display_pdf')->name('drive.pdf.display');
        Route::get('/sharedShow/{id}', 'sharedShow')->name('drive.sharedShow');
        Route::get('/edit/{id}', 'edit')->name('drive.edit');
        Route::post('/update/{id}', 'update')->name('drive.update');
        Route::post('/rename/{id}', 'rename')->name('drive.rename');
        Route::get('/get-folders', 'getFolders')->name('folders.list');
        Route::post('/move/{fileId}/{destinationFolderId}', 'move')->name('drive.move');
        Route::post('/copy/{fileId}', 'copy')->name('drive.copy');
        Route::post('/paste/{destinationFolderId}', 'paste')->name('drive.paste');
    });


    // Shared Routes
    Route::prefix('shared')->controller(SharedController::class)->group(function () {
        Route::get('/', 'index')->name('shared');
        Route::post('/store', 'store')->name('shared.store');
        Route::delete('/{id}', 'destroy')->name('shared.destroy');
        Route::post('/{id}', 'update')->name('shared.update');
        Route::get('/{id}/edit', 'edit')->name('shared.edit');
        Route::post('/{id}/download', 'download')->name('shared.download');
        Route::post('/move/{fileId}/{destinationFolderId}', 'move')->name('shared.move');
        Route::get('/folders', 'getSharedFolders')->name('shared.getSharedFolders');
        Route::post('/paste/{destinationFolderId}', 'paste')->name('shared.paste');
    });

    // Account Routes
    Route::prefix('account')->controller(AccountController::class)->group(function () {
        Route::get('/', 'index')->name('account');
        Route::get('/profile', 'profile')->name('account.profile');
        Route::post('/update/profile', 'update_profile')->name('account.profile.update');
        Route::post('/store', 'store')->name('account.store');
        Route::post('/update', 'update')->name('account.update');
        Route::get('/destroy/{id}', 'destroy')->name('account.destroy');
    });

    // Subfolder Routes
    Route::prefix('subfolder')->controller(SubfolderController::class)->group(function () {
        Route::post('/store', 'store')->name('subfolder.store');
        Route::post('/update', 'update')->name('subfolder.update');
        Route::get('/show/{id}', 'show')->name('subfolder.show');
        Route::delete('/destroy/{id}', 'destroy')->name('subfolder.destroy');
    });

    Route::prefix('folder/shareable')->controller(UsersFolderShareableController::class)->group(function () {
        Route::post('/store', 'store')->name('folder.shareable.store');
        Route::post('/add-file/{folderShareableId}', 'addShareableFile')->name('folder.shareable.addFile');
        Route::get('/view', 'viewSharedFolders')->name('folder.shareable.view');
        Route::get('/show/{id}', 'show')->name('folder.shareable.show');
        Route::post('/update', 'update')->name('folder.shareable.update');
        Route::delete('/destroy/{id}', 'destroy')->name('folder.shareable.destroy');
        Route::get('/shared', 'index')->name('folder.shareable.index');
        Route::post('/share', 'createSharedFolder')->name('folder.shareable.share');
    });
});
