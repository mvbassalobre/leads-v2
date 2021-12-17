<?php

use App\Http\Controllers\WhatsappController;

Route::group(["prefix" => "whatsapp"], function () {
    Route::get('', [WhatsappController::class, 'index']);
});
