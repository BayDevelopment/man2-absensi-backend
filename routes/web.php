<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', fn() => redirect()->route('filament.admin.auth.login'))
    ->name('login');

// Email Verification Routes
Route::get('/email/verify', function () {
    return redirect()->route('filament.admin.auth.login');
})->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (string $id, string $hash) {

    $user = User::findOrFail($id);

    // Validasi hash
    if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        abort(403, 'Link verifikasi tidak valid.');
    }

    // Validasi signature URL
    if (! request()->hasValidSignature()) {
        abort(403, 'Link verifikasi sudah expired atau tidak valid.');
    }

    // Sudah verified sebelumnya
    if ($user->hasVerifiedEmail()) {
        return redirect()->route('filament.admin.auth.login')
            ->with('info', 'Email sudah terverifikasi sebelumnya. Silakan login.');
    }

    // Tandai verified
    $user->email_verified_at = Carbon::now();
    $user->save();

    event(new Verified($user));

    return redirect()->route('filament.admin.auth.login')
        ->with('success', 'Email berhasil diverifikasi! Silakan login.');
})->middleware(['signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Link verifikasi telah dikirim!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');
