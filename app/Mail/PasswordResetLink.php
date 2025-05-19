<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Address;

class PasswordResetLink extends Mailable
{
    use Queueable, SerializesModels;
    public $password_reset_link;
    public $firstname;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($password_reset_link, $firstname)
    {
        //
        $this->password_reset_link = $password_reset_link;
        $this->firstname = $firstname;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            from: new Address("Support@bizgrowthhackerz.billspal.com.ng", "BizGrowthHackerz Support"),
            subject: 'BizGrowthHackerz - New Password Reset Link',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'email.password_reset_link',
            with: ['password_reset_link' => $this->password_reset_link, 'firstname' => $this->firstname],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
