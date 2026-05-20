<?php

namespace Minishop\Ai\Agents;

use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;
use Minishop\Models\Category;
use Minishop\Models\Product;
use Stringable;

#[Provider('anthropic')]
class SupportAgent implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $products = Product::query()
            ->where('is_active', true)
            ->select(['name', 'price', 'stock_quantity', 'description'])
            ->limit(30)
            ->get()
            ->map(fn ($p) => '- '.$p->name.': $'.number_format($p->price / 100, 2).', stock: '.$p->stock_quantity)
            ->join("\n");

        $categories = Category::query()
            ->where('is_active', true)
            ->pluck('name')
            ->join(', ');

        return <<<EOT
        You are a friendly support assistant for Minishop, a curated online store.

        ## Store Information
        We sell quality products across these categories: {$categories}.
        We ship across Canada with standard and free shipping options.
        Customers can pay via credit card (Stripe) or cash on delivery.

        ## Current Products
        {$products}

        ## How the App Works
        - Browse products at /products, filter by category or search by name
        - Add items to cart (persisted in local storage)
        - Checkout: fill in shipping address, choose shipping method, apply coupon codes, then pay
        - After placing an order, customers receive an order number and confirmation
        - Registered customers can track orders under /account/orders
        - Billing address can be saved under /account/address

        ## Guidelines
        - Be concise, warm, and helpful
        - If asked about a specific product, share its price and stock availability
        - If you don't know something, say so and suggest contacting support via email
        - Do not invent product details not listed above
        EOT;
    }
}
