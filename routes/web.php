<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrganizationController as AdminOrganizationController;
use App\Http\Controllers\Admin\OrganizationTypeController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\Organizer\EventController as OrganizerEventController;
use App\Http\Controllers\Organizer\EventPhotoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventController::class, 'index'])->name('home');
Route::get('/api/cities', [CityController::class, 'index'])->middleware('throttle:120,1')->name('api.cities');
Route::get('/kegiatan/{event}', [EventController::class, 'show'])->name('events.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/kegiatan/{event}/daftar', [RegistrationController::class, 'create'])->name('registrations.create');
    Route::post('/kegiatan/{event}/daftar', [RegistrationController::class, 'store'])->name('registrations.store');
    Route::get('/pendaftaran-saya', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('/pendaftaran-saya/{registration}/tiket', [RegistrationController::class, 'ticket'])->name('registrations.ticket');
    Route::delete('/pendaftaran/{registration}', [RegistrationController::class, 'destroy'])->name('registrations.destroy');

    Route::get('/penyelenggara/daftar', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/penyelenggara/daftar', [OrganizationController::class, 'store'])->name('organizations.store');

    // Akses per kegiatan diatur oleh EventPolicy; pembuatan kegiatan butuh organisasi terverifikasi.
    Route::prefix('penyelenggara')->name('organizer.')->group(function () {
        Route::get('jadwal', [OrganizerEventController::class, 'schedule'])->name('schedule');
        Route::get('laporan', [OrganizerEventController::class, 'reports'])->name('reports');
        Route::resource('events', OrganizerEventController::class)->except('show');
        Route::post('events/{event}/galeri', [EventPhotoController::class, 'store'])->name('events.photos.store');
        Route::delete('events/{event}/galeri/{photo}', [EventPhotoController::class, 'destroy'])->scopeBindings()->name('events.photos.destroy');
        Route::get('events/{event}/pendaftar', [OrganizerEventController::class, 'registrants'])->name('events.registrants');
        Route::get('events/{event}/pendaftar/ekspor', [OrganizerEventController::class, 'exportRegistrants'])->name('events.registrants.export');
        Route::get('events/{event}/laporan', [OrganizerEventController::class, 'eventReport'])->name('events.report');
        Route::put('events/{event}/laporan', [OrganizerEventController::class, 'updateReport'])->name('events.report.update');
    });

    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('jenis-organisasi', [OrganizationTypeController::class, 'index'])->name('organization-types.index');
        Route::post('jenis-organisasi', [OrganizationTypeController::class, 'store'])->name('organization-types.store');
        Route::put('jenis-organisasi/{organizationType}', [OrganizationTypeController::class, 'update'])->name('organization-types.update');
        Route::delete('jenis-organisasi/{organizationType}', [OrganizationTypeController::class, 'destroy'])->name('organization-types.destroy');
        Route::get('organisasi', [AdminOrganizationController::class, 'index'])->name('organizations.index');
        Route::post('organisasi/{organization}/verifikasi', [AdminOrganizationController::class, 'verify'])->name('organizations.verify');
        Route::post('organisasi/{organization}/tolak', [AdminOrganizationController::class, 'reject'])->name('organizations.reject');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [AvatarController::class, 'update'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [AvatarController::class, 'destroy'])->name('profile.avatar.destroy');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
