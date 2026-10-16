<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email|max:255']);

        $email = strtolower(trim($request->input('email')));

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => $email],
            [
                'token'      => NewsletterSubscriber::generateToken(),
                'ip_address' => $request->ip(),
            ]
        );

        Mail::to($subscriber->email)->send(new NewsletterConfirmation($subscriber));

        return back()->with('success', 'Terima kasih! Cek email Anda untuk konfirmasi langganan.');
    }

    public function unsubscribe(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();
        $subscriber->update(['unsubscribed_at' => now()]);

        return redirect('/')->with('success', 'Anda telah berhenti berlangganan newsletter.');
    }

    public function confirm(string $token): RedirectResponse
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->firstOrFail();
        $subscriber->update([
            'confirmed' => true,
            'confirmed_at' => now(),
        ]);

        return redirect('/')->with('success', 'Terima kasih! Langganan newsletter Anda telah dikonfirmasi.');
    }
}
