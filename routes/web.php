<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutoController;


Route::get('/', [HomeController::class, 'login'])->name('login');

// Route::middleware('token.check')->group(function () {
//     Route::get('/dashboard', [HomeController::class, 'dashboard']);
//     Route::get('/profile', [HomeController::class, 'profile']);


//     Route::get('/turkey-scraping', [HomeController::class, 'turkeyScraping'])->name('turkeyScraping');
//     Route::get('/belarus-scraping', [HomeController::class, 'belarusScraping'])->name('belarusScraping');
//     Route::get('/qozoq-scraping', [HomeController::class, 'qozoqScraping'])->name('qozoqScraping');
//     Route::get('/mintrans-scraping', [HomeController::class, 'mintransScraping'])->name('mintransScraping');


//     Route::get('/e-ombor-AT-scraping', [HomeController::class, 'eOmborATScraping'])->name('eOmborATScraping');
//     Route::get('/e-ombor-RW-scraping', [HomeController::class, 'eOmborRWScraping'])->name('eOmborRWScraping');
//     Route::get('/e-ombor-AVIA-scraping', [HomeController::class, 'eOmborAVIAScraping'])->name('eOmborAVIAScraping');



//     Route::get('/belarus-benyakoni', [HomeController::class, 'belarusBenyakoni'])->name('belarusBenyakoni');
//     Route::get('/belarus-brest', [HomeController::class, 'belarusBrest'])->name('belarusBrest');
//     Route::get('/belarus-gigorovschina', [HomeController::class, 'belarusGigorovschina'])->name('belarusGigorovschina');
//     Route::get('/belarus-keminnii-log', [HomeController::class, 'belarusKeminnii'])->name('belarusKeminnii');
//     Route::get('/belarus-kozlovichi', [HomeController::class, 'belarusKozlovichi'])->name('belarusKozlovichi');

// });

  Route::get('/dashboard', [HomeController::class, 'dashboard']);
    Route::get('/profile', [HomeController::class, 'profile']);


    Route::get('/turkey-scraping', [HomeController::class, 'turkeyScraping'])->name('turkeyScraping');
    Route::get('/belarus-scraping', [HomeController::class, 'belarusScraping'])->name('belarusScraping');

    Route::get('/qozoq-scraping', [HomeController::class, 'qozoqScraping'])->name('qozoqScraping');
    Route::get('/checked-qozoq-scraping', [HomeController::class, 'checkedQozoqScraping'])->name('checkedQozoqScraping');
    Route::get('/import-qozoq-scraping', [HomeController::class, 'importQozoqScraping'])->name('importQozoqScraping');
    Route::get('/import-eksport-qozoq-scraping', [HomeController::class, 'importEksportQozoqScraping'])->name('importEksportQozoqScraping');

    Route::get('/mintrans-scraping', [HomeController::class, 'mintransScraping'])->name('mintransScraping');


    Route::get('/e-ombor-AT-scraping', [HomeController::class, 'eOmborATScraping'])->name('eOmborATScraping');
    Route::get('/e-ombor-RW-scraping', [HomeController::class, 'eOmborRWScraping'])->name('eOmborRWScraping');
    Route::get('/e-ombor-AVIA-scraping', [HomeController::class, 'eOmborAVIAScraping'])->name('eOmborAVIAScraping');



    Route::get('/belarus-benyakoni', [HomeController::class, 'belarusBenyakoni'])->name('belarusBenyakoni');
    Route::get('/belarus-brest', [HomeController::class, 'belarusBrest'])->name('belarusBrest');
    Route::get('/belarus-gigorovschina', [HomeController::class, 'belarusGigorovschina'])->name('belarusGigorovschina');
    Route::get('/belarus-keminnii-log', [HomeController::class, 'belarusKeminnii'])->name('belarusKeminnii');
    Route::get('/belarus-kozlovichi', [HomeController::class, 'belarusKozlovichi'])->name('belarusKozlovichi');

    Route::get('/drivers', [HomeController::class, 'drivers'])->name('drivers');

    Route::get('/uzbekistan-import', [HomeController::class, 'uzbekistanImport'])->name('uzbekistanImport');
    Route::get('/uzbekistan-export', [HomeController::class, 'uzbekistanExport'])->name('uzbekistanExport');

    Route::get('/autos-export', [AutoController::class, 'export']);
