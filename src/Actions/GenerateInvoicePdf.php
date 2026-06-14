<?php

namespace Minishop\Actions;

use Barryvdh\DomPDF\Facade\Pdf;
use Minishop\Models\Order;
use Minishop\Models\StoreSettings;

/**
 * Render an order's invoice to a PDF. Shared by the admin "Invoice" download
 * action and the order-confirmation email attachment so both produce an
 * identical document from the minishop::pdf.invoice template.
 */
class GenerateInvoicePdf
{
    public function execute(Order $order): string
    {
        $order->loadMissing(['items', 'customer.user']);

        return Pdf::loadView('minishop::pdf.invoice', [
            'order' => $order,
            'settings' => StoreSettings::current(),
        ])->output();
    }

    public function filename(Order $order): string
    {
        return "invoice-{$order->order_number}.pdf";
    }
}
