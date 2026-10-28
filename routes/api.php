<?php

use App\Http\Controllers\API\AVIAEomborController;
use App\Http\Controllers\API\BelarusBenyakoniController;
use App\Http\Controllers\API\BelarusBrestController;
use App\Http\Controllers\API\BelarusGigorovschinaController;
use App\Http\Controllers\API\BelarusKomenniController;
use App\Http\Controllers\API\BelarusKozlovichiController;
use App\Http\Controllers\API\EOmborController;
use App\Http\Controllers\API\MintransController;
use App\Http\Controllers\API\QozoqController;
use App\Http\Controllers\API\RWEomborController;
use App\Http\Controllers\API\TurkeyController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\CheckedQozoqController;
use App\Http\Controllers\API\ImportQozoqController;
use App\Http\Controllers\API\ImportEksportQozoqContoller;
use App\Http\Controllers\API\UzbekistanImportController;
use App\Http\Controllers\API\UzbekistanExporterContactController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\TestController;
use App\Http\Controllers\API\DriverController;


Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Illuminate\Http\Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/uploads', [DashboardController::class, 'uploads']);
    Route::put('/user/update', [AuthController::class, 'updateProfile']);

    Route::post('/avia-eombor/import', [AVIAEomborController::class, 'import'])->middleware('log.upload:eombor-avia');
    Route::get('/avia-e-ombor', [AVIAEomborController::class, 'index']);
    Route::get('/avia-e-ombor/export', [AVIAEomborController::class, 'export']);

    Route::post('/rw-e-ombor/import', [RWEomborController::class, 'import'])->middleware('log.upload:eombor-rw');
    Route::get('/rw-e-ombor', [RWEomborController::class, 'index']);
    Route::get('/rw-e-ombor/export', [RwEOmborController::class, 'export']);

    Route::post('/at-e-ombor/import', [EomborController::class, 'import'])->middleware('log.upload:eombor-at');
    Route::get('/at-e-ombor', [EomborController::class, 'index']);
    Route::get('/at-e-ombor/export', [EomborController::class, 'export']);

    Route::post('/belarus-benyakoni/import', [BelarusBenyakoniController::class, 'import'])->middleware('log.upload:belarus-benyakoni');
    Route::get('/belarus-benyakoni', [BelarusBenyakoniController::class, 'index']);
    Route::get('/belarus-benyakoni/export', [BelarusBenyakoniController::class, 'export']);

    Route::post('/belarus-brest/import', [BelarusBrestController::class, 'import'])->middleware('log.upload:belarus-brest');
    Route::get('/belarus-brest', [BelarusBrestController::class, 'index']);
    Route::get('/belarus-brest/export', [BelarusBrestController::class, 'export']);

    Route::post('/belarus-gigorovschina/import', [BelarusGigorovschinaController::class, 'import'])->middleware('log.upload:belarus-gigorovschina');
    Route::get('/belarus-gigorovschina', [BelarusGigorovschinaController::class, 'index']);
    Route::get('/belarus-gigorovschina/export', [BelarusGigorovschinaController::class, 'export']);

    Route::post('/belarus-komennii/import', [BelarusKomenniController::class, 'import'])->middleware('log.upload:belarus-kamenny-log');
    Route::get('/belarus-komennii', [BelarusKomenniController::class, 'index']);
    Route::get('/belarus-komennii/export', [BelarusKomenniController::class, 'export']);

    Route::post('/belarus-kozlovichi/import', [BelarusKozlovichiController::class, 'import'])->middleware('log.upload:belarus-kozlovichi');
    Route::get('/belarus-kozlovichi', [BelarusKozlovichiController::class, 'index']);
    Route::get('/belarus-kozlovichi/export', [BelarusKozlovichiController::class, 'export']);

    Route::post('/turkey/import', [TurkeyController::class, 'import'])->middleware('log.upload:turkey');
    Route::get('/turkey', [TurkeyController::class, 'index']);
    Route::get('/turkey/export', [TurkeyController::class, 'export']);

    Route::post('/qozoq/import', [QozoqController::class, 'import'])->middleware('log.upload:qozoq');
    Route::get('/qozoq', [QozoqController::class, 'index']);
    Route::get('/qozoq/export', [QozoqController::class, 'export']);

     Route::post('/import-qozoq/import', [ImportQozoqController::class, 'import'])->middleware('log.upload:import-qozoq');
    Route::get('/import-qozoq', [ImportQozoqController::class, 'index']);
    Route::get('/import-qozoq/export', [ImportQozoqController::class, 'export']);

    Route::post('/import-eksport-qozoq/import', [ImportEksportQozoqContoller::class, 'import'])->middleware('log.upload:import-eksport-qozoq');
    Route::get('/import-eksport-qozoq', [ImportEksportQozoqContoller::class, 'index']);
    Route::get('/import-eksport-qozoq/export', [ImportEksportQozoqContoller::class, 'export']);

    // O'zbekiston savdo ma'lumotlari: import va eksport bir xil endpoint'lar bilan
    foreach (['import', 'export'] as $dataset) {
        Route::prefix("/uzbekistan-$dataset")->group(function () use ($dataset) {
            Route::get('/', [UzbekistanImportController::class, 'index'])->defaults('dataset', $dataset);
            Route::get('/columns', [UzbekistanImportController::class, 'columns'])->defaults('dataset', $dataset);
            Route::get('/filter-options', [UzbekistanImportController::class, 'filterOptions'])->defaults('dataset', $dataset);
            Route::get('/firms', [UzbekistanImportController::class, 'firms'])->defaults('dataset', $dataset);
            Route::post('/export', [UzbekistanImportController::class, 'export'])->defaults('dataset', $dataset);
            Route::get('/zanjeer/today', [UzbekistanImportController::class, 'zanjeerToday'])->defaults('dataset', $dataset);
        });
    }
    // fayl yuklash (turi avtomatik aniqlanadi) va vazifalar — ikkala yo'nalish uchun umumiy
    Route::post('/uzbekistan-import/import', [UzbekistanImportController::class, 'import']);
    Route::get('/uzbekistan-firms/summary', [UzbekistanImportController::class, 'firmsSummary']);
    Route::get('/uzbekistan-import/tasks', [UzbekistanImportController::class, 'tasks']);
    Route::get('/uzbekistan-import/tasks/{task}', [UzbekistanImportController::class, 'task']);
    Route::get('/uzbekistan-import/tasks/{task}/download', [UzbekistanImportController::class, 'download']);

    // eksportyor firmalarga xodim biriktirish
    Route::get('/uzbekistan-export/exporters', [UzbekistanExporterContactController::class, 'index']);
    Route::put('/uzbekistan-export/exporters/{inn}/contact', [UzbekistanExporterContactController::class, 'save'])->where('inn', '[0-9A-Za-z]+');
    Route::delete('/uzbekistan-export/exporters/{inn}/contact', [UzbekistanExporterContactController::class, 'destroy'])->where('inn', '[0-9A-Za-z]+');
    Route::put('/uzbekistan-export/exporters/{inn}/declarant', [UzbekistanExporterContactController::class, 'saveDeclarant'])->where('inn', '[0-9A-Za-z]+');
    Route::delete('/uzbekistan-export/exporters/{inn}/declarant', [UzbekistanExporterContactController::class, 'destroyDeclarant'])->where('inn', '[0-9A-Za-z]+');

    Route::post('/checked-qozoq/import', [CheckedQozoqController::class, 'import'])->middleware('log.upload:checked-qozoq');
    Route::get('/checked-qozoq', [CheckedQozoqController::class, 'index']);
    Route::get('/checked-qozoq/export', [CheckedQozoqController::class, 'export']);

    Route::post('/mintrans/import', [MintransController::class, 'import'])->middleware('log.upload:mintrans');
    Route::get('/mintrans', [MintransController::class, 'index']);
    Route::get('/mintrans/export', [MintransController::class, 'export']);

    Route::get('/drivers', [DriverController::class, 'index']);
    Route::post('/import-drivers', [DriverController::class, 'importDrivers'])->middleware('log.upload:drivers');
    Route::get('/export-drivers', [DriverController::class, 'export']);

    Route::get('/test', [TestController::class, 'index']);


});



