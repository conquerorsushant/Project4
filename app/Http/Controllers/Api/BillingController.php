<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    /**
     * List all invoices for the authenticated user.
     */
    public function invoices(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->hasStripeId()) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $invoices = $user->invoices()->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'date' => $invoice->date()->toFormattedDateString(),
                'total' => $invoice->total(),
                'status' => $invoice->status,
                'invoice_pdf' => $invoice->invoice_pdf,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Download a specific invoice as PDF.
     */
    public function downloadInvoice(Request $request, string $invoiceId)
    {
        return $request->user()->downloadInvoice($invoiceId, [
            'vendor' => 'NeedAnEstimate.com',
            'product' => 'Business Listing Subscription',
        ]);
    }
}
