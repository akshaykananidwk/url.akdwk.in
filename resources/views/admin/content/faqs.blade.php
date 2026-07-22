@extends('layouts.admin')

@section('title', __('FAQs') . ' — ' . site_name())
@section('page-title', __('Content'))

@section('content')
<div class="space-y-5">

    <div class="flex gap-2 overflow-x-auto">
        <a href="{{ route('admin.content.pages') }}" class="btn-secondary btn-sm shrink-0">{{ __('Pages') }}</a>
        <a href="{{ route('admin.content.posts') }}" class="btn-secondary btn-sm shrink-0">{{ __('Blog posts') }}</a>
        <a href="{{ route('admin.content.faqs') }}" class="btn-primary btn-sm shrink-0">{{ __('FAQs') }}</a>
    </div>

    {{-- Create FAQ --}}
    <form method="POST" action="{{ route('admin.content.faqs.store') }}" class="card card-pad space-y-4">
        @csrf
        <h2 class="font-semibold">{{ __('Add FAQ') }}</h2>
        <x-field name="question" :label="__('Question')">
            <input type="text" id="question" name="question" value="{{ old('question') }}" class="input" required>
        </x-field>
        <x-field name="answer" :label="__('Answer')">
            <textarea id="answer" name="answer" rows="3" class="input" required>{{ old('answer') }}</textarea>
        </x-field>
        <div class="flex items-end gap-4">
            <x-field name="sort_order" :label="__('Sort order')" class="w-32">
                <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}" class="input">
            </x-field>
            <button class="btn-primary btn-sm mb-0.5"><x-icon name="plus" class="h-4 w-4"/> {{ __('Add FAQ') }}</button>
        </div>
    </form>

    {{-- FAQ list --}}
    <div class="space-y-3">
        @forelse($faqs as $faq)
            <div class="card card-pad flex items-start gap-3">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-500">{{ $faq->sort_order }}</span>
                <div class="min-w-0 flex-1">
                    <p class="font-medium">{{ $faq->question }}</p>
                    <p class="mt-1 text-sm text-slate-500 whitespace-pre-wrap">{{ $faq->answer }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-1.5">
                    @unless($faq->active)<span class="badge-gray">{{ __('Hidden') }}</span>@endunless
                    <button type="button" class="btn-secondary btn-sm" @click="$dispatch('open-modal', 'faq-{{ $faq->id }}')" aria-label="{{ __('Edit') }}">
                        <x-icon name="pencil" class="h-4 w-4"/>
                    </button>
                    <x-confirm :action="route('admin.content.faqs.destroy', $faq)" method="DELETE" :title="__('Delete this FAQ?')">
                        <button type="button" class="btn-danger btn-sm" aria-label="{{ __('Delete') }}"><x-icon name="trash" class="h-4 w-4"/></button>
                    </x-confirm>
                </div>
            </div>

            {{-- Edit modal --}}
            <x-modal name="faq-{{ $faq->id }}" :title="__('Edit FAQ')">
                <form method="POST" action="{{ route('admin.content.faqs.update', $faq) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="label" for="faq-question-{{ $faq->id }}">{{ __('Question') }}</label>
                        <input type="text" id="faq-question-{{ $faq->id }}" name="question" value="{{ $faq->question }}" class="input" required>
                    </div>
                    <div>
                        <label class="label" for="faq-answer-{{ $faq->id }}">{{ __('Answer') }}</label>
                        <textarea id="faq-answer-{{ $faq->id }}" name="answer" rows="4" class="input" required>{{ $faq->answer }}</textarea>
                    </div>
                    <div class="flex items-end justify-between gap-4">
                        <div class="w-32">
                            <label class="label" for="faq-sort-{{ $faq->id }}">{{ __('Sort order') }}</label>
                            <input type="number" id="faq-sort-{{ $faq->id }}" name="sort_order" value="{{ $faq->sort_order }}" class="input">
                        </div>
                        <x-toggle name="active" :checked="(bool) $faq->active" :label="__('Visible')"/>
                    </div>
                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" class="btn-secondary" @click="$dispatch('close-modal')">{{ __('Cancel') }}</button>
                        <button class="btn-primary">{{ __('Save FAQ') }}</button>
                    </div>
                </form>
            </x-modal>
        @empty
            <x-empty-state icon="doc" :title="__('No FAQs yet')" :description="__('Add your first question above — FAQs appear on the public homepage.')"/>
        @endforelse
    </div>
</div>
@endsection
