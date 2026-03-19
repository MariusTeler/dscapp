<?php
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiBasicAuth;
use App\Http\Middleware\ApiThrottle;
use App\Http\Controllers\GetController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;

Route::middleware([ApiThrottle::class])->group(function () {
    //prod
    Route::get('/', function (Request $request) {
        return 'Dragon Star Curier SRL';
    });

    Route::post('/', function (Request $request) {
        return 'Dragon Star Curier SRL';
    });
    //test
    Route::prefix('test')->group(function () {
        Route::get('/', function (Request $request) {
            return 'Dragon Star Curier SRL';
        });
        Route::post('/', function (Request $request) {
            return 'Dragon Star Curier SRL';
        });
    });
});


Route::middleware([ApiBasicAuth::class, ApiThrottle::class])->group(function () {
    Route::get('judete', [GetController::class, 'getJudete']);
    Route::get('localitati/{judet}', [GetController::class, 'getLocalitati'])->where('judet', '[A-Za-z]{2}');
    Route::get('localitatikm/{judet}', [GetController::class, 'getLocalitatiKm'])->where('judet', '[A-Za-z]{2}');
    Route::prefix('awb')->group(function () {
        Route::get('status/{awb}', [GetController::class, 'trackAWB'])->whereNumber('awb');
        Route::get('history/{awb}', [GetController::class, 'historyAWB'])->whereNumber('awb');
        Route::get('print/{awb}/{tip?}', [GetController::class, 'printAWB'])->whereNumber('awb')->where('tip', 'A4|A6');
        Route::get('zpl/{awb}/{nb}', [GetController::class, 'printZPL'])->whereNumber('awb')->whereNumber('nb');
        Route::post('cost', [PostController::class, 'getCostAWB']);
        Route::post('costForPc', [PostController::class, 'getCostAwbPc']);
        Route::post('send', [PostController::class, 'sendAWB']);
        Route::post('sendForPc', [PostController::class, 'sendAwbPc']);
        Route::delete('{awb}', [PostController::class, 'deleteAWB'])->whereNumber('awb');
    });
    Route::get('retur/status/{awb}', [GetController::class, 'trackAWBRetur'])->whereNumber('awb');
    Route::get('borderou/print/{borderou}', [GetController::class, 'printBorderou'])->whereNumber('borderou');
    Route::get('master/pcs', [GetController::class, 'getPcs']);
    Route::post('borderou/make', [PostController::class, 'makeBorderou']);
    Route::post('pickup/send', [PostController::class, 'sendComanda']);
    Route::post('pickup/sendForPc', [PostController::class, 'sendComandaPc']);
    

    Route::prefix('test')->group(function () {
        Route::get('judete', [GetController::class, 'getJudete']);
        Route::get('localitati/{judet}', [GetController::class, 'getLocalitati'])->where('judet', '[A-Za-z]{2}');
        Route::get('localitatikm/{judet}', [GetController::class, 'getLocalitatiKm'])->where('judet', '[A-Za-z]{2}');
        Route::prefix('awb')->group(function () {
            Route::get('status/{awb}', [GetController::class, 'trackAWB'])->whereNumber('awb');
            Route::get('print/{awb}', [GetController::class, 'printAWB'])->whereNumber('awb');
            Route::get('zpl/{awb}/{nb}', [GetController::class, 'printZPL'])->whereNumber('awb')->whereNumber('nb');
            Route::post('cost', [PostController::class, 'getCostAWB']);
            Route::post('costForPc', [PostController::class, 'getCostAwbPc']);
            Route::post('send', [PostController::class, 'sendAWB']);
            Route::post('sendForPc', [PostController::class, 'sendAwbPc']);
            Route::delete('{awb}', [PostController::class, 'deleteAWB'])->whereNumber('awb');
        });
        Route::get('borderou/print/{borderou}', [GetController::class, 'printBorderou'])->whereNumber('borderou');
        Route::get('master/pcs', [GetController::class, 'getPcs']);
        Route::post('borderou/make', [PostController::class, 'makeBorderou']);
        
    });

    Route::any('{any}', function () {
        return response()->json([
            'error_type' => '999',
            'message' => 'Resource not found'
        ], 404);
    })->where('any', '.*');
});

Route::any('{any}', function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Resource not found'
    ], 404);
})->where('any', '.*');