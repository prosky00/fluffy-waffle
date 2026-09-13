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
use App\Http\Controllers\AdminController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\DiscordSyncController;
use App\Http\Controllers\AllomanyController;
use App\Http\Controllers\EsemenyekController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\FactionApplicationController;
use App\Http\Controllers\SchedulingController;
use App\Http\Controllers\HrController;
use App\Http\Controllers\ChangelogController;

// Install wizard (accessible only when not yet installed — enforced by CheckInstalled middleware)
Route::get('/install',  [InstallController::class, 'show'])->name('install');
Route::post('/install', [InstallController::class, 'install'])->name('install.post');

// Public front page — anyone can view it, logged-in members are bounced to the dashboard
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// Auth
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/auth/discord/callback', [AuthController::class, 'callback']);

// Account creation — instant, self-service, no admin gate. Just a guest account.
Route::get('/regisztracio',  [RegisterController::class, 'create'])->name('register');
Route::post('/regisztracio', [RegisterController::class, 'store'])->name('register.store');

// Everything below requires login. Guests (is_member = false) can only reach the routes
// in this outer group; the nested 'member' group is for accepted faction members only.
Route::middleware(['auth', 'suspended'])->group(function () {

    Route::get('/settings', [SettingsController::class, 'show'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update']);
    Route::post('/settings/password', [SettingsController::class, 'changePassword'])->name('settings.password');

    // Discord account linking (optional, lets guests receive application-status DMs too)
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

    // Faction application ("Join Us" + "My Applications")
    Route::get('/jelentkezes', [FactionApplicationController::class, 'index'])->name('applications.index');
    Route::post('/jelentkezes', [FactionApplicationController::class, 'store'])->name('applications.store');
    Route::post('/jelentkezes/idopontok', [FactionApplicationController::class, 'proposeSlots'])->name('applications.slots');

    Route::middleware('member')->group(function () {

        // HR: form builder, application review, scheduling, notification archive
        Route::middleware('hr')->group(function () {
            Route::get('/hr', [HrController::class, 'index'])->name('hr.index');

            Route::post('/hr/utemezes/{id}/confirm', [SchedulingController::class, 'confirmSlot'])->name('scheduling.confirm');
            Route::post('/hr/utemezes/{id}/grant-access', [SchedulingController::class, 'grantAccess'])->name('scheduling.grant');
            Route::post('/hr/utemezes/{id}/reject', [SchedulingController::class, 'rejectAfterInterview'])->name('scheduling.reject');

            Route::post('/admin/join-requests/{id}/approve', [AdminController::class, 'approveJoinRequest']);
            Route::post('/admin/join-requests/{id}/reject', [AdminController::class, 'rejectJoinRequest']);
            Route::post('/admin/join-requests/{id}/needs-changes', [AdminController::class, 'needsChangesJoinRequest']);

            Route::post('/admin/form-fields', [AdminController::class, 'storeFormField']);
            Route::put('/admin/form-fields/{id}', [AdminController::class, 'updateFormField']);
            Route::delete('/admin/form-fields/{id}', [AdminController::class, 'destroyFormField']);
        });

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile');

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
        Route::post('/intranet/{id}/star', [IntranetController::class, 'toggleStar']);
        Route::post('/intranet/{id}/trash', [IntranetController::class, 'trashConversation']);
        Route::post('/intranet/{id}/restore', [IntranetController::class, 'restoreConversation']);

        // Állomány
        Route::get('/allomany', [AllomanyController::class, 'index'])->name('allomany');

        // Event RSVP (all members)
        Route::post('/esemenyek/events/{id}/rsvp', [EventController::class, 'rsvp'])->name('events.rsvp');

        // Lekérdező
        Route::get('/lekerdezo', [LekerdezoController::class, 'index'])->name('lekerdezo');

        // Események
        Route::get('/esemenyek', [EsemenyekController::class, 'index'])->name('esemenyek');

        // Változásnapló
        Route::get('/valtozasnaplo', [ChangelogController::class, 'index'])->name('changelog');

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

            // Messages
            Route::delete('/admin/messages/{id}', [AdminController::class, 'deleteMessage']);
            Route::delete('/admin/conversations/{id}', [AdminController::class, 'deleteConversation']);

            // (Faction applications + form builder moved to the 'hr' middleware group above,
            // reachable by admins and HR alike)

            // Settings
            Route::patch('/admin/settings', [AdminController::class, 'updateSettings']);
            Route::patch('/admin/discord-settings', [AdminController::class, 'updateDiscordSettings'])->name('admin.discord-settings');

            // Discord
            Route::post('/admin/sync', [DiscordSyncController::class, 'sync'])->name('admin.sync');
            Route::post('/admin/sync-push', [DiscordSyncController::class, 'pushAll'])->name('admin.sync-push');
            Route::post('/admin/discord-embed', [AdminController::class, 'sendEmbed'])->name('admin.discord-embed');
            Route::get('/admin/discord-messages', [AdminController::class, 'getDiscordMessages'])->name('admin.discord-messages');
            Route::get('/admin/audit-log', [AdminController::class, 'getAuditLog'])->name('admin.audit-log');

            // Changelog
            Route::post('/admin/changelog', [AdminController::class, 'storeChangelog'])->name('admin.changelog.store');

            // Departments (CRUD + Discord role/channel), all under the admin "Alosztályok" tab
            Route::post('/admin/departments', [AdminController::class, 'storeDepartment']);
            Route::put('/admin/departments/{id}', [AdminController::class, 'updateDepartmentInfo']);
            Route::delete('/admin/departments/{id}', [AdminController::class, 'destroyDepartment']);
            Route::put('/admin/departments/{id}/discord', [AdminController::class, 'updateDepartment'])->name('admin.departments.discord');

            // Front-page builder (nav links + content sections)
            Route::post('/admin/nav-links', [AdminController::class, 'storeNavLink']);
            Route::put('/admin/nav-links/{id}', [AdminController::class, 'updateNavLink']);
            Route::delete('/admin/nav-links/{id}', [AdminController::class, 'destroyNavLink']);
            Route::post('/admin/page-sections', [AdminController::class, 'storePageSection']);
            Route::put('/admin/page-sections/{id}', [AdminController::class, 'updatePageSection']);
            Route::delete('/admin/page-sections/{id}', [AdminController::class, 'destroyPageSection']);
            Route::post('/admin/page-sections/{id}/toggle', [AdminController::class, 'togglePageSection']);
        });
    });
});

// Old standalone department-database page moved into the admin panel's "Alosztályok" tab
Route::redirect('/alosztaly-adatbazis', '/admin?tab=departments');

// Old standalone scheduling-only page moved into the HR menu's "Ütemezés" tab
Route::redirect('/hr/utemezes', '/hr');
