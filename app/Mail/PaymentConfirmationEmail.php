<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmationEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $user;
    public $date;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
        $date = date_format(now(), 'Y-m-d H:i:s T');
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('NEXT MBA Account')->view('email.payment-confirmation')->with([
            'email' => $this->user['email'],
            'date' => date('Y-m-d H:i:s'),
            'course' => $this->user['course'],
            'reference_id' => $this->user['reference_id'],
            'qty' => $this->user['qty'],
            'amount' => $this->user['amount'],
        ]);
    }
}
