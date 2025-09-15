<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// Autenticação
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Registro
Route::get('/register/individual', [RegisterController::class, 'showIndividualRegistrationForm'])->name('register.individual');
Route::post('/register/individual', [RegisterController::class, 'registerIndividual']);
Route::get('/register/corporate', [RegisterController::class, 'showCorporateRegistrationForm'])->name('register.corporate');
Route::post('/register/corporate', [RegisterController::class, 'registerCorporate']);

// Rotas protegidas
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Contas
    Route::resource('accounts', AccountController::class);
    Route::patch('/accounts/{account}/status', [AccountController::class, 'updateStatus'])->name('accounts.update-status');
    
    // Carteiras
    Route::resource('wallets', WalletController::class);
    Route::patch('/wallets/{wallet}/status', [WalletController::class, 'updateStatus'])->name('wallets.update-status');
    Route::get('/accounts/{account}/wallets/create', [WalletController::class, 'createForAccount'])->name('accounts.wallets.create');
    
    // Transferências
    Route::resource('transfers', TransferController::class)->only(['index', 'create', 'store', 'show']);
    
    // Extratos
    Route::get('/statements', [StatementController::class, 'index'])->name('statements.index');
    
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
