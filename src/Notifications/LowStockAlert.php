<?php

namespace Minishop\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Minishop\Data\LowStockSubject;

class LowStockAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public LowStockSubject $subject) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Low Stock Alert: {$this->subject->name}")
            ->line("The product \"{$this->subject->name}\" has reached a low stock level.")
            ->line("Current stock: {$this->subject->stockQuantity} units.")
            ->line('SKU: '.($this->subject->sku ?? 'N/A'))
            ->action('View Product', $this->subject->productUrl)
            ->line('Please restock this product soon.');
    }
}
