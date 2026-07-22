@props(['name', 'checked' => false, 'label' => null])
<label class="inline-flex items-center gap-3 cursor-pointer min-h-touch">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" class="peer sr-only" @checked($checked) {{ $attributes }}>
    <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-300 dark:bg-slate-700 transition peer-checked:bg-brand-600
        after:absolute after:top-0.5 after:start-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:transition
        peer-checked:after:translate-x-5 rtl:peer-checked:after:-translate-x-5"></span>
    @if($label)<span class="text-sm font-medium">{{ $label }}</span>@endif
</label>
