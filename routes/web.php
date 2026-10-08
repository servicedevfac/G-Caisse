<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CashController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\DocumentController;
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/connexion', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/mot-de-passe-oublie', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
});
Route::get('/reinitialiser-mot-de-passe/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
Route::post('/reinitialiser-mot-de-passe', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.update');
Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/invitation/{token}', [InvitationController::class, 'store'])->name('invitation.store');
Route::middleware(['auth', 'active', 'audit'])->group(function () {
    Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', [CashController::class, 'index'])->name('dashboard');
    Route::get('/mouvements/entrees', [CashController::class, 'entries'])->name('entries.index');
    Route::get('/mouvements/sorties', [CashController::class, 'expenses'])->name('expenses.index');
    Route::get('/mouvements/historique', [CashController::class, 'history'])->name('history.index');
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/telecharger', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    Route::post('/operations', [CashController::class, 'store'])->name('transactions.store');
    Route::patch('/operations/{transaction}/signer', [CashController::class, 'sign'])->name('transactions.sign');
    Route::post('/operations/{transaction}/annuler', [CashController::class, 'cancel'])->name('transactions.cancel');
    Route::get('/operations/{transaction}/recu', [CashController::class, 'receipt'])->name('transactions.receipt');
    Route::get('/operations/{transaction}/document', [CashController::class, 'attachment'])->name('transactions.attachment');
    Route::get('/rapports/{format}', [CashController::class, 'export'])->name('reports.export');
    Route::middleware('admin')->prefix('administration')->name('admin.')->group(function () {
        Route::get('/utilisateurs', [UserController::class, 'index'])->name('users.index');
        Route::post('/utilisateurs', [UserController::class, 'store'])->name('users.store');
        Route::post('/utilisateurs/{user}/invitation', [UserController::class, 'resendInvitation'])->name('users.invitation');
        Route::patch('/utilisateurs/{user}/statut', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::get('/journal-activite', [ActivityController::class, 'index'])->name('activities.index');
    });
});
