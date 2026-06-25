@php
    $settings = \App\Models\SystemSetting::query()->first();
    $pages = \App\Models\Information::query()->orderBy('sort_order')->orderBy('title')->get();
    $whatsAppDigits = preg_replace('/\D+/', '', (string) ($settings?->store_phone ?? ''));
@endphp

<footer class="site-footer">
    <div class="site-footer-links">
        @foreach ($pages as $page)
            <a href="{{ filled($page->custom_link) ? $page->custom_link : route('information.show', $page) }}">{{ $page->title }}</a>
        @endforeach
    </div>
    <p>&copy; {{ now()->year }} {{ $settings?->store_name ?: 'Maison Be Residences' }}. All rights reserved.</p>
    @if ($whatsAppDigits !== '')
        <a class="site-footer-whatsapp" href="https://wa.me/{{ $whatsAppDigits }}" target="_blank" rel="noopener noreferrer" aria-label="Chat with Maison Be on WhatsApp">
            <svg viewBox="0 0 32 32" aria-hidden="true"><path d="M16 3.5a12.3 12.3 0 0 0-10.5 18.7L4 28l6-1.4A12.5 12.5 0 1 0 16 3.5Z"></path><path d="M12.4 10.2c-.4-.9-.8-.9-1.2-.9h-.9c-.3 0-.8.1-1.1.5-.4.4-1.5 1.5-1.5 3.8s1.5 4.5 1.7 4.8c.2.3 3 4.8 7.4 6.5 3.6 1.4 4.4 1.1 5.2 1s2.7-1.1 3.1-2.2.4-2.1.3-2.3c-.1-.2-.3-.3-.7-.5s-2.7-1.3-3.1-1.4c-.4-.2-.7-.2-1 .2-.3.4-1.2 1.4-1.4 1.7-.3.3-.5.3-1 .1-2.8-1.3-4.6-3.4-5.1-4-.3-.4 0-.6.2-.8l.7-.8c.2-.3.3-.5.5-.8.2-.3.1-.6 0-.8l-1.4-3.1Z"></path></svg>
        </a>
    @endif
</footer>
