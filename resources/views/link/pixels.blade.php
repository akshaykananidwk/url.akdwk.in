<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ __('Redirecting…') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            background: #f8fafc; color: #0f172a; margin: 0;
            min-height: 100vh; display: flex; align-items: center; justify-content: center; text-align: center;
        }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #f1f5f9; } }
        .spinner {
            width: 40px; height: 40px; margin: 0 auto 14px;
            border: 3px solid rgba(99, 102, 241, .25); border-top-color: #6366f1;
            border-radius: 50%; animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        p { font-size: 14px; color: #64748b; }
        a { color: #6366f1; }
    </style>

    @foreach($link->pixels as $pixel)
        @switch($pixel->type)
            @case('gads')
            @case('ga4')
                <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($pixel->value) }}"></script>
                <script>
                    window.dataLayer = window.dataLayer || [];
                    function gtag(){ dataLayer.push(arguments); }
                    gtag('js', new Date());
                    gtag('config', {{ json_encode($pixel->value) }});
                </script>
                @break

            @case('gtm')
                <script>
                    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});
                    var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';
                    j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                    })(window,document,'script','dataLayer',{{ json_encode($pixel->value) }});
                </script>
                @break

            @case('meta')
                <script>
                    !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                    n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
                    n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
                    t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
                    document,'script','https://connect.facebook.net/en_US/fbevents.js');
                    fbq('init', {{ json_encode($pixel->value) }});
                    fbq('track', 'PageView');
                </script>
                @break

            @case('bing')
                <script>
                    (function(w,d,t,r,u){var f,n,i;w[u]=w[u]||[],f=function(){var o={ti:{{ json_encode($pixel->value) }}, enableAutoSpaTracking: true};
                    o.q=w[u],w[u]=new UET(o),w[u].push('pageLoad')},n=d.createElement(t),n.src=r,n.async=1,
                    n.onload=n.onreadystatechange=function(){var s=this.readyState;s&&s!=='loaded'&&s!=='complete'||(f(),n.onload=n.onreadystatechange=null)},
                    i=d.getElementsByTagName(t)[0],i.parentNode.insertBefore(n,i)})(window,document,'script','//bat.bing.com/bat.js','uetq');
                </script>
                @break

            @case('twitter')
                <script>
                    !function(e,t,n,s,u,a){e.twq||(s=e.twq=function(){s.exe?s.exe.apply(s,arguments):s.queue.push(arguments);},
                    s.version='1.1',s.queue=[],u=t.createElement(n),u.async=!0,u.src='https://static.ads-twitter.com/uwt.js',
                    a=t.getElementsByTagName(n)[0],a.parentNode.insertBefore(u,a))}(window,document,'script');
                    twq('config', {{ json_encode($pixel->value) }});
                </script>
                @break

            @case('pinterest')
                <script>
                    !function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};
                    var n=window.pintrk;n.queue=[],n.version="3.0";var t=document.createElement("script");
                    t.async=!0,t.src=e;var r=document.getElementsByTagName("script")[0];r.parentNode.insertBefore(t,r)}}("https://s.pinimg.com/ct/core.js");
                    pintrk('load', {{ json_encode($pixel->value) }});
                    pintrk('page');
                </script>
                @break

            @case('linkedin')
                <script>
                    window._linkedin_partner_id = {{ json_encode($pixel->value) }};
                    window._linkedin_data_partner_ids = window._linkedin_data_partner_ids || [];
                    window._linkedin_data_partner_ids.push(window._linkedin_partner_id);
                    (function(l){if(!l){window.lintrk=function(a,b){window.lintrk.q.push([a,b])};window.lintrk.q=[]}
                    var s=document.getElementsByTagName("script")[0];var b=document.createElement("script");
                    b.type="text/javascript";b.async=true;b.src="https://snap.licdn.com/li.lms-analytics/insight.min.js";
                    s.parentNode.insertBefore(b,s)})(window.lintrk);
                </script>
                @break

            @case('quora')
                <script>
                    !function(q,e,v,n,t,s){if(q.qp)return;n=q.qp=function(){n.qp?n.qp.apply(n,arguments):n.queue.push(arguments);};
                    n.queue=[];t=e.createElement(v);t.async=!0;t.src=s;var a=e.getElementsByTagName(v)[0];
                    a.parentNode.insertBefore(t,a);}(window,document,'script',undefined,undefined,'https://a.quora.com/qevents.js');
                    qp('init', {{ json_encode($pixel->value) }});
                    qp('track', 'ViewContent');
                </script>
                @break

            @case('snapchat')
                <script>
                    (function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function(){a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
                    a.queue=[];var s='script';var r=t.createElement(s);r.async=!0;r.src=n;
                    var u=t.getElementsByTagName(s)[0];u.parentNode.insertBefore(r,u);})(window,document,'https://sc-static.net/scevent.min.js');
                    snaptr('init', {{ json_encode($pixel->value) }});
                    snaptr('track', 'PAGE_VIEW');
                </script>
                @break

            @case('tiktok')
                <script>
                    !function (w, d, t) {
                        w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"];
                        ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
                        for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
                        ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};
                        ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";
                        ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};
                        var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;
                        var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
                        ttq.load({{ json_encode($pixel->value) }});
                        ttq.page();
                    }(window, document, 'ttq');
                </script>
                @break

            @case('reddit')
                <script>
                    !function(w,d){if(!w.rdt){var p=w.rdt=function(){p.sendEvent?p.sendEvent.apply(p,arguments):p.callQueue.push(arguments)};
                    p.callQueue=[];var t=d.createElement("script");t.src="https://www.redditstatic.com/ads/pixel.js",t.async=!0;
                    var s=d.getElementsByTagName("script")[0];s.parentNode.insertBefore(t,s)}}(window,document);
                    rdt('init', {{ json_encode($pixel->value) }});
                    rdt('track', 'PageVisit');
                </script>
                @break

            @case('adroll')
                @php([$adrollAdv, $adrollPix] = array_pad(explode('|', (string) $pixel->value, 2), 2, null))
                <script>
                    adroll_adv_id = {{ json_encode($adrollAdv) }};
                    adroll_pix_id = {{ json_encode($adrollPix ?: $adrollAdv) }};
                    (function () {
                        var _onload = function(){
                            if (document.readyState && !/loaded|complete/.test(document.readyState)){setTimeout(_onload, 10);return}
                            if (!window.__adroll_loaded){__adroll_loaded=true;setTimeout(_onload, 50);return}
                            var scr = document.createElement("script");
                            var host = (("https:" == document.location.protocol) ? "https://s.adroll.com" : "http://a.adroll.com");
                            scr.setAttribute('async', 'true');
                            scr.type = "text/javascript";
                            scr.src = host + "/j/roundtrip.js";
                            ((document.getElementsByTagName('head') || [null])[0] || document.getElementsByTagName('script')[0].parentNode).appendChild(scr);
                        };
                        if (window.addEventListener) {window.addEventListener('load', _onload, false);}
                        else {window.attachEvent('onload', _onload)}
                    }());
                </script>
                @break

            @case('custom')
                {!! $pixel->value !!}
                @break
        @endswitch
    @endforeach

    <noscript>
        <meta http-equiv="refresh" content="1;url={{ $destination }}">
    </noscript>
</head>
<body>
    <div>
        <div class="spinner" aria-hidden="true"></div>
        <p>{{ __('Redirecting…') }} <a href="{{ $destination }}">{{ __('Click here if you are not redirected.') }}</a></p>
    </div>
    <script>
        setTimeout(function () { location.replace(@json($destination)); }, 800);
    </script>
</body>
</html>
