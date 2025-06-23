<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SigningController; // <-- تأكد من إضافة هذا السطر
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\TemplateController;


// --- الروابط العامة ---
Route::get('/', function () {
    return view('welcome');
});

Route::post('/folders', [FolderController::class, 'store'])->name('folders.store');
Route::post('/documents/{document}/assign-folder', [DocumentController::class, 'assignFolder'])->name('documents.assignFolder');

// --- روابط التوقيع العامة (لا تتطلب تسجيل دخول) ---
Route::get('/sign/{token}', [SigningController::class, 'show'])->name('sign.page');
Route::post('/sign/{token}', [SigningController::class, 'store'])->name('sign.store');


// --- الروابط التي تتطلب تسجيل دخول ---
Route::middleware(['auth', 'verified'])->group(function () {
    // لوحة التحكم
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // الملف الشخصي
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // المستندات
    Route::resource('documents', DocumentController::class)->except(['index']);
    Route::post('/documents/{document}/participants', [DocumentController::class, 'addParticipant'])->name('documents.participants.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::patch('/documents/{document}/fields', [DocumentController::class, 'saveFields'])->name('documents.fields.update');
    Route::post('/documents/{document}/send', [DocumentController::class, 'send'])->name('documents.send');
    Route::resource('templates', TemplateController::class);
    // لعرض صفحة إسناد الموقعين للقالب
    Route::get('/templates/{template}/use', [App\Http\Controllers\TemplateController::class, 'showUseForm'])->name('templates.use');
    // لمعالجة النموذج وإنشاء مستند جديد من القالب
    Route::post('/templates/{template}/use', [App\Http\Controllers\TemplateController::class, 'createDocumentFromTemplate'])->name('templates.create_document');

});


require __DIR__.'/auth.php';