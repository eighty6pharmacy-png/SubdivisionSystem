<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class NewUserCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Althesa Subdivision - Your Account Credentials',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                <div style='background: #3b82f6; padding: 20px; border-radius: 12px; text-align: center;'>
                    <h2 style='color: #ffffff; margin: 0;'>Althesa Subdivision</h2>
                    <p style='color: #eff6ff; margin: 4px 0 0 0; font-size: 13px;'>Account Access Details</p>
                </div>
                <div style='padding: 20px 0;'>
                    <p style='font-size: 16px; color: #0f172a;'>Hello <strong>{$this->user->name}</strong>,</p>
                    <p style='color: #475569;'>Your account has been successfully created. You can now log in to the system using the following credentials:</p>
                    <div style='background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; margin: 20px 0;'>
                        <p style='margin: 0; color: #334155;'><strong>Email:</strong> {$this->user->email}</p>
                        <p style='margin: 8px 0 0 0; color: #334155;'><strong>Password:</strong> <span style='font-family: monospace; font-size: 16px; background: #e2e8f0; padding: 2px 6px; border-radius: 4px;'>{$this->password}</span></p>
                    </div>
                    <p style='color: #475569; font-size: 13px;'>For security reasons, please log in and change your password immediately.</p>
                </div>
                <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                    Althesa Subdivision Management Office
                </div>
            </div>
            "
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
