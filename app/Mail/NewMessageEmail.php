<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewMessageEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $sender;
    

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender)
    {
        
        $this->data = $data;
        $this->sender = $sender;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)
        ->view('emails.new-message');
    }
}
