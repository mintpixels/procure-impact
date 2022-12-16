<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CompletePO extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    
    public function __construct($order)
    {
        $this->order = $order;
    }

    public function build()
    {
        $subject = "Your Procure Impact purchase order #{$this->order->id} is approved";
        return $this->subject($subject)
            ->view('emails.complete-po');
    }
}
