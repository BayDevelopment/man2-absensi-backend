<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Request $request
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Login Baru Terdeteksi - Absensi Digital');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.login-notification', with: [
            'user'    => $this->user,
            'device'  => $this->request->userAgent(),
            'ip'      => $this->request->ip(),
            'waktu'   => now()->format('d M Y, H:i'),
        ]);
    }
}
