@props(['tone' => 'slate'])
@php
    $tones = [
        'brand' => 'border-brand/30 bg-brand/10 text-brand',
        'amber' => 'border-amber-400/30 bg-amber-400/10 text-amber-300',
        'red' => 'border-red-400/30 bg-red-400/10 text-red-300',
        'sky' => 'border-sky-400/30 bg-sky-400/10 text-sky-300',
        'violet' => 'border-violet-400/30 bg-violet-400/10 text-violet-300',
        'slate' => 'border-line bg-ink-950 text-mist',
    ];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded border px-2 py-0.5 font-display text-[0.7rem] font-semibold tracking-wide uppercase whitespace-nowrap', $tones[$tone] ?? $tones['slate']]) }}>{{ $slot }}</span>
