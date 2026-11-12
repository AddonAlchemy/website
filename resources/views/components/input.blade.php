@props(['label' => null, 'name', 'type' => 'text', 'value' => null, 'hint' => null])
<x-field :label="$label" :name="$name" :hint="$hint" :class="$attributes->get('wrapper-class')">
    <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}"
           @if ($type !== 'password' && $type !== 'file') value="{{ old($name, $value) }}" @endif
           {{ $attributes->except('wrapper-class')->class(['input', 'border-red-500/60' => $errors->has($name)]) }}>
</x-field>
