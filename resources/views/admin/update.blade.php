@extends('layouts.admin')

@section('title', __('Update') . ' — ' . site_name())
@section('page-title', __('Update'))

@section('content')
<div class="space-y-5 max-w-3xl" x-data="updater()">

    {{-- Big one-click card --}}
    <div class="card card-pad text-center">
        <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-950 dark:text-brand-400">
            <x-icon name="refresh" class="h-8 w-8"/>
        </span>
        <h2 class="mt-4 text-lg font-bold">{{ __('One-click update') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('Pull the latest version straight from GitHub and apply it to your site — no terminal or file uploads.') }}
        </p>

        <div class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            {{ __('Installed version') }}: <b>{{ $version }}</b>
            @if($deployed)
                · {{ __('current build') }}: <span class="mono">{{ substr($deployed, 0, 7) }}</span>
                @if($deployedAt) · {{ \Carbon\Carbon::parse($deployedAt)->diffForHumans() }} @endif
            @endif
        </div>

        {{-- status line after Check --}}
        <template x-if="status">
            <div class="mt-4 rounded-xl px-4 py-3 text-sm"
                 :class="upToDate ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                  : 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-300'">
                <span x-text="status"></span>
                <template x-if="latest">
                    <div class="mono text-xs mt-1 opacity-80" x-text="latest.sha.slice(0,7) + ' · ' + latest.message"></div>
                </template>
            </div>
        </template>

        <div class="mt-5 flex flex-wrap gap-2 justify-center">
            <button class="btn-secondary" @click="check()" :disabled="busy">
                <span x-show="!checking"><x-icon name="search" class="h-4 w-4"/></span>
                <span x-show="checking" class="spin" style="border-top-color:currentColor"></span>
                {{ __('Check for updates') }}
            </button>
            <button class="btn-primary" @click="runUpdate()" :disabled="busy"
                    style="min-width:170px">
                <span x-show="!running"><x-icon name="download" class="h-5 w-5"/></span>
                <span x-show="running" class="spin"></span>
                <span x-text="running ? '{{ __('Updating…') }}' : '{{ __('Update now') }}'"></span>
            </button>
        </div>
        <p class="mt-3 text-xs text-slate-400">{{ __('Your data, settings and uploads are kept safe — only program files are replaced.') }}</p>
    </div>

    {{-- Live log --}}
    <template x-if="log.length">
        <div class="card card-pad">
            <h3 class="font-semibold text-sm mb-2">{{ __('Update log') }}</h3>
            <div class="rounded-xl bg-slate-900 text-slate-100 text-xs font-mono p-4 space-y-1 max-h-72 overflow-auto">
                <template x-for="(line, i) in log" :key="i"><div x-text="line"></div></template>
            </div>
        </div>
    </template>

    {{-- Recent changes --}}
    <template x-if="commits.length">
        <div class="card card-pad">
            <h3 class="font-semibold text-sm mb-3">{{ __('Latest changes on GitHub') }}</h3>
            <div class="space-y-2">
                <template x-for="c in commits" :key="c.sha">
                    <div class="flex items-start gap-3 text-sm">
                        <span class="mono text-xs badge-gray shrink-0" x-text="c.sha"></span>
                        <span class="min-w-0" x-text="c.message"></span>
                    </div>
                </template>
            </div>
        </div>
    </template>

    {{-- Update source settings --}}
    <div class="card">
        <button class="w-full flex items-center justify-between card-pad !py-4" @click="showSrc = !showSrc">
            <span class="font-semibold text-sm">{{ __('Update source (GitHub)') }}</span>
            <x-icon name="chevron-down" class="h-4 w-4 transition-transform" ::class="showSrc && 'rotate-180'"/>
        </button>
        <div x-show="showSrc" x-cloak class="card-pad !pt-0 space-y-4">
            <form method="POST" action="{{ route('admin.updates.source') }}" class="space-y-4">
                @csrf
                <div class="grid sm:grid-cols-2 gap-4">
                    <x-field name="repo" :label="__('Repository (owner/repo)')">
                        <input name="repo" class="input" value="{{ $repo }}" spellcheck="false" autocapitalize="off" required>
                    </x-field>
                    <x-field name="branch" :label="__('Branch')">
                        <input name="branch" class="input" value="{{ $branch }}" spellcheck="false" autocapitalize="off" required>
                    </x-field>
                </div>
                <x-field name="token" :label="__('Token (only for private repos)')" :help="__('Leave the dots to keep the saved token. Needs Contents: Read permission.')">
                    <input name="token" type="password" class="input" value="{{ $hasToken ? '••••••••' : '' }}" spellcheck="false" autocomplete="off">
                </x-field>
                <div class="flex gap-2">
                    <button class="btn-primary">{{ __('Save source') }}</button>
                    @if($hasToken)
                        <a href="#" onclick="event.preventDefault();document.getElementById('clearTokenForm').submit()" class="btn-ghost">{{ __('Remove token') }}</a>
                    @endif
                </div>
            </form>
            @if($hasToken)
                <form id="clearTokenForm" method="POST" action="{{ route('admin.updates.clear-token') }}" class="hidden">@csrf</form>
            @endif

            <div class="border-t border-slate-100 dark:border-slate-800 pt-4 text-sm">
                <a href="{{ route('admin.updates.editor') }}" class="text-brand-600 hover:underline inline-flex items-center gap-1">
                    <x-icon name="code" class="h-4 w-4"/> {{ __('Advanced: edit individual files on GitHub') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function updater(){
    return {
        busy:false, checking:false, running:false, showSrc:false,
        status:'', upToDate:false, latest:null, commits:[], log:[],
        async check(){
            this.busy=true; this.checking=true; this.status='';
            try{
                const r = await fetch('{{ route('admin.updates.check') }}', {headers:{'Accept':'application/json'}});
                const d = await r.json();
                if(!d.ok){ this.status = d.message; this.upToDate=false; }
                else{
                    this.latest = d.latest; this.commits = d.commits || [];
                    this.upToDate = d.up_to_date;
                    this.status = d.up_to_date ? @js(__('You are on the latest version. ✅')) : @js(__('A new update is available. Press “Update now”.'));
                }
            }catch(e){ this.status = 'Error: '+e.message; }
            finally{ this.busy=false; this.checking=false; }
        },
        async runUpdate(){
            if(!confirm(@js(__('Update the site to the latest version now?')))) return;
            this.busy=true; this.running=true; this.log=[@js(__('Starting update…'))];
            try{
                const r = await fetch('{{ route('admin.updates.run') }}', {
                    method:'POST',
                    headers:{'Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}
                });
                const d = await r.json();
                this.log = d.log || this.log;
                if(d.ok){ toast(@js(__('Update complete!')), 'ok'); this.status=@js(__('You are on the latest version. ✅')); this.upToDate=true; }
                else{ toast(d.message || 'Update failed', 'err'); }
            }catch(e){ this.log.push('❌ '+e.message); toast('Update failed: '+e.message,'err'); }
            finally{ this.busy=false; this.running=false; }
        }
    }
}
</script>
@endpush
