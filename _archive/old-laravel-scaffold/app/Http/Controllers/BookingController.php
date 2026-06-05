<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'phone'    => 'required|string|max:50',
            'email'    => 'required|email|max:255',
            'suburb'   => 'required|string|max:255',
            'vehicle'  => 'required|string|max:255',
            'date'     => 'required|date',
            'time'     => 'required|string|max:50',
            'package'  => 'required|string|max:100',
            'message'  => 'nullable|string|max:2000',
            'photos'   => 'nullable|array',
            'photos.*' => 'nullable|image|max:5120',
        ]);

        $to      = 'hello@shiningheadlights.com.au';
        $subject = "New Booking Request - {$validated['name']} ({$validated['vehicle']})";

        $body  = "New booking request from shiningheadlights.com.au\n\n";
        $body .= "Name:            {$validated['name']}\n";
        $body .= "Phone:           {$validated['phone']}\n";
        $body .= "Email:           {$validated['email']}\n";
        $body .= "Suburb/Location: {$validated['suburb']}\n";
        $body .= "Vehicle:         {$validated['vehicle']}\n";
        $body .= "Preferred Date:  {$validated['date']}\n";
        $body .= "Preferred Time:  {$validated['time']}\n";
        $body .= "Package:         {$validated['package']}\n";

        if (!empty($validated['message'])) {
            $body .= "Message:\n{$validated['message']}\n";
        }

        // List uploaded photo filenames (files are not stored by default)
        if ($request->hasFile('photos')) {
            $names = collect($request->file('photos'))
                ->filter()
                ->map(fn($f) => $f->getClientOriginalName())
                ->implode(', ');
            if ($names) {
                $body .= "\nUploaded photos: {$names}\n";
                $body .= "Note: ask the customer to reply with photos if you need them.\n";
            }
        }

        $headers  = "From: hello@shiningheadlights.com.au\r\n";
        $headers .= "Reply-To: {$validated['email']}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $sent = mail($to, $subject, $body, $headers);

        return response()->json([
            'success' => $sent,
            'message' => $sent
                ? "Thanks! We'll contact you shortly to confirm your mobile booking."
                : 'Could not send your request. Please call us directly.',
        ]);
    }
}
