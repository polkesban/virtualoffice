@extends('layouts.email')

@section('content')
<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
    <h2>Konfirmasi Langganan Newsletter</h2>
    <p>Halo,
    <br><br>
    Terima kasih telah berlangganan newsletter kami. Silakan klik tombol di bawah untuk mengkonfirmasi langganan Anda.
    <br><br>
    <a href="{{ url('/newsletter/confirm/' . $token) }}" style="display: inline-block; padding: 12px 24px; background-color: #2563eb; color: white; text-decoration: none; border-radius: 6px;">Konfirmasi Langganan</a>
    <br><br>
    Jika Anda tidak mengirim permintaan ini, silakan abaikan email ini.
    </p>
    <hr>
    <p style="font-size: 12px; color: #666;">Poltekkes Kemenkes Bandung</p>
</div>
@endsection
