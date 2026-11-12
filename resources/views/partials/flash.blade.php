<div class="pointer-events-none fixed top-20 right-4 z-[60] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2.5"
     x-data
     x-init="
        @if (session('success')) $store.toasts.push(@js(session('success')), 'success'); @endif
        @if (session('error')) $store.toasts.push(@js(session('error')), 'error'); @endif
        @if ($errors->any()) $store.toasts.push(@js($errors->first()), 'error'); @endif
     ">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div class="card pointer-events-auto flex items-start gap-3 border-l-4 px-4 py-3 text-sm shadow-2xl shadow-black/60"
             :class="toast.type === 'error' ? 'border-l-red-500' : 'border-l-brand'"
             x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-6 opacity-0">
            <span :class="toast.type === 'error' ? 'text-red-400' : 'text-brand'">
                <template x-if="toast.type === 'error'"><x-icon name="circle-alert" /></template>
                <template x-if="toast.type !== 'error'"><x-icon name="circle-check" /></template>
            </span>
            <p class="flex-1 leading-snug" x-text="toast.message"></p>
            <button type="button" class="text-mist hover:text-snow" x-on:click="$store.toasts.dismiss(toast.id)" aria-label="Schließen"><x-icon name="x" class="size-4" /></button>
        </div>
    </template>
</div>
