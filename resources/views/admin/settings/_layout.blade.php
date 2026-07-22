@php
    $tabLabels = [
        'general' => __('General'),
        'seo' => __('SEO'),
        'email' => __('Email'),
        'social' => __('Social login'),
        'security' => __('Security'),
        'registration' => __('Registration'),
        'affiliate' => __('Affiliate'),
        'ads' => __('Ads'),
        'storage' => __('Storage'),
        'gdpr' => __('GDPR & invoices'),
        'payments' => __('Payments'),
        'advanced' => __('Advanced'),
    ];
@endphp
<div class="flex gap-2 overflow-x-auto pb-2 -mx-4 px-4 sm:mx-0 sm:px-0 mb-5">
    @foreach($tabs as $t)
        <a href="{{ route('admin.settings', $t) }}"
           class="shrink-0 inline-flex items-center rounded-xl px-4 py-2.5 text-sm font-medium min-h-touch transition-colors
               {{ $tab === $t
                   ? 'bg-brand-600 text-white'
                   : 'bg-white dark:bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
            {{ $tabLabels[$t] ?? ucfirst($t) }}
        </a>
    @endforeach
</div>
