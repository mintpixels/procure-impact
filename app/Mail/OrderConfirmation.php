<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    
    public function __construct($order)
    {
        $this->order = $order;
    }

    public function build()
    {
        $subject = "Your Procure Impact order #{$this->order->id}";
        return $this->subject($subject)
            ->view('emails.order-confirmation');
    }
}
