<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QuotationBotService
{
    /**
     * Generate a draft quotation PDF
     */
    public function generateQuotation(Customer $customer, array $requestedItems): string
    {
        $items = [];
        $totalAmount = 0;

        foreach ($requestedItems as $req) {
            $product = Product::where('name', 'like', "%{$req['product_name']}%")
                ->orWhere('sku', 'like', "%{$req['product_name']}%")
                ->where('is_active', true)
                ->first();

            if ($product) {
                $qty = $req['quantity'] ?? 1;
                $price = $product->selling_price;
                $subtotal = $price * $qty;

                $items[] = [
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'qty' => $qty,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];

                $totalAmount += $subtotal;
            }
        }

        if (empty($items)) {
            return ''; // No products found
        }

        $data = [
            'customer' => $customer,
            'items' => $items,
            'total_amount' => $totalAmount,
        ];

        $pdf = Pdf::loadView('pdf.quotation', $data)->setPaper('a4');
        
        $filename = 'quotation_draft_' . $customer->id . '_' . time() . '.pdf';
        $path = 'quotations/' . $filename;
        
        // Ensure directory exists
        if (!Storage::disk('public')->exists('quotations')) {
            Storage::disk('public')->makeDirectory('quotations');
        }

        Storage::disk('public')->put($path, $pdf->output());

        return config('app.url') . '/storage/' . $path;
    }
}
