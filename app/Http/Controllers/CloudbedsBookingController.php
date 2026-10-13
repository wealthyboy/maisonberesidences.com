<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CloudbedsBookingController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        // The old embedded engine bypasses local ID uploads and discounts.
        // Keep existing /book links, but send them through the website checkout.
        $filters = $request->only('checkin', 'checkout', 'guests', 'rooms', 'currency', 'search');
        if (! isset($filters['guests']) && $request->filled('adults')) {
            $filters['guests'] = $request->input('adults');
        }

        return redirect()->route('apartments.index', $filters);
    }
}
