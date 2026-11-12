@props(['label' => null, 'name', 'value' => null, 'hint' => null, 'rows' => 4])
<x-field :label="$label" :name="$name" :hint="$hint">
    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->class(['input', 'border-red-500/60' => $errors->has($name)]) }}>{{ old($name, $value) }}</textarea>
</x-field>
