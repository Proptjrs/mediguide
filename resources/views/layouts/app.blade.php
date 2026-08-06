<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? 'MediGuide — Trouvez le bon médecin, au bon endroit, au bon moment' }}</title>
{{-- Bootstrap 5.3 : imposé par la stack du mémoire (chap. 3.2.10 / section 1).
     Chargé AVANT le design system MediGuide pour que la charte de MediGuide_Demo.html
     (chap. 12) garde la priorité sur les classes homonymes de Bootstrap (.btn, .card…). --}}
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@500&display=swap');

/* ============================================================
   MediGuide — Design System "Clinique Moderne"
   Bleu médical · Vert santé · Fond clair · Cartes 16px
   (reproduction fidèle de MediGuide_Demo.html, chap. 12 du mémoire)
   ============================================================ */
:root{
  --blue:       #0284C7;
  --blue-dark:  #0369A1;
  --blue-deep:  #075985;
  --blue-900:   #0C3A52;
  --blue-pale:  #E0F2FE;
  --blue-pale-2:#F0F9FF;
  --grad-primary: linear-gradient(135deg,#0EA5E9 0%,#0284C7 52%,#0369A1 100%);
  --grad-hero:  radial-gradient(1100px 620px at 82% -8%,rgba(14,165,233,.16),transparent 60%),
                radial-gradient(800px 500px at -6% 108%,rgba(16,185,129,.10),transparent 55%);

  --green:      #10B981;
  --green-dark: #059669;
  --green-pale: #D1FAE5;
  --grad-success: linear-gradient(135deg,#34D399,#10B981 60%,#059669);

  --amber:      #F59E0B;
  --amber-pale: #FEF3C7;

  --red:        #EF4444;
  --red-dark:   #DC2626;
  --red-pale:   #FEE2E2;

  --white:      #FFFFFF;
  --bg:         #F7F9FC;
  --surface:    #FFFFFF;
  --border:     #E4E9F1;
  --border-2:   #EEF2F8;

  --text:       #33415A;
  --text-dark:  #101B2D;
  --muted:      #64748B;
  --muted-2:    #94A3B8;

  --r-sm:  12px;
  --r:     18px;
  --r-lg:  26px;
  --r-pill:999px;

  --shadow-xs: 0 1px 2px rgba(16,27,45,.05), 0 1px 1px rgba(2,132,199,.03);
  --shadow-sm: 0 3px 10px rgba(16,27,45,.06), 0 1px 3px rgba(2,132,199,.05);
  --shadow:    0 10px 28px rgba(2,132,199,.10), 0 3px 8px rgba(16,27,45,.05);
  --shadow-lg: 0 22px 52px rgba(2,132,199,.16), 0 6px 16px rgba(16,27,45,.07);
  --shadow-btn:0 8px 20px rgba(2,132,199,.32), 0 2px 6px rgba(2,132,199,.18);
  --shadow-glow: 0 0 0 1px rgba(2,132,199,.08), 0 16px 40px rgba(2,132,199,.22);

  --disp: 'Inter', -apple-system, system-ui, sans-serif;
  --body: 'Inter', -apple-system, system-ui, sans-serif;
  --mono: 'IBM Plex Mono', ui-monospace, monospace;

  --ease:      cubic-bezier(.22,.8,.3,1);
  --ease-pop:  cubic-bezier(.34,1.56,.64,1);
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{
  font-family:var(--body); background:var(--bg); color:var(--text);
  line-height:1.6; font-size:16px; -webkit-font-smoothing:antialiased;
}
button{font-family:var(--body);cursor:pointer;border:none;background:none;color:inherit}
img,svg{display:block}
::selection{background:var(--blue-pale);color:var(--blue-deep)}
:focus-visible{outline:2.5px solid var(--blue);outline-offset:2px;border-radius:4px}
@media (prefers-reduced-motion:reduce){*,*::before,*::after{animation-duration:.01ms!important;transition-duration:.01ms!important}}
a{color:inherit;text-decoration:none}
[x-cloak]{display:none!important}

.eyebrow{
  display:inline-flex;align-items:center;gap:8px;font-size:.82rem;font-weight:600;
  color:var(--blue-dark);background:var(--blue-pale);padding:8px 16px;border-radius:var(--r-pill);
}
.eyebrow .dot{width:6px;height:6px;border-radius:50%;background:var(--green);animation:pulse-dot 2s infinite}
@keyframes pulse-dot{0%,100%{box-shadow:0 0 0 0 rgba(16,185,129,.5)}55%{box-shadow:0 0 0 6px rgba(16,185,129,0)}}

.nav{position:fixed;top:0;left:0;right:0;z-index:1000;background:rgba(255,255,255,.86);backdrop-filter:blur(14px);border-bottom:1px solid var(--border-2)}
.nav-in{max-width:1240px;margin:0 auto;padding:0 28px;height:72px;display:flex;align-items:center;justify-content:space-between;gap:16px}
.brand{display:flex;align-items:center;gap:11px;font-weight:800;font-size:1.28rem;color:var(--text-dark);letter-spacing:-.01em}
.brand .mk{width:34px;height:34px;flex:none}
.nav-links{display:flex;gap:6px;align-items:center}
.nav-cta{margin-left:6px}
.nav-link{display:flex;align-items:center;gap:7px;padding:9px 16px;border-radius:var(--r-pill);font-weight:600;font-size:.89rem;color:var(--muted);transition:all .22s var(--ease)}
.nav-link svg{width:16px;height:16px;stroke-width:2}
.nav-link:hover{color:var(--blue-dark);background:var(--blue-pale-2)}
.nav-link.on{color:var(--blue-dark);background:var(--blue-pale)}
.nav-links .cta-gap{width:1px;height:24px;background:var(--border);margin:0 6px}

.btn{display:inline-flex;align-items:center;gap:9px;font-weight:700;font-size:.92rem;border-radius:var(--r-pill);transition:all .28s var(--ease);white-space:nowrap;position:relative}
.btn svg{width:16px;height:16px;stroke-width:2.1}
.btn-primary{background:var(--grad-primary);color:#fff;padding:13px 25px;box-shadow:var(--shadow-btn);border:1px solid rgba(255,255,255,.14)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:var(--shadow-glow)}
.btn-primary:active{transform:translateY(0) scale(.98)}
.btn-outline{border:1.6px solid var(--border);color:var(--text-dark);padding:12px 23px;background:var(--white);box-shadow:var(--shadow-xs)}
.btn-outline:hover{border-color:var(--blue);color:var(--blue-dark);background:var(--blue-pale-2);transform:translateY(-1px);box-shadow:var(--shadow-sm)}
.btn-outline.on-hero{border-color:rgba(255,255,255,.5);color:#fff;background:rgba(255,255,255,.06)}
.btn-outline.on-hero:hover{background:#fff;color:var(--blue-dark);border-color:#fff}
.btn-ghost{color:var(--blue-dark);font-weight:600}
.btn-ghost:hover{text-decoration:underline}
.btn-sm{padding:9px 17px;font-size:.83rem}
.btn:disabled{opacity:.5;cursor:not-allowed}
.burger{display:none;flex-direction:column;gap:5px;padding:8px}
.burger span{width:22px;height:2px;border-radius:2px;background:var(--text-dark)}

.wrap{max-width:1240px;margin:0 auto;padding:0 28px}
main{padding-top:72px;min-height:70vh}

/* ====== HERO ====== */
.hero{position:relative;overflow:hidden;background:linear-gradient(180deg,var(--blue-pale-2) 0%,var(--bg) 78%),var(--grad-hero)}
.hero::before{
  content:""; position:absolute; top:-180px; right:-160px; width:640px; height:640px; border-radius:50%;
  background:radial-gradient(circle,rgba(2,132,199,.14),transparent 70%); pointer-events:none;
}
.hero::after{
  content:""; position:absolute; bottom:-220px; left:-140px; width:480px; height:480px; border-radius:50%;
  background:radial-gradient(circle,rgba(16,185,129,.10),transparent 70%); pointer-events:none;
}
.hero-in{position:relative;z-index:2;max-width:1240px;margin:0 auto;padding:88px 28px 96px;display:grid;grid-template-columns:1.15fr .85fr;gap:56px;align-items:center}
.hero h1{
  font-family:var(--disp); font-weight:800; font-size:clamp(2.4rem,4.4vw,3.6rem);
  line-height:1.1; color:var(--text-dark); letter-spacing:-.028em; margin:20px 0 18px;
}
.hero h1 .accent{background:var(--grad-primary);-webkit-background-clip:text;background-clip:text;color:transparent}
.hero h1 .accent2{color:var(--green-dark)}
.hero p.lead{color:var(--muted);font-size:1.14rem;max-width:33em;line-height:1.7}
.hero-cta{display:flex;gap:14px;margin-top:32px;flex-wrap:wrap}
.hero-stats{display:flex;gap:36px;margin-top:44px;flex-wrap:wrap}
.hero-stats div b{display:block;font-size:1.7rem;font-weight:800;color:var(--text-dark)}
.hero-stats div span{font-size:.82rem;color:var(--muted);font-weight:500}

.rise{opacity:0;transform:translateY(18px);animation:rise .8s var(--ease) forwards}
@keyframes rise{to{opacity:1;transform:none}}
.d1{animation-delay:.05s}.d2{animation-delay:.15s}.d3{animation-delay:.25s}.d4{animation-delay:.35s}.d5{animation-delay:.45s}

.hero-visual{position:relative}
.entry-card{
  background:var(--white); border-radius:28px; box-shadow:var(--shadow-lg); padding:28px;
  border:1px solid var(--border-2); position:relative; overflow:hidden;
}
.entry-card::before{
  content:"";position:absolute;top:0;left:0;right:0;height:4px;background:var(--grad-primary);
}
.entry-head{margin-bottom:18px}
.entry-step{
  display:inline-block;font-size:.7rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;
  color:var(--blue-dark);background:var(--blue-pale);padding:5px 12px;border-radius:var(--r-pill);margin-bottom:12px;
}
.entry-head h3{font-size:1.28rem;font-weight:800;color:var(--text-dark);letter-spacing:-.02em}
.entry-head p{font-size:.88rem;color:var(--muted);margin-top:5px;line-height:1.55}
.entry-body{display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:center}
.entry-svg{width:126px;flex:none}
.ez{fill:#EAF1F7;stroke:#94A3B8;stroke-width:1.2;cursor:pointer;transition:all .25s var(--ease)}
.ez:hover{fill:var(--blue-pale);stroke:var(--blue)}
.ez.sel{fill:url(#ezGrad);stroke:var(--blue-dark);filter:drop-shadow(0 3px 8px rgba(2,132,199,.4))}
.entry-side{min-height:150px;display:flex;flex-direction:column;justify-content:center;gap:12px}
.entry-hint{font-size:.85rem;color:var(--muted-2);line-height:1.55}
.entry-result{display:none}
.entry-result.show{display:block;animation:entryPop .45s var(--ease-pop)}
@keyframes entryPop{from{opacity:0;transform:translateY(8px) scale(.96)}to{opacity:1;transform:none}}
.entry-result .lbl{font-size:.7rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--muted-2)}
.entry-result .spec{
  font-size:1.32rem;font-weight:800;letter-spacing:-.02em;margin-top:4px;
  background:var(--grad-primary);-webkit-background-clip:text;background-clip:text;color:transparent;
}
.entry-result .zone{font-size:.83rem;color:var(--muted);margin-top:3px}
.entry-go{width:100%;justify-content:center;font-size:.85rem;padding:11px 18px}
@media(max-width:1020px){.entry-card{max-width:440px;margin:0 auto}}
@media(max-width:420px){.entry-body{grid-template-columns:1fr;justify-items:center;text-align:center}.entry-side{min-height:auto}}

.sec-head{margin-bottom:44px;max-width:640px}
.k-title{font-family:var(--disp);font-weight:800;font-size:clamp(1.7rem,2.6vw,2.3rem);color:var(--text-dark);letter-spacing:-.022em}
.k-sub{color:var(--muted);margin-top:10px;font-size:1.02rem;line-height:1.7}

.route-band{padding:88px 0}
.route{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
.stop{
  background:var(--white); border:1px solid var(--border-2); border-radius:var(--r-lg); padding:32px;
  transition:all .32s var(--ease); position:relative; overflow:hidden; box-shadow:var(--shadow-xs);
}
.stop:hover{box-shadow:var(--shadow-lg);transform:translateY(-5px);border-color:transparent}
.stop-mark{
  width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;
  background:var(--grad-primary);margin-bottom:22px;transition:all .3s var(--ease);box-shadow:0 6px 16px rgba(2,132,199,.28);
}
.stop-mark svg{width:26px;height:26px;stroke:#fff;stroke-width:1.8}
.stop:hover .stop-mark{transform:scale(1.08) rotate(-4deg)}
.stop:nth-child(2) .stop-mark{background:var(--grad-success);box-shadow:0 6px 16px rgba(16,185,129,.28)}
.stop:nth-child(3) .stop-mark{background:linear-gradient(135deg,#FCD34D,#F59E0B 60%,#D97706);box-shadow:0 6px 16px rgba(245,158,11,.28)}
.stop-idx{font-family:var(--mono);font-size:.72rem;font-weight:500;color:var(--muted-2);letter-spacing:.06em;margin-bottom:6px}
.stop h3{font-size:1.22rem;font-weight:700;color:var(--text-dark)}
.stop p{color:var(--muted);font-size:.94rem;margin-top:10px;line-height:1.65}

.stats-band{padding:0 0 88px}
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.stat{background:var(--white);border:1px solid var(--border-2);border-radius:var(--r);padding:28px;text-align:center;transition:all .3s var(--ease);box-shadow:var(--shadow-xs)}
.stat:hover{box-shadow:var(--shadow);transform:translateY(-3px);border-color:transparent}
.stat .n{font-family:var(--disp);font-weight:800;font-size:2.5rem;line-height:1;background:var(--grad-primary);-webkit-background-clip:text;background-clip:text;color:transparent}
.stat .n .u{color:var(--green-dark);font-size:1.4rem;-webkit-text-fill-color:var(--green-dark)}
.stat .l{color:var(--muted);font-size:.87rem;margin-top:10px;line-height:1.5;font-weight:500}
.stats-src{text-align:center;color:var(--muted-2);font-size:.8rem;margin-top:24px}

.q-shell{max-width:820px;margin:0 auto;padding:56px 28px 96px}
.q-route{position:relative;display:flex;justify-content:space-between;margin:36px 4px 40px}
.q-route::before{content:"";position:absolute;top:18px;left:22px;right:22px;height:3px;background:var(--border);border-radius:2px}
.q-route .trail{position:absolute;top:18px;left:22px;height:3px;border-radius:2px;background:linear-gradient(90deg,var(--blue),var(--green));transition:width .5s var(--ease);max-width:calc(100% - 44px)}
.q-dot{position:relative;z-index:1;width:38px;height:38px;border-radius:50%;background:var(--white);border:2.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;color:var(--muted-2);transition:all .35s var(--ease)}
.q-dot span{position:absolute;top:46px;left:50%;transform:translateX(-50%);font-size:.72rem;font-weight:600;color:var(--muted-2);white-space:nowrap}
.q-dot.done{background:var(--green);border-color:var(--green);color:#fff}
.q-dot.now{background:var(--grad-primary);border-color:var(--blue);color:#fff;box-shadow:0 0 0 6px var(--blue-pale),0 4px 12px rgba(2,132,199,.3);transform:scale(1.1)}
.q-dot.now span,.q-dot.done span{color:var(--text-dark);font-weight:700}

.q-card{background:var(--white);border:1px solid var(--border-2);border-radius:var(--r-lg);box-shadow:var(--shadow);padding:44px;margin-top:28px}
.q-step h2{font-size:1.5rem;color:var(--text-dark);font-weight:800}
.q-step .hint{color:var(--muted);font-size:.94rem;margin:8px 0 28px}

.field{margin-bottom:20px}
.field label{display:block;font-weight:700;font-size:.88rem;color:var(--text-dark);margin-bottom:9px}
.field input,.field textarea{
  width:100%;padding:13px 16px;border:1.6px solid var(--border);border-radius:var(--r-sm);
  font-family:var(--body);font-size:1rem;background:var(--bg);transition:all .2s;color:var(--text)
}
.field input:focus,.field textarea:focus{outline:none;border-color:var(--blue);background:var(--white);box-shadow:0 0 0 4px var(--blue-pale)}
.field-error{font-size:.8rem;color:var(--red);margin-top:8px;font-weight:600}

select{
  appearance:none;-webkit-appearance:none;-moz-appearance:none;
  width:100%;padding:13px 40px 13px 16px;border:1.6px solid var(--border);border-radius:var(--r-sm);
  font-family:var(--body);font-size:.95rem;font-weight:500;color:var(--text-dark);background-color:var(--bg);
  cursor:pointer;transition:all .2s var(--ease);
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748B' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
  background-repeat:no-repeat;background-position:right 14px center;background-size:16px;
}
select:hover{border-color:var(--blue)}
select:focus{outline:none;border-color:var(--blue);background-color:var(--white);box-shadow:0 0 0 4px var(--blue-pale)}
select:disabled{opacity:.5;cursor:not-allowed}
select::-ms-expand{display:none}

.grid2{display:grid;grid-template-columns:1fr 1fr;gap:18px}

.geo-box{display:flex;gap:16px;align-items:center;padding:20px;border-radius:var(--r);background:var(--blue-pale-2);border:1px solid var(--blue-pale)}
.geo-box .st{flex:1;font-size:.93rem;color:var(--muted)}
.geo-box .st b{color:var(--text-dark)}

.choice-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
.choice{
  padding:20px 14px;border:1.6px solid var(--border);border-radius:var(--r);text-align:center;font-size:.88rem;font-weight:600;
  color:var(--text);background:var(--white);transition:all .22s var(--ease);display:flex;flex-direction:column;align-items:center;gap:11px
}
.choice svg{width:24px;height:24px;stroke:var(--muted);stroke-width:1.8;transition:all .2s}
.choice:hover{border-color:var(--blue);transform:translateY(-2px);box-shadow:var(--shadow-sm)}
.choice.sel{border-color:var(--blue);background:var(--blue-pale-2);color:var(--blue-deep);transform:scale(1.03);box-shadow:var(--shadow-sm)}
.choice.sel svg{stroke:var(--blue)}

.body-wrap{display:grid;grid-template-columns:auto 1fr;gap:36px;align-items:start}
.body-svg{width:200px;margin:0 auto}
.zone{fill:#EAF1F7;stroke:#94A3B8;stroke-width:1.2;cursor:pointer;transition:all .25s var(--ease)}
.zone:hover{fill:var(--blue-pale)}
.zone.sel{fill:var(--blue);stroke:var(--blue-dark);filter:drop-shadow(0 3px 8px rgba(2,132,199,.35))}
.zone-list{display:grid;grid-template-columns:1fr 1fr;gap:11px}
.zone-tag{padding:13px 15px;border:1.6px solid var(--border);border-radius:var(--r-sm);font-size:.87rem;text-align:center;transition:all .2s;font-weight:600;background:var(--white)}
.zone-tag:hover{border-color:var(--blue);background:var(--blue-pale-2)}
.zone-tag.sel{background:var(--blue);border-color:var(--blue);color:#fff;box-shadow:var(--shadow-sm)}

.urg-slider{width:100%;accent-color:var(--blue);height:8px;margin:16px 0 8px;border-radius:4px}
.urg-scale{display:flex;justify-content:space-between;font-size:.8rem;color:var(--muted);font-weight:500}
.urg-val{font-family:var(--disp);font-size:3.4rem;font-weight:800;text-align:center;color:var(--green-dark);transition:color .3s}
.urg-val.mid{color:var(--amber)}
.urg-val.high{color:var(--red)}
.urg-zone{transition:background .4s var(--ease);border-radius:var(--r-lg);padding:28px;margin:0 -28px}
.alarm{display:flex;align-items:center;gap:13px;padding:14px 16px;border:1.6px solid var(--border);border-radius:var(--r-sm);margin-bottom:10px;font-size:.92rem;transition:all .2s;background:var(--white)}
.alarm:hover{border-color:var(--red)}
.alarm input{width:18px;height:18px;accent-color:var(--red);flex:none}
.alarm.sel{background:var(--red-pale);border-color:var(--red);font-weight:700;color:var(--red-dark)}
.q-nav{display:flex;justify-content:space-between;margin-top:36px;gap:14px}

/* ====== URGENCE (page dédiée) ====== */
.urgence-screen{min-height:calc(100vh - 72px);background:linear-gradient(160deg,#7F1D1D,var(--red));display:flex;align-items:center;justify-content:center;padding:56px 28px}
.urg-card{max-width:560px;text-align:center;color:#fff}
.urg-card .mk{width:88px;height:88px;margin:0 auto;border-radius:50%;background:rgba(255,255,255,.16);display:flex;align-items:center;justify-content:center;animation:pulse-big 1.2s infinite}
.urg-card .mk svg{width:42px;height:42px;stroke:#fff;stroke-width:1.8}
@keyframes pulse-big{0%,100%{box-shadow:0 0 0 0 rgba(255,255,255,.35)}55%{box-shadow:0 0 0 18px rgba(255,255,255,0)}}
.urg-card h2{font-weight:800;font-size:2.1rem;margin:24px 0 12px}
.urg-card p{color:#FECACA;font-size:1.04rem;line-height:1.7}
.urg-actions{display:flex;gap:16px;justify-content:center;margin-top:32px;flex-wrap:wrap}
.btn-white{background:#fff;color:var(--red-dark);font-weight:800;font-size:1.1rem;padding:17px 32px;border-radius:var(--r-pill)}
.btn-white:hover{transform:translateY(-2px) scale(1.02);box-shadow:0 12px 28px rgba(0,0,0,.25)}

.res-head{padding:44px 0 28px}
.res-badge{display:inline-flex;gap:9px;align-items:center;background:var(--green-pale);color:var(--green-dark);padding:9px 18px;border-radius:var(--r-pill);font-weight:700;font-size:.88rem}
.res-grid{display:grid;grid-template-columns:410px 1fr;gap:24px;padding-bottom:88px}
/* Le badge « Le plus proche » chevauche le bord haut de la première carte
   (top:-11px). Comme cette liste défile (overflow:auto), il faut lui réserver
   de la place en haut, sinon le badge est rogné. */
.res-list{display:flex;flex-direction:column;gap:14px;max-height:640px;overflow:auto;padding:14px 4px 2px 2px}
.res-card{background:var(--white);border:1.6px solid var(--border-2);border-radius:var(--r);padding:22px;transition:all .28s var(--ease);position:relative;box-shadow:var(--shadow-xs)}
.res-card.top-pick{border-color:var(--blue);box-shadow:0 0 0 3px var(--blue-pale)}
.res-card:hover{box-shadow:var(--shadow-lg);transform:translateY(-2px);border-color:var(--blue)}
.res-card .badge-top{position:absolute;top:-11px;left:18px;z-index:2;display:inline-block;white-space:nowrap;line-height:1.45;background:var(--grad-primary);color:#fff;font-size:.68rem;font-weight:700;padding:3px 12px;border-radius:var(--r-pill);letter-spacing:.02em;box-shadow:0 4px 10px rgba(2,132,199,.35)}
.res-card .top{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
.res-card h3{font-size:1.08rem;color:var(--text-dark);font-weight:700}
.res-card .type{font-size:.74rem;font-weight:600;color:var(--muted-2);text-transform:uppercase;letter-spacing:.04em}
.res-card .rank{width:32px;height:32px;flex:none;border-radius:50%;background:var(--blue-pale);color:var(--blue-dark);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem}
.res-card .meta{display:flex;gap:16px;margin-top:12px;font-size:.86rem;color:var(--muted);flex-wrap:wrap}
.res-card .meta span{display:flex;align-items:center;gap:5px}
.res-card .meta svg{width:14px;height:14px;stroke-width:2}
.res-card .meta b{color:var(--text-dark)}
.res-card .act{display:flex;justify-content:flex-end;margin-top:14px}
#map{height:640px;border-radius:var(--r-lg);border:1px solid var(--border-2);box-shadow:var(--shadow);z-index:1;overflow:hidden}
.leaflet-container{font-family:var(--body)}
.leaflet-popup-content-wrapper{border-radius:14px;box-shadow:var(--shadow-lg);padding:2px}
.leaflet-popup-content{margin:14px 16px;font-family:var(--body);font-size:.87rem;color:var(--text);line-height:1.55}
.leaflet-popup-content b{color:var(--blue-deep);font-size:.95rem}
.leaflet-popup-tip{box-shadow:var(--shadow-sm)}
.leaflet-control-zoom{border-radius:12px!important;overflow:hidden;box-shadow:var(--shadow-sm)!important;border:1px solid var(--border-2)!important}
.leaflet-control-zoom a{width:34px!important;height:34px!important;line-height:34px!important;background:var(--white)!important;color:var(--text-dark)!important;font-weight:700!important;border-color:var(--border-2)!important}
.leaflet-control-zoom a:hover{background:var(--blue-pale-2)!important;color:var(--blue-dark)!important}
.leaflet-control-attribution{background:rgba(255,255,255,.85)!important;border-radius:8px 0 0 0;font-size:.68rem!important;padding:2px 8px!important}
.mg-marker{animation:markerDrop .5s cubic-bezier(.3,1.4,.4,1) backwards}
.mg-marker-0{animation-delay:0ms}.mg-marker-1{animation-delay:90ms}.mg-marker-2{animation-delay:180ms}
.mg-marker-3{animation-delay:270ms}.mg-marker-4{animation-delay:360ms}
@keyframes markerDrop{from{transform:translateY(-16px) scale(.4);opacity:0}to{transform:none;opacity:1}}
.mg-pin{width:34px;height:38px;position:relative;cursor:pointer;transition:transform .22s var(--ease);filter:drop-shadow(0 3px 6px rgba(0,0,0,.3))}
.mg-pin::before{content:"";position:absolute;top:0;left:2px;width:30px;height:30px;border-radius:50% 50% 50% 0;background:var(--pin-color);transform:rotate(-45deg);border:2.5px solid #fff}
.mg-pin span{position:absolute;top:5px;left:0;width:34px;text-align:center;color:#fff;font-family:Inter,sans-serif;font-size:12.5px;font-weight:800;z-index:1}
.mg-pin:hover{transform:scale(1.16) translateY(-3px)}
.mg-you-marker{animation:pulse-dot 2s infinite}

.cal-shell{max-width:980px;margin:0 auto;padding:52px 28px 96px}
.cal-doc{display:flex;gap:18px;align-items:center;background:var(--white);border:1px solid var(--border-2);border-radius:var(--r-lg);padding:22px 26px;box-shadow:var(--shadow-sm)}
.ava{width:58px;height:58px;border-radius:16px;background:var(--grad-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.2rem;flex:none;box-shadow:0 6px 16px rgba(2,132,199,.28)}
.cal-doc h3{font-size:1.14rem;font-weight:700;color:var(--text-dark)}
.cal-doc .s{color:var(--muted);font-size:.9rem}
.cal-week{display:flex;justify-content:space-between;align-items:center;margin:32px 0 18px}
.cal-week h4{font-size:1.15rem;font-weight:700;color:var(--text-dark)}
.wk-btn{width:40px;height:40px;border-radius:50%;border:1.6px solid var(--border);background:var(--white);color:var(--text-dark);font-size:1.1rem;transition:.2s}
.wk-btn:hover{border-color:var(--blue);background:var(--blue-pale-2);color:var(--blue-dark)}
.cal-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px}
.cal-day{background:var(--white);border:1px solid var(--border-2);border-radius:var(--r);overflow:hidden;transition:box-shadow .2s}
.cal-day:hover{box-shadow:var(--shadow-sm)}
.cal-day .dh{background:var(--blue-pale-2);color:var(--blue-deep);text-align:center;padding:12px 6px;font-size:.86rem;font-weight:700}
.cal-day .dh small{display:block;color:var(--blue-dark);font-weight:500;font-size:.74rem;opacity:.75}
.slots{padding:11px;display:flex;flex-direction:column;gap:8px;min-height:220px}
.slot{padding:11px 6px;border-radius:var(--r-sm);text-align:center;font-size:.83rem;font-weight:700;transition:all .2s;width:100%}
.slot.free{background:var(--green-pale);color:var(--green-dark)}
.slot.free:hover{background:var(--green);color:#fff;transform:scale(1.04);box-shadow:var(--shadow-sm)}
.slot.busy{background:var(--bg);color:var(--muted-2);cursor:not-allowed}
.cal-legend{display:flex;gap:24px;margin-top:20px;font-size:.85rem;color:var(--muted)}
.lg{display:flex;gap:8px;align-items:center}
.lg i{width:12px;height:12px;border-radius:4px;display:inline-block}

.dash-shell{padding:44px 0 96px}
.dash-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px}
.kpi{background:var(--white);border:1px solid var(--border-2);border-radius:var(--r-lg);padding:26px;position:relative;overflow:hidden;transition:all .28s var(--ease);box-shadow:var(--shadow-xs)}
.kpi:hover{box-shadow:var(--shadow-lg);transform:translateY(-4px);border-color:transparent}
.kpi .ic{width:44px;height:44px;border-radius:13px;background:var(--grad-primary);display:flex;align-items:center;justify-content:center;margin-bottom:16px;box-shadow:0 6px 14px rgba(2,132,199,.26)}
.kpi .ic svg{width:22px;height:22px;stroke:#fff;stroke-width:1.8}
.kpi .n{font-family:var(--disp);font-weight:800;font-size:2.3rem;color:var(--text-dark)}
.kpi .l{color:var(--muted);font-size:.88rem;margin-top:6px;font-weight:500}
.kpi .tag{position:absolute;top:22px;right:22px;font-size:.72rem;font-weight:700;padding:5px 11px;border-radius:var(--r-pill);background:var(--green-pale);color:var(--green-dark)}
.panel{background:var(--white);border:1px solid var(--border-2);border-radius:var(--r-lg);padding:28px;margin-bottom:20px;box-shadow:var(--shadow-xs)}
.panel h3{font-size:1.15rem;font-weight:700;color:var(--text-dark);margin-bottom:18px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
.panel .panel-note{font-size:.86rem;color:var(--muted);margin:-8px 0 16px}
.row-item{display:flex;gap:14px;align-items:center;padding:15px 4px;border-bottom:1px solid var(--border-2)}
.row-item:last-child{border:none}
.row-item .grow{flex:1}
.row-item h4{font-size:.96rem;color:var(--text-dark);font-weight:700}
.row-item .sub{font-size:.83rem;color:var(--muted);margin-top:2px}
.pill{font-size:.74rem;font-weight:700;padding:6px 13px;border-radius:var(--r-pill);display:inline-block}
.pill.g{background:var(--green-pale);color:var(--green-dark)}
.pill.o{background:var(--amber-pale);color:#B45309}
.pill.r{background:var(--red-pale);color:var(--red-dark)}
.pill.b{background:var(--blue-pale);color:var(--blue-dark)}
.donut-wrap{display:flex;align-items:center;gap:28px;flex-wrap:wrap}
.donut-svg{width:168px;height:168px;flex:none}
.donut-center{font-family:var(--disp)}
.donut-legend{display:flex;flex-direction:column;gap:12px;flex:1;min-width:180px}
.dl-item{display:flex;align-items:center;gap:10px;font-size:.86rem}
.dl-item i{width:11px;height:11px;border-radius:3px;flex:none}
.dl-item .lbl{color:var(--text);font-weight:600;flex:1}
.dl-item .val{color:var(--text-dark);font-weight:800}
.donut-seg{transition:stroke-dasharray 1s cubic-bezier(.22,.8,.3,1)}
.notif{display:flex;gap:13px;padding:13px 4px;border-bottom:1px solid var(--border-2);font-size:.88rem;align-items:flex-start}
.notif:last-child{border:none}
.notif .ic{width:36px;height:36px;flex:none;border-radius:11px;display:flex;align-items:center;justify-content:center;background:var(--blue-pale)}
.notif .ic svg{width:17px;height:17px;stroke:var(--blue-dark);stroke-width:1.9}

footer{background:var(--text-dark);color:#94A3B8;padding:56px 0 32px;margin-top:0}
.foot-grid{display:grid;grid-template-columns:1.4fr 1fr 1fr;gap:40px;padding-bottom:36px;border-bottom:1px solid rgba(255,255,255,.1)}
.foot-grid h4{color:#fff;margin-bottom:16px;font-size:1.05rem;font-weight:700}
.foot-grid a{display:flex;align-items:center;gap:8px;color:#94A3B8;text-decoration:none;padding:5px 0;font-size:.92rem;transition:color .2s}
.foot-grid a:hover{color:var(--blue-pale)}
.foot-grid a svg{width:15px;height:15px;stroke-width:2}
.foot-base{display:flex;justify-content:space-between;gap:14px;padding-top:24px;font-size:.83rem;flex-wrap:wrap}

.auth-shell{max-width:920px;margin:0 auto;padding:64px 28px 96px}
.auth-shell-in{background:var(--white);border-radius:var(--r-lg);box-shadow:var(--shadow-lg);overflow:hidden;display:grid;grid-template-columns:1fr 1fr}
.auth-side{background:linear-gradient(160deg,var(--blue-deep),var(--blue));padding:48px 40px;color:#fff;display:flex;flex-direction:column;justify-content:space-between}
.auth-side h3{font-size:1.5rem;font-weight:800;line-height:1.3}
.auth-side p{color:#BAE6FD;font-size:.92rem;margin-top:14px;line-height:1.7}
.auth-role-list{display:flex;flex-direction:column;gap:10px;margin-top:28px}
.auth-role-btn{display:flex;align-items:center;gap:12px;padding:13px 16px;border-radius:12px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.16);color:#fff;font-weight:600;font-size:.86rem;transition:all .22s var(--ease);text-align:left}
.auth-role-btn:hover{background:rgba(255,255,255,.2);transform:translateX(3px)}
.auth-role-btn svg{width:17px;height:17px;flex:none}
.auth-role-btn small{display:block;color:#BAE6FD;font-weight:400;font-size:.76rem;margin-top:1px}
.auth-form{padding:48px 40px}
.auth-form h2{font-size:1.4rem;font-weight:800;color:var(--text-dark)}
.auth-form .sub{color:var(--muted);font-size:.9rem;margin-top:6px;margin-bottom:28px}
.auth-tabs{display:flex;border-bottom:1.6px solid var(--border);margin-bottom:24px}
.auth-tab{padding:10px 4px;margin-right:24px;font-weight:700;font-size:.9rem;color:var(--muted-2);border-bottom:2px solid transparent;transform:translateY(1.6px)}
.auth-tab.on{color:var(--blue-dark);border-color:var(--blue)}
/* Renvoi vers l'inscription médecin, sous le formulaire patient. */
.auth-switch{display:flex;align-items:center;gap:12px;margin-top:22px;padding:14px 16px;border:1.6px solid var(--border-2);border-radius:var(--r-sm);background:var(--blue-pale);transition:all .22s var(--ease)}
.auth-switch:hover{border-color:var(--blue);box-shadow:var(--shadow-sm)}
.auth-switch svg{width:20px;height:20px;flex:none;color:var(--blue-dark)}
.auth-switch span{display:flex;flex-direction:column;gap:2px;flex:1}
.auth-switch b{font-size:.9rem;color:var(--text-dark);font-weight:700}
.auth-switch small{font-size:.78rem;color:var(--muted);line-height:1.4}
.auth-switch .auth-switch-go{width:16px;height:16px}
.session-chip{display:inline-flex;align-items:center;gap:9px;background:var(--green-pale);color:var(--green-dark);font-weight:700;font-size:.84rem;padding:8px 16px;border-radius:var(--r-pill);margin-bottom:24px}
.session-chip svg{width:15px;height:15px}
@media(max-width:760px){.auth-shell-in{grid-template-columns:1fr}.auth-side{padding:36px 28px}.auth-form{padding:36px 28px}}

/* ====== TOAST (messages flash) ====== */
.toast-wrap{position:fixed;bottom:28px;left:50%;transform:translateX(-50%);z-index:4000;display:flex;flex-direction:column;gap:10px;align-items:center;max-width:92vw}
.toast{background:var(--text-dark);color:#fff;padding:15px 24px;border-radius:var(--r-pill);display:flex;gap:12px;align-items:center;font-size:.92rem;font-weight:500;box-shadow:0 16px 36px rgba(0,0,0,.28)}
.toast svg{width:18px;height:18px;stroke:var(--green);flex:none;stroke-width:2.2}
.toast.err svg{stroke:var(--red)}

/* ====== RESPONSIVE ====== */
@media(max-width:1020px){
  .hero-in{grid-template-columns:1fr;padding:64px 28px 72px}
  .res-grid{grid-template-columns:1fr}
  #map{height:420px}
  .res-list{max-height:none;order:2}
  .two-col{grid-template-columns:1fr}
  .stats{grid-template-columns:repeat(2,1fr)}
  .dash-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:720px){
  .nav-links{position:fixed;top:72px;right:0;left:0;background:var(--white);flex-direction:column;align-items:stretch;padding:16px 24px 22px;gap:6px;border-bottom:1px solid var(--border-2);transform:translateY(-130%);transition:transform .35s;box-shadow:var(--shadow)}
  .nav-links.open{transform:none}
  .nav-link{justify-content:center}
  .burger{display:flex}
  .route{grid-template-columns:1fr;gap:20px}
  .grid2,.choice-grid,.zone-list{grid-template-columns:1fr 1fr}
  .body-wrap{grid-template-columns:1fr}
  .q-card{padding:28px 20px}
  .cal-grid{grid-template-columns:repeat(2,1fr)}
  .dash-grid{grid-template-columns:1fr}
  .foot-grid{grid-template-columns:1fr}
  .q-dot span{display:none}
  .hero-cta .btn{width:100%;justify-content:center}
  .hero-stats{gap:22px}
}
@media(max-width:460px){.choice-grid{grid-template-columns:1fr}.cal-grid{grid-template-columns:1fr}}

/* ====== ORDONNANCE ====== */
.rx-shell{max-width:620px;margin:0 auto;padding:52px 28px 96px}
.rx-paper{background:#fff;border:1px solid var(--border);border-radius:var(--r);overflow:hidden}
.rx-head{background:var(--blue-pale-2);padding:22px 28px;display:flex;justify-content:space-between;align-items:flex-start;border-bottom:1px dashed var(--border)}
.rx-head h4{font-size:1.05rem;font-weight:800;color:var(--blue-deep)}
.rx-head .sub{font-size:.8rem;color:var(--muted);margin-top:3px}
.rx-lock{display:flex;align-items:center;gap:6px;font-size:.72rem;font-weight:700;color:var(--green-dark);background:var(--green-pale);padding:5px 10px;border-radius:var(--r-pill)}
.rx-lock svg{width:12px;height:12px}
.rx-body{padding:26px 28px}
.rx-row{display:flex;justify-content:space-between;font-size:.86rem;color:var(--muted);margin-bottom:6px}
.rx-row b{color:var(--text-dark)}
.rx-meds{margin-top:18px;border-top:1.6px dashed var(--border);padding-top:18px}
.rx-med{display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--border-2)}
.rx-med:last-child{border:none}
.rx-med .n{width:26px;height:26px;border-radius:50%;background:var(--blue-pale);color:var(--blue-dark);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.78rem;flex:none}
.rx-med h5{font-size:.92rem;font-weight:700;color:var(--text-dark)}
.rx-med span{font-size:.82rem;color:var(--muted);white-space:pre-wrap}
.rx-sign{margin-top:22px;padding-top:16px;border-top:1px solid var(--border-2);display:flex;justify-content:space-between;align-items:center}
.rx-sign .who{font-size:.86rem;color:var(--muted)}
.rx-sign .who b{color:var(--text-dark);display:block;font-size:.94rem}
.rx-acts{display:flex;justify-content:flex-end;gap:10px;padding:16px 20px;background:var(--bg)}
@media print{
  body *{visibility:hidden}
  .rx-paper, .rx-paper *{visibility:visible}
  .rx-paper{position:fixed;top:0;left:0;width:100%;margin:0;box-shadow:none;border:none}
  .rx-lock,.rx-acts,.nav,footer{display:none}
  @page{margin:14mm}
}
</style>
@livewireStyles
</head>
<body x-data="{ navOpen:false }">

<!-- ============================================================
     ICONES — sprite SVG inline, trait fin cohérent (MediGuide_Demo.html)
     ============================================================ -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
<defs>
  <linearGradient id="ezGrad" x1="0" y1="0" x2="1" y2="1">
    <stop offset="0%" stop-color="#0EA5E9"/><stop offset="100%" stop-color="#0369A1"/>
  </linearGradient>
  <symbol id="i-compass" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M15 9l-2 6-6 2 2-6z"/></symbol>
  <symbol id="i-pin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7-5.5-7-11a7 7 0 0114 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.4"/></symbol>
  <symbol id="i-cal" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M8 3v4M16 3v4M3.5 10.5h17"/></symbol>
  <symbol id="i-alert" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l10 18H2z" stroke-linejoin="round"/><path d="M12 10v4"/><circle cx="12" cy="17" r=".6" fill="currentColor" stroke="none"/></symbol>
  <symbol id="i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 12l6 6L20 6" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-mail" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6l8.5 7 8.5-7"/></symbol>
  <symbol id="i-doc" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M9 13h6M9 16.5h6"/></symbol>
  <symbol id="i-shield" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l8 3v6c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V6z"/></symbol>
  <symbol id="i-heart" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20s-7.5-4.7-9.7-9.4C.8 7 2.3 3.8 5.6 3.2 8 2.8 10 4 12 6.5 14 4 16 2.8 18.4 3.2c3.3.6 4.8 3.8 3.3 7.4C19.5 15.3 12 20 12 20z"/></symbol>
  <symbol id="i-lungs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3v9M12 12c-2 0-4 1-4 5s1 4 3 3 1-6 1-8M12 12c2 0 4 1 4 5s-1 4-3 3-1-6-1-8"/></symbol>
  <symbol id="i-stomach" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 4c0 3-3 3-3 7a7 7 0 0014 0c0-2-1-3-2.5-3S14 9 12 9s-2-5-4-5z"/></symbol>
  <symbol id="i-skin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M8 5C5 6 4 9 5 12s4 4 4 8"/><circle cx="14" cy="9" r="1" fill="currentColor" stroke="none"/><circle cx="17" cy="14" r="1" fill="currentColor" stroke="none"/><circle cx="12" cy="16" r="1" fill="currentColor" stroke="none"/></symbol>
  <symbol id="i-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.6"/></symbol>
  <symbol id="i-ear" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 5a5 5 0 015 5c0 3-2 3-2 6a2.5 2.5 0 01-5 0"/><path d="M9 5C6 5 4 7.5 4 11c0 5 4 4 4 8"/></symbol>
  <symbol id="i-preg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="6" r="2.2"/><path d="M9 9c-3 0-4 3-4 6M13 20c2-3 3-9-1-11"/></symbol>
  <symbol id="i-child" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="6" r="2.4"/><path d="M8 20v-5a4 4 0 018 0v5"/></symbol>
  <symbol id="i-brain" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 4a3 3 0 00-3 3 3 3 0 000 8 3 3 0 003 3M15 4a3 3 0 013 3 3 3 0 010 8 3 3 0 01-3 3M9 4v14M15 4v14"/></symbol>
  <symbol id="i-tooth" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 4c-2.5 0-5 1-5 4.5 0 3 1 5.5 2 9 .5 1.7 2 1.7 2.3 0 .3-1.7.7-3 1.7-3s1.4 1.3 1.7 3c.3 1.7 1.8 1.7 2.3 0 1-3.5 2-6 2-9C19 5 16.5 4 14 4z"/></symbol>
  <symbol id="i-kit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="8" width="18" height="12" rx="2"/><path d="M9 8V6a2 2 0 012-2h2a2 2 0 012 2v2M12 12v4M10 14h4"/></symbol>
  <symbol id="i-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M4 12h16M14 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></symbol>
  <symbol id="i-target" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="4.5"/><circle cx="12" cy="12" r=".7" fill="currentColor" stroke="none"/></symbol>
  <symbol id="i-users" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 016-6"/><circle cx="17" cy="9" r="2.4"/><path d="M14 20v-1a5 5 0 016 0"/></symbol>
  <symbol id="i-lock" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="4.5" y="10.5" width="15" height="10" rx="2"/><path d="M8 10.5V7a4 4 0 018 0v3.5"/></symbol>
  <symbol id="i-bone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 17L17 7"/><circle cx="6" cy="18" r="2.6"/><circle cx="18" cy="6" r="2.6"/></symbol>
  <symbol id="i-drop" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3s6.5 7.2 6.5 11.5a6.5 6.5 0 01-13 0C5.5 10.2 12 3 12 3z"/></symbol>
</defs>
</svg>

<nav class="nav">
  <div class="nav-in">
    <a class="brand" href="{{ route('accueil') }}">
      <svg class="mk" viewBox="0 0 34 34" fill="none" aria-hidden="true">
        <circle cx="17" cy="17" r="16" fill="#E0F2FE"/>
        <path d="M17 8 L19.5 16.5 L17 15 L14.5 16.5 Z" fill="#0284C7"/>
        <path d="M17 26 L14.5 17.5 L17 19 L19.5 17.5 Z" fill="#10B981"/>
        <circle cx="17" cy="17" r="1.8" fill="#1E293B"/>
      </svg>
      MediGuide
    </a>
    <div class="nav-links" :class="{ open: navOpen }">
      @auth
        @if (auth()->user()->role === 'patient')
          <a class="nav-link {{ request()->routeIs('accueil') ? 'on' : '' }}" href="{{ route('accueil') }}"><svg><use href="#i-compass"/></svg>Accueil</a>
          <a class="nav-link {{ request()->routeIs('orientation') ? 'on' : '' }}" href="{{ route('orientation') }}"><svg><use href="#i-target"/></svg>Orientation</a>
          <a class="nav-link {{ request()->routeIs('resultats') ? 'on' : '' }}" href="{{ route('resultats') }}"><svg><use href="#i-pin"/></svg>Structures</a>
          <a class="nav-link {{ request()->routeIs('dashboard') ? 'on' : '' }}" href="{{ route('dashboard') }}"><svg><use href="#i-cal"/></svg>Mes rendez-vous</a>
        @elseif (auth()->user()->role === 'medecin')
          <a class="nav-link {{ request()->routeIs('accueil') ? 'on' : '' }}" href="{{ route('accueil') }}"><svg><use href="#i-compass"/></svg>Accueil</a>
          <a class="nav-link {{ request()->routeIs('dashboard') ? 'on' : '' }}" href="{{ route('dashboard') }}"><svg><use href="#i-cal"/></svg>Mon agenda</a>
        @else
          <a class="nav-link {{ request()->routeIs('accueil') ? 'on' : '' }}" href="{{ route('accueil') }}"><svg><use href="#i-compass"/></svg>Accueil</a>
          <a class="nav-link {{ request()->routeIs('dashboard') ? 'on' : '' }}" href="{{ route('dashboard') }}"><svg><use href="#i-shield"/></svg>Administration</a>
        @endif
        <span class="cta-gap"></span>
        {{-- Le nom mène au profil (et non à la déconnexion, qui a son propre bouton). --}}
        <a class="btn btn-outline btn-sm {{ request()->routeIs('profil') ? 'on' : '' }}" href="{{ route('profil') }}">
            <svg><use href="#i-users"/></svg>{{ auth()->user()->fullName() }}
        </a>
        <form method="POST" action="{{ route('logout') }}" style="display:contents">
            @csrf
            <button class="btn btn-ghost btn-sm nav-cta" type="submit" title="Se déconnecter">Déconnexion</button>
        </form>
      @else
        <a class="nav-link {{ request()->routeIs('accueil') ? 'on' : '' }}" href="{{ route('accueil') }}"><svg><use href="#i-compass"/></svg>Accueil</a>
        <a class="nav-link {{ request()->routeIs('orientation') ? 'on' : '' }}" href="{{ route('orientation') }}"><svg><use href="#i-target"/></svg>Orientation</a>
        <a class="nav-link {{ request()->routeIs('resultats') ? 'on' : '' }}" href="{{ route('resultats') }}"><svg><use href="#i-pin"/></svg>Structures</a>
        <span class="cta-gap"></span>
        <a class="btn btn-outline btn-sm" href="{{ route('register') }}">S'inscrire</a>
        <a class="btn btn-primary btn-sm nav-cta" href="{{ route('login') }}">Se connecter <svg><use href="#i-arrow"/></svg></a>
      @endauth
    </div>
    <button class="burger" aria-label="Menu" @click="navOpen = !navOpen"><span></span><span></span><span></span></button>
  </div>
</nav>

<main>
{{ $slot ?? '' }}
@yield('content')

<footer>
  <div class="wrap">
    <div class="foot-grid">
      <div>
        <h4>MediGuide</h4>
        <p style="font-size:.9rem;max-width:30em;color:#94A3B8">Plateforme d'orientation et de prise de rendez-vous médicaux. Mémoire de fin d'études L3GL — Institut Supérieur d'Informatique, Dakar.</p>
      </div>
      <div>
        @auth
          @if (auth()->user()->role === 'patient')
            <h4>Mon parcours</h4>
            <a href="{{ route('orientation') }}"><svg><use href="#i-compass"/></svg>Questionnaire d'orientation</a>
            <a href="{{ route('resultats') }}"><svg><use href="#i-pin"/></svg>Structures proches</a>
            <a href="{{ route('dashboard') }}"><svg><use href="#i-cal"/></svg>Mes rendez-vous</a>
            <a href="{{ route('profil') }}"><svg><use href="#i-users"/></svg>Mon profil</a>
          @else
            <h4>Mon espace</h4>
            <a href="{{ route('dashboard') }}"><svg><use href="#i-{{ auth()->user()->role === 'admin' ? 'shield' : 'cal' }}"/></svg>{{ auth()->user()->role === 'admin' ? 'Administration' : 'Mon agenda' }}</a>
            <a href="{{ route('profil') }}"><svg><use href="#i-users"/></svg>Mon profil</a>
          @endif
        @else
          <h4>Accès</h4>
          <a href="{{ route('login') }}"><svg><use href="#i-shield"/></svg>Se connecter</a>
          <a href="{{ route('register') }}"><svg><use href="#i-check"/></svg>Créer un compte patient</a>
        @endauth
      </div>
      <div>
        <h4>Urgences</h4>
        <a href="tel:15"><svg><use href="#i-alert"/></svg>SAMU — 15</a>
        <a href="tel:18"><svg><use href="#i-alert"/></svg>Sapeurs-Pompiers — 18</a>
        <a href="{{ route('accueil') }}"><svg><use href="#i-pin"/></svg>Hôpital Roi Baudouin · Guédiawaye</a>
      </div>
    </div>
    <div class="foot-base">
      <span>© 2026 MediGuide · GUEYE Samba — ISI Dakar</span>
      <span>Laravel 11 · Leaflet / OpenStreetMap</span>
    </div>
  </div>
</footer>
</main>

@if (session('ok') || session('erreur') || $errors->any())
<div class="toast-wrap" x-data="{ show:true }" x-show="show" x-init="setTimeout(() => show=false, 6000)" x-transition>
    @if (session('ok'))
        <div class="toast"><svg><use href="#i-check"/></svg><span>{{ session('ok') }}</span></div>
    @endif
    @if (session('erreur'))
        <div class="toast err"><svg><use href="#i-alert"/></svg><span>{{ session('erreur') }}</span></div>
    @endif
    @foreach ($errors->all() as $error)
        <div class="toast err"><svg><use href="#i-alert"/></svg><span>{{ $error }}</span></div>
    @endforeach
</div>
@endif

@livewireScripts
<script>
// Compteur animé des statistiques de l'accueil (équivalent d'animCount() dans la
// maquette) : progression cubique sur 1,3 s, déclenchée à l'entrée dans le viewport.
document.addEventListener('alpine:init', () => {
    Alpine.data('compteur', (cible) => ({
        valeur: 0,
        demarrer() {
            let depart = null;
            const etape = (horodatage) => {
                if (! depart) depart = horodatage;
                const p = Math.min((horodatage - depart) / 1300, 1);
                this.valeur = Math.round(cible * (1 - Math.pow(1 - p, 3)));
                if (p < 1) requestAnimationFrame(etape);
            };
            requestAnimationFrame(etape);
        },
    }));
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
