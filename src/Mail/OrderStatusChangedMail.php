<?php

namespace Minishop\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Minishop\Models\Order;
use Minishop\Models\StoreSettings;

class OrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your order {$this->order->order_number} has been {$this->order->status->label()}",
        );
    }

    public function content(): Content
    {
        $settings = StoreSettings::current();

        return new Content(
            markdown: 'minishop::mail.order-status-changed',
            with: [
                'order' => $this->order,
                'currency' => $settings->currency,
            ],
        );
    }
}
