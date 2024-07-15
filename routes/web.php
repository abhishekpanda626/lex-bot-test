<?php

use App\Filament\Resources\ChatResource\Pages\ChatPage;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\MockConversationController;
use App\Services\LexService;
use AWS\CRT\HTTP\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/webhook/appointment', [AppointmentController::class, 'handleWebhook']);
Route::get('/mock-conversation', [MockConversationController::class, 'handleConversation']);

Route::post('/chat-page/submit', [ChatPage::class, 'submit'])->name('filament.pages.chat-page.submit');