<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CashController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Admin\UserController;
Route::middleware('guest')->group(function () {
    Route::get('/connexion', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/connexion', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/inscription', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/inscription', [RegisteredUserController::class, 'store'])->name('register.store');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/deconnexion', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', [CashController::class, 'index'])->name('dashboard');
    Route::get('/mouvements/entrees', [CashController::class, 'entries'])->name('entries.index');
    Route::get('/mouvements/sorties', [CashController::class, 'expenses'])->name('expenses.index');
    Route::get('/mouvements/historique', [CashController::class, 'history'])->name('history.index');
    Route::post('/operations', [CashController::class, 'store'])->name('transactions.store');
    Route::post('/operations/{transaction}/annuler', [CashController::class, 'cancel'])->name('transactions.cancel');
    Route::get('/operations/{transaction}/recu', [CashController::class, 'receipt'])->name('transactions.receipt');
    Route::get('/rapports/{format}', [CashController::class, 'export'])->name('reports.export');
    Route::middleware('admin')->prefix('administration')->name('admin.')->group(function () {
        Route::get('/utilisateurs', [UserController::class, 'index'])->name('users.index');
        Route::patch('/utilisateurs/{user}/statut', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    });
});
