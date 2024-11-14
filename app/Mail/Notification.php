<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class Notification extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $type;

    /**
     * Create a new message instance.
     *
     * @param string $email
     * @param string $type
     */
    public function __construct($email, $type)
    {
        $this->email = $email;
        $this->type = $type; // 'file' or 'folder'
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email')->with([
            'email' => $this->email,
            'type' => $this->type,
        ]);
    }
}
