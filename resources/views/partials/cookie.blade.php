@if ($settings->get('cookie_enabled'))
    <div x-data="dismissible('cookie-consent', 600)" x-cloak x-show="visible" x-transition.opacity x-on:cookie-settings.window="reopen()"
         class="card fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-3xl flex-wrap items-center gap-4 border-l-4 border-l-brand p-5 shadow-2xl shadow-black/60">
        <p class="min-w-60 flex-1 text-sm leading-relaxed text-mist">{{ $settings->get('cookie_text') }}</p>
        <div class="flex gap-2">
            <a href="{{ route('legal.show', 'datenschutz') }}" class="btn btn-secondary btn-sm">Mehr erfahren</a>
            <button type="button" class="btn btn-primary btn-sm" x-on:click="dismiss()">Akzeptieren</button>
        </div>
    </div>
@endif
