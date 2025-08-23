<?php

use App\Http\Controllers\Api\WhatsAppWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::match(['GET','POST'], '/whatsapp/webhook/appB', [WhatsAppWebhookController::class, 'handle'])->defaults('appKey','appB');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Services\WhatsAppMessageHandler;

Route::post('/wa/test-webhook', function (Request $req, WhatsAppMessageHandler $h) {
    $h->process($req->all());

    return response()->json(['ok' => true]);
});

Route::get('/wa/test-webhook', fn () => response('ok', 200));
