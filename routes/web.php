<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\IntranetController;
use App\Http\Controllers\LekerdezoController;
use App\Http\Controllers\AlosztalyomController;
use App\Http\Controllers\AlosztalyAdatbazisController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\DiscordSyncController;
use App\Http\Controllers\AllomanyController;
use App\Http\Controllers\EsemenyekController;
use App\Http\Controllers\EventController;

// Install wizard (accessible only when not yet installed — enforced by CheckInstalled middleware)
Route::get('/install',  [InstallController::class, 'show'])->name('install');
Route::post('/install', [InstallController::class, 'install'])->name('install.post');

// Auth
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/auth/discord/callback', [AuthController::class, 'callback']);

// Protected routes
Route::middleware(['auth', 'suspended'])->group(function () {

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update']);
    Route::post('/settings/password', [SettingsController::class, 'changePassword'])->name('settings.password');

    // Discord account linking (optional)
    Route::get('/auth/discord/link', [AuthController::class, 'discordLink'])->name('auth.discord.link');
    Route::post('/auth/discord/unlink', [AuthController::class, 'discordUnlink'])->name('auth.discord.unlink');

    // Presence heartbeat
    Route::post('/heartbeat', function () {
        \Illuminate\Support\Facades\DB::table('users')
            ->where('id', auth()->id())
            ->update(['last_active_at' => now()]);
        return response()->json(['ok' => true]);
    })->name('heartbeat');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Announcements
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show'])->name('announcements.show');

    // Reports
    Route::get('/jelentesek', [ReportController::class, 'index'])->name('jelentesek');
    Route::post('/jelentesek', [ReportController::class, 'store']);
    Route::get('/jelentesek/{id}', [ReportController::class, 'show'])->name('jelentesek.show');
    Route::put('/jelentesek/{id}', [ReportController::class, 'update']);
    Route::delete('/jelentesek/{id}', [ReportController::class, 'destroy']);

    // Intranet
    Route::get('/intranet', [IntranetController::class, 'index'])->name('intranet');
    Route::post('/intranet/conversations', [IntranetController::class, 'createConversation']);
    Route::get('/intranet/{id}', [IntranetController::class, 'show']);
    Route::post('/intranet/{id}/messages', [IntranetController::class, 'sendMessage']);
    Route::delete('/intranet/messages/{id}', [IntranetController::class, 'deleteMessage']);
    Route::get('/intranet/{id}/messages', [IntranetController::class, 'pollMessages'])->name('intranet.poll');

    // Állomány
    Route::get('/allomany', [AllomanyController::class, 'index'])->name('allomany');

    // Event RSVP (all authenticated users)
    Route::post('/esemenyek/events/{id}/rsvp', [EventController::class, 'rsvp'])->name('events.rsvp');

    // Lekérdező
    Route::get('/lekerdezo', [LekerdezoController::class, 'index'])->name('lekerdezo');

    // Események
    Route::get('/esemenyek', [EsemenyekController::class, 'index'])->name('esemenyek');

    // Supervisor+ routes (admin or supervisor)
    Route::middleware('supervisor')->group(function () {
        Route::post('/esemenyek', [EsemenyekController::class, 'update'])->name('esemenyek.update');
        Route::post('/esemenyek/events', [EventController::class, 'store'])->name('events.store');
        Route::put('/esemenyek/events/{id}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/esemenyek/events/{id}', [EventController::class, 'destroy'])->name('events.destroy');
        Route::post('/admin/announcements', [AdminController::class, 'storeAnnouncement'])->name('admin.announcements.store');
        Route::delete('/admin/announcements/{id}', [AdminController::class, 'deleteAnnouncement'])->name('admin.announcements.destroy');
        // Report category CRUD
        Route::post('/admin/report-categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
        Route::put('/admin/report-categories/{id}', [AdminController::class, 'updateCategory'])->name('admin.categories.update');
        Route::delete('/admin/report-categories/{id}', [AdminController::class, 'destroyCategory'])->name('admin.categories.destroy');
        // Department discord settings
        Route::put('/admin/departments/{id}/discord', [AdminController::class, 'updateDepartment'])->name('admin.departments.discord');
    });

    // Alosztályom
    Route::get('/alosztalyom', [AlosztalyomController::class, 'index'])->name('alosztalyom');
    Route::post('/alosztalyom/rules', [AlosztalyomController::class, 'updateRules']);
    Route::post('/alosztalyom/ranks', [AlosztalyomController::class, 'storeRank']);
    Route::put('/alosztalyom/ranks/{rankId}', [AlosztalyomController::class, 'updateRank']);
    Route::delete('/alosztalyom/ranks/{rankId}', [AlosztalyomController::class, 'deleteRank']);
    Route::put('/alosztalyom/members/{userId}', [AlosztalyomController::class, 'updateMember']);

    // File upload
    Route::post('/upload', [UploadController::class, 'store'])->name('upload');

    // Supervisor+ can view the panel (tabs filtered by role in the view)
    Route::middleware('supervisor')->get('/admin', [AdminController::class, 'index'])->name('admin');

    // Admin only
    Route::middleware('admin')->group(function () {

        // User management
        Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::post('/admin/users/{id}', [AdminController::class, 'updateUser']);
        Route::post('/admin/users/{id}/suspend', [AdminController::class, 'toggleSuspend'])->name('admin.users.suspend');
        Route::delete('/admin/users/{id}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');

        // Ranks
        Route::post('/admin/ranks', [AdminController::class, 'storeRank']);
        Route::put('/admin/ranks/{id}', [AdminController::class, 'updateRank']);
        Route::delete('/admin/ranks/{id}', [AdminController::class, 'deleteRank']);

        // (Announcements moved to supervisor middleware group above)

        // Messages
        Route::delete('/admin/messages/{id}', [AdminController::class, 'deleteMessage']);

        // Settings
        Route::patch('/admin/settings', [AdminController::class, 'updateSettings']);

        // Discord
        Route::post('/admin/sync', [DiscordSyncController::class, 'sync'])->name('admin.sync');
        Route::post('/admin/discord-embed', [AdminController::class, 'sendEmbed'])->name('admin.discord-embed');
        Route::get('/admin/discord-messages', [AdminController::class, 'getDiscordMessages'])->name('admin.discord-messages');

        // Department database
        Route::get('/alosztaly-adatbazis', [AlosztalyAdatbazisController::class, 'index'])->name('alosztaly-adatbazis');
        Route::post('/alosztaly-adatbazis', [AlosztalyAdatbazisController::class, 'store']);
        Route::put('/alosztaly-adatbazis/{id}', [AlosztalyAdatbazisController::class, 'update']);
        Route::delete('/alosztaly-adatbazis/{id}', [AlosztalyAdatbazisController::class, 'destroy']);
    });
});
