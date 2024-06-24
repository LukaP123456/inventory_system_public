<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;

//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use function PHPUnit\Framework\isEmpty;

//Load Composer's autoloader
require '../vendor/autoload.php';

class RegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $name;
    public string $user_id;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($name,$user_id)
    {
        $this->name = $name;
        $this->user_id = $user_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('regMail')
            ->subject('Welcome '.$this->name.'!')
            ->from('lpbudgeting987@gmail.com', 'LV Inventory');    }
}
