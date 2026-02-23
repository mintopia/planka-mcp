<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlankaWebhookController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/health', fn () => response('OK'))->name('health');
Route::post('/planka-webhook', PlankaWebhookController::class)->name('planka-webhook');
Route::post('/test/tool', [TestController::class, 'callTool'])->middleware('throttle:60,1')->name('test.tool');
Route::post('/test/resource', [TestController::class, 'readResource'])->middleware('throttle:60,1')->name('test.resource');
