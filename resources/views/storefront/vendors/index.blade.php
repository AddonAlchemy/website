<x-layouts.app title="Studios" description="Alle verifizierten Studios und Entwickler im Marktplatz.">
    <div class="container-page py-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight">Verifizierte <span class="text-brand">Studios</span></h1>
            <p class="mt-1 text-sm text-mist">Die Entwickler hinter den Plugins – geprüft und freigeschaltet durch unser Team.</p>
        </div>

        @if ($vendors->isEmpty())
            <x-empty icon="store" title="Noch keine Studios" text="Sobald die ersten Vendoren freigeschaltet sind, erscheinen sie hier.">
                <a href="{{ route('vendor.apply') }}" class="btn btn-primary btn-sm">Vendor werden</a>
            </x-empty>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($vendors as $vendor)
                    <a href="{{ route('vendors.show', $vendor) }}" class="card card-hover flex min-w-0 flex-col p-6">
                        <div class="flex items-center gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-lg bg-brand/10 font-display text-xl font-extrabold text-brand">{{ mb_strtoupper(mb_substr($vendor->name, 0, 1)) }}</span>
                            <div class="min-w-0">
                                <h2 class="flex items-center gap-1.5 truncate text-lg font-semibold">{{ $vendor->name }} <x-icon name="badge-check" class="size-4 text-brand" /></h2>
                                <p class="truncate text-xs text-mist">{{ $vendor->tagline ?: 'Verifizierter Vendor' }}</p>
                            </div>
                        </div>
                        <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-line pt-4 text-sm">
                            <div><dt class="text-xs text-mist">Plugins</dt><dd class="font-display font-bold">{{ $vendor->plugins_count }}</dd></div>
                            <div><dt class="text-xs text-mist">Downloads</dt><dd class="font-display font-bold">{{ number_format((int) $vendor->plugins_sum_downloads_count, 0, ',', '.') }}</dd></div>
                        </dl>
                    </a>
                @endforeach
            </div>

            {{ $vendors->links() }}
        @endif
    </div>
</x-layouts.app>
