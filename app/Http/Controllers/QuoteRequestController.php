<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequestRequest;
use App\Mail\NewQuoteRequestReceived;
use App\Models\QuoteRequest;
use App\Services\Customization\DimensionForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class QuoteRequestController extends Controller
{
    public function store(StoreQuoteRequestRequest $request, DimensionForm $dimensionForm): RedirectResponse
    {
        $quoteRequest = QuoteRequest::create([
            ...$request->safe()->except(['website', 'dimensions', DimensionForm::NOTES_INPUT]),
            'customer_id' => $request->user('customer')?->id,
            'customization' => $request->product()
                ? $dimensionForm->snapshot($request->product(), $request->validated())
                : null,
        ]);

        $adminEmail = setting('contact_email');

        if ($adminEmail) {
            try {
                Mail::to($adminEmail)->send(new NewQuoteRequestReceived($quoteRequest));
            } catch (\Throwable $e) {
                // Never lose a real lead just because the mail transport failed —
                // the request is already saved and visible in the admin panel.
                Log::warning('Failed to send quote request notification email', [
                    'quote_request_id' => $quoteRequest->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('quote_request_sent', true);
    }
}
