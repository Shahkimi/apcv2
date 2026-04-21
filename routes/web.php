<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\KehadiranController as AdminKehadiranController;
use App\Http\Controllers\Admin\Kawalan\GredController as KawalanGredController;
use App\Http\Controllers\Admin\Kawalan\JawatanController as KawalanJawatanController;
use App\Http\Controllers\Admin\Kawalan\MejaController as KawalanMejaController;
use App\Http\Controllers\Admin\Kawalan\PtjController as KawalanPtjController;
use App\Http\Controllers\Admin\Kawalan\SesiMajlisController as KawalanSesiMajlisController;
use App\Http\Controllers\Admin\Kawalan\UserManagementController as KawalanUserManagementController;
use App\Http\Controllers\Media\DashboardController as MediaDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        /** @var User $user */
        $user = auth()->user();

        return match ($user->role) {
            User::ROLE_USER => redirect()->route('user.dashboard'),
            User::ROLE_MEDIA => redirect()->route('media.dashboard'),
            User::ROLE_ADMIN => redirect()->route('admin.dashboard'),
            default => abort(403),
        };
    })->name('dashboard');

    Route::middleware('role.user')
        ->prefix('user')
        ->name('user.')
        ->group(function () {
            Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard');
        });

    Route::middleware('role.media')
        ->prefix('media')
        ->name('media.')
        ->group(function () {
            Route::get('/dashboard', [MediaDashboardController::class, 'index'])->name('dashboard');
        });

    Route::middleware('role.admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

            Route::prefix('kehadiran')->name('kehadiran.')->group(function () {
                Route::get('/', [AdminKehadiranController::class, 'index'])->name('index');
                Route::get('/datatable', [AdminKehadiranController::class, 'datatable'])->name('datatable');
                Route::get('/{pegawai}/details', [AdminKehadiranController::class, 'getDetails'])->name('details');
                Route::put('/{pegawai}/verify', [AdminKehadiranController::class, 'verify'])->name('verify');
            });

            Route::prefix('kawalan')->name('kawalan.')->group(function () {
                Route::get('ptj', [KawalanPtjController::class, 'index'])->name('ptj.index');
                Route::get('ptj/datatable', [KawalanPtjController::class, 'datatable'])->name('ptj.datatable');
                Route::post('ptj', [KawalanPtjController::class, 'store'])->name('ptj.store');
                Route::put('ptj/{ptj}', [KawalanPtjController::class, 'update'])->name('ptj.update');
                Route::delete('ptj/{ptj}', [KawalanPtjController::class, 'destroy'])->name('ptj.destroy');

                Route::get('jawatan', [KawalanJawatanController::class, 'index'])->name('jawatan.index');
                Route::get('jawatan/datatable', [KawalanJawatanController::class, 'datatable'])->name('jawatan.datatable');
                Route::post('jawatan', [KawalanJawatanController::class, 'store'])->name('jawatan.store');
                Route::put('jawatan/{jawatan}', [KawalanJawatanController::class, 'update'])->name('jawatan.update');
                Route::delete('jawatan/{jawatan}', [KawalanJawatanController::class, 'destroy'])->name('jawatan.destroy');

                Route::get('gred', [KawalanGredController::class, 'index'])->name('gred.index');
                Route::get('gred/datatable', [KawalanGredController::class, 'datatable'])->name('gred.datatable');
                Route::post('gred', [KawalanGredController::class, 'store'])->name('gred.store');
                Route::put('gred/{gred}', [KawalanGredController::class, 'update'])->name('gred.update');
                Route::delete('gred/{gred}', [KawalanGredController::class, 'destroy'])->name('gred.destroy');

                Route::get('meja', [KawalanMejaController::class, 'index'])->name('meja.index');
                Route::get('meja/datatable', [KawalanMejaController::class, 'datatable'])->name('meja.datatable');
                Route::post('meja', [KawalanMejaController::class, 'store'])->name('meja.store');
                Route::put('meja/{meja}', [KawalanMejaController::class, 'update'])->name('meja.update');
                Route::delete('meja/{meja}', [KawalanMejaController::class, 'destroy'])->name('meja.destroy');

                Route::get('sesi-majlis', [KawalanSesiMajlisController::class, 'index'])->name('sesi-majlis.index');
                Route::get('sesi-majlis/datatable', [KawalanSesiMajlisController::class, 'datatable'])->name('sesi-majlis.datatable');
                Route::post('sesi-majlis', [KawalanSesiMajlisController::class, 'store'])->name('sesi-majlis.store');
                Route::put('sesi-majlis/{sesi_majlis}', [KawalanSesiMajlisController::class, 'update'])->name('sesi-majlis.update');
                Route::delete('sesi-majlis/{sesi_majlis}', [KawalanSesiMajlisController::class, 'destroy'])->name('sesi-majlis.destroy');

                Route::get('user', [KawalanUserManagementController::class, 'index'])->name('user.index');
                Route::get('user/datatable', [KawalanUserManagementController::class, 'datatable'])->name('user.datatable');
                Route::post('user', [KawalanUserManagementController::class, 'store'])->name('user.store');
                Route::put('user/{user}', [KawalanUserManagementController::class, 'update'])->name('user.update');
                Route::delete('user/{user}', [KawalanUserManagementController::class, 'destroy'])->name('user.destroy');
            });
        });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
