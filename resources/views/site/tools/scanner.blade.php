@extends('layouts.landing')

@section('title', __('QR Code Scanner') . ' — ' . site_name())

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-10" x-data="qrScanner()" x-init="init()">

    <div class="max-w-2xl">
        <span class="badge-brand mb-4">{{ __('Free tool') }}</span>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">{{ __('QR Code Scanner') }}</h1>
        <p class="mt-4 text-slate-500 dark:text-slate-400">
            {{ __('Scan any QR code with your device camera — right in the browser. Nothing is uploaded; decoding happens entirely on your device. Point your camera at a code and the link appears instantly.') }}
        </p>
    </div>

    <div class="mt-8 grid lg:grid-cols-2 gap-6 items-start">
        <div class="card card-pad">
            <template x-if="!supported">
                <div class="flex items-start gap-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 p-4 text-sm text-amber-700 dark:text-amber-300">
                    <x-icon name="warning" class="h-5 w-5 shrink-0"/>
                    <span>{{ __('Your browser does not support in-browser QR scanning yet. Try the latest Chrome or Edge on Android or desktop.') }}</span>
                </div>
            </template>

            <template x-if="supported">
                <div>
                    <div class="relative aspect-square w-full overflow-hidden rounded-xl bg-slate-900 ring-1 ring-slate-200 dark:ring-slate-800">
                        <video x-ref="video" class="h-full w-full object-cover" playsinline muted></video>
                        <div x-show="!running" class="absolute inset-0 flex items-center justify-center text-slate-400 text-sm">
                            {{ __('Camera is off') }}
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button type="button" class="btn-primary flex-1" x-show="!running" @click="start()">
                            <x-icon name="eye" class="h-4 w-4"/> {{ __('Start camera') }}
                        </button>
                        <button type="button" class="btn-secondary flex-1" x-show="running" @click="stop()">
                            <x-icon name="x" class="h-4 w-4"/> {{ __('Stop camera') }}
                        </button>
                    </div>
                    <p x-show="error" x-text="error" class="mt-3 text-sm text-rose-600 dark:text-rose-400"></p>
                </div>
            </template>
        </div>

        <div class="card card-pad">
            <h2 class="text-sm font-medium mb-3">{{ __('Decoded result') }}</h2>
            <template x-if="!result">
                <div class="flex flex-col items-center justify-center text-center py-10 text-slate-400">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800">
                        <x-icon name="search" class="h-7 w-7"/>
                    </span>
                    <p class="mt-3 text-sm">{{ __('Point your camera at a QR code to see its contents.') }}</p>
                </div>
            </template>
            <template x-if="result">
                <div>
                    <div class="rounded-xl bg-slate-50 dark:bg-slate-800/60 p-4 text-sm break-all font-mono" x-text="result"></div>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" class="btn-secondary" @click="copy()">
                            <x-icon name="copy" class="h-4 w-4"/>
                            <span x-text="copied ? @js(__('Copied!')) : @js(__('Copy'))"></span>
                        </button>
                        <a x-show="isUrl" :href="result" target="_blank" rel="noopener nofollow" class="btn-primary">
                            <x-icon name="external" class="h-4 w-4"/> {{ __('Open link') }}
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>

</div>

<script>
    function qrScanner() {
        return {
            supported: false, running: false, result: '', error: '', copied: false, stream: null, raf: null, detector: null,
            get isUrl() { return /^https?:\/\//i.test(this.result); },
            init() {
                this.supported = 'BarcodeDetector' in window;
                if (this.supported) {
                    try { this.detector = new BarcodeDetector({ formats: ['qr_code'] }); }
                    catch (e) { this.supported = false; }
                }
            },
            async start() {
                this.error = '';
                try {
                    this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                    this.$refs.video.srcObject = this.stream;
                    await this.$refs.video.play();
                    this.running = true;
                    this.scan();
                } catch (e) {
                    this.error = @js(__('Could not access the camera. Please grant permission and try again.'));
                }
            },
            async scan() {
                if (!this.running) return;
                try {
                    const codes = await this.detector.detect(this.$refs.video);
                    if (codes && codes.length) {
                        this.result = codes[0].rawValue;
                        this.stop();
                        return;
                    }
                } catch (e) {}
                this.raf = requestAnimationFrame(() => this.scan());
            },
            stop() {
                this.running = false;
                if (this.raf) cancelAnimationFrame(this.raf);
                if (this.stream) { this.stream.getTracks().forEach(t => t.stop()); this.stream = null; }
            },
            async copy() {
                try { await navigator.clipboard.writeText(this.result); this.copied = true; setTimeout(() => this.copied = false, 1500); } catch (e) {}
            },
        };
    }
</script>
@endsection
