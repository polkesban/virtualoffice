<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber)
    {
    }

    public function build()
    {
        return $this->subject('Konfirmasi Langganan Newsletter')
                    ->markdown('emails.newsletter-confirmation', [
                        'token' => $this->subscriber->token,
                    ]);
    }
}
