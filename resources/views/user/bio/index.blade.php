@extends('layouts.app')

@section('title', __('Bio Pages') . ' — ' . site_name())
@section('page-title', __('Bio Pages'))

@section('content')
<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('One page for all your links — perfect for social media profiles.') }}</p>
        <button class="btn-primary" @click="$dispatch('open-modal', 'new-bio')">
            <x-icon name="plus" class="h-5 w-5"/> {{ __('New bio page') }}
        </button>
    </div>

    @if($pages->isEmpty())
        <x-empty-state icon="user" :title="__('No bio pages yet')" :description="__('Create your link-in-bio page and share every link from one place.')">
            <button class="btn-primary btn-sm" @click="$dispatch('open-modal', 'new-bio')">{{ __('Create bio page') }}</button>
        </x-empty-state>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($pages as $page)
                <div class="card card-pad flex flex-col gap-3">
                    <div class="flex items-center gap-3">
                        @if($page->avatar)
                            <img src="{{ storage_url($page->avatar) }}" alt="" class="h-12 w-12 rounded-full object-cover">
                        @else
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-950 dark:text-brand-300 font-bold text-lg">
                                {{ mb_substr($page->title, 0, 1) }}
                            </span>
                        @endif
                        <div class="min-w-0">
                            <div class="font-semibold truncate">{{ $page->title }}</div>
                            <a href="{{ $page->url() }}" target="_blank" rel="noopener" class="text-xs text-brand-600 hover:underline truncate block">@{{ $page->username }}</a>
                        </div>
                        @if(! $page->active)
                            <span class="badge-gray ms-auto">{{ __('Off') }}</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-500 dark:text-slate-400">
                        <span>{{ format_number($page->views) }} {{ __('views') }}</span>
                        <span>{{ $page->blocks_count }} {{ __('blocks') }}</span>
                    </div>
                    <div class="flex gap-2 mt-auto">
                        <a href="{{ route('bio.edit', $page) }}" class="btn-secondary btn-sm flex-1">
                            <x-icon name="pencil" class="h-4 w-4"/> {{ __('Edit') }}
                        </a>
                        <button class="btn-ghost btn-sm" onclick="copyText(@js($page->url()))" aria-label="{{ __('Copy link') }}">
                            <x-icon name="copy" class="h-4 w-4"/>
                        </button>
                        <x-confirm :action="route('bio.destroy', $page)" method="DELETE" :title="__('Delete this bio page?')">
                            <button type="button" class="btn-ghost btn-sm text-rose-600" aria-label="{{ __('Delete') }}">
                                <x-icon name="trash" class="h-4 w-4"/>
                            </button>
                        </x-confirm>
                    </div>
                </div>
            @endforeach
        </div>
        {{ $pages->links() }}
    @endif

    <x-modal name="new-bio" :title="__('New bio page')">
        <form method="POST" action="{{ route('bio.store') }}" class="space-y-4">
            @csrf
            <x-field name="username" :label="__('Username')" :help="__('Your page will live at :url', ['url' => url('/@username')])">
                <input type="text" name="username" class="input" required maxlength="60" pattern="[a-zA-Z0-9_.\-]+" value="{{ old('username') }}">
            </x-field>
            <x-field name="title" :label="__('Page title')">
                <input type="text" name="title" class="input" required maxlength="100" value="{{ old('title') }}">
            </x-field>
            <button type="submit" class="btn-primary w-full">{{ __('Create page') }}</button>
        </form>
    </x-modal>
</div>
@endsection
