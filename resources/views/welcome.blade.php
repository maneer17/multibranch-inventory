<!DOCTYPE html>
<html lang="en" dir="ltr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stoq — Multi-Branch Inventory Platform</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            /* Teal + Navy — financial trust palette */
            --bg: #f8fafb;
            --bg-alt: #f0f4f5;
            --surface: #ffffff;
            --text: #0f2b3c;
            --text-s: #4a6274;
            --text-t: #8da3b1;
            --accent: #0d9488;
            --accent-h: #0a7a70;
            --accent-l: #ccfbf1;
            --navy: #0f2b3c;
            --navy-l: #e8eef2;
            --green: #059669;
            --green-l: #ecfdf5;
            --amber: #d97706;
            --amber-l: #fffbeb;
            --blue: #2563eb;
            --blue-l: #eff6ff;
            --red: #dc2626;
            --red-l: #fef2f2;
            --border: #dce5ea;
            --border-s: #c4d1d9;
            --font-en: 'Inter', system-ui, -apple-system, sans-serif;
            --font-ar: 'IBM Plex Sans Arabic', 'Inter', system-ui, sans-serif;
            --radius: 12px;
            --shadow-s: 0 1px 2px rgba(15,43,60,0.04);
            --shadow: 0 1px 3px rgba(15,43,60,0.07), 0 1px 2px rgba(15,43,60,0.04);
            --shadow-l: 0 4px 14px rgba(15,43,60,0.06);
            --nav-bg: rgba(248,250,251,0.9);
            --term-bg: #0f2b3c;
            --term-bar: #0a1f2c;
            --term-text: #94d2bd;
            --proof-bg: #0f2b3c;
        }

        html.dark {
            --bg: #0a1a24;
            --bg-alt: #0f2430;
            --surface: #132e3c;
            --text: #e8f0f4;
            --text-s: #94b4c4;
            --text-t: #5a7f90;
            --accent-l: rgba(13,148,136,0.18);
            --navy-l: rgba(15,43,60,0.5);
            --green-l: rgba(5,150,105,0.15);
            --amber-l: rgba(217,119,6,0.15);
            --blue-l: rgba(37,99,235,0.15);
            --red-l: rgba(220,38,38,0.15);
            --border: #1e3d4e;
            --border-s: #2a5060;
            --nav-bg: rgba(10,26,36,0.9);
            --shadow-s: 0 1px 2px rgba(0,0,0,0.2);
            --shadow: 0 1px 3px rgba(0,0,0,0.3);
            --shadow-l: 0 4px 14px rgba(0,0,0,0.25);
            --term-bg: #081820;
            --term-bar: #060f16;
            --proof-bg: #081820;
        }

        body {
            font-family: var(--font-en);
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            transition: background 0.3s, color 0.3s;
        }

        html[dir="rtl"] body { font-family: var(--font-ar); }
        html[dir="rtl"] .t-body { direction: ltr; text-align: left; }
        html[dir="rtl"] .btn svg, html[dir="rtl"] .nav-cta svg { transform: scaleX(-1); }

        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }

        /* ── Nav ── */
        .nav {
            position: sticky; top: 0; z-index: 50;
            background: var(--nav-bg);
            backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid var(--border); padding: 0 1.5rem;
        }
        .nav-inner {
            max-width: 1080px; margin: 0 auto;
            display: flex; align-items: center; justify-content: space-between; height: 56px;
        }
        .nav-brand {
            font-weight: 800; font-size: 1.15rem; color: var(--text);
            letter-spacing: -0.03em; display: flex; align-items: center; gap: 0.5rem;
        }
        .nav-brand:hover { text-decoration: none; }
        .nav-right { display: flex; align-items: center; gap: 0.5rem; list-style: none; }
        .nav-right a.nl {
            color: var(--text-s); font-size: 0.88rem; font-weight: 500;
            padding: 0.3rem 0.6rem; border-radius: 6px;
        }
        .nav-right a.nl:hover { color: var(--text); text-decoration: none; }
        .nav-cta {
            background: var(--accent); color: #fff !important;
            padding: 0.4rem 1rem; border-radius: 8px; font-weight: 600; font-size: 0.85rem;
            display: inline-flex; align-items: center; gap: 0.35rem; transition: background 0.15s;
        }
        .nav-cta:hover { background: var(--accent-h); text-decoration: none !important; }
        .tb {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 8px; padding: 0.35rem 0.55rem; cursor: pointer;
            font-size: 0.82rem; font-weight: 600; color: var(--text-s);
            display: flex; align-items: center; gap: 0.25rem; transition: all 0.15s;
        }
        .tb:hover { border-color: var(--border-s); color: var(--text); }

        /* ── Buttons ── */
        .btn {
            display: inline-flex; align-items: center; gap: 0.45rem;
            padding: 0.7rem 1.5rem; border-radius: 10px; font-weight: 600;
            font-size: 0.9rem; border: none; cursor: pointer; transition: all 0.15s;
        }
        .btn-p { background: var(--accent); color: #fff; box-shadow: 0 1px 3px rgba(13,148,136,0.25); }
        .btn-p:hover { background: var(--accent-h); text-decoration: none; }
        .btn-s { background: var(--surface); color: var(--text); border: 1px solid var(--border-s); box-shadow: var(--shadow-s); }
        .btn-s:hover { background: var(--bg-alt); text-decoration: none; }

        /* ── Hero ── */
        .hero { max-width: 1080px; margin: 0 auto; padding: 5rem 1.5rem 3.5rem; text-align: center; }
        .hero h1 {
            font-size: clamp(2rem, 4.5vw, 2.85rem); font-weight: 800;
            letter-spacing: -0.035em; line-height: 1.18; max-width: 700px; margin: 0 auto 1rem;
        }
        .hero > p { font-size: 1.05rem; color: var(--text-s); max-width: 530px; margin: 0 auto 2rem; line-height: 1.65; }
        .hero-actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; margin-bottom: 3.5rem; }

        /* Terminal */
        .hero-terminal {
            max-width: 660px; margin: 0 auto; background: var(--term-bg);
            border-radius: 14px; overflow: hidden; box-shadow: var(--shadow-l); text-align: left;
        }
        .t-bar { display: flex; align-items: center; gap: 6px; padding: 11px 16px; background: var(--term-bar); }
        .td { width: 10px; height: 10px; border-radius: 50%; }
        .td.r { background: #ef4444; } .td.y { background: #eab308; } .td.g { background: #22c55e; }
        .t-body {
            padding: 1.1rem 1.4rem 1.3rem; font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.74rem; line-height: 1.85; color: var(--term-text); overflow-x: auto; white-space: pre;
        }
        .t-body .p { color: #22c55e; } .t-body .c { color: #e2e8f0; }
        .t-body .d { color: #5a7f90; } .t-body .ok { color: #4ade80; }
        .t-body .n { color: #67e8f9; } .t-body .lb { color: #94d2bd; font-weight: 600; }

        /* ── Stats ── */
        .stats-strip { max-width: 1080px; margin: 0 auto; padding: 0 1.5rem; }
        .stats-inner { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.25rem 1.5rem; box-shadow: var(--shadow-s); text-align: center; }
        .stat-card .sv { font-size: 1.8rem; font-weight: 800; letter-spacing: -0.02em; }
        .stat-card .sl { font-size: 0.78rem; color: var(--text-t); margin-top: 0.15rem; }
        .c-teal { color: var(--accent); } .c-green { color: var(--green); }
        .c-amber { color: var(--amber); } .c-blue { color: var(--blue); }

        /* ── Section ── */
        .section { max-width: 1080px; margin: 0 auto; padding: 5rem 1.5rem; }
        .se { font-size: 0.73rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--accent); margin-bottom: 0.5rem; }
        .st { font-size: 1.6rem; font-weight: 700; letter-spacing: -0.025em; margin-bottom: 0.5rem; }
        .sd { color: var(--text-s); max-width: 530px; margin-bottom: 2.5rem; }
        .sc { text-align: center; } .sc .sd { margin-left: auto; margin-right: auto; }

        /* ── Benefits ── */
        .bg { display: grid; grid-template-columns: repeat(3,1fr); gap: 1.25rem; }
        .bc { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 1.5rem; box-shadow: var(--shadow-s); transition: all 0.2s; }
        .bc:hover { box-shadow: var(--shadow); transform: translateY(-1px); }
        .bi { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.85rem; }
        .bi-t { background: var(--accent-l); } .bi-g { background: var(--green-l); }
        .bi-a { background: var(--amber-l); } .bi-b { background: var(--blue-l); } .bi-r { background: var(--red-l); }
        .bc h3 { font-size: 0.95rem; font-weight: 650; margin-bottom: 0.35rem; }
        .bc p { font-size: 0.84rem; color: var(--text-s); line-height: 1.55; }

        /* ── Steps ── */
        .steps { display: grid; grid-template-columns: repeat(3,1fr); gap: 2rem; }
        .step { text-align: center; }
        .sn { width: 40px; height: 40px; border-radius: 50%; background: var(--accent); color: #fff; font-weight: 700; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
        .step h4 { font-size: 1rem; font-weight: 650; margin-bottom: 0.35rem; }
        .step p { font-size: 0.85rem; color: var(--text-s); line-height: 1.5; max-width: 280px; margin: 0 auto; }

        /* ── Proof ── */
        .proof { background: var(--proof-bg); color: #fff; padding: 2.5rem 1.5rem; }
        .proof-inner { max-width: 1080px; margin: 0 auto; display: flex; justify-content: space-around; flex-wrap: wrap; gap: 2rem; text-align: center; }
        .proof-n { font-size: 2rem; font-weight: 800; } .proof-l { font-size: 0.82rem; opacity: 0.75; }

        /* ── Demo ── */
        .demo-sec { background: var(--bg-alt); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .demo-inner { max-width: 1080px; margin: 0 auto; padding: 4.5rem 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; }
        .demo-text h2 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.6rem; }
        .demo-text > p { color: var(--text-s); margin-bottom: 1.5rem; }
        .demo-creds { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 1.5rem; box-shadow: var(--shadow-s); }
        .cr { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 1.25rem; font-size: 0.85rem; }
        .cr + .cr { border-top: 1px solid var(--border); }
        .ck { color: var(--text-t); font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
        .cv { font-family: 'JetBrains Mono', monospace; font-weight: 600; color: var(--accent); font-size: 0.85rem; }

        .demo-pv { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; box-shadow: var(--shadow); }
        .dph { display: flex; align-items: center; gap: 0.6rem; padding: 0.85rem 1.25rem; border-bottom: 1px solid var(--border); background: var(--bg); font-size: 0.78rem; color: var(--text-t); }
        .dph .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--green); }
        .dpr { padding: 0.5rem; }
        .dp { display: flex; justify-content: space-between; align-items: center; padding: 0.65rem 0.85rem; border-radius: 8px; font-size: 0.82rem; }
        .dp:nth-child(odd) { background: var(--bg); }
        .dpn { color: var(--text); font-weight: 500; } .dpl { color: var(--text-t); font-size: 0.75rem; }
        .dpb { padding: 0.15rem 0.55rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600; white-space: nowrap; }
        .b-ok { background: var(--green-l); color: var(--green); }
        .b-w { background: var(--amber-l); color: var(--amber); }
        .b-tr { background: var(--blue-l); color: var(--blue); }
        .b-lo { background: var(--red-l); color: var(--red); }

        /* ── FAQ ── */
        .faq-list { max-width: 680px; margin: 0 auto; }
        .faq-item { border-bottom: 1px solid var(--border); padding: 1.25rem 0; }
        .faq-item summary { font-weight: 600; font-size: 0.95rem; cursor: pointer; list-style: none; display: flex; justify-content: space-between; align-items: center; }
        .faq-item summary::after { content: '+'; font-size: 1.3rem; color: var(--text-t); font-weight: 400; transition: transform 0.2s; flex-shrink: 0; margin-inline-start: 1rem; }
        .faq-item[open] summary::after { transform: rotate(45deg); }
        .faq-answer { margin-top: 0.6rem; font-size: 0.88rem; color: var(--text-s); line-height: 1.6; }

        /* ── Final CTA ── */
        .final-cta { text-align: center; padding: 5rem 1.5rem; }
        .final-cta h2 { font-size: 1.65rem; font-weight: 700; margin-bottom: 0.6rem; }
        .final-cta p { color: var(--text-s); margin-bottom: 1.75rem; max-width: 440px; margin-inline: auto; }

        /* ── Footer ── */
        footer { text-align: center; padding: 2rem 1.5rem; border-top: 1px solid var(--border); color: var(--text-t); font-size: 0.82rem; }
        footer a { color: var(--text-t); } footer a:hover { color: var(--text); }

        @media (max-width: 900px) {
            .bg { grid-template-columns: 1fr 1fr; }
            .steps { grid-template-columns: 1fr; gap: 1.5rem; }
            .demo-inner { grid-template-columns: 1fr; }
            .stats-inner { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 600px) {
            .bg { grid-template-columns: 1fr; }
            .stats-inner { grid-template-columns: 1fr 1fr; }
            .nav-right .hm { display: none; }
            .proof-inner { flex-direction: column; gap: 1rem; }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet"></noscript>
</head>
<body>

<nav class="nav">
    <div class="nav-inner">
        <a href="/" class="nav-brand">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                <rect width="28" height="28" rx="7" fill="#0d9488"/>
                <path d="M8 9h4v10H8zM14 7h4v12h-4zM20 12h-1v5h4v-5h-3z" fill="#fff" opacity="0.9"/>
            </svg>
            Stoq
        </a>
        <ul class="nav-right">
            <li><a href="#benefits" class="nl hm" data-i18n="nav_features">Features</a></li>
            <li><a href="#how" class="nl hm" data-i18n="nav_how">How It Works</a></li>
            <li><a href="#demo" class="nl hm" data-i18n="nav_demo">Demo</a></li>
            <li><button class="tb" id="langToggle" title="Switch language"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15 15 0 0 1 4 10 15 15 0 0 1-4 10 15 15 0 0 1-4-10A15 15 0 0 1 12 2z"/></svg><span id="langLabel">AR</span></button></li>
            <li><button class="tb" id="themeToggle" title="Toggle theme"><svg id="themeIcon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg></button></li>
            <li><a href="/admin" class="nav-cta"><span data-i18n="nav_dashboard">Dashboard</span> <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a></li>
        </ul>
    </div>
</nav>

<section class="hero">
    <h1 data-i18n="hero_title">Know exactly when to restock — across every branch</h1>
    <p data-i18n="hero_desc">Track inventory in real time across all your locations, get alerts when stock is running low, and move products between branches before you run out.</p>
    <div class="hero-actions">
        <a href="/admin" class="btn btn-p"><span data-i18n="hero_cta">Try the Dashboard</span> <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
        <a href="#how" class="btn btn-s" data-i18n="hero_cta2">See how it works</a>
    </div>
    <div class="hero-terminal">
        <div class="t-bar"><div class="td r"></div><div class="td y"></div><div class="td g"></div></div>
        <div class="t-body"><span class="p">$</span> <span class="c">php artisan inventory:reconcile</span>

<span class="d">  Reconciling cached stock against the movement ledger...</span>

  <span class="lb">Branch</span>         <span class="lb">Product</span>            <span class="lb">Cached</span>   <span class="lb">Ledger</span>   <span class="lb">Status</span>
  <span class="d">─────────────  ──────────────────  ───────  ───────  ──────</span>
  Downtown       Paracetamol 500mg   <span class="n">142.000</span>  <span class="n">142.000</span>  <span class="ok">  ✓ OK</span>
  Downtown       Surgical Gloves M   <span class="n"> 85.500</span>  <span class="n"> 85.500</span>  <span class="ok">  ✓ OK</span>
  Westside       Antiseptic 1L       <span class="n"> 67.000</span>  <span class="n"> 67.000</span>  <span class="ok">  ✓ OK</span>
  Riverside      Bandage Roll        <span class="n">200.000</span>  <span class="n">200.000</span>  <span class="ok">  ✓ OK</span>

  <span class="ok">✓ All {{ $stats['products'] }} products across {{ $stats['branches'] }} branches reconciled. 0 mismatches.</span></div>
    </div>
</section>

<div class="stats-strip">
    <div class="stats-inner">
        <div class="stat-card"><div class="sv c-teal">{{ $stats['products'] }}</div><div class="sl" data-i18n="stat_products">Products Tracked</div></div>
        <div class="stat-card"><div class="sv c-green">{{ $stats['branches'] }}</div><div class="sl" data-i18n="stat_branches">Active Branches</div></div>
        <div class="stat-card"><div class="sv c-amber">{{ $stats['movements'] }}</div><div class="sl" data-i18n="stat_movements">Stock Movements</div></div>
        <div class="stat-card"><div class="sv c-blue">{{ $stats['transfers'] }}</div><div class="sl" data-i18n="stat_transfers">Transfers Completed</div></div>
    </div>
</div>

<section id="benefits" class="section">
    <div class="se" data-i18n="benefits_eye">Features</div>
    <h2 class="st" data-i18n="benefits_title">Everything you need to manage multi-location stock</h2>
    <p class="sd" data-i18n="benefits_desc">Stop guessing what's in stock, stop running out, and stop manually counting.</p>
    <div class="bg">
        <div class="bc">
            <div class="bi bi-t"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg></div>
            <h3 data-i18n="b1_title">Automatic Reorder Alerts</h3>
            <p data-i18n="b1_desc">The system learns your sales patterns and tells you when to restock — before you run out, not after.</p>
        </div>
        <div class="bc">
            <div class="bi bi-g"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M16 3h5v5"/><path d="M8 3H3v5"/><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
            <h3 data-i18n="b2_title">Branch-to-Branch Transfers</h3>
            <p data-i18n="b2_desc">Move stock between locations with full tracking — who requested it, who approved it, and when it arrived.</p>
        </div>
        <div class="bc">
            <div class="bi bi-a"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path d="M9 5H2v7l6.29 6.29a1 1 0 0 0 1.42 0l5.58-5.58a1 1 0 0 0 0-1.42L9 5z"/><circle cx="6" cy="9" r="1" fill="#d97706"/></svg></div>
            <h3 data-i18n="b3_title">Every Movement Recorded</h3>
            <p data-i18n="b3_desc">Sales, purchases, adjustments, transfers — every stock change is logged with who did it and when.</p>
        </div>
        <div class="bc">
            <div class="bi bi-b"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg></div>
            <h3 data-i18n="b4_title">Real-Time Dashboard</h3>
            <p data-i18n="b4_desc">See what's in stock, what's running low, and what's in transit — all in one place, across all branches.</p>
        </div>
        <div class="bc">
            <div class="bi bi-r"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><path d="M12 22V8"/><path d="m5 12 7-7 7 7"/><line x1="3" y1="22" x2="21" y2="22"/></svg></div>
            <h3 data-i18n="b5_title">Revenue Reporting</h3>
            <p data-i18n="b5_desc">Track revenue per branch with historically accurate pricing — see exactly what was sold at what price.</p>
        </div>
        <div class="bc">
            <div class="bi bi-t"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg></div>
            <h3 data-i18n="b6_title">Integrity Verification</h3>
            <p data-i18n="b6_desc">Run a reconciliation check anytime to verify that stock totals match the movement records exactly.</p>
        </div>
    </div>
</section>

<section id="how" class="section sc" style="padding-top:0;">
    <div class="se" data-i18n="how_eye">How It Works</div>
    <h2 class="st" data-i18n="how_title">Three steps to full visibility</h2>
    <p class="sd" data-i18n="how_desc">Set up your branches, add your products, and the system handles the rest.</p>
    <div class="steps">
        <div class="step"><div class="sn">1</div><h4 data-i18n="s1_title">Set Up Branches</h4><p data-i18n="s1_desc">Add your locations and assign staff to each branch. Every branch gets its own stock view.</p></div>
        <div class="step"><div class="sn">2</div><h4 data-i18n="s2_title">Track Stock Changes</h4><p data-i18n="s2_desc">Record sales, purchases, and adjustments. The system maintains a complete audit trail automatically.</p></div>
        <div class="step"><div class="sn">3</div><h4 data-i18n="s3_title">Get Smart Alerts</h4><p data-i18n="s3_desc">The reorder engine monitors usage patterns and flags products that need restocking — with suggested sources.</p></div>
    </div>
</section>

<div class="proof">
    <div class="proof-inner">
        <div class="proof-item"><div class="proof-n">{{ $stats['movements'] }}+</div><div class="proof-l" data-i18n="proof_1">Stock movements processed</div></div>
        <div class="proof-item"><div class="proof-n">3</div><div class="proof-l" data-i18n="proof_2">Decimal places of precision</div></div>
        <div class="proof-item"><div class="proof-n">0</div><div class="proof-l" data-i18n="proof_3">Rounding errors</div></div>
    </div>
</div>

<section id="demo" class="demo-sec">
    <div class="demo-inner">
        <div class="demo-text">
            <h2 data-i18n="demo_title">See it in action</h2>
            <p data-i18n="demo_desc">The dashboard is pre-loaded with branches, products, stock movements, and transfer orders. Log in and explore.</p>
            <div class="demo-creds">
                <div class="cr"><span class="ck" data-i18n="demo_email">Email</span><span class="cv">admin@example.com</span></div>
                <div class="cr"><span class="ck" data-i18n="demo_pass">Password</span><span class="cv">password</span></div>
            </div>
            <a href="/admin" class="btn btn-p" style="width:100%;justify-content:center;"><span data-i18n="demo_cta">Launch Dashboard</span> <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
        </div>
        <div class="demo-pv">
            <div class="dph"><div class="dot"></div><span data-i18n="pv_title">Reorder Dashboard</span></div>
            <div class="dpr">
                <div class="dp"><div><div class="dpn">Paracetamol 500mg</div><div class="dpl" data-i18n="pv_downtown">Downtown Branch</div></div><span class="dpb b-w" data-i18n="pv_reorder">Reorder</span></div>
                <div class="dp"><div><div class="dpn">Surgical Gloves M</div><div class="dpl" data-i18n="pv_westside">Westside Branch</div></div><span class="dpb b-ok" data-i18n="pv_instock">In Stock</span></div>
                <div class="dp"><div><div class="dpn">Antiseptic 1L</div><div class="dpl" data-i18n="pv_riverside">Riverside Branch</div></div><span class="dpb b-tr" data-i18n="pv_transit">In Transit</span></div>
                <div class="dp"><div><div class="dpn">Bandage Roll 10cm</div><div class="dpl" data-i18n="pv_downtown">Downtown Branch</div></div><span class="dpb b-lo" data-i18n="pv_low">Low Stock</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section sc">
    <div class="se" data-i18n="faq_eye">FAQ</div>
    <h2 class="st" style="margin-bottom:2rem;" data-i18n="faq_title">Common questions</h2>
    <div class="faq-list">
        <details class="faq-item"><summary data-i18n="faq1_q">How does the reorder point calculation work?</summary><div class="faq-answer" data-i18n="faq1_a">The system analyzes your recent sales history to calculate average daily usage for each product at each branch. It combines that with actual transit time between branches and adds a safety buffer. When stock drops below this point, you get an alert with a suggested transfer source.</div></details>
        <details class="faq-item"><summary data-i18n="faq2_q">Can I track partial shipments?</summary><div class="faq-answer" data-i18n="faq2_a">Yes. When shipping a transfer, you can specify the actual quantity sent. When receiving, you can record what actually arrived — handling short-shipments and damage in transit.</div></details>
        <details class="faq-item"><summary data-i18n="faq3_q">How do I verify stock accuracy?</summary><div class="faq-answer" data-i18n="faq3_a">Run the built-in reconciliation check. It compares every product's cached total against the sum of all movement records and reports any mismatches — so you can trust your numbers.</div></details>
        <details class="faq-item"><summary data-i18n="faq4_q">What about simultaneous transactions?</summary><div class="faq-answer" data-i18n="faq4_a">The system uses database-level row locking to prevent overselling. If two people try to use the same stock at the same time, one completes and the other gets an error — no negative inventory.</div></details>
    </div>
</section>

<section class="final-cta">
    <h2 data-i18n="cta_title">Ready to take control of your inventory?</h2>
    <p data-i18n="cta_desc">Log in to the dashboard and see how Stoq manages stock across multiple branches.</p>
    <a href="/admin" class="btn btn-p"><span data-i18n="cta_btn">Open Dashboard</span> <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg></a>
</section>

<footer><p>Built by <strong style="color:var(--text);">Maneer</strong> · <a href="https://github.com/manar-mk" target="_blank">GitHub</a></p></footer>

<script>
const T = {
en: {
    nav_features: "Features",
    nav_how: "How It Works",
    nav_demo: "Demo",
    nav_dashboard: "Dashboard",
    hero_title: "Know exactly when to restock — across every branch",
    hero_desc: "Track inventory in real time across all your locations, get alerts when stock is running low, and move products between branches before you run out.",
    hero_cta: "Try the Dashboard",
    hero_cta2: "See how it works",
    stat_products: "Products Tracked",
    stat_branches: "Active Branches",
    stat_movements: "Stock Movements",
    stat_transfers: "Transfers Completed",
    benefits_eye: "Features",
    benefits_title: "Everything you need to manage multi-location stock",
    benefits_desc: "Stop guessing what's in stock, stop running out, and stop manually counting.",
    b1_title: "Automatic Reorder Alerts",
    b1_desc: "The system learns your sales patterns and tells you when to restock — before you run out, not after.",
    b2_title: "Branch-to-Branch Transfers",
    b2_desc: "Move stock between locations with full tracking — who requested it, who approved it, and when it arrived.",
    b3_title: "Every Movement Recorded",
    b3_desc: "Sales, purchases, adjustments, transfers — every stock change is logged with who did it and when.",
    b4_title: "Real-Time Dashboard",
    b4_desc: "See what's in stock, what's running low, and what's in transit — all in one place, across all branches.",
    b5_title: "Revenue Reporting",
    b5_desc: "Track revenue per branch with historically accurate pricing — see exactly what was sold at what price.",
    b6_title: "Integrity Verification",
    b6_desc: "Run a reconciliation check anytime to verify that stock totals match the movement records exactly.",
    how_eye: "How It Works",
    how_title: "Three steps to full visibility",
    how_desc: "Set up your branches, add your products, and the system handles the rest.",
    s1_title: "Set Up Branches",
    s1_desc: "Add your locations and assign staff to each branch. Every branch gets its own stock view.",
    s2_title: "Track Stock Changes",
    s2_desc: "Record sales, purchases, and adjustments. The system maintains a complete audit trail automatically.",
    s3_title: "Get Smart Alerts",
    s3_desc: "The reorder engine monitors usage patterns and flags products that need restocking — with suggested sources.",
    proof_1: "Stock movements processed",
    proof_2: "Decimal places of precision",
    proof_3: "Rounding errors",
    demo_title: "See it in action",
    demo_desc: "The dashboard is pre-loaded with branches, products, stock movements, and transfer orders. Log in and explore.",
    demo_email: "Email",
    demo_pass: "Password",
    demo_cta: "Launch Dashboard",
    pv_title: "Reorder Dashboard",
    pv_downtown: "Downtown Branch",
    pv_westside: "Westside Branch",
    pv_riverside: "Riverside Branch",
    pv_reorder: "Reorder",
    pv_instock: "In Stock",
    pv_transit: "In Transit",
    pv_low: "Low Stock",
    faq_eye: "FAQ",
    faq_title: "Common questions",
    faq1_q: "How does the reorder point calculation work?",
    faq1_a: "The system analyzes your recent sales history to calculate average daily usage for each product at each branch. It combines that with actual transit time between branches and adds a safety buffer. When stock drops below this point, you get an alert with a suggested transfer source.",
    faq2_q: "Can I track partial shipments?",
    faq2_a: "Yes. When shipping a transfer, you can specify the actual quantity sent. When receiving, you can record what actually arrived — handling short-shipments and damage in transit.",
    faq3_q: "How do I verify stock accuracy?",
    faq3_a: "Run the built-in reconciliation check. It compares every product's cached total against the sum of all movement records and reports any mismatches — so you can trust your numbers.",
    faq4_q: "What about simultaneous transactions?",
    faq4_a: "The system uses database-level row locking to prevent overselling. If two people try to use the same stock at the same time, one completes and the other gets an error — no negative inventory.",
    cta_title: "Ready to take control of your inventory?",
    cta_desc: "Log in to the dashboard and see how Stoq manages stock across multiple branches.",
    cta_btn: "Open Dashboard",
},
ar: {
    nav_features: "المزايا",
    nav_how: "آلية العمل",
    nav_demo: "تجربة",
    nav_dashboard: "لوحة التحكم",
    hero_title: "اعرف بالضبط متى تحتاج إعادة التوريد — في كل فرع",
    hero_desc: "تتبّع المخزون لحظة بلحظة في جميع فروعك، واحصل على تنبيهات عند انخفاض مستوى المخزون، وانقل البضائع بين الفروع قبل نفادها.",
    hero_cta: "جرّب لوحة التحكم",
    hero_cta2: "اعرف آلية العمل",
    stat_products: "منتج في النظام",
    stat_branches: "فروع نشطة",
    stat_movements: "حركة مخزون",
    stat_transfers: "أمر تحويل مكتمل",
    benefits_eye: "المزايا",
    benefits_title: "كل ما تحتاجه لإدارة مخزون متعدد الفروع",
    benefits_desc: "توقّف عن التخمين، وعن النفاد المفاجئ، وعن الجرد اليدوي.",
    b1_title: "تنبيهات نقطة إعادة الطلب",
    b1_desc: "يحلّل النظام معدل الاستهلاك اليومي من مبيعاتك ويخبرك متى تحتاج إعادة التوريد — قبل نفاد المخزون، لا بعده.",
    b2_title: "التحويل بين الفروع",
    b2_desc: "انقل المخزون بين المواقع مع تتبّع كامل — من طلب التحويل، ومن وافق عليه، ومتى تم الاستلام.",
    b3_title: "تسجيل كل حركة مخزون",
    b3_desc: "مبيعات، مشتريات، تسويات، تحويلات — كل تغيير في الرصيد مسجّل في السجل مع المسؤول والتوقيت.",
    b4_title: "لوحة تحكم لحظية",
    b4_desc: "شاهد ما هو متوفر، وما ينخفض، وما هو قيد النقل — كل شيء في مكان واحد عبر جميع الفروع.",
    b5_title: "تقارير الإيرادات",
    b5_desc: "تتبّع الإيرادات لكل فرع بسعر الوحدة المثبّت وقت البيع — اعرف بالضبط ما بيع وبأي سعر.",
    b6_title: "التحقق من سلامة البيانات",
    b6_desc: "شغّل أمر المطابقة في أي وقت للتحقق من أن أرصدة المخزون المخبّأة تطابق سجل الحركات بالضبط.",
    how_eye: "آلية العمل",
    how_title: "ثلاث خطوات لرؤية شاملة",
    how_desc: "أضف فروعك، وأدخل منتجاتك، والنظام يتكفّل بالباقي.",
    s1_title: "أنشئ الفروع",
    s1_desc: "أضف مواقع المستودعات وعيّن الموظفين لكل فرع. كل فرع يحصل على عرض مخزون خاص.",
    s2_title: "سجّل حركات المخزون",
    s2_desc: "سجّل المبيعات والمشتريات والتسويات. النظام يحتفظ بسجل تدقيق كامل تلقائياً.",
    s3_title: "احصل على تنبيهات ذكية",
    s3_desc: "محرّك إعادة الطلب يراقب أنماط الاستهلاك ويُشير للمنتجات التي وصلت لنقطة إعادة الطلب — مع اقتراح فرع المصدر.",
    proof_1: "حركة مخزون تمت معالجتها",
    proof_2: "منازل عشرية من الدقة",
    proof_3: "أخطاء تقريب",
    demo_title: "شاهده يعمل",
    demo_desc: "لوحة التحكم محمّلة مسبقاً بفروع ومنتجات وحركات مخزون وأوامر تحويل. سجّل الدخول واستكشف.",
    demo_email: "البريد الإلكتروني",
    demo_pass: "كلمة المرور",
    demo_cta: "افتح لوحة التحكم",
    pv_title: "لوحة إعادة الطلب",
    pv_downtown: "فرع وسط المدينة",
    pv_westside: "فرع الجهة الغربية",
    pv_riverside: "فرع ضفة النهر",
    pv_reorder: "يحتاج توريد",
    pv_instock: "متوفر",
    pv_transit: "مخزون العبور",
    pv_low: "مخزون منخفض",
    faq_eye: "أسئلة شائعة",
    faq_title: "أسئلة شائعة",
    faq1_q: "كيف يعمل حساب نقطة إعادة الطلب؟",
    faq1_a: "يحلّل النظام سجل المبيعات الأخير لحساب معدل الاستهلاك اليومي لكل منتج في كل فرع. يجمع ذلك مع وقت التوريد الفعلي بين الفروع ويضيف مخزون الأمان. عندما ينخفض الرصيد عن هذه النقطة، تحصل على تنبيه مع اقتراح فرع المصدر للتحويل.",
    faq2_q: "هل يمكنني تتبّع الشحنات الجزئية؟",
    faq2_a: "نعم. عند شحن أمر تحويل، يمكنك تحديد الكمية الفعلية المرسلة. وعند الاستلام، يمكنك تسجيل ما وصل فعلاً — للتعامل مع النقص والتلف أثناء النقل.",
    faq3_q: "كيف أتحقق من دقة أرصدة المخزون؟",
    faq3_a: "شغّل أمر المطابقة المدمج. يقارن الرصيد المخبّأ لكل منتج بمجموع كل سجلات الحركات ويُبلّغ عن أي فرق — حتى تثق بأرقامك.",
    faq4_q: "ماذا عن العمليات المتزامنة؟",
    faq4_a: "يستخدم النظام قفل الصفوف على مستوى قاعدة البيانات لمنع البيع الزائد. إذا حاول شخصان استخدام نفس الرصيد في نفس الوقت، تكتمل عملية واحدة وتفشل الأخرى — بدون رصيد سالب.",
    cta_title: "جاهز للسيطرة على مخزونك؟",
    cta_desc: "سجّل الدخول إلى لوحة التحكم وشاهد كيف يدير Stoq المخزون عبر فروع متعددة.",
    cta_btn: "افتح لوحة التحكم",
}
};

function setLang(l) {
    const h = document.documentElement;
    h.lang = l; h.dir = l === 'ar' ? 'rtl' : 'ltr';
    document.getElementById('langLabel').textContent = l === 'en' ? 'AR' : 'EN';
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const k = el.getAttribute('data-i18n');
        if (T[l][k]) el.textContent = T[l][k];
    });
    try { localStorage.setItem('stoq-lang', l); } catch(e) {}
}

function setTheme(dark) {
    document.documentElement.classList.toggle('dark', dark);
    document.getElementById('themeIcon').innerHTML = dark
        ? '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>'
        : '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
    try { localStorage.setItem('stoq-theme', dark ? 'dark' : 'light'); } catch(e) {}
}

(function() {
    let l = 'en', d = false;
    try { l = localStorage.getItem('stoq-lang') || 'en'; } catch(e) {}
    try { d = localStorage.getItem('stoq-theme') === 'dark'; } catch(e) {}
    if (l === 'ar') setLang('ar');
    if (d) setTheme(true);
    document.getElementById('langToggle').addEventListener('click', () => setLang(document.documentElement.lang === 'en' ? 'ar' : 'en'));
    document.getElementById('themeToggle').addEventListener('click', () => setTheme(!document.documentElement.classList.contains('dark')));
})();
</script>
</body>
</html>
