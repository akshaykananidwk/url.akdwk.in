@extends('layouts.admin')

@section('title', __('Pages') . ' — ' . site_name())
@section('page-title', __('Content'))

@section('content')
<div class="space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex gap-2 overflow-x-auto">
            <a href="{{ route('admin.content.pages') }}" class="btn-primary btn-sm shrink-0">{{ __('Pages') }}</a>
            <a href="{{ route('admin.content.posts') }}" class="btn-secondary btn-sm shrink-0">{{ __('Blog posts') }}</a>
            <a href="{{ route('admin.content.faqs') }}" class="btn-secondary btn-sm shrink-0">{{ __('FAQs') }}</a>
        </div>
        <a href="{{ route('admin.content.pages.create') }}" class="btn-primary btn-sm"><x-icon name="plus" class="h-4 w-4"/> {{ __('New page') }}</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="table-cards">
            <thead>
                <tr>
                    <th>{{ __('Title') }}</th>
                    <th>{{ __('Slug') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Footer') }}</th>
                    <th class="text-end">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td data-label="{{ __('Title') }}" class="font-medium">{{ $page->title }}</td>
                        <td data-label="{{ __('Slug') }}"><span class="font-mono text-xs text-slate-500">/{{ $page->slug }}</span></td>
                        <td data-label="{{ __('Status') }}">
                            @if($page->active)<span class="badge-green">{{ __('Active') }}</span>
                            @else<span class="badge-gray">{{ __('Draft') }}</span>@endif
                        </td>
                        <td data-label="{{ __('Footer') }}">
                            @if($page->show_in_footer)<span class="badge-brand">{{ __('In footer') }}</span>
                            @else<span class="text-slate-400 text-xs">—</span>@endif
                        </td>
                        <td data-label="{{ __('Actions') }}" class="sm:text-end">
                            <span class="inline-flex items-center gap-1.5">
                                <a href="{{ route('admin.content.pages.edit', $page) }}" class="btn-secondary btn-sm"><x-icon name="pencil" class="h-4 w-4"/> {{ __('Edit') }}</a>
                                <x-confirm :action="route('admin.content.pages.destroy', $page)" method="DELETE" :title="__('Delete this page?')">
                                    <button type="button" class="btn-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4"/></button>
                                </x-confirm>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-500 py-10 sm:table-cell">{{ __('No pages yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
