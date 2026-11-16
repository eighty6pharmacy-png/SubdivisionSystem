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
    public string $roleName;
    public string $recipientName;
    public string $websiteUrl;
    public string $loginUrl;
    public string $roleIntroduction;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $password, ?string $roleName = null)
    {
        $this->user = $user;
        $this->password = $password;
        $this->roleName = $roleName ?: ($user->getRoleNames()->first() ?: 'Member');
        $this->recipientName = trim($user->name ?: ($user->first_name ?: 'Valued Member'));
        $this->websiteUrl = url('/');
        $this->loginUrl = url('/login');
        $this->roleIntroduction = $this->introductionForRole($this->roleName);
    }

    /**
     * Get a tailored introduction message for the user's role.
     */
    private function introductionForRole(string $role): string
    {
        return match ($role) {
            'Admin' => 'Welcome to the Althesa Subdivision Management System. Your administrator account gives you full access to manage community operations, user accounts, announcements, and subdivision records.',
            'Resident' => 'Welcome to the Althesa Subdivision Portal! Your resident account connects you with our community—allowing you to view announcements, manage bills and visitor passes, and stay updated with subdivision services.',
            'Buyer' => 'Welcome to the Althesa Subdivision Buyer Portal! Your account allows you to track your property acquisition, view payment schedules and financing records, and stay connected with our management office.',
            'Security Guard' => 'Welcome to the Althesa Subdivision Security Portal. Your account provides access to gate operations, visitor verification, and community safety logs.',
            'Finance Officer' => 'Welcome to the Althesa Subdivision Finance Portal. Your account provides access to billing management, payment verification, and financial reporting tools.',
            default => 'Welcome to the Althesa Subdivision Portal! Your account has been set up so you can conveniently access subdivision services, updates, and your personalized dashboard online.',
        };
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
            view: 'emails.new-user-credentials',
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
