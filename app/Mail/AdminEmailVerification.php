<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminEmailVerification extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $emailSubject;
    public $custom_message;
    

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $subject, $msg)
    {
        $this->data = $data;
        $this->emailSubject = $subject;
        $this->custom_message = $msg;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->emailSubject)
        ->view('emails.email-verification-admin');
    }
}
