@props(['name', 'label' => null, 'help' => null])
<div {{ $attributes->only('class') }}>
    @if($label)<label for="{{ $name }}" class="label">{{ $label }}</label>@endif
    {{ $slot }}
    @if($help)<p class="help">{{ $help }}</p>@endif
    @error($name)<p class="error">{{ $message }}</p>@enderror
</div>
