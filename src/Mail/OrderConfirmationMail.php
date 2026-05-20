<?php

namespace Minishop\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Minishop\Models\Order;
use Minishop\Models\StoreSettings;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Order Confirmed – {$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        $settings = StoreSettings::current();

        return new Content(
            markdown: 'mail.order-confirmation',
            with: [
                'order' => $this->order,
                'currency' => $settings->currency,
            ],
        );
    }
}
