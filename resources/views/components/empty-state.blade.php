@props(['icon' => 'sparkles', 'title', 'description' => null])
<div class="card card-pad text-center py-12">
    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500">
        <x-icon :name="$icon" class="h-7 w-7"/>
    </span>
    <h3 class="mt-4 font-semibold">{{ $title }}</h3>
    @if($description)
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-sm mx-auto">{{ $description }}</p>
    @endif
    <div class="mt-4">{{ $slot }}</div>
</div>
