<?php

namespace Minishop\Tests\Feature\Mail;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Minishop\Actions\GenerateInvoicePdf;
use Minishop\Mail\OrderConfirmationMail;
use Minishop\Models\Customer;
use Minishop\Models\Order;
use Minishop\Models\OrderItem;
use Minishop\Models\User;
use Minishop\Tests\TestCase;

class OrderInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(): Order
    {
        $customer = Customer::factory()->create(['user_id' => User::factory()->create()->id]);
        $order = Order::factory()->create(['customer_id' => $customer->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);

        return $order->load(['items', 'customer.user']);
    }

    public function test_generates_a_pdf_invoice_for_an_order(): void
    {
        $action = app(GenerateInvoicePdf::class);
        $order = $this->makeOrder();

        $pdf = $action->execute($order);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame("invoice-{$order->order_number}.pdf", $action->filename($order));
    }

    public function test_confirmation_email_attaches_the_invoice_pdf(): void
    {
        $mail = new OrderConfirmationMail($this->makeOrder());

        $attachments = $mail->attachments();

        $this->assertCount(1, $attachments);
        $this->assertInstanceOf(Attachment::class, $attachments[0]);
    }
}
