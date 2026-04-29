<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\Kawalan\BackdropController as KawalanBackdropController;
use App\Http\Controllers\Admin\Kawalan\GredController as KawalanGredController;
use App\Http\Controllers\Admin\Kawalan\JawatanController as KawalanJawatanController;
use App\Http\Controllers\Admin\Kawalan\MejaController as KawalanMejaController;
use App\Http\Controllers\Admin\Kawalan\PtjController as KawalanPtjController;
use App\Http\Controllers\Admin\Kawalan\SesiMajlisController as KawalanSesiMajlisController;
use App\Http\Controllers\Admin\Kawalan\UserManagementController as KawalanUserManagementController;
use App\Http\Controllers\Admin\KehadiranController as AdminKehadiranController;
use App\Http\Controllers\Admin\PaparanController as AdminPaparanController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SenaraiAnalyticsController as AdminSenaraiAnalyticsController;
use App\Http\Controllers\Media\DashboardController as MediaDashboardController;
use App\Http\Controllers\Media\Kawalan\PresentationSettingsController as MediaPresentationSettingsController;
use App\Http\Controllers\Media\PaparanController as MediaPaparanController;
use App\Http\Controllers\Media\SenaraiAnalyticsController as MediaSenaraiAnalyticsController;
use App\Http\Controllers\Media\SenaraiController as MediaSenaraiController;
use App\Http\Controllers\Media\SenaraiProgressController as MediaSenaraiProgressController;
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
            Route::get('/paparan', [MediaPaparanController::class, 'index'])->name('paparan.index');
            Route::get('/senarai/present', [MediaSenaraiController::class, 'present'])->name('senarai.present');
            Route::get('/senarai', [MediaSenaraiController::class, 'index'])->name('senarai.index');
            Route::get('/senarai/analytics', [MediaSenaraiAnalyticsController::class, 'index'])->name('senarai.analytics');
            Route::get('/senarai/progress', [MediaSenaraiProgressController::class, 'show'])->name('senarai.progress.show');
            Route::post('/senarai/progress', [MediaSenaraiProgressController::class, 'update'])->name('senarai.progress.update');
            Route::get('/senarai/progress/analytics', [MediaSenaraiProgressController::class, 'analytics'])->name('senarai.progress.analytics');

            Route::prefix('kawalan')->name('kawalan.')->group(function () {
                Route::get('/presentation', [MediaPresentationSettingsController::class, 'index'])->name('presentation.index');
                Route::put('/presentation', [MediaPresentationSettingsController::class, 'update'])->name('presentation.update');
            });
        });

    Route::middleware('role.admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
            Route::get('/senarai/analytics', [AdminSenaraiAnalyticsController::class, 'index'])->name('senarai.analytics');
            Route::get('/senarai/progress/analytics', [MediaSenaraiProgressController::class, 'analytics'])->name('senarai.progress.analytics');
            Route::get('/report', [AdminReportController::class, 'index'])->name('report.index');
            Route::get('/report/preview', [AdminReportController::class, 'preview'])->name('report.preview');
            Route::get('/report/datatable', [AdminReportController::class, 'datatable'])->name('report.datatable');
            Route::get('/report/download', [AdminReportController::class, 'download'])->name('report.download');

            Route::prefix('kehadiran')->name('kehadiran.')->group(function () {
                Route::get('/', [AdminKehadiranController::class, 'index'])->name('index');
                Route::get('/datatable', [AdminKehadiranController::class, 'datatable'])->name('datatable');
                Route::get('/{pegawai}/details', [AdminKehadiranController::class, 'getDetails'])->name('details');
                Route::put('/{pegawai}/verify', [AdminKehadiranController::class, 'verify'])->name('verify');
            });

            Route::get('paparan', [AdminPaparanController::class, 'index'])->name('paparan.index');

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
                Route::post('meja/toggle-display', [KawalanMejaController::class, 'toggleTableDisplay'])->name('meja.toggle-display');
                Route::put('meja/{meja}', [KawalanMejaController::class, 'update'])->name('meja.update');
                Route::delete('meja/{meja}', [KawalanMejaController::class, 'destroy'])->name('meja.destroy');

                Route::get('sesi-majlis', [KawalanSesiMajlisController::class, 'index'])->name('sesi-majlis.index');
                Route::get('sesi-majlis/datatable', [KawalanSesiMajlisController::class, 'datatable'])->name('sesi-majlis.datatable');
                Route::post('sesi-majlis', [KawalanSesiMajlisController::class, 'store'])->name('sesi-majlis.store');
                Route::put('sesi-majlis/{sesi_majlis}', [KawalanSesiMajlisController::class, 'update'])->name('sesi-majlis.update');
                Route::delete('sesi-majlis/{sesi_majlis}', [KawalanSesiMajlisController::class, 'destroy'])->name('sesi-majlis.destroy');

                Route::get('backdrop', [KawalanBackdropController::class, 'index'])->name('backdrop.index');
                Route::get('backdrop/datatable', [KawalanBackdropController::class, 'datatable'])->name('backdrop.datatable');
                Route::post('backdrop', [KawalanBackdropController::class, 'store'])->name('backdrop.store');
                Route::put('backdrop/{backdrop}', [KawalanBackdropController::class, 'update'])->name('backdrop.update');
                Route::delete('backdrop/{backdrop}', [KawalanBackdropController::class, 'destroy'])->name('backdrop.destroy');
                Route::post('backdrop/{backdrop}/toggle-active', [KawalanBackdropController::class, 'toggleActive'])->name('backdrop.toggle-active');

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
