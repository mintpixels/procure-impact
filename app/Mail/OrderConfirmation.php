<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $order, $pi, $brand;
    
    public function __construct($order, $pi = false, $brand = false)
    {
        $this->order = $order;
        $this->pi = $pi;
        $this->brand = $brand;
    }

    public function build()
    {
        $subject = "Your Procure Impact order #{$this->order->id}";

        if($this->pi)
        {
            $subject = "A new Procure Impact order #{$this->order->id}";
        }

        if($this->brand)
        {
            
        }

        return $this->subject($subject)
            ->view('emails.order-confirmation');
    }
}
