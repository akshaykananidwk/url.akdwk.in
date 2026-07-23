{{-- Browser push notifications toggle. Self-contained; @include into any page. --}}
<div class="card card-pad"
     x-data="pushToggle()"
     x-init="init()">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <h2 class="font-semibold flex items-center gap-2">
                <x-icon name="megaphone" class="h-5 w-5 text-brand-600"/>
                {{ __('Browser notifications') }}
            </h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ __('Get notified in your browser about important account and link activity.') }}
            </p>
            <p class="mt-2 text-xs" x-show="message" x-text="message"
               :class="error ? 'text-rose-600' : 'text-emerald-600'" x-cloak></p>
        </div>

        <template x-if="supported">
            <button type="button" role="switch" :aria-checked="enabled.toString()"
                    @click="toggle()" :disabled="busy"
                    class="relative h-6 w-11 shrink-0 rounded-full transition disabled:opacity-50"
                    :class="enabled ? 'bg-brand-600' : 'bg-slate-300 dark:bg-slate-700'">
                <span class="absolute top-0.5 start-0.5 h-5 w-5 rounded-full bg-white transition"
                      :class="enabled ? 'translate-x-5 rtl:-translate-x-5' : ''"></span>
            </button>
        </template>

        <template x-if="!supported">
            <span class="badge-amber shrink-0">{{ __('Not supported') }}</span>
        </template>
    </div>
</div>

<script>
function pushToggle() {
    return {
        supported: false,
        enabled: false,
        busy: false,
        message: '',
        error: false,
        keyUrl: @json(route('push.key')),
        subscribeUrl: @json(route('push.subscribe')),
        unsubscribeUrl: @json(route('push.unsubscribe')),
        csrf: @json(csrf_token()),

        async init() {
            this.supported = ('serviceWorker' in navigator) && ('PushManager' in window) && ('Notification' in window);
            if (!this.supported) return;
            try {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                this.enabled = !!sub;
            } catch (e) { /* leave disabled */ }
        },

        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
            const raw = atob(base64);
            const output = new Uint8Array(raw.length);
            for (let i = 0; i < raw.length; ++i) output[i] = raw.charCodeAt(i);
            return output;
        },

        setMsg(text, isError = false) { this.message = text; this.error = isError; },

        async toggle() {
            if (this.busy) return;
            this.busy = true;
            this.setMsg('');
            try {
                if (this.enabled) { await this.disable(); }
                else { await this.enable(); }
            } catch (e) {
                this.setMsg(e.message || @json(__('Something went wrong.')), true);
            } finally {
                this.busy = false;
            }
        },

        async enable() {
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                this.setMsg(@json(__('Notification permission was denied.')), true);
                return;
            }

            const reg = await navigator.serviceWorker.register('/sw.js').catch(() => navigator.serviceWorker.ready);
            await navigator.serviceWorker.ready;

            const res = await fetch(this.keyUrl, { headers: { 'Accept': 'application/json' } });
            const { key } = await res.json();

            let sub = await reg.pushManager.getSubscription();
            if (!sub) {
                sub = await reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(key),
                });
            }

            const json = sub.toJSON();
            await fetch(this.subscribeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                body: JSON.stringify({ endpoint: json.endpoint, keys: json.keys }),
            });

            this.enabled = true;
            this.setMsg(@json(__('Notifications enabled.')));
        },

        async disable() {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (sub) {
                const endpoint = sub.endpoint;
                await sub.unsubscribe().catch(() => {});
                await fetch(this.unsubscribeUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ endpoint }),
                });
            }
            this.enabled = false;
            this.setMsg(@json(__('Notifications disabled.')));
        },
    };
}
</script>
