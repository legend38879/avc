<?php
require_once __DIR__ . '/../includes/auth.php';
startSession();
$user = requireLogin();
checkBanned($user);
$isAdmin = in_array($user['role'], ['admin', 'owner']);
$unreadNotif = getUnreadCount($user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title>Fuel Oracle</title>
<script src="https://cdn.tailwindcss.com/3.4.17"></script>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<?php require_once __DIR__ . '/../includes/ui.php'; lightThemeCSS(); darkModeScript(); ?>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html { font-size: 16px; }
  body {
    font-family: 'Outfit', sans-serif;
    background: var(--fuel-bg);
    color: #1e293b;
    min-height: 100vh;
    overflow-x: hidden;
    touch-action: pan-y;
    overscroll-behavior-x: none;
  }

  .app { position: relative; z-index: 2; max-width: 1300px; margin: 0 auto; padding: 16px 16px 40px; }
  @media(min-width:640px){ .app { padding: 40px 16px 40px; } }

  /* Hero */
  .fuel-hero{text-align:center;padding:32px 0 28px;position:relative;}
  .fuel-hero-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 16px;border-radius:99px;font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;margin-bottom:18px;background:rgba(59,130,246,0.1);color:#2563eb;border:1px solid rgba(59,130,246,0.25);}
  .fuel-live-dot{width:7px;height:7px;border-radius:50%;background:#22c55e;animation:blink 1.3s ease-in-out infinite;}
  @keyframes blink{0%,100%{opacity:1}50%{opacity:0.2}}
  .fuel-hero h1{font-size:clamp(1.8rem,5vw,3.5rem);font-weight:900;color:var(--fuel-hero-h1);letter-spacing:-0.02em;line-height:1.1;margin-bottom:8px;}
  .fuel-hero h1 span{background:linear-gradient(135deg,#3b82f6,#6366f1,#a855f7);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
  .fuel-hero-sub{font-size:14px;color:var(--fuel-hero-sub);margin-bottom:20px;}
  .fuel-time-chips{display:flex;align-items:center;justify-content:center;gap:8px;flex-wrap:wrap;}
  .time-chip{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:8px;font-size:12px;font-family:'Space Mono',monospace;background:var(--fuel-chip-bg);border:1px solid var(--fuel-chip-border);color:var(--fuel-chip-text);box-shadow:0 1px 3px rgba(0,0,0,0.04);}

  /* Main layout */
  .fuel-grid{display:grid;grid-template-columns:320px 1fr;gap:16px;margin-top:20px;}
  @media(max-width:900px){.fuel-grid{grid-template-columns:1fr;}}

  /* Cards */
  .card{background:var(--bg-card);border:1px solid var(--border);border-radius:20px;padding:20px;position:relative;overflow:hidden;box-shadow:0 2px 16px rgba(99,102,241,0.06),0 1px 3px rgba(0,0,0,0.04);}
  .card::before{content:'';position:absolute;top:0;left:0;right:0;height:1px;background:linear-gradient(90deg,transparent,var(--fuel-card-accent),transparent);}
  .card-title{font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:var(--fuel-card-title);margin-bottom:16px;display:flex;align-items:center;gap:6px;}
  .card-title-dot{width:6px;height:6px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);}

  /* Price display */
  .price-hero{text-align:center;padding:8px 0 16px;}
  .price-label{font-size:11px;color:var(--fuel-price-label);letter-spacing:0.08em;text-transform:uppercase;margin-bottom:8px;}
  .price-big{font-size:clamp(2.8rem,8vw,4.5rem);font-weight:900;line-height:1;font-family:'Space Mono',monospace;transition:color 0.4s;}
  .price-prefix{font-size:0.4em;vertical-align:super;color:#94a3b8;}
  .price-badge{display:inline-block;padding:4px 14px;border-radius:99px;font-size:11px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;margin-top:10px;}

  /* Tier colors */
  .t-best{color:#16a34a;} .t-good{color:#2563eb;} .t-avg{color:#d97706;} .t-high{color:#ea580c;} .t-spike{color:#dc2626;}
  .b-best{background:rgba(22,163,74,0.1);border:1px solid rgba(22,163,74,0.3);color:#16a34a;}
  .b-good{background:rgba(37,99,235,0.1);border:1px solid rgba(37,99,235,0.3);color:#2563eb;}
  .b-avg{background:rgba(217,119,6,0.1);border:1px solid rgba(217,119,6,0.3);color:#d97706;}
  .b-high{background:rgba(234,88,12,0.1);border:1px solid rgba(234,88,12,0.3);color:#ea580c;}
  .b-spike{background:rgba(220,38,38,0.1);border:1px solid rgba(220,38,38,0.3);color:#dc2626;}

  /* Stats */
  .stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:14px;}
  .stat-box{background:var(--fuel-stat-bg);border:1px solid var(--fuel-stat-border);border-radius:14px;padding:12px 8px;text-align:center;}
  .stat-val{font-size:1.1rem;font-weight:800;color:var(--fuel-stat-text);font-family:'Space Mono',monospace;}
  .stat-lbl{font-size:10px;color:#94a3b8;margin-top:3px;text-transform:uppercase;letter-spacing:0.06em;}

  /* Recommendation */
  .reco{border-radius:14px;padding:14px 16px;margin-top:14px;border-left:3px solid #6366f1;background:var(--fuel-reco-bg);font-size:13px;line-height:1.55;color:#334155;}
  .reco.reco-buy{border-color:#22c55e;background:rgba(34,197,94,0.05);}
  .reco.reco-wait{border-color:#f59e0b;background:rgba(245,158,11,0.05);}
  .reco.reco-skip{border-color:#ef4444;background:rgba(239,68,68,0.05);}
  .reco-lbl{font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#94a3b8;margin-bottom:5px;}

  /* Co2 row */
  .co2-strip{display:flex;align-items:center;gap:10px;justify-content:center;padding:12px 0;border-top:1px solid rgba(203,213,225,0.5);margin-top:12px;flex-wrap:wrap;}
  .co2-lbl{font-size:11px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;}
  .co2-val{font-size:1.1rem;font-weight:700;color:#3b82f6;font-family:'Space Mono',monospace;}

  /* Mode buttons */
  .mode-row{display:grid;grid-template-columns:repeat(3,1fr);gap:5px;margin-bottom:12px;}
  .mode-btn{padding:7px 4px;background:var(--fuel-mode-btn-bg);border:1px solid var(--fuel-mode-btn-border);border-radius:10px;color:var(--fuel-mode-btn-text);font-family:'Outfit',sans-serif;font-size:11px;font-weight:600;cursor:pointer;transition:all 0.18s;text-align:center;letter-spacing:0.02em;}
  .mode-btn:hover{background:var(--bg-hover);color:var(--text-muted);}
  .mode-btn.active{background:rgba(37,99,235,0.08);border-color:rgba(37,99,235,0.3);color:#2563eb;}

  /* Horizon tabs */
  .h-tabs{display:flex;border:1px solid var(--fuel-htabs-border);border-radius:10px;overflow:hidden;margin-bottom:12px;background:var(--fuel-htabs-bg);}
  .h-tab{flex:1;padding:8px 4px;background:transparent;border:none;color:var(--fuel-h-tab-text);font-family:'Space Mono',monospace;font-size:10px;cursor:pointer;transition:all 0.18s;letter-spacing:0.04em;}
  .h-tab:hover{background:var(--bg-hover);color:var(--text-muted);}
  .h-tab.active{background:rgba(37,99,235,0.08);color:#2563eb;}
  .h-tab+.h-tab{border-left:1px solid rgba(203,213,225,0.5);}

  /* Chart */
  .chart-wrap{position:relative;height:clamp(140px,22vw,220px);margin-bottom:12px;}
  canvas{touch-action:pan-y;}

  /* Table */
  .table-scroll{max-height:300px;overflow-y:auto;overflow-x:hidden;}
  .forecast-table{width:100%;border-collapse:collapse;font-size:13px;}
  .forecast-table th{font-size:10px;letter-spacing:0.08em;color:var(--text-subtle);text-transform:uppercase;padding:7px 8px;border-bottom:1px solid var(--border);text-align:left;font-weight:600;background:var(--fuel-th-bg);position:sticky;top:0;z-index:2;}
  .forecast-table td{padding:7px 8px;border-bottom:1px solid var(--fuel-table-td-border);color:var(--fuel-td-color);}
  .ft-time{font-family:'Space Mono',monospace;font-size:11px;color:#94a3b8;}
  .ft-price{font-family:'Space Mono',monospace;font-weight:700;}
  .ft-badge{font-size:10px;padding:2px 7px;border-radius:6px;}
  .bar-track{height:4px;background:rgba(203,213,225,0.4);border-radius:2px;}
  .bar-fill{height:4px;border-radius:2px;transition:width 0.5s;}

  /* Windows grid */
  .windows-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;}
  .win-card{border-radius:12px;padding:12px;border:1px solid rgba(34,197,94,0.2);background:rgba(34,197,94,0.05);}
  .win-card.high-card{border-color:rgba(239,68,68,0.2);background:rgba(239,68,68,0.05);}
  .win-time{font-size:10px;font-family:'Space Mono',monospace;color:#16a34a;margin-bottom:4px;}
  .win-card.high-card .win-time{color:#dc2626;}
  .win-price{font-size:1.1rem;font-weight:800;color:#16a34a;font-family:'Space Mono',monospace;}
  .win-card.high-card .win-price{color:#dc2626;}
  .win-lbl{font-size:10px;color:#94a3b8;margin-top:2px;}

  /* Heatmap */
  .heatmap-scroll{overflow-x:hidden;padding-bottom:4px;}
  @media(max-width:640px){
    .heatmap-row{display:grid!important;grid-template-columns:repeat(4,1fr)!important;width:100%!important;}
    .h-cell{min-width:0!important;width:100%!important;}
    .table-scroll{overflow-x:hidden!important;}
    .fuel-grid{touch-action:pan-y!important;}
    .col-delta,.col-co2{display:none!important;}
  }
  .heatmap-row{display:flex;gap:2px;flex-wrap:wrap;}
  .hm-col{display:flex;flex-direction:column;gap:2px;}
  .hm-cell{width:8px;height:16px;border-radius:2px;cursor:pointer;transition:transform 0.12s,filter 0.12s;}
  @media(max-width:640px){.hm-cell{width:6px;height:12px;}}

  /* Tooltip */
  .hm-tooltip{position:fixed;background:var(--fuel-tooltip-bg);border:1px solid rgba(99,102,241,0.25);border-radius:10px;padding:9px 13px;font-family:'Space Mono',monospace;font-size:11px;color:#2563eb;pointer-events:none;z-index:1000;display:none;box-shadow:0 4px 20px rgba(99,102,241,0.15);min-width:130px;color:var(--text-muted);}
  .hm-tooltip.show{display:block;}
  .hm-tooltip strong{display:block;color:var(--text-heading);font-family:'Outfit',monospace;font-size:11px;font-weight:700;margin-bottom:3px;}

  /* ===== LOADING SCREEN ===== */
  #fuel-loading{
    position:fixed;inset:0;
    background:var(--loading-bg);
    z-index:9999;
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    gap:0;transition:opacity 0.7s ease;overflow:hidden;
  }

  /* Subtle grid overlay */
  #fuel-loading::before{
    content:'';position:absolute;inset:0;
    background-image:linear-gradient(var(--loading-grid-color) 1px,transparent 1px),linear-gradient(90deg,var(--loading-grid-color) 1px,transparent 1px);
    background-size:50px 50px;pointer-events:none;
  }

  /* Falling coins container */
  .coin-rain{position:absolute;inset:0;pointer-events:none;overflow:hidden;}
  .coin{
    position:absolute;top:-60px;
    width:28px;height:28px;border-radius:50%;
    background:radial-gradient(circle at 35% 35%,#fde68a,#f59e0b 45%,#b45309 80%,#78350f);
    border:2px solid #fbbf24;
    box-shadow:0 2px 8px rgba(251,191,36,0.4),inset 0 1px 3px rgba(253,230,138,0.6);
    display:flex;align-items:center;justify-content:center;
    font-size:12px;font-weight:900;color:#92400e;
    font-family:'Space Mono',monospace;
    animation:coinFall var(--dur,1.2s) var(--delay,0s) cubic-bezier(0.25,0.46,0.45,0.94) forwards;
  }
  .coin::after{
    content:'$';
    font-size:11px;font-weight:900;color:#92400e;
    text-shadow:0 1px 0 rgba(253,230,138,0.8);
  }
  @keyframes coinFall{
    0%  {transform:translateY(0) rotate(0deg) scaleX(1);opacity:1;}
    70% {opacity:1;}
    85% {transform:translateY(var(--fall,110vh)) rotate(var(--spin,540deg)) scaleX(0.15);}
    87% {transform:translateY(var(--fall,110vh)) rotate(var(--spin,540deg)) scaleX(1);}
    90% {transform:translateY(calc(var(--fall,110vh) - 18px)) rotate(var(--spin,540deg)) scaleX(1);}
    95% {transform:translateY(var(--fall,110vh)) rotate(var(--spin,540deg)) scaleX(0.2);}
    100%{transform:translateY(var(--fall,110vh)) rotate(var(--spin,540deg)) scaleX(1);opacity:0;}
  }

  /* Sparkle burst on coin land */
  .spark{
    position:absolute;width:6px;height:6px;border-radius:50%;
    background:#fbbf24;pointer-events:none;
    animation:sparkFly var(--sd,0.5s) ease-out forwards;
  }
  @keyframes sparkFly{
    0%  {transform:translate(0,0) scale(1);opacity:1;}
    100%{transform:translate(var(--sx,10px),var(--sy,-20px)) scale(0);opacity:0;}
  }

  /* Center icon — coin stack */
  .load-coin-stack{
    position:relative;z-index:2;
    margin-bottom:28px;
    animation:stackFloat 2.2s ease-in-out infinite;
  }
  @keyframes stackFloat{0%,100%{transform:translateY(0);}50%{transform:translateY(-8px);}}
  .coin-stack-svg{filter:drop-shadow(0 8px 24px rgba(251,191,36,0.5)) drop-shadow(0 2px 6px rgba(245,158,11,0.3));}

  /* Glow pulse behind stack */
  .load-glow{
    position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
    width:120px;height:120px;border-radius:50%;
    background:radial-gradient(circle,rgba(251,191,36,0.18) 0%,transparent 70%);
    animation:glowPulse 2s ease-in-out infinite;z-index:1;
  }
  @keyframes glowPulse{0%,100%{transform:translate(-50%,-50%) scale(1);opacity:0.6;}50%{transform:translate(-50%,-50%) scale(1.4);opacity:1;}}

  .load-title{
    position:relative;z-index:2;
    font-family:'Outfit',sans-serif;font-size:26px;font-weight:900;
    color:var(--loading-title-color);letter-spacing:-0.02em;line-height:1.15;
    text-align:center;margin-bottom:6px;
    text-shadow:0 2px 20px rgba(251,191,36,0.2);
  }
  .load-title span{
    background:linear-gradient(135deg,#fde68a,#f59e0b,#fbbf24);
    -webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;
  }
  .load-subtitle{
    position:relative;z-index:2;
    font-size:11px;color:var(--loading-sub-color);
    letter-spacing:0.12em;text-transform:uppercase;
    margin-bottom:36px;font-family:'Space Mono',monospace;
  }

  /* Progress bar */
  .load-progress-wrap{position:relative;z-index:2;width:260px;}
  .load-bar-track{
    width:100%;height:5px;
    background:var(--loading-bar-track);
    border-radius:99px;overflow:hidden;margin-bottom:10px;
    border:1px solid rgba(251,191,36,0.1);
  }
  .load-bar{
    height:100%;width:0;
    background:linear-gradient(90deg,#f59e0b,#fbbf24,#fde68a);
    border-radius:99px;
    transition:width 0.45s cubic-bezier(0.4,0,0.2,1);
    position:relative;
    box-shadow:0 0 12px rgba(251,191,36,0.5);
  }
  .load-bar::after{
    content:'';position:absolute;top:0;right:0;bottom:0;width:40px;
    background:linear-gradient(90deg,transparent,rgba(255,255,255,0.5));
    border-radius:99px;animation:shimmer 0.9s ease-in-out infinite;
  }
  @keyframes shimmer{0%{opacity:0;}50%{opacity:1;}100%{opacity:0;}}
  .load-status{
    font-family:'Space Mono',monospace;font-size:10px;
    color:var(--loading-badge-text);text-align:center;
    letter-spacing:0.1em;height:16px;text-transform:uppercase;
  }

  /* Coin counter badge */
  .load-counter{
    position:relative;z-index:2;
    margin-top:18px;
    display:inline-flex;align-items:center;gap:7px;
    padding:5px 14px;border-radius:99px;
    background:var(--loading-badge-bg);
    border:1px solid var(--loading-badge-border);
    font-family:'Space Mono',monospace;font-size:11px;
    color:var(--loading-badge-text);letter-spacing:0.06em;
  }
  .load-coin-icon{font-size:13px;}

  /* Next Best Slot countdown */
  .next-best-wrap{margin-top:14px;border-radius:14px;padding:12px 14px;background:linear-gradient(135deg,rgba(34,197,94,0.06),rgba(37,99,235,0.06));border:1px solid rgba(34,197,94,0.2);display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
  .next-best-wrap.nb-good{background:linear-gradient(135deg,rgba(37,99,235,0.06),rgba(99,102,241,0.06));border-color:rgba(37,99,235,0.2);}
  .next-best-wrap.nb-none{background:rgba(148,163,184,0.06);border-color:rgba(148,163,184,0.2);}
  .nb-icon{font-size:22px;flex-shrink:0;}
  .nb-body{flex:1;min-width:0;}
  .nb-label{font-size:10px;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:#94a3b8;margin-bottom:3px;}
  .nb-row{display:flex;align-items:baseline;gap:8px;flex-wrap:wrap;}
  .nb-timer{font-family:'Space Mono',monospace;font-size:1.35rem;font-weight:700;color:#16a34a;letter-spacing:0.04em;line-height:1;}
  .nb-wrap.nb-good .nb-timer{color:#2563eb;}
  .nb-price{font-family:'Space Mono',monospace;font-size:0.82rem;font-weight:600;color:#64748b;}
  .nb-slot-time{font-size:10px;color:#94a3b8;font-family:'Space Mono',monospace;margin-top:2px;}
  .nb-pulse{width:8px;height:8px;border-radius:50%;background:#22c55e;animation:blink 1.3s ease-in-out infinite;flex-shrink:0;}
  .nb-good .nb-pulse{background:#3b82f6;}

  /* Bottom row */
  .bottom-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;}
  @media(max-width:640px){.bottom-row{grid-template-columns:1fr;}.windows-grid{grid-template-columns:1fr;}.mode-row{grid-template-columns:repeat(2,1fr);}.col-bar,.col-delta,.col-co2{display:none;}.stats-row{gap:6px;}.stat-val{font-size:0.95rem;}}

  ::-webkit-scrollbar{width:4px;height:4px;}::-webkit-scrollbar-track{background:transparent;}::-webkit-scrollbar-thumb{background:rgba(203,213,225,0.6);border-radius:2px;}

</style>
</head>
<body>
<div class="w-full min-h-screen">

<?= lightNavHTML($user, $isAdmin, 0, $unreadNotif) ?>

<!-- Loading -->
<div id="fuel-loading">
  <!-- Falling coins layer -->
  <div class="coin-rain" id="coinRain"></div>

  <!-- Glow orb -->
  <div style="position:relative;z-index:2;">
    <div class="load-glow"></div>
    <!-- Coin stack SVG -->
    <div class="load-coin-stack">
      <svg class="coin-stack-svg" width="90" height="90" viewBox="0 0 90 90" fill="none">
        <!-- Shadow -->
        <ellipse cx="45" cy="84" rx="28" ry="5" fill="rgba(251,191,36,0.15)"/>
        <!-- Coin 3 (bottom) -->
        <ellipse cx="45" cy="72" rx="28" ry="8" fill="#78350f"/>
        <ellipse cx="45" cy="70" rx="28" ry="8" fill="#b45309"/>
        <ellipse cx="45" cy="68" rx="28" ry="8" fill="#d97706"/>
        <!-- Coin 2 (middle) -->
        <ellipse cx="45" cy="58" rx="28" ry="8" fill="#78350f"/>
        <ellipse cx="45" cy="56" rx="28" ry="8" fill="#b45309"/>
        <ellipse cx="45" cy="54" rx="28" ry="8" fill="#f59e0b"/>
        <!-- Coin 1 (top) -->
        <ellipse cx="45" cy="44" rx="28" ry="8" fill="#78350f"/>
        <ellipse cx="45" cy="42" rx="28" ry="8" fill="#d97706"/>
        <ellipse cx="45" cy="40" rx="28" ry="8" fill="#fbbf24"/>
        <!-- Top face -->
        <ellipse cx="45" cy="32" rx="28" ry="8" fill="#fde68a"/>
        <ellipse cx="45" cy="32" rx="22" ry="6" fill="#fbbf24"/>
        <text x="45" y="36" text-anchor="middle" font-family="'Space Mono',monospace" font-size="11" font-weight="900" fill="#92400e">$</text>
        <!-- Shine -->
        <ellipse cx="38" cy="28" rx="7" ry="3" fill="rgba(255,255,255,0.35)" transform="rotate(-20,38,28)"/>
      </svg>
    </div>
  </div>

  <div class="load-title">AM4 <span>Fuel Oracle</span></div>
  <div class="load-subtitle">Real-Time Fuel Intelligence</div>
  <div class="load-progress-wrap">
    <div class="load-bar-track"><div class="load-bar" id="loadBar"></div></div>
    <div class="load-status" id="loadStatus">INITIALIZING…</div>
  </div>
  <div class="load-counter">
    <span class="load-coin-icon">🪙</span>
    <span id="coinCounter">0</span> coins collected
  </div>
</div>

<div id="hm-tooltip" class="hm-tooltip"></div>

<div class="app">
  <!-- Hero -->
  <div class="fuel-hero">
    <div class="fuel-hero-badge"><span class="fuel-live-dot"></span>Live Tracking</div>
    <h1>AM4 <span>Fuel Oracle</span></h1>
    <p class="fuel-hero-sub">Real-time fuel price intelligence for Airline Manager 4</p>
    <div class="fuel-time-chips">
      <span class="time-chip"><span class="fuel-live-dot" style="width:5px;height:5px;"></span>UTC: <span id="utcClock">--:--:--</span></span>
      <span class="time-chip">Local: <span id="localClock">--:--:--</span></span>
      <span class="time-chip" id="tzChip">TZ: detecting…</span>
    </div>
  </div>

  <!-- Main grid -->
  <div class="fuel-grid">
    <!-- LEFT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Current Price -->
      <div class="card">
        <div class="card-title"><span class="card-title-dot"></span>Current Fuel Price</div>
        <div class="price-hero">
          <div class="price-label">Right Now · Your Timezone</div>
          <div class="price-big t-best" id="priceDisplay">
            <span class="price-prefix">$</span><span id="priceNum">---</span>
          </div>
          <div class="price-badge b-best" id="priceBadge">Loading…</div>
          <div class="co2-strip">
            <span class="co2-lbl">CO₂</span>
            <span class="co2-val" id="co2Val">---</span>
          </div>
          <div style="font-size:10px;color:#4b5563;text-align:center;margin-top:4px;font-family:'Space Mono',monospace;" id="tzInfo">Detecting timezone...</div>
          <!-- Next Best Slot countdown -->
          <div class="next-best-wrap" id="nextBestWrap">
            <div class="nb-pulse" id="nbPulse"></div>
            <div class="nb-icon" id="nbIcon">⏱️</div>
            <div class="nb-body">
              <div class="nb-label" id="nbLabel">Next Best Slot</div>
              <div class="nb-row">
                <span class="nb-timer" id="nbTimer">--:--</span>
                <span class="nb-price" id="nbPrice"></span>
              </div>
              <div class="nb-slot-time" id="nbSlotTime"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats -->
      <div class="card">
        <div class="card-title"><span class="card-title-dot"></span>12h Statistics</div>
        <div class="stats-row">
          <div class="stat-box"><div class="stat-val" id="s-min">---</div><div class="stat-lbl">Min</div></div>
          <div class="stat-box"><div class="stat-val" id="s-avg">---</div><div class="stat-lbl">Avg</div></div>
          <div class="stat-box"><div class="stat-val" id="s-max">---</div><div class="stat-lbl">Max</div></div>
        </div>
        <div class="reco reco-buy" id="recoBox" style="margin-top:14px;">
          <div class="reco-lbl">AI Recommendation</div>
          <span id="recoText">Analyzing market conditions…</span>
        </div>
      </div>

      <!-- Best Windows -->
      <div class="card">
        <div class="card-title"><span class="card-title-dot"></span>Best Windows (Next 12h)</div>
        <div class="windows-grid" id="windowsGrid"></div>
      </div>

    </div>

    <!-- RIGHT COLUMN -->
    <div style="display:flex;flex-direction:column;gap:16px;">

      <!-- Chart + Table -->
      <div class="card">
        <div class="card-title"><span class="card-title-dot"></span>Fuel Price Forecast</div>
        <div class="mode-row">
          <button class="mode-btn active" onclick="setMode('all',this)">All Prices</button>
          <button class="mode-btn" onclick="setMode('best',this)">Best ≤920</button>
          <button class="mode-btn" onclick="setMode('good',this)">Good ≤1430</button>
          <button class="mode-btn" onclick="setMode('avg',this)">Average</button>
          <button class="mode-btn" onclick="setMode('high',this)">High ≥1960</button>
          <button class="mode-btn" onclick="setMode('spike',this)">Spike ≥2400</button>
        </div>
        <div class="h-tabs">
          <button class="h-tab active" onclick="setHorizon(12,this)">12H</button>
          <button class="h-tab" onclick="setHorizon(24,this)">24H</button>
          <button class="h-tab" onclick="setHorizon(6,this)">6H</button>
          <button class="h-tab" onclick="setHorizon(48,this)">48H</button>
        </div>
        <div class="chart-wrap"><canvas id="priceChart"></canvas></div>
        <div class="table-scroll">
          <table class="forecast-table">
            <thead><tr>
              <th>UTC</th><th>Local</th><th>Price</th>
              <th class="col-co2">CO₂</th><th>Level</th>
              <th class="col-delta">Δ</th><th class="col-bar">Bar</th>
            </tr></thead>
            <tbody id="forecastBody"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom row -->
  <div class="bottom-row">
    <!-- Heatmap -->
    <div class="card">
      <div class="card-title"><span class="card-title-dot"></span>Monthly Heatmap · Tap for Details</div>
      <p style="font-size:10px;color:#94a3b8;font-family:'Space Mono',monospace;margin-bottom:10px;">Each column = Day 1→31 · Each row = 30min slot · Scroll →</p>
      <div class="heatmap-scroll"><div class="heatmap-row" id="heatmap"></div></div>
      <div style="display:flex;gap:12px;margin-top:10px;flex-wrap:wrap;font-size:11px;color:#6b7280;">
        <?php foreach([['#22c55e','Best'],['#22d3ee','Good'],['#f59e0b','Avg'],['#fb923c','High'],['#ef4444','Spike']] as [$c,$l]): ?>
          <span style="display:flex;align-items:center;gap:4px;"><span style="width:8px;height:8px;border-radius:2px;background:<?= $c ?>;display:inline-block;"></span><?= $l ?></span>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- CO2 Chart -->
    <div class="card">
      <div class="card-title"><span class="card-title-dot"></span>CO₂ Forecast · Next 12h</div>
      <div class="chart-wrap" style="height:160px;"><canvas id="co2Chart"></canvas></div>
      <div class="stats-row" style="margin-top:12px;">
        <div class="stat-box"><div class="stat-val" style="color:#3b82f6;" id="co2Min">---</div><div class="stat-lbl">Min</div></div>
        <div class="stat-box"><div class="stat-val" style="color:#3b82f6;" id="co2Avg">---</div><div class="stat-lbl">Avg</div></div>
        <div class="stat-box"><div class="stat-val" style="color:#3b82f6;" id="co2Max">---</div><div class="stat-lbl">Max</div></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
// ============ DATA (real prices from DB) ============
const RAW = {"Day1":[{"time":"00:00","fuel":520,"co2":126},{"time":"00:30","fuel":1280,"co2":138},{"time":"01:00","fuel":2140,"co2":142},{"time":"01:30","fuel":340,"co2":191},{"time":"02:00","fuel":1060,"co2":197},{"time":"02:30","fuel":540,"co2":109},{"time":"03:00","fuel":380,"co2":147},{"time":"03:30","fuel":2450,"co2":110},{"time":"04:00","fuel":1890,"co2":157},{"time":"04:30","fuel":800,"co2":114},{"time":"05:00","fuel":590,"co2":124},{"time":"05:30","fuel":2420,"co2":132},{"time":"06:00","fuel":2200,"co2":104},{"time":"06:30","fuel":340,"co2":108},{"time":"07:00","fuel":2200,"co2":175},{"time":"07:30","fuel":1700,"co2":143},{"time":"08:00","fuel":940,"co2":112},{"time":"08:30","fuel":1990,"co2":148},{"time":"09:00","fuel":2280,"co2":178},{"time":"09:30","fuel":1380,"co2":190},{"time":"10:00","fuel":2260,"co2":193},{"time":"10:30","fuel":360,"co2":199},{"time":"11:00","fuel":650,"co2":150},{"time":"11:30","fuel":840,"co2":148},{"time":"12:00","fuel":1280,"co2":133},{"time":"12:30","fuel":2200,"co2":115},{"time":"13:00","fuel":1480,"co2":177},{"time":"13:30","fuel":1080,"co2":183},{"time":"14:00","fuel":1330,"co2":171},{"time":"14:30","fuel":2280,"co2":108},{"time":"15:00","fuel":2430,"co2":107},{"time":"15:30","fuel":1420,"co2":157},{"time":"16:00","fuel":1300,"co2":178},{"time":"16:30","fuel":300,"co2":155},{"time":"17:00","fuel":1070,"co2":143},{"time":"17:30","fuel":1600,"co2":199},{"time":"18:00","fuel":1790,"co2":103},{"time":"18:30","fuel":400,"co2":169},{"time":"19:00","fuel":390,"co2":103},{"time":"19:30","fuel":770,"co2":167},{"time":"20:00","fuel":1580,"co2":197},{"time":"20:30","fuel":2480,"co2":131},{"time":"21:00","fuel":1030,"co2":193},{"time":"21:30","fuel":1640,"co2":197},{"time":"22:00","fuel":1870,"co2":106},{"time":"22:30","fuel":1430,"co2":121},{"time":"23:00","fuel":1930,"co2":157},{"time":"23:30","fuel":2090,"co2":119}],"Day2":[{"time":"00:00","fuel":610,"co2":175},{"time":"00:30","fuel":1960,"co2":152},{"time":"01:00","fuel":1960,"co2":163},{"time":"01:30","fuel":1550,"co2":156},{"time":"02:00","fuel":390,"co2":180},{"time":"02:30","fuel":2210,"co2":188},{"time":"03:00","fuel":1840,"co2":103},{"time":"03:30","fuel":1910,"co2":157},{"time":"04:00","fuel":380,"co2":175},{"time":"04:30","fuel":980,"co2":178},{"time":"05:00","fuel":1930,"co2":125},{"time":"05:30","fuel":2270,"co2":115},{"time":"06:00","fuel":1100,"co2":124},{"time":"06:30","fuel":1820,"co2":108},{"time":"07:00","fuel":1750,"co2":108},{"time":"07:30","fuel":1130,"co2":190},{"time":"08:00","fuel":1640,"co2":181},{"time":"08:30","fuel":1850,"co2":179},{"time":"09:00","fuel":1170,"co2":145},{"time":"09:30","fuel":1790,"co2":183},{"time":"10:00","fuel":2050,"co2":165},{"time":"10:30","fuel":340,"co2":142},{"time":"11:00","fuel":2320,"co2":114},{"time":"11:30","fuel":1110,"co2":108},{"time":"12:00","fuel":1930,"co2":186},{"time":"12:30","fuel":1960,"co2":153},{"time":"13:00","fuel":1230,"co2":163},{"time":"13:30","fuel":1820,"co2":199},{"time":"14:00","fuel":1500,"co2":133},{"time":"14:30","fuel":2190,"co2":133},{"time":"15:00","fuel":850,"co2":160},{"time":"15:30","fuel":2420,"co2":163},{"time":"16:00","fuel":890,"co2":158},{"time":"16:30","fuel":1920,"co2":157},{"time":"17:00","fuel":1900,"co2":200},{"time":"17:30","fuel":1250,"co2":146},{"time":"18:00","fuel":870,"co2":179},{"time":"18:30","fuel":2200,"co2":173},{"time":"19:00","fuel":2360,"co2":160},{"time":"19:30","fuel":2100,"co2":160},{"time":"20:00","fuel":2340,"co2":109},{"time":"20:30","fuel":2480,"co2":188},{"time":"21:00","fuel":980,"co2":156},{"time":"21:30","fuel":720,"co2":195},{"time":"22:00","fuel":1010,"co2":158},{"time":"22:30","fuel":1510,"co2":106},{"time":"23:00","fuel":2080,"co2":167},{"time":"23:30","fuel":1460,"co2":106}],"Day3":[{"time":"00:00","fuel":1590,"co2":182},{"time":"00:30","fuel":420,"co2":140},{"time":"01:00","fuel":2440,"co2":193},{"time":"01:30","fuel":1900,"co2":135},{"time":"02:00","fuel":1640,"co2":137},{"time":"02:30","fuel":870,"co2":159},{"time":"03:00","fuel":1280,"co2":182},{"time":"03:30","fuel":1690,"co2":148},{"time":"04:00","fuel":1630,"co2":125},{"time":"04:30","fuel":490,"co2":128},{"time":"05:00","fuel":940,"co2":149},{"time":"05:30","fuel":1590,"co2":123},{"time":"06:00","fuel":1700,"co2":121},{"time":"06:30","fuel":2450,"co2":181},{"time":"07:00","fuel":550,"co2":158},{"time":"07:30","fuel":2360,"co2":174},{"time":"08:00","fuel":2250,"co2":179},{"time":"08:30","fuel":1800,"co2":139},{"time":"09:00","fuel":2410,"co2":175},{"time":"09:30","fuel":1900,"co2":119},{"time":"10:00","fuel":910,"co2":111},{"time":"10:30","fuel":1480,"co2":149},{"time":"11:00","fuel":1020,"co2":192},{"time":"11:30","fuel":650,"co2":146},{"time":"12:00","fuel":1360,"co2":165},{"time":"12:30","fuel":1380,"co2":110},{"time":"13:00","fuel":950,"co2":186},{"time":"13:30","fuel":2440,"co2":175},{"time":"14:00","fuel":1420,"co2":107},{"time":"14:30","fuel":730,"co2":174},{"time":"15:00","fuel":1240,"co2":152},{"time":"15:30","fuel":1560,"co2":192},{"time":"16:00","fuel":1860,"co2":137},{"time":"16:30","fuel":750,"co2":196},{"time":"17:00","fuel":1330,"co2":140},{"time":"17:30","fuel":1420,"co2":121},{"time":"18:00","fuel":1730,"co2":139},{"time":"18:30","fuel":1070,"co2":193},{"time":"19:00","fuel":670,"co2":191},{"time":"19:30","fuel":2500,"co2":113},{"time":"20:00","fuel":2190,"co2":132},{"time":"20:30","fuel":2050,"co2":133},{"time":"21:00","fuel":1610,"co2":114},{"time":"21:30","fuel":1210,"co2":172},{"time":"22:00","fuel":1170,"co2":111},{"time":"22:30","fuel":720,"co2":120},{"time":"23:00","fuel":2210,"co2":193},{"time":"23:30","fuel":2250,"co2":117}],"Day4":[{"time":"00:00","fuel":1200,"co2":162},{"time":"00:30","fuel":1400,"co2":151},{"time":"01:00","fuel":1750,"co2":124},{"time":"01:30","fuel":2000,"co2":196},{"time":"02:00","fuel":2470,"co2":109},{"time":"02:30","fuel":2060,"co2":169},{"time":"03:00","fuel":2480,"co2":165},{"time":"03:30","fuel":630,"co2":132},{"time":"04:00","fuel":1230,"co2":108},{"time":"04:30","fuel":900,"co2":148},{"time":"05:00","fuel":2280,"co2":144},{"time":"05:30","fuel":2120,"co2":149},{"time":"06:00","fuel":480,"co2":185},{"time":"06:30","fuel":750,"co2":123},{"time":"07:00","fuel":1250,"co2":174},{"time":"07:30","fuel":1770,"co2":165},{"time":"08:00","fuel":1440,"co2":191},{"time":"08:30","fuel":1250,"co2":174},{"time":"09:00","fuel":1410,"co2":133},{"time":"09:30","fuel":530,"co2":188},{"time":"10:00","fuel":2490,"co2":134},{"time":"10:30","fuel":2040,"co2":159},{"time":"11:00","fuel":1100,"co2":151},{"time":"11:30","fuel":310,"co2":105},{"time":"12:00","fuel":650,"co2":173},{"time":"12:30","fuel":2030,"co2":199},{"time":"13:00","fuel":1640,"co2":149},{"time":"13:30","fuel":1330,"co2":146},{"time":"14:00","fuel":810,"co2":136},{"time":"14:30","fuel":1720,"co2":195},{"time":"15:00","fuel":2380,"co2":180},{"time":"15:30","fuel":1890,"co2":113},{"time":"16:00","fuel":930,"co2":134},{"time":"16:30","fuel":1910,"co2":170},{"time":"17:00","fuel":2360,"co2":177},{"time":"17:30","fuel":2260,"co2":149},{"time":"18:00","fuel":930,"co2":142},{"time":"18:30","fuel":1200,"co2":152},{"time":"19:00","fuel":2310,"co2":178},{"time":"19:30","fuel":1430,"co2":144},{"time":"20:00","fuel":560,"co2":159},{"time":"20:30","fuel":1330,"co2":193},{"time":"21:00","fuel":870,"co2":176},{"time":"21:30","fuel":1000,"co2":165},{"time":"22:00","fuel":1310,"co2":189},{"time":"22:30","fuel":1490,"co2":110},{"time":"23:00","fuel":2200,"co2":130},{"time":"23:30","fuel":1720,"co2":100}],"Day5":[{"time":"00:00","fuel":1550,"co2":168},{"time":"00:30","fuel":1370,"co2":173},{"time":"01:00","fuel":2080,"co2":144},{"time":"01:30","fuel":1330,"co2":117},{"time":"02:00","fuel":2480,"co2":111},{"time":"02:30","fuel":2180,"co2":113},{"time":"03:00","fuel":2200,"co2":144},{"time":"03:30","fuel":650,"co2":112},{"time":"04:00","fuel":540,"co2":194},{"time":"04:30","fuel":1780,"co2":119},{"time":"05:00","fuel":1090,"co2":162},{"time":"05:30","fuel":1860,"co2":179},{"time":"06:00","fuel":520,"co2":152},{"time":"06:30","fuel":1250,"co2":191},{"time":"07:00","fuel":2350,"co2":136},{"time":"07:30","fuel":1130,"co2":100},{"time":"08:00","fuel":1510,"co2":175},{"time":"08:30","fuel":1490,"co2":178},{"time":"09:00","fuel":1170,"co2":127},{"time":"09:30","fuel":380,"co2":124},{"time":"10:00","fuel":1550,"co2":193},{"time":"10:30","fuel":2130,"co2":137},{"time":"11:00","fuel":1970,"co2":166},{"time":"11:30","fuel":330,"co2":109},{"time":"12:00","fuel":1080,"co2":160},{"time":"12:30","fuel":1660,"co2":177},{"time":"13:00","fuel":1900,"co2":131},{"time":"13:30","fuel":400,"co2":121},{"time":"14:00","fuel":410,"co2":163},{"time":"14:30","fuel":710,"co2":156},{"time":"15:00","fuel":1200,"co2":125},{"time":"15:30","fuel":1820,"co2":103},{"time":"16:00","fuel":1590,"co2":158},{"time":"16:30","fuel":1160,"co2":175},{"time":"17:00","fuel":600,"co2":111},{"time":"17:30","fuel":660,"co2":115},{"time":"18:00","fuel":550,"co2":119},{"time":"18:30","fuel":1030,"co2":155},{"time":"19:00","fuel":2100,"co2":118},{"time":"19:30","fuel":2070,"co2":162},{"time":"20:00","fuel":2200,"co2":131},{"time":"20:30","fuel":1970,"co2":114},{"time":"21:00","fuel":1860,"co2":156},{"time":"21:30","fuel":1980,"co2":145},{"time":"22:00","fuel":1100,"co2":169},{"time":"22:30","fuel":1220,"co2":108},{"time":"23:00","fuel":1820,"co2":133},{"time":"23:30","fuel":2430,"co2":145}],"Day6":[{"time":"00:00","fuel":580,"co2":186},{"time":"00:30","fuel":980,"co2":145},{"time":"01:00","fuel":640,"co2":169},{"time":"01:30","fuel":1080,"co2":134},{"time":"02:00","fuel":760,"co2":190},{"time":"02:30","fuel":1870,"co2":127},{"time":"03:00","fuel":1180,"co2":107},{"time":"03:30","fuel":2170,"co2":102},{"time":"04:00","fuel":580,"co2":128},{"time":"04:30","fuel":710,"co2":112},{"time":"05:00","fuel":2070,"co2":142},{"time":"05:30","fuel":2420,"co2":130},{"time":"06:00","fuel":1590,"co2":114},{"time":"06:30","fuel":2200,"co2":191},{"time":"07:00","fuel":1230,"co2":122},{"time":"07:30","fuel":1450,"co2":174},{"time":"08:00","fuel":1520,"co2":146},{"time":"08:30","fuel":1450,"co2":196},{"time":"09:00","fuel":960,"co2":124},{"time":"09:30","fuel":1500,"co2":104},{"time":"10:00","fuel":1380,"co2":181},{"time":"10:30","fuel":490,"co2":140},{"time":"11:00","fuel":1670,"co2":113},{"time":"11:30","fuel":1930,"co2":123},{"time":"12:00","fuel":310,"co2":169},{"time":"12:30","fuel":760,"co2":121},{"time":"13:00","fuel":970,"co2":129},{"time":"13:30","fuel":720,"co2":106},{"time":"14:00","fuel":470,"co2":169},{"time":"14:30","fuel":1950,"co2":101},{"time":"15:00","fuel":420,"co2":135},{"time":"15:30","fuel":1500,"co2":162},{"time":"16:00","fuel":2260,"co2":191},{"time":"16:30","fuel":1590,"co2":176},{"time":"17:00","fuel":1450,"co2":127},{"time":"17:30","fuel":2320,"co2":170},{"time":"18:00","fuel":1520,"co2":193},{"time":"18:30","fuel":1020,"co2":192},{"time":"19:00","fuel":2230,"co2":199},{"time":"19:30","fuel":1300,"co2":185},{"time":"20:00","fuel":1050,"co2":187},{"time":"20:30","fuel":2430,"co2":112},{"time":"21:00","fuel":1940,"co2":102},{"time":"21:30","fuel":460,"co2":164},{"time":"22:00","fuel":1520,"co2":111},{"time":"22:30","fuel":560,"co2":193},{"time":"23:00","fuel":2200,"co2":147},{"time":"23:30","fuel":2120,"co2":108}],"Day7":[{"time":"00:00","fuel":2020,"co2":107},{"time":"00:30","fuel":1560,"co2":195},{"time":"01:00","fuel":560,"co2":143},{"time":"01:30","fuel":2400,"co2":112},{"time":"02:00","fuel":680,"co2":101},{"time":"02:30","fuel":1300,"co2":141},{"time":"03:00","fuel":1630,"co2":150},{"time":"03:30","fuel":1050,"co2":185},{"time":"04:00","fuel":2060,"co2":110},{"time":"04:30","fuel":380,"co2":117},{"time":"05:00","fuel":610,"co2":188},{"time":"05:30","fuel":1690,"co2":184},{"time":"06:00","fuel":1260,"co2":134},{"time":"06:30","fuel":2430,"co2":180},{"time":"07:00","fuel":1390,"co2":173},{"time":"07:30","fuel":2490,"co2":167},{"time":"08:00","fuel":2340,"co2":113},{"time":"08:30","fuel":1460,"co2":190},{"time":"09:00","fuel":1780,"co2":134},{"time":"09:30","fuel":2000,"co2":132},{"time":"10:00","fuel":2470,"co2":178},{"time":"10:30","fuel":1170,"co2":109},{"time":"11:00","fuel":340,"co2":125},{"time":"11:30","fuel":720,"co2":117},{"time":"12:00","fuel":510,"co2":187},{"time":"12:30","fuel":1250,"co2":178},{"time":"13:00","fuel":2010,"co2":106},{"time":"13:30","fuel":630,"co2":157},{"time":"14:00","fuel":1080,"co2":194},{"time":"14:30","fuel":690,"co2":176},{"time":"15:00","fuel":1630,"co2":159},{"time":"15:30","fuel":1410,"co2":170},{"time":"16:00","fuel":1170,"co2":128},{"time":"16:30","fuel":1930,"co2":156},{"time":"17:00","fuel":2020,"co2":188},{"time":"17:30","fuel":1420,"co2":120},{"time":"18:00","fuel":1430,"co2":118},{"time":"18:30","fuel":360,"co2":168},{"time":"19:00","fuel":1960,"co2":144},{"time":"19:30","fuel":1290,"co2":152},{"time":"20:00","fuel":2320,"co2":118},{"time":"20:30","fuel":1950,"co2":179},{"time":"21:00","fuel":1900,"co2":187},{"time":"21:30","fuel":1640,"co2":143},{"time":"22:00","fuel":1450,"co2":113},{"time":"22:30","fuel":1150,"co2":160},{"time":"23:00","fuel":2360,"co2":128},{"time":"23:30","fuel":450,"co2":199}],"Day8":[{"time":"00:00","fuel":1460,"co2":168},{"time":"00:30","fuel":2140,"co2":189},{"time":"01:00","fuel":1520,"co2":183},{"time":"01:30","fuel":370,"co2":145},{"time":"02:00","fuel":720,"co2":196},{"time":"02:30","fuel":720,"co2":188},{"time":"03:00","fuel":1880,"co2":176},{"time":"03:30","fuel":2370,"co2":194},{"time":"04:00","fuel":610,"co2":109},{"time":"04:30","fuel":970,"co2":103},{"time":"05:00","fuel":910,"co2":173},{"time":"05:30","fuel":1550,"co2":132},{"time":"06:00","fuel":1450,"co2":119},{"time":"06:30","fuel":2400,"co2":172},{"time":"07:00","fuel":400,"co2":122},{"time":"07:30","fuel":1580,"co2":185},{"time":"08:00","fuel":1360,"co2":129},{"time":"08:30","fuel":720,"co2":126},{"time":"09:00","fuel":1120,"co2":157},{"time":"09:30","fuel":1090,"co2":168},{"time":"10:00","fuel":1140,"co2":157},{"time":"10:30","fuel":500,"co2":106},{"time":"11:00","fuel":490,"co2":167},{"time":"11:30","fuel":1220,"co2":195},{"time":"12:00","fuel":1930,"co2":187},{"time":"12:30","fuel":1170,"co2":168},{"time":"13:00","fuel":560,"co2":118},{"time":"13:30","fuel":1160,"co2":143},{"time":"14:00","fuel":320,"co2":186},{"time":"14:30","fuel":760,"co2":196},{"time":"15:00","fuel":620,"co2":176},{"time":"15:30","fuel":2480,"co2":156},{"time":"16:00","fuel":680,"co2":158},{"time":"16:30","fuel":1370,"co2":104},{"time":"17:00","fuel":2220,"co2":138},{"time":"17:30","fuel":1630,"co2":106},{"time":"18:00","fuel":340,"co2":155},{"time":"18:30","fuel":1760,"co2":153},{"time":"19:00","fuel":2480,"co2":175},{"time":"19:30","fuel":1240,"co2":179},{"time":"20:00","fuel":930,"co2":164},{"time":"20:30","fuel":1790,"co2":146},{"time":"21:00","fuel":1900,"co2":172},{"time":"21:30","fuel":2270,"co2":134},{"time":"22:00","fuel":2120,"co2":163},{"time":"22:30","fuel":1590,"co2":115},{"time":"23:00","fuel":1360,"co2":122},{"time":"23:30","fuel":390,"co2":139}],"Day9":[{"time":"00:00","fuel":2250,"co2":174},{"time":"00:30","fuel":2230,"co2":181},{"time":"01:00","fuel":1920,"co2":197},{"time":"01:30","fuel":2200,"co2":129},{"time":"02:00","fuel":2150,"co2":186},{"time":"02:30","fuel":2450,"co2":163},{"time":"03:00","fuel":430,"co2":140},{"time":"03:30","fuel":440,"co2":167},{"time":"04:00","fuel":1360,"co2":100},{"time":"04:30","fuel":1680,"co2":121},{"time":"05:00","fuel":1540,"co2":170},{"time":"05:30","fuel":920,"co2":177},{"time":"06:00","fuel":1640,"co2":189},{"time":"06:30","fuel":2110,"co2":102},{"time":"07:00","fuel":2370,"co2":167},{"time":"07:30","fuel":950,"co2":104},{"time":"08:00","fuel":1680,"co2":154},{"time":"08:30","fuel":650,"co2":175},{"time":"09:00","fuel":1050,"co2":195},{"time":"09:30","fuel":2090,"co2":175},{"time":"10:00","fuel":880,"co2":150},{"time":"10:30","fuel":1840,"co2":157},{"time":"11:00","fuel":2430,"co2":200},{"time":"11:30","fuel":1740,"co2":188},{"time":"12:00","fuel":2190,"co2":119},{"time":"12:30","fuel":1550,"co2":118},{"time":"13:00","fuel":1080,"co2":174},{"time":"13:30","fuel":2220,"co2":142},{"time":"14:00","fuel":830,"co2":121},{"time":"14:30","fuel":1150,"co2":193},{"time":"15:00","fuel":1580,"co2":178},{"time":"15:30","fuel":1410,"co2":162},{"time":"16:00","fuel":1760,"co2":167},{"time":"16:30","fuel":340,"co2":115},{"time":"17:00","fuel":2410,"co2":174},{"time":"17:30","fuel":1590,"co2":121},{"time":"18:00","fuel":2130,"co2":148},{"time":"18:30","fuel":960,"co2":150},{"time":"19:00","fuel":1130,"co2":123},{"time":"19:30","fuel":1520,"co2":107},{"time":"20:00","fuel":710,"co2":147},{"time":"20:30","fuel":730,"co2":179},{"time":"21:00","fuel":1640,"co2":145},{"time":"21:30","fuel":1640,"co2":188},{"time":"22:00","fuel":1350,"co2":141},{"time":"22:30","fuel":1590,"co2":116},{"time":"23:00","fuel":1210,"co2":106},{"time":"23:30","fuel":1700,"co2":186}],"Day10":[{"time":"00:00","fuel":2270,"co2":105},{"time":"00:30","fuel":1540,"co2":139},{"time":"01:00","fuel":1230,"co2":107},{"time":"01:30","fuel":300,"co2":132},{"time":"02:00","fuel":460,"co2":117},{"time":"02:30","fuel":970,"co2":126},{"time":"03:00","fuel":2500,"co2":141},{"time":"03:30","fuel":2380,"co2":169},{"time":"04:00","fuel":330,"co2":175},{"time":"04:30","fuel":1980,"co2":189},{"time":"05:00","fuel":1820,"co2":153},{"time":"05:30","fuel":860,"co2":104},{"time":"06:00","fuel":880,"co2":153},{"time":"06:30","fuel":1630,"co2":194},{"time":"07:00","fuel":670,"co2":161},{"time":"07:30","fuel":2300,"co2":137},{"time":"08:00","fuel":540,"co2":173},{"time":"08:30","fuel":2020,"co2":167},{"time":"09:00","fuel":920,"co2":152},{"time":"09:30","fuel":1390,"co2":121},{"time":"10:00","fuel":2350,"co2":170},{"time":"10:30","fuel":2430,"co2":114},{"time":"11:00","fuel":2340,"co2":163},{"time":"11:30","fuel":2040,"co2":137},{"time":"12:00","fuel":510,"co2":160},{"time":"12:30","fuel":460,"co2":127},{"time":"13:00","fuel":2080,"co2":192},{"time":"13:30","fuel":2230,"co2":128},{"time":"14:00","fuel":1890,"co2":111},{"time":"14:30","fuel":2240,"co2":146},{"time":"15:00","fuel":730,"co2":187},{"time":"15:30","fuel":940,"co2":148},{"time":"16:00","fuel":340,"co2":151},{"time":"16:30","fuel":2400,"co2":134},{"time":"17:00","fuel":1950,"co2":154},{"time":"17:30","fuel":2330,"co2":154},{"time":"18:00","fuel":1090,"co2":131},{"time":"18:30","fuel":810,"co2":105},{"time":"19:00","fuel":330,"co2":126},{"time":"19:30","fuel":1480,"co2":108},{"time":"20:00","fuel":1020,"co2":182},{"time":"20:30","fuel":2220,"co2":111},{"time":"21:00","fuel":2070,"co2":151},{"time":"21:30","fuel":1160,"co2":127},{"time":"22:00","fuel":1220,"co2":170},{"time":"22:30","fuel":1900,"co2":172},{"time":"23:00","fuel":1370,"co2":110},{"time":"23:30","fuel":1850,"co2":163}],"Day11":[{"time":"00:00","fuel":430,"co2":158},{"time":"00:30","fuel":1400,"co2":162},{"time":"01:00","fuel":340,"co2":102},{"time":"01:30","fuel":1880,"co2":148},{"time":"02:00","fuel":1070,"co2":146},{"time":"02:30","fuel":1240,"co2":108},{"time":"03:00","fuel":1190,"co2":147},{"time":"03:30","fuel":720,"co2":118},{"time":"04:00","fuel":2200,"co2":132},{"time":"04:30","fuel":2400,"co2":182},{"time":"05:00","fuel":420,"co2":199},{"time":"05:30","fuel":1520,"co2":172},{"time":"06:00","fuel":1970,"co2":124},{"time":"06:30","fuel":1030,"co2":199},{"time":"07:00","fuel":2500,"co2":169},{"time":"07:30","fuel":1230,"co2":106},{"time":"08:00","fuel":840,"co2":102},{"time":"08:30","fuel":320,"co2":105},{"time":"09:00","fuel":1120,"co2":197},{"time":"09:30","fuel":1930,"co2":179},{"time":"10:00","fuel":9300,"co2":180},{"time":"10:30","fuel":750,"co2":155},{"time":"11:00","fuel":1470,"co2":181},{"time":"11:30","fuel":480,"co2":125},{"time":"12:00","fuel":1310,"co2":100},{"time":"12:30","fuel":1190,"co2":159},{"time":"13:00","fuel":1340,"co2":192},{"time":"13:30","fuel":1380,"co2":190},{"time":"14:00","fuel":650,"co2":189},{"time":"14:30","fuel":1280,"co2":134},{"time":"15:00","fuel":1440,"co2":189},{"time":"15:30","fuel":960,"co2":191},{"time":"16:00","fuel":2120,"co2":180},{"time":"16:30","fuel":2170,"co2":139},{"time":"17:00","fuel":1580,"co2":101},{"time":"17:30","fuel":1330,"co2":152},{"time":"18:00","fuel":320,"co2":103},{"time":"18:30","fuel":1410,"co2":173},{"time":"19:00","fuel":2150,"co2":196},{"time":"19:30","fuel":2180,"co2":140},{"time":"20:00","fuel":310,"co2":164},{"time":"20:30","fuel":1870,"co2":157},{"time":"21:00","fuel":500,"co2":131},{"time":"21:30","fuel":2410,"co2":193},{"time":"22:00","fuel":980,"co2":116},{"time":"22:30","fuel":2430,"co2":137},{"time":"23:00","fuel":1720,"co2":102},{"time":"23:30","fuel":1930,"co2":179}],"Day12":[{"time":"00:00","fuel":1930,"co2":179},{"time":"00:30","fuel":1980,"co2":175},{"time":"01:00","fuel":1500,"co2":191},{"time":"01:30","fuel":1090,"co2":200},{"time":"02:00","fuel":810,"co2":188},{"time":"02:30","fuel":1630,"co2":106},{"time":"03:00","fuel":1360,"co2":165},{"time":"03:30","fuel":1790,"co2":172},{"time":"04:00","fuel":2150,"co2":113},{"time":"04:30","fuel":780,"co2":143},{"time":"05:00","fuel":1330,"co2":160},{"time":"05:30","fuel":2090,"co2":142},{"time":"06:00","fuel":1820,"co2":152},{"time":"06:30","fuel":1070,"co2":157},{"time":"07:00","fuel":2020,"co2":119},{"time":"07:30","fuel":500,"co2":194},{"time":"08:00","fuel":510,"co2":163},{"time":"08:30","fuel":2040,"co2":181},{"time":"09:00","fuel":590,"co2":160},{"time":"09:30","fuel":2190,"co2":168},{"time":"10:00","fuel":700,"co2":174},{"time":"10:30","fuel":540,"co2":184},{"time":"11:00","fuel":1380,"co2":164},{"time":"11:30","fuel":690,"co2":110},{"time":"12:00","fuel":1310,"co2":174},{"time":"12:30","fuel":1560,"co2":144},{"time":"13:00","fuel":1350,"co2":136},{"time":"13:30","fuel":450,"co2":198},{"time":"14:00","fuel":2040,"co2":102},{"time":"14:30","fuel":1560,"co2":114},{"time":"15:00","fuel":1360,"co2":116},{"time":"15:30","fuel":1240,"co2":120},{"time":"16:00","fuel":980,"co2":156},{"time":"16:30","fuel":630,"co2":147},{"time":"17:00","fuel":910,"co2":100},{"time":"17:30","fuel":900,"co2":102},{"time":"18:00","fuel":660,"co2":197},{"time":"18:30","fuel":1500,"co2":127},{"time":"19:00","fuel":950,"co2":127},{"time":"19:30","fuel":1010,"co2":166},{"time":"20:00","fuel":930,"co2":200},{"time":"20:30","fuel":1980,"co2":125},{"time":"21:00","fuel":690,"co2":106},{"time":"21:30","fuel":1030,"co2":100},{"time":"22:00","fuel":1320,"co2":192},{"time":"22:30","fuel":480,"co2":142},{"time":"23:00","fuel":1980,"co2":123},{"time":"23:30","fuel":1590,"co2":107}],"Day13":[{"time":"00:00","fuel":1770,"co2":144},{"time":"00:30","fuel":1470,"co2":160},{"time":"01:00","fuel":2230,"co2":184},{"time":"01:30","fuel":930,"co2":176},{"time":"02:00","fuel":910,"co2":172},{"time":"02:30","fuel":2190,"co2":183},{"time":"03:00","fuel":1280,"co2":105},{"time":"03:30","fuel":1620,"co2":184},{"time":"04:00","fuel":2010,"co2":110},{"time":"04:30","fuel":480,"co2":148},{"time":"05:00","fuel":2020,"co2":164},{"time":"05:30","fuel":2020,"co2":185},{"time":"06:00","fuel":2420,"co2":146},{"time":"06:30","fuel":510,"co2":119},{"time":"07:00","fuel":1960,"co2":138},{"time":"07:30","fuel":2170,"co2":133},{"time":"08:00","fuel":1130,"co2":107},{"time":"08:30","fuel":1700,"co2":129},{"time":"09:00","fuel":1860,"co2":169},{"time":"09:30","fuel":2250,"co2":146},{"time":"10:00","fuel":1690,"co2":125},{"time":"10:30","fuel":1700,"co2":144},{"time":"11:00","fuel":520,"co2":182},{"time":"11:30","fuel":450,"co2":157},{"time":"12:00","fuel":1800,"co2":114},{"time":"12:30","fuel":1020,"co2":105},{"time":"13:00","fuel":730,"co2":192},{"time":"13:30","fuel":1620,"co2":195},{"time":"14:00","fuel":2320,"co2":167},{"time":"14:30","fuel":1730,"co2":165},{"time":"15:00","fuel":1900,"co2":146},{"time":"15:30","fuel":950,"co2":171},{"time":"16:00","fuel":920,"co2":197},{"time":"16:30","fuel":2060,"co2":138},{"time":"17:00","fuel":1690,"co2":175},{"time":"17:30","fuel":500,"co2":142},{"time":"18:00","fuel":2160,"co2":139},{"time":"18:30","fuel":2140,"co2":107},{"time":"19:00","fuel":1090,"co2":158},{"time":"19:30","fuel":840,"co2":121},{"time":"20:00","fuel":1410,"co2":154},{"time":"20:30","fuel":1900,"co2":132},{"time":"21:00","fuel":1160,"co2":186},{"time":"21:30","fuel":1200,"co2":127},{"time":"22:00","fuel":2160,"co2":184},{"time":"22:30","fuel":320,"co2":122},{"time":"23:00","fuel":1920,"co2":194},{"time":"23:30","fuel":1850,"co2":101}],"Day14":[{"time":"00:00","fuel":1840,"co2":159},{"time":"00:30","fuel":2040,"co2":190},{"time":"01:00","fuel":1270,"co2":127},{"time":"01:30","fuel":380,"co2":168},{"time":"02:00","fuel":1900,"co2":158},{"time":"02:30","fuel":1100,"co2":136},{"time":"03:00","fuel":2160,"co2":150},{"time":"03:30","fuel":430,"co2":130},{"time":"04:00","fuel":2120,"co2":200},{"time":"04:30","fuel":1450,"co2":172},{"time":"05:00","fuel":530,"co2":159},{"time":"05:30","fuel":1280,"co2":138},{"time":"06:00","fuel":1880,"co2":136},{"time":"06:30","fuel":1610,"co2":192},{"time":"07:00","fuel":1000,"co2":123},{"time":"07:30","fuel":610,"co2":156},{"time":"08:00","fuel":1800,"co2":142},{"time":"08:30","fuel":930,"co2":151},{"time":"09:00","fuel":1150,"co2":154},{"time":"09:30","fuel":680,"co2":146},{"time":"10:00","fuel":940,"co2":138},{"time":"10:30","fuel":1750,"co2":154},{"time":"11:00","fuel":1480,"co2":186},{"time":"11:30","fuel":2200,"co2":149},{"time":"12:00","fuel":2390,"co2":112},{"time":"12:30","fuel":1570,"co2":133},{"time":"13:00","fuel":1340,"co2":115},{"time":"13:30","fuel":440,"co2":168},{"time":"14:00","fuel":370,"co2":120},{"time":"14:30","fuel":1390,"co2":126},{"time":"15:00","fuel":1170,"co2":113},{"time":"15:30","fuel":890,"co2":128},{"time":"16:00","fuel":1350,"co2":118},{"time":"16:30","fuel":2240,"co2":169},{"time":"17:00","fuel":2110,"co2":171},{"time":"17:30","fuel":520,"co2":110},{"time":"18:00","fuel":720,"co2":186},{"time":"18:30","fuel":1500,"co2":194},{"time":"19:00","fuel":2360,"co2":136},{"time":"19:30","fuel":1850,"co2":138},{"time":"20:00","fuel":1020,"co2":185},{"time":"20:30","fuel":2210,"co2":157},{"time":"21:00","fuel":310,"co2":168},{"time":"21:30","fuel":960,"co2":176},{"time":"22:00","fuel":590,"co2":118},{"time":"22:30","fuel":1830,"co2":119},{"time":"23:00","fuel":2320,"co2":129},{"time":"23:30","fuel":1530,"co2":135}],"Day15":[{"time":"00:00","fuel":850,"co2":161},{"time":"00:30","fuel":2380,"co2":171},{"time":"01:00","fuel":1530,"co2":134},{"time":"01:30","fuel":2420,"co2":131},{"time":"02:00","fuel":1020,"co2":142},{"time":"02:30","fuel":580,"co2":191},{"time":"03:00","fuel":2430,"co2":142},{"time":"03:30","fuel":2200,"co2":117},{"time":"04:00","fuel":440,"co2":115},{"time":"04:30","fuel":1600,"co2":129},{"time":"05:00","fuel":1200,"co2":112},{"time":"05:30","fuel":510,"co2":112},{"time":"06:00","fuel":1670,"co2":151},{"time":"06:30","fuel":320,"co2":112},{"time":"07:00","fuel":590,"co2":128},{"time":"07:30","fuel":1040,"co2":149},{"time":"08:00","fuel":950,"co2":197},{"time":"08:30","fuel":1860,"co2":174},{"time":"09:00","fuel":480,"co2":139},{"time":"09:30","fuel":2210,"co2":124},{"time":"10:00","fuel":1860,"co2":198},{"time":"10:30","fuel":1540,"co2":121},{"time":"11:00","fuel":1280,"co2":115},{"time":"11:30","fuel":1980,"co2":161},{"time":"12:00","fuel":770,"co2":180},{"time":"12:30","fuel":1380,"co2":188},{"time":"13:00","fuel":1300,"co2":162},{"time":"13:30","fuel":500,"co2":143},{"time":"14:00","fuel":300,"co2":194},{"time":"14:30","fuel":1090,"co2":164},{"time":"15:00","fuel":970,"co2":175},{"time":"15:30","fuel":1790,"co2":164},{"time":"16:00","fuel":540,"co2":164},{"time":"16:30","fuel":370,"co2":198},{"time":"17:00","fuel":2210,"co2":190},{"time":"17:30","fuel":870,"co2":160},{"time":"18:00","fuel":1590,"co2":138},{"time":"18:30","fuel":420,"co2":199},{"time":"19:00","fuel":880,"co2":156},{"time":"19:30","fuel":1790,"co2":115},{"time":"20:00","fuel":1880,"co2":123},{"time":"20:30","fuel":2290,"co2":101},{"time":"21:00","fuel":710,"co2":192},{"time":"21:30","fuel":1250,"co2":106},{"time":"22:00","fuel":2300,"co2":192},{"time":"22:30","fuel":1980,"co2":117},{"time":"23:00","fuel":2020,"co2":102},{"time":"23:30","fuel":360,"co2":195}],"Day16":[{"time":"00:00","fuel":2190,"co2":162},{"time":"00:30","fuel":1670,"co2":179},{"time":"01:00","fuel":2130,"co2":173},{"time":"01:30","fuel":1560,"co2":120},{"time":"02:00","fuel":2210,"co2":109},{"time":"02:30","fuel":1860,"co2":184},{"time":"03:00","fuel":1240,"co2":169},{"time":"03:30","fuel":740,"co2":163},{"time":"04:00","fuel":2260,"co2":156},{"time":"04:30","fuel":2380,"co2":119},{"time":"05:00","fuel":1300,"co2":168},{"time":"05:30","fuel":2230,"co2":191},{"time":"06:00","fuel":1350,"co2":114},{"time":"06:30","fuel":2200,"co2":178},{"time":"07:00","fuel":340,"co2":165},{"time":"07:30","fuel":2360,"co2":110},{"time":"08:00","fuel":1540,"co2":186},{"time":"08:30","fuel":1320,"co2":166},{"time":"09:00","fuel":1180,"co2":119},{"time":"09:30","fuel":2000,"co2":121},{"time":"10:00","fuel":1440,"co2":150},{"time":"10:30","fuel":2120,"co2":146},{"time":"11:00","fuel":500,"co2":109},{"time":"11:30","fuel":480,"co2":171},{"time":"12:00","fuel":1720,"co2":178},{"time":"12:30","fuel":630,"co2":185},{"time":"13:00","fuel":1810,"co2":149},{"time":"13:30","fuel":1000,"co2":197},{"time":"14:00","fuel":1210,"co2":168},{"time":"14:30","fuel":1910,"co2":192},{"time":"15:00","fuel":1790,"co2":189},{"time":"15:30","fuel":1780,"co2":162},{"time":"16:00","fuel":1840,"co2":170},{"time":"16:30","fuel":2260,"co2":162},{"time":"17:00","fuel":1700,"co2":144},{"time":"17:30","fuel":1320,"co2":184},{"time":"18:00","fuel":590,"co2":132},{"time":"18:30","fuel":2130,"co2":133},{"time":"19:00","fuel":2470,"co2":126},{"time":"19:30","fuel":1770,"co2":100},{"time":"20:00","fuel":2280,"co2":122},{"time":"20:30","fuel":1380,"co2":121},{"time":"21:00","fuel":470,"co2":135},{"time":"21:30","fuel":310,"co2":159},{"time":"22:00","fuel":700,"co2":111},{"time":"22:30","fuel":1850,"co2":123},{"time":"23:00","fuel":2230,"co2":111},{"time":"23:30","fuel":1070,"co2":171}],"Day17":[{"time":"00:00","fuel":1010,"co2":165},{"time":"00:30","fuel":1530,"co2":147},{"time":"01:00","fuel":470,"co2":177},{"time":"01:30","fuel":1330,"co2":104},{"time":"02:00","fuel":2420,"co2":130},{"time":"02:30","fuel":1090,"co2":123},{"time":"03:00","fuel":2480,"co2":138},{"time":"03:30","fuel":790,"co2":153},{"time":"04:00","fuel":1360,"co2":108},{"time":"04:30","fuel":430,"co2":117},{"time":"05:00","fuel":820,"co2":195},{"time":"05:30","fuel":2450,"co2":125},{"time":"06:00","fuel":540,"co2":167},{"time":"06:30","fuel":470,"co2":115},{"time":"07:00","fuel":650,"co2":111},{"time":"07:30","fuel":1840,"co2":131},{"time":"08:00","fuel":1820,"co2":163},{"time":"08:30","fuel":2430,"co2":104},{"time":"09:00","fuel":1870,"co2":129},{"time":"09:30","fuel":2430,"co2":162},{"time":"10:00","fuel":950,"co2":121},{"time":"10:30","fuel":640,"co2":189},{"time":"11:00","fuel":510,"co2":161},{"time":"11:30","fuel":1130,"co2":147},{"time":"12:00","fuel":1750,"co2":128},{"time":"12:30","fuel":980,"co2":170},{"time":"13:00","fuel":730,"co2":186},{"time":"13:30","fuel":1300,"co2":141},{"time":"14:00","fuel":680,"co2":189},{"time":"14:30","fuel":420,"co2":174},{"time":"15:00","fuel":950,"co2":155},{"time":"15:30","fuel":1430,"co2":191},{"time":"16:00","fuel":1040,"co2":160},{"time":"16:30","fuel":1340,"co2":180},{"time":"17:00","fuel":1100,"co2":186},{"time":"17:30","fuel":1890,"co2":120},{"time":"18:00","fuel":2440,"co2":188},{"time":"18:30","fuel":1210,"co2":174},{"time":"19:00","fuel":1130,"co2":199},{"time":"19:30","fuel":1810,"co2":198},{"time":"20:00","fuel":720,"co2":159},{"time":"20:30","fuel":1990,"co2":117},{"time":"21:00","fuel":1030,"co2":173},{"time":"21:30","fuel":850,"co2":131},{"time":"22:00","fuel":1050,"co2":113},{"time":"22:30","fuel":1230,"co2":199},{"time":"23:00","fuel":670,"co2":158},{"time":"23:30","fuel":740,"co2":101}],"Day18":[{"time":"00:00","fuel":580,"co2":109},{"time":"00:30","fuel":360,"co2":196},{"time":"01:00","fuel":2300,"co2":195},{"time":"01:30","fuel":940,"co2":138},{"time":"02:00","fuel":1850,"co2":165},{"time":"02:30","fuel":1980,"co2":113},{"time":"03:00","fuel":430,"co2":188},{"time":"03:30","fuel":440,"co2":147},{"time":"04:00","fuel":350,"co2":133},{"time":"04:30","fuel":540,"co2":166},{"time":"05:00","fuel":2250,"co2":113},{"time":"05:30","fuel":780,"co2":103},{"time":"06:00","fuel":470,"co2":127},{"time":"06:30","fuel":1860,"co2":135},{"time":"07:00","fuel":2480,"co2":132},{"time":"07:30","fuel":560,"co2":132},{"time":"08:00","fuel":1480,"co2":159},{"time":"08:30","fuel":1330,"co2":160},{"time":"09:00","fuel":1760,"co2":103},{"time":"09:30","fuel":2100,"co2":182},{"time":"10:00","fuel":690,"co2":163},{"time":"10:30","fuel":330,"co2":181},{"time":"11:00","fuel":2040,"co2":179},{"time":"11:30","fuel":1180,"co2":109},{"time":"12:00","fuel":340,"co2":158},{"time":"12:30","fuel":810,"co2":167},{"time":"13:00","fuel":1090,"co2":140},{"time":"13:30","fuel":1570,"co2":172},{"time":"14:00","fuel":1250,"co2":102},{"time":"14:30","fuel":1440,"co2":127},{"time":"15:00","fuel":2180,"co2":148},{"time":"15:30","fuel":1420,"co2":149},{"time":"16:00","fuel":1040,"co2":181},{"time":"16:30","fuel":2370,"co2":146},{"time":"17:00","fuel":2460,"co2":123},{"time":"17:30","fuel":2480,"co2":134},{"time":"18:00","fuel":1040,"co2":186},{"time":"18:30","fuel":1030,"co2":194},{"time":"19:00","fuel":430,"co2":120},{"time":"19:30","fuel":1580,"co2":200},{"time":"20:00","fuel":660,"co2":185},{"time":"20:30","fuel":2350,"co2":138},{"time":"21:00","fuel":400,"co2":154},{"time":"21:30","fuel":2400,"co2":147},{"time":"22:00","fuel":1440,"co2":102},{"time":"22:30","fuel":1060,"co2":200},{"time":"23:00","fuel":760,"co2":140},{"time":"23:30","fuel":300,"co2":115}],"Day19":[{"time":"00:00","fuel":690,"co2":111},{"time":"00:30","fuel":1600,"co2":145},{"time":"01:00","fuel":390,"co2":192},{"time":"01:30","fuel":1650,"co2":122},{"time":"02:00","fuel":490,"co2":179},{"time":"02:30","fuel":780,"co2":169},{"time":"03:00","fuel":1190,"co2":162},{"time":"03:30","fuel":1080,"co2":102},{"time":"04:00","fuel":1770,"co2":149},{"time":"04:30","fuel":980,"co2":164},{"time":"05:00","fuel":1110,"co2":163},{"time":"05:30","fuel":910,"co2":134},{"time":"06:00","fuel":1740,"co2":103},{"time":"06:30","fuel":810,"co2":163},{"time":"07:00","fuel":1150,"co2":112},{"time":"07:30","fuel":1430,"co2":165},{"time":"08:00","fuel":1600,"co2":183},{"time":"08:30","fuel":1630,"co2":140},{"time":"09:00","fuel":1700,"co2":152},{"time":"09:30","fuel":1860,"co2":171},{"time":"10:00","fuel":1790,"co2":105},{"time":"10:30","fuel":960,"co2":139},{"time":"11:00","fuel":2170,"co2":193},{"time":"11:30","fuel":1450,"co2":197},{"time":"12:00","fuel":390,"co2":179},{"time":"12:30","fuel":1050,"co2":184},{"time":"13:00","fuel":640,"co2":199},{"time":"13:30","fuel":860,"co2":118},{"time":"14:00","fuel":2110,"co2":195},{"time":"14:30","fuel":1440,"co2":119},{"time":"15:00","fuel":1030,"co2":163},{"time":"15:30","fuel":540,"co2":106},{"time":"16:00","fuel":1610,"co2":160},{"time":"16:30","fuel":990,"co2":142},{"time":"17:00","fuel":780,"co2":156},{"time":"17:30","fuel":920,"co2":121},{"time":"18:00","fuel":1050,"co2":187},{"time":"18:30","fuel":1820,"co2":137},{"time":"19:00","fuel":1660,"co2":153},{"time":"19:30","fuel":640,"co2":118},{"time":"20:00","fuel":1700,"co2":109},{"time":"20:30","fuel":340,"co2":109},{"time":"21:00","fuel":1090,"co2":128},{"time":"21:30","fuel":1420,"co2":168},{"time":"22:00","fuel":780,"co2":157},{"time":"22:30","fuel":2040,"co2":186},{"time":"23:00","fuel":650,"co2":182},{"time":"23:30","fuel":940,"co2":136}],"Day20":[{"time":"00:00","fuel":1450,"co2":160},{"time":"00:30","fuel":2270,"co2":153},{"time":"01:00","fuel":1610,"co2":110},{"time":"01:30","fuel":340,"co2":154},{"time":"02:00","fuel":1650,"co2":144},{"time":"02:30","fuel":1020,"co2":193},{"time":"03:00","fuel":830,"co2":111},{"time":"03:30","fuel":1800,"co2":130},{"time":"04:00","fuel":2460,"co2":163},{"time":"04:30","fuel":2330,"co2":190},{"time":"05:00","fuel":320,"co2":200},{"time":"05:30","fuel":1080,"co2":153},{"time":"06:00","fuel":1380,"co2":188},{"time":"06:30","fuel":2180,"co2":102},{"time":"07:00","fuel":2070,"co2":172},{"time":"07:30","fuel":1030,"co2":168},{"time":"08:00","fuel":1410,"co2":194},{"time":"08:30","fuel":1590,"co2":129},{"time":"09:00","fuel":1050,"co2":110},{"time":"09:30","fuel":350,"co2":165},{"time":"10:00","fuel":1200,"co2":190},{"time":"10:30","fuel":1830,"co2":179},{"time":"11:00","fuel":2320,"co2":155},{"time":"11:30","fuel":1270,"co2":126},{"time":"12:00","fuel":2160,"co2":107},{"time":"12:30","fuel":300,"co2":162},{"time":"13:00","fuel":2500,"co2":149},{"time":"13:30","fuel":670,"co2":123},{"time":"14:00","fuel":710,"co2":121},{"time":"14:30","fuel":1930,"co2":192},{"time":"15:00","fuel":310,"co2":173},{"time":"15:30","fuel":360,"co2":124},{"time":"16:00","fuel":1810,"co2":167},{"time":"16:30","fuel":340,"co2":184},{"time":"17:00","fuel":2250,"co2":177},{"time":"17:30","fuel":1080,"co2":187},{"time":"18:00","fuel":1410,"co2":151},{"time":"18:30","fuel":2390,"co2":174},{"time":"19:00","fuel":740,"co2":123},{"time":"19:30","fuel":2010,"co2":133},{"time":"20:00","fuel":1270,"co2":187},{"time":"20:30","fuel":1820,"co2":121},{"time":"21:00","fuel":1000,"co2":121},{"time":"21:30","fuel":820,"co2":132},{"time":"22:00","fuel":1930,"co2":160},{"time":"22:30","fuel":1380,"co2":186},{"time":"23:00","fuel":920,"co2":112},{"time":"23:30","fuel":1760,"co2":161}],"Day21":[{"time":"00:00","fuel":1370,"co2":139},{"time":"00:30","fuel":1890,"co2":121},{"time":"01:00","fuel":1270,"co2":168},{"time":"01:30","fuel":2090,"co2":193},{"time":"02:00","fuel":2140,"co2":120},{"time":"02:30","fuel":1890,"co2":174},{"time":"03:00","fuel":1780,"co2":149},{"time":"03:30","fuel":770,"co2":128},{"time":"04:00","fuel":1680,"co2":181},{"time":"04:30","fuel":2490,"co2":167},{"time":"05:00","fuel":1360,"co2":168},{"time":"05:30","fuel":1810,"co2":156},{"time":"06:00","fuel":1840,"co2":191},{"time":"06:30","fuel":1010,"co2":174},{"time":"07:00","fuel":540,"co2":186},{"time":"07:30","fuel":2440,"co2":145},{"time":"08:00","fuel":1520,"co2":187},{"time":"08:30","fuel":930,"co2":189},{"time":"09:00","fuel":1010,"co2":156},{"time":"09:30","fuel":950,"co2":182},{"time":"10:00","fuel":1610,"co2":155},{"time":"10:30","fuel":2370,"co2":180},{"time":"11:00","fuel":1400,"co2":112},{"time":"11:30","fuel":2070,"co2":124},{"time":"12:00","fuel":2010,"co2":176},{"time":"12:30","fuel":1230,"co2":154},{"time":"13:00","fuel":2030,"co2":175},{"time":"13:30","fuel":2100,"co2":142},{"time":"14:00","fuel":1660,"co2":144},{"time":"14:30","fuel":1600,"co2":150},{"time":"15:00","fuel":1800,"co2":159},{"time":"15:30","fuel":1180,"co2":149},{"time":"16:00","fuel":880,"co2":101},{"time":"16:30","fuel":480,"co2":139},{"time":"17:00","fuel":760,"co2":167},{"time":"17:30","fuel":1810,"co2":191},{"time":"18:00","fuel":570,"co2":147},{"time":"18:30","fuel":400,"co2":142},{"time":"19:00","fuel":1980,"co2":142},{"time":"19:30","fuel":2040,"co2":167},{"time":"20:00","fuel":760,"co2":186},{"time":"20:30","fuel":620,"co2":170},{"time":"21:00","fuel":650,"co2":139},{"time":"21:30","fuel":2270,"co2":170},{"time":"22:00","fuel":2210,"co2":114},{"time":"22:30","fuel":2120,"co2":199},{"time":"23:00","fuel":690,"co2":190},{"time":"23:30","fuel":1320,"co2":192}],"Day22":[{"time":"00:00","fuel":2270,"co2":103},{"time":"00:30","fuel":390,"co2":188},{"time":"01:00","fuel":2120,"co2":137},{"time":"01:30","fuel":420,"co2":171},{"time":"02:00","fuel":1660,"co2":107},{"time":"02:30","fuel":880,"co2":143},{"time":"03:00","fuel":2480,"co2":198},{"time":"03:30","fuel":2070,"co2":121},{"time":"04:00","fuel":1950,"co2":187},{"time":"04:30","fuel":1330,"co2":189},{"time":"05:00","fuel":1610,"co2":124},{"time":"05:30","fuel":1500,"co2":189},{"time":"06:00","fuel":400,"co2":135},{"time":"06:30","fuel":1820,"co2":175},{"time":"07:00","fuel":960,"co2":155},{"time":"07:30","fuel":1860,"co2":108},{"time":"08:00","fuel":1040,"co2":170},{"time":"08:30","fuel":1230,"co2":134},{"time":"09:00","fuel":1330,"co2":181},{"time":"09:30","fuel":820,"co2":188},{"time":"10:00","fuel":680,"co2":105},{"time":"10:30","fuel":970,"co2":141},{"time":"11:00","fuel":700,"co2":144},{"time":"11:30","fuel":1900,"co2":176},{"time":"12:00","fuel":1800,"co2":197},{"time":"12:30","fuel":1490,"co2":117},{"time":"13:00","fuel":570,"co2":142},{"time":"13:30","fuel":1530,"co2":154},{"time":"14:00","fuel":2200,"co2":139},{"time":"14:30","fuel":960,"co2":116},{"time":"15:00","fuel":1120,"co2":199},{"time":"15:30","fuel":680,"co2":162},{"time":"16:00","fuel":1140,"co2":169},{"time":"16:30","fuel":2250,"co2":192},{"time":"17:00","fuel":2200,"co2":200},{"time":"17:30","fuel":970,"co2":176},{"time":"18:00","fuel":1370,"co2":102},{"time":"18:30","fuel":1010,"co2":116},{"time":"19:00","fuel":2370,"co2":124},{"time":"19:30","fuel":1840,"co2":194},{"time":"20:00","fuel":360,"co2":121},{"time":"20:30","fuel":1080,"co2":152},{"time":"21:00","fuel":860,"co2":196},{"time":"21:30","fuel":1260,"co2":169},{"time":"22:00","fuel":800,"co2":175},{"time":"22:30","fuel":460,"co2":129},{"time":"23:00","fuel":2310,"co2":162},{"time":"23:30","fuel":1330,"co2":197}],"Day23":[{"time":"00:00","fuel":1610,"co2":186},{"time":"00:30","fuel":2230,"co2":112},{"time":"01:00","fuel":1330,"co2":135},{"time":"01:30","fuel":890,"co2":116},{"time":"02:00","fuel":2410,"co2":135},{"time":"02:30","fuel":1700,"co2":150},{"time":"03:00","fuel":1910,"co2":162},{"time":"03:30","fuel":890,"co2":182},{"time":"04:00","fuel":1120,"co2":183},{"time":"04:30","fuel":1680,"co2":106},{"time":"05:00","fuel":310,"co2":184},{"time":"05:30","fuel":2130,"co2":169},{"time":"06:00","fuel":2380,"co2":130},{"time":"06:30","fuel":1720,"co2":168},{"time":"07:00","fuel":2130,"co2":135},{"time":"07:30","fuel":530,"co2":110},{"time":"08:00","fuel":2040,"co2":188},{"time":"08:30","fuel":1410,"co2":114},{"time":"09:00","fuel":1340,"co2":198},{"time":"09:30","fuel":2200,"co2":134},{"time":"10:00","fuel":2370,"co2":138},{"time":"10:30","fuel":2060,"co2":182},{"time":"11:00","fuel":2400,"co2":133},{"time":"11:30","fuel":1190,"co2":156},{"time":"12:00","fuel":1170,"co2":162},{"time":"12:30","fuel":590,"co2":111},{"time":"13:00","fuel":440,"co2":170},{"time":"13:30","fuel":2240,"co2":171},{"time":"14:00","fuel":2200,"co2":140},{"time":"14:30","fuel":1630,"co2":149},{"time":"15:00","fuel":1900,"co2":140},{"time":"15:30","fuel":450,"co2":140},{"time":"16:00","fuel":1700,"co2":143},{"time":"16:30","fuel":1750,"co2":175},{"time":"17:00","fuel":1340,"co2":197},{"time":"17:30","fuel":1130,"co2":171},{"time":"18:00","fuel":2170,"co2":108},{"time":"18:30","fuel":880,"co2":138},{"time":"19:00","fuel":1350,"co2":164},{"time":"19:30","fuel":1500,"co2":130},{"time":"20:00","fuel":1570,"co2":119},{"time":"20:30","fuel":500,"co2":120},{"time":"21:00","fuel":1800,"co2":139},{"time":"21:30","fuel":2390,"co2":172},{"time":"22:00","fuel":1470,"co2":142},{"time":"22:30","fuel":1000,"co2":117},{"time":"23:00","fuel":2080,"co2":143},{"time":"23:30","fuel":590,"co2":127}],"Day24":[{"time":"00:00","fuel":1680,"co2":126},{"time":"00:30","fuel":2330,"co2":160},{"time":"01:00","fuel":2450,"co2":131},{"time":"01:30","fuel":2060,"co2":171},{"time":"02:00","fuel":1580,"co2":186},{"time":"02:30","fuel":2490,"co2":115},{"time":"03:00","fuel":1130,"co2":120},{"time":"03:30","fuel":1430,"co2":160},{"time":"04:00","fuel":2280,"co2":155},{"time":"04:30","fuel":1190,"co2":108},{"time":"05:00","fuel":1410,"co2":113},{"time":"05:30","fuel":2350,"co2":145},{"time":"06:00","fuel":1780,"co2":189},{"time":"06:30","fuel":1550,"co2":108},{"time":"07:00","fuel":1900,"co2":161},{"time":"07:30","fuel":470,"co2":114},{"time":"08:00","fuel":2420,"co2":113},{"time":"08:30","fuel":2350,"co2":130},{"time":"09:00","fuel":2030,"co2":129},{"time":"09:30","fuel":410,"co2":153},{"time":"10:00","fuel":2110,"co2":184},{"time":"10:30","fuel":1080,"co2":163},{"time":"11:00","fuel":2410,"co2":102},{"time":"11:30","fuel":1100,"co2":130},{"time":"12:00","fuel":1850,"co2":193},{"time":"12:30","fuel":490,"co2":112},{"time":"13:00","fuel":870,"co2":135},{"time":"13:30","fuel":2440,"co2":156},{"time":"14:00","fuel":1110,"co2":150},{"time":"14:30","fuel":1230,"co2":105},{"time":"15:00","fuel":470,"co2":133},{"time":"15:30","fuel":1420,"co2":135},{"time":"16:00","fuel":1430,"co2":188},{"time":"16:30","fuel":1260,"co2":136},{"time":"17:00","fuel":2490,"co2":109},{"time":"17:30","fuel":2040,"co2":109},{"time":"18:00","fuel":1870,"co2":166},{"time":"18:30","fuel":1140,"co2":138},{"time":"19:00","fuel":1090,"co2":100},{"time":"19:30","fuel":2350,"co2":200},{"time":"20:00","fuel":1260,"co2":123},{"time":"20:30","fuel":340,"co2":155},{"time":"21:00","fuel":1790,"co2":136},{"time":"21:30","fuel":1580,"co2":180},{"time":"22:00","fuel":610,"co2":186},{"time":"22:30","fuel":2030,"co2":197},{"time":"23:00","fuel":1540,"co2":157},{"time":"23:30","fuel":1920,"co2":195}],"Day25":[{"time":"00:00","fuel":1820,"co2":179},{"time":"00:30","fuel":380,"co2":105},{"time":"01:00","fuel":1470,"co2":176},{"time":"01:30","fuel":2090,"co2":158},{"time":"02:00","fuel":1350,"co2":184},{"time":"02:30","fuel":1480,"co2":176},{"time":"03:00","fuel":1000,"co2":113},{"time":"03:30","fuel":1000,"co2":119},{"time":"04:00","fuel":2140,"co2":151},{"time":"04:30","fuel":430,"co2":170},{"time":"05:00","fuel":1160,"co2":196},{"time":"05:30","fuel":1030,"co2":108},{"time":"06:00","fuel":1590,"co2":100},{"time":"06:30","fuel":1030,"co2":107},{"time":"07:00","fuel":1740,"co2":118},{"time":"07:30","fuel":1540,"co2":176},{"time":"08:00","fuel":2300,"co2":115},{"time":"08:30","fuel":1800,"co2":104},{"time":"09:00","fuel":1040,"co2":135},{"time":"09:30","fuel":1720,"co2":122},{"time":"10:00","fuel":1630,"co2":148},{"time":"10:30","fuel":1250,"co2":165},{"time":"11:00","fuel":1810,"co2":119},{"time":"11:30","fuel":560,"co2":197},{"time":"12:00","fuel":1510,"co2":138},{"time":"12:30","fuel":1990,"co2":160},{"time":"13:00","fuel":2170,"co2":176},{"time":"13:30","fuel":2350,"co2":114},{"time":"14:00","fuel":1520,"co2":151},{"time":"14:30","fuel":2470,"co2":111},{"time":"15:00","fuel":1020,"co2":174},{"time":"15:30","fuel":1110,"co2":186},{"time":"16:00","fuel":1440,"co2":187},{"time":"16:30","fuel":1670,"co2":182},{"time":"17:00","fuel":1720,"co2":159},{"time":"17:30","fuel":430,"co2":121},{"time":"18:00","fuel":2000,"co2":135},{"time":"18:30","fuel":370,"co2":105},{"time":"19:00","fuel":1060,"co2":174},{"time":"19:30","fuel":2260,"co2":106},{"time":"20:00","fuel":1170,"co2":198},{"time":"20:30","fuel":1360,"co2":185},{"time":"21:00","fuel":2080,"co2":170},{"time":"21:30","fuel":870,"co2":113},{"time":"22:00","fuel":1200,"co2":196},{"time":"22:30","fuel":2220,"co2":125},{"time":"23:00","fuel":1560,"co2":140},{"time":"23:30","fuel":2250,"co2":152}],"Day26":[{"time":"00:00","fuel":630,"co2":111},{"time":"00:30","fuel":1950,"co2":176},{"time":"01:00","fuel":1790,"co2":145},{"time":"01:30","fuel":2450,"co2":115},{"time":"02:00","fuel":2280,"co2":149},{"time":"02:30","fuel":1950,"co2":157},{"time":"03:00","fuel":2230,"co2":104},{"time":"03:30","fuel":1260,"co2":104},{"time":"04:00","fuel":1870,"co2":110},{"time":"04:30","fuel":420,"co2":129},{"time":"05:00","fuel":1760,"co2":125},{"time":"05:30","fuel":2370,"co2":165},{"time":"06:00","fuel":690,"co2":188},{"time":"06:30","fuel":1720,"co2":100},{"time":"07:00","fuel":1900,"co2":138},{"time":"07:30","fuel":850,"co2":174},{"time":"08:00","fuel":380,"co2":191},{"time":"08:30","fuel":1710,"co2":157},{"time":"09:00","fuel":1140,"co2":152},{"time":"09:30","fuel":1230,"co2":193},{"time":"10:00","fuel":540,"co2":185},{"time":"10:30","fuel":2360,"co2":160},{"time":"11:00","fuel":2470,"co2":193},{"time":"11:30","fuel":390,"co2":157},{"time":"12:00","fuel":1850,"co2":105},{"time":"12:30","fuel":670,"co2":181},{"time":"13:00","fuel":1390,"co2":200},{"time":"13:30","fuel":2450,"co2":172},{"time":"14:00","fuel":690,"co2":169},{"time":"14:30","fuel":1950,"co2":126},{"time":"15:00","fuel":760,"co2":127},{"time":"15:30","fuel":1940,"co2":162},{"time":"16:00","fuel":1810,"co2":197},{"time":"16:30","fuel":2250,"co2":125},{"time":"17:00","fuel":1550,"co2":118},{"time":"17:30","fuel":710,"co2":188},{"time":"18:00","fuel":1960,"co2":106},{"time":"18:30","fuel":2020,"co2":166},{"time":"19:00","fuel":740,"co2":119},{"time":"19:30","fuel":1990,"co2":149},{"time":"20:00","fuel":1160,"co2":100},{"time":"20:30","fuel":600,"co2":110},{"time":"21:00","fuel":960,"co2":133},{"time":"21:30","fuel":950,"co2":172},{"time":"22:00","fuel":940,"co2":144},{"time":"22:30","fuel":920,"co2":186},{"time":"23:00","fuel":1400,"co2":144},{"time":"23:30","fuel":1460,"co2":192}],"Day27":[{"time":"00:00","fuel":1450,"co2":104},{"time":"00:30","fuel":800,"co2":179},{"time":"01:00","fuel":1180,"co2":182},{"time":"01:30","fuel":390,"co2":117},{"time":"02:00","fuel":1900,"co2":182},{"time":"02:30","fuel":1830,"co2":150},{"time":"03:00","fuel":1160,"co2":180},{"time":"03:30","fuel":1200,"co2":121},{"time":"04:00","fuel":940,"co2":188},{"time":"04:30","fuel":1670,"co2":186},{"time":"05:00","fuel":1440,"co2":189},{"time":"05:30","fuel":530,"co2":125},{"time":"06:00","fuel":860,"co2":128},{"time":"06:30","fuel":890,"co2":134},{"time":"07:00","fuel":1660,"co2":138},{"time":"07:30","fuel":2320,"co2":122},{"time":"08:00","fuel":1480,"co2":186},{"time":"08:30","fuel":830,"co2":155},{"time":"09:00","fuel":1740,"co2":162},{"time":"09:30","fuel":340,"co2":136},{"time":"10:00","fuel":640,"co2":100},{"time":"10:30","fuel":590,"co2":115},{"time":"11:00","fuel":2100,"co2":196},{"time":"11:30","fuel":720,"co2":154},{"time":"12:00","fuel":1560,"co2":137},{"time":"12:30","fuel":2390,"co2":150},{"time":"13:00","fuel":650,"co2":119},{"time":"13:30","fuel":1160,"co2":165},{"time":"14:00","fuel":2010,"co2":107},{"time":"14:30","fuel":780,"co2":123},{"time":"15:00","fuel":2260,"co2":107},{"time":"15:30","fuel":1620,"co2":143},{"time":"16:00","fuel":1140,"co2":129},{"time":"16:30","fuel":1240,"co2":110},{"time":"17:00","fuel":350,"co2":127},{"time":"17:30","fuel":1500,"co2":124},{"time":"18:00","fuel":1200,"co2":118},{"time":"18:30","fuel":490,"co2":136},{"time":"19:00","fuel":1940,"co2":165},{"time":"19:30","fuel":1360,"co2":197},{"time":"20:00","fuel":1240,"co2":163},{"time":"20:30","fuel":1490,"co2":179},{"time":"21:00","fuel":1320,"co2":100},{"time":"21:30","fuel":2010,"co2":167},{"time":"22:00","fuel":440,"co2":164},{"time":"22:30","fuel":1620,"co2":129},{"time":"23:00","fuel":2050,"co2":172},{"time":"23:30","fuel":2240,"co2":130}],"Day28":[{"time":"00:00","fuel":460,"co2":134},{"time":"00:30","fuel":1430,"co2":115},{"time":"01:00","fuel":1650,"co2":153},{"time":"01:30","fuel":1610,"co2":123},{"time":"02:00","fuel":1660,"co2":138},{"time":"02:30","fuel":1850,"co2":111},{"time":"03:00","fuel":1210,"co2":132},{"time":"03:30","fuel":310,"co2":116},{"time":"04:00","fuel":2160,"co2":112},{"time":"04:30","fuel":360,"co2":132},{"time":"05:00","fuel":1970,"co2":198},{"time":"05:30","fuel":2400,"co2":158},{"time":"06:00","fuel":500,"co2":128},{"time":"06:30","fuel":700,"co2":120},{"time":"07:00","fuel":560,"co2":191},{"time":"07:30","fuel":1120,"co2":152},{"time":"08:00","fuel":920,"co2":123},{"time":"08:30","fuel":1410,"co2":176},{"time":"09:00","fuel":2410,"co2":187},{"time":"09:30","fuel":1540,"co2":169},{"time":"10:00","fuel":1980,"co2":194},{"time":"10:30","fuel":1950,"co2":144},{"time":"11:00","fuel":2100,"co2":171},{"time":"11:30","fuel":1230,"co2":128},{"time":"12:00","fuel":2230,"co2":130},{"time":"12:30","fuel":1020,"co2":189},{"time":"13:00","fuel":1280,"co2":119},{"time":"13:30","fuel":710,"co2":167},{"time":"14:00","fuel":980,"co2":179},{"time":"14:30","fuel":650,"co2":187},{"time":"15:00","fuel":1640,"co2":164},{"time":"15:30","fuel":1520,"co2":123},{"time":"16:00","fuel":1230,"co2":153},{"time":"16:30","fuel":2370,"co2":132},{"time":"17:00","fuel":1890,"co2":179},{"time":"17:30","fuel":2170,"co2":150},{"time":"18:00","fuel":2140,"co2":158},{"time":"18:30","fuel":640,"co2":133},{"time":"19:00","fuel":1190,"co2":155},{"time":"19:30","fuel":2010,"co2":160},{"time":"20:00","fuel":1900,"co2":177},{"time":"20:30","fuel":2140,"co2":100},{"time":"21:00","fuel":2480,"co2":105},{"time":"21:30","fuel":1900,"co2":161},{"time":"22:00","fuel":2130,"co2":173},{"time":"22:30","fuel":330,"co2":179},{"time":"23:00","fuel":530,"co2":130},{"time":"23:30","fuel":1010,"co2":100}],"Day29":[{"time":"00:00","fuel":400,"co2":149},{"time":"00:30","fuel":1130,"co2":132},{"time":"01:00","fuel":1840,"co2":182},{"time":"01:30","fuel":1740,"co2":146},{"time":"02:00","fuel":1090,"co2":190},{"time":"02:30","fuel":1030,"co2":114},{"time":"03:00","fuel":1760,"co2":123},{"time":"03:30","fuel":1920,"co2":122},{"time":"04:00","fuel":1750,"co2":168},{"time":"04:30","fuel":2260,"co2":108},{"time":"05:00","fuel":1070,"co2":173},{"time":"05:30","fuel":2270,"co2":189},{"time":"06:00","fuel":790,"co2":121},{"time":"06:30","fuel":2320,"co2":100},{"time":"07:00","fuel":2040,"co2":147},{"time":"07:30","fuel":420,"co2":180},{"time":"08:00","fuel":1070,"co2":197},{"time":"08:30","fuel":2390,"co2":119},{"time":"09:00","fuel":810,"co2":135},{"time":"09:30","fuel":580,"co2":146},{"time":"10:00","fuel":940,"co2":189},{"time":"10:30","fuel":2020,"co2":190},{"time":"11:00","fuel":2450,"co2":145},{"time":"11:30","fuel":2490,"co2":168},{"time":"12:00","fuel":1050,"co2":170},{"time":"12:30","fuel":1610,"co2":118},{"time":"13:00","fuel":880,"co2":126},{"time":"13:30","fuel":320,"co2":162},{"time":"14:00","fuel":780,"co2":104},{"time":"14:30","fuel":1580,"co2":189},{"time":"15:00","fuel":1000,"co2":101},{"time":"15:30","fuel":850,"co2":188},{"time":"16:00","fuel":980,"co2":175},{"time":"16:30","fuel":2270,"co2":109},{"time":"17:00","fuel":2210,"co2":162},{"time":"17:30","fuel":1260,"co2":174},{"time":"18:00","fuel":2500,"co2":111},{"time":"18:30","fuel":1900,"co2":122},{"time":"19:00","fuel":400,"co2":158},{"time":"19:30","fuel":1810,"co2":148},{"time":"20:00","fuel":1110,"co2":144},{"time":"20:30","fuel":760,"co2":102},{"time":"21:00","fuel":680,"co2":131},{"time":"21:30","fuel":2260,"co2":129},{"time":"22:00","fuel":1110,"co2":144},{"time":"22:30","fuel":500,"co2":144},{"time":"23:00","fuel":1220,"co2":135},{"time":"23:30","fuel":1690,"co2":189}],"Day30":[{"time":"00:00","fuel":570,"co2":130},{"time":"00:30","fuel":830,"co2":129},{"time":"01:00","fuel":430,"co2":151},{"time":"01:30","fuel":1710,"co2":161},{"time":"02:00","fuel":960,"co2":127},{"time":"02:30","fuel":2360,"co2":162},{"time":"03:00","fuel":1710,"co2":137},{"time":"03:30","fuel":1680,"co2":191},{"time":"04:00","fuel":650,"co2":194},{"time":"04:30","fuel":380,"co2":184},{"time":"05:00","fuel":520,"co2":170},{"time":"05:30","fuel":720,"co2":137},{"time":"06:00","fuel":2120,"co2":105},{"time":"06:30","fuel":810,"co2":181},{"time":"07:00","fuel":2010,"co2":158},{"time":"07:30","fuel":790,"co2":132},{"time":"08:00","fuel":1350,"co2":128},{"time":"08:30","fuel":600,"co2":123},{"time":"09:00","fuel":2110,"co2":108},{"time":"09:30","fuel":2180,"co2":108},{"time":"10:00","fuel":1850,"co2":149},{"time":"10:30","fuel":1600,"co2":135},{"time":"11:00","fuel":470,"co2":141},{"time":"11:30","fuel":2130,"co2":159},{"time":"12:00","fuel":2330,"co2":123},{"time":"12:30","fuel":1070,"co2":157},{"time":"13:00","fuel":1150,"co2":139},{"time":"13:30","fuel":590,"co2":174},{"time":"14:00","fuel":2110,"co2":149},{"time":"14:30","fuel":1310,"co2":195},{"time":"15:00","fuel":2330,"co2":180},{"time":"15:30","fuel":2390,"co2":189},{"time":"16:00","fuel":450,"co2":176},{"time":"16:30","fuel":2230,"co2":106},{"time":"17:00","fuel":1720,"co2":196},{"time":"17:30","fuel":2090,"co2":196},{"time":"18:00","fuel":330,"co2":107},{"time":"18:30","fuel":1130,"co2":113},{"time":"19:00","fuel":2060,"co2":199},{"time":"19:30","fuel":1250,"co2":155},{"time":"20:00","fuel":2310,"co2":111},{"time":"20:30","fuel":2040,"co2":197},{"time":"21:00","fuel":1490,"co2":177},{"time":"21:30","fuel":560,"co2":197},{"time":"22:00","fuel":1750,"co2":113},{"time":"22:30","fuel":2300,"co2":105},{"time":"23:00","fuel":1930,"co2":125},{"time":"23:30","fuel":510,"co2":158}],"Day31":[{"time":"00:00","fuel":2230,"co2":153},{"time":"00:30","fuel":840,"co2":133},{"time":"01:00","fuel":950,"co2":196},{"time":"01:30","fuel":1840,"co2":111},{"time":"02:00","fuel":2070,"co2":103},{"time":"02:30","fuel":950,"co2":109},{"time":"03:00","fuel":1860,"co2":113},{"time":"03:30","fuel":1240,"co2":158},{"time":"04:00","fuel":2140,"co2":178},{"time":"04:30","fuel":1410,"co2":149},{"time":"05:00","fuel":2330,"co2":150},{"time":"05:30","fuel":1100,"co2":178},{"time":"06:00","fuel":650,"co2":160},{"time":"06:30","fuel":1330,"co2":191},{"time":"07:00","fuel":510,"co2":186},{"time":"07:30","fuel":930,"co2":195},{"time":"08:00","fuel":950,"co2":130},{"time":"08:30","fuel":2120,"co2":150},{"time":"09:00","fuel":1730,"co2":148},{"time":"09:30","fuel":2060,"co2":136},{"time":"10:00","fuel":2460,"co2":169},{"time":"10:30","fuel":2470,"co2":164},{"time":"11:00","fuel":460,"co2":156},{"time":"11:30","fuel":2460,"co2":198},{"time":"12:00","fuel":1960,"co2":180},{"time":"12:30","fuel":1500,"co2":154},{"time":"13:00","fuel":1370,"co2":196},{"time":"13:30","fuel":2240,"co2":158},{"time":"14:00","fuel":1830,"co2":195},{"time":"14:30","fuel":370,"co2":130},{"time":"15:00","fuel":1530,"co2":123},{"time":"15:30","fuel":1180,"co2":107},{"time":"16:00","fuel":370,"co2":164},{"time":"16:30","fuel":400,"co2":189},{"time":"17:00","fuel":1380,"co2":116},{"time":"17:30","fuel":1250,"co2":182},{"time":"18:00","fuel":2020,"co2":139},{"time":"18:30","fuel":990,"co2":121},{"time":"19:00","fuel":2240,"co2":153},{"time":"19:30","fuel":1410,"co2":162},{"time":"20:00","fuel":540,"co2":119},{"time":"20:30","fuel":2450,"co2":134},{"time":"21:00","fuel":1020,"co2":164},{"time":"21:30","fuel":2160,"co2":180},{"time":"22:00","fuel":2480,"co2":154},{"time":"22:30","fuel":2260,"co2":174},{"time":"23:00","fuel":2390,"co2":164},{"time":"23:30","fuel":2410,"co2":164}]};

const T={BEST:920,GOOD:1430,AVG:1960,HIGH:2399};
function getTier(f){if(f<=T.BEST)return'best';if(f<=T.GOOD)return'good';if(f<=T.AVG)return'avg';if(f<=T.HIGH)return'high';return'spike';}
function tierColor(t){return{best:'#22c55e',good:'#22d3ee',avg:'#f59e0b',high:'#fb923c',spike:'#ef4444'}[t];}
function tierLabel(t){return{best:'BEST BUY',good:'GOOD',avg:'AVERAGE',high:'HIGH',spike:'SPIKE'}[t];}
function tierBadge(t){return't-'+t;}
function badgeCls(t){return'b-'+t;}

let curMode='all', curHorizon=12, pChart=null, co2C=null;

// ---- FIXED: use actual UTC date-of-month (same as dark version) ----
function getUTCSlotIndex(){
  const n=new Date();
  return n.getUTCHours()*2+(n.getUTCMinutes()>=30?1:0);
}
function getUTCDayIndex(){
  const d=new Date().getUTCDate();
  return Math.min(d,31)-1;
}

function getForecast(h){
  const slots=[];
  const sd=getUTCDayIndex(), ss=getUTCSlotIndex();
  for(let i=0;i<h*2;i++){
    const abs=ss+i;
    const di=(sd+Math.floor(abs/48))%31;
    const si=abs%48;
    const k='Day'+(di+1);
    const s=RAW[k][si];
    slots.push({...s,absOffset:i});
  }
  return slots;
}
function curSlot(){
  return RAW['Day'+(getUTCDayIndex()+1)][getUTCSlotIndex()];
}
function utcToLocal(t){
  const n=new Date();const[h,m]=t.split(':').map(Number);
  const d=new Date(Date.UTC(n.getUTCFullYear(),n.getUTCMonth(),n.getUTCDate(),h,m));
  return d.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',hour12:false});
}

function updatePrice(){
  const s=curSlot();const t=getTier(s.fuel);
  document.getElementById('priceNum').textContent=s.fuel.toLocaleString();
  document.getElementById('priceDisplay').className='price-big '+tierBadge(t);
  document.getElementById('priceBadge').className='price-badge '+badgeCls(t);
  document.getElementById('priceBadge').textContent=tierLabel(t);
  document.getElementById('co2Val').textContent=s.co2;
  const tz=Intl.DateTimeFormat().resolvedOptions().timeZone;
  const off=(-new Date().getTimezoneOffset()/60);
  document.getElementById('tzInfo').textContent=tz+' (UTC'+(off>=0?'+':'')+off+') · Slot: '+s.time+' UTC';
}
function updateCountdown(){
  const wrap=document.getElementById('nextBestWrap');
  const timerEl=document.getElementById('nbTimer');
  const priceEl=document.getElementById('nbPrice');
  const slotEl=document.getElementById('nbSlotTime');
  const labelEl=document.getElementById('nbLabel');
  const iconEl=document.getElementById('nbIcon');
  const pulseEl=document.getElementById('nbPulse');

  // Get next 24h of slots, skip absOffset=0 (current), find BEST then GOOD
  const upcoming=getForecast(24).filter(s=>s.absOffset>0);
  const nextBest=upcoming.find(s=>getTier(s.fuel)==='best')
                ||upcoming.find(s=>getTier(s.fuel)==='good');

  if(!nextBest){
    wrap.className='next-best-wrap nb-none';
    iconEl.textContent='✅';
    labelEl.textContent='No better slot in next 24h';
    timerEl.textContent='—';
    priceEl.textContent='';
    slotEl.textContent='No improvement forecast';
    pulseEl.style.display='none';
    return;
  }

  const isBest=getTier(nextBest.fuel)==='best';
  wrap.className='next-best-wrap'+(isBest?'':' nb-good');
  pulseEl.style.display='';
  pulseEl.style.background=isBest?'#22c55e':'#3b82f6';
  iconEl.textContent=isBest?'🏆':'👍';
  labelEl.textContent=isBest?'Next BEST Price Window':'Next GOOD Price Window';

  // Seconds remaining = slots-ahead × 1800 minus seconds already elapsed in current slot
  const now=new Date();
  const secsIntoSlot=(now.getUTCMinutes()%30)*60+now.getUTCSeconds();
  let diffSec=nextBest.absOffset*1800-secsIntoSlot;
  if(diffSec<0)diffSec=0;

  const hh=Math.floor(diffSec/3600);
  const mm=Math.floor((diffSec%3600)/60);
  const ss=diffSec%60;
  const pad=n=>String(n).padStart(2,'0');
  timerEl.style.color=isBest?'#16a34a':'#2563eb';
  timerEl.textContent=hh>0?`${hh}:${pad(mm)}:${pad(ss)}`:`${pad(mm)}:${pad(ss)}`;
  priceEl.textContent=`$${nextBest.fuel.toLocaleString()} · CO₂ ${nextBest.co2}`;
  slotEl.textContent=`Slot at ${nextBest.time} UTC · ${tierLabel(getTier(nextBest.fuel))}`;
}

function updateStats(slots){
  const f=slots.map(s=>s.fuel);
  document.getElementById('s-min').textContent=Math.min(...f).toLocaleString();
  document.getElementById('s-avg').textContent=Math.round(f.reduce((a,b)=>a+b)/f.length).toLocaleString();
  document.getElementById('s-max').textContent=Math.max(...f).toLocaleString();
}
function updateReco(slots){
  const cur=curSlot().fuel;const curTier=getTier(cur);
  const fuels=slots.map(s=>s.fuel);
  const avg12=Math.round(fuels.reduce((a,b)=>a+b)/fuels.length);
  const min12=Math.min(...fuels);
  const box=document.getElementById('recoBox');const txt=document.getElementById('recoText');

  // Step 1 — look at the very next slots (up to 3 = 90 min) for a significantly cheaper price
  const soonBetter=slots.slice(0,3).find(s=>s.fuel<cur && (getTier(s.fuel)<curTier||s.fuel<=cur*0.75));

  // Step 2 — look within 3 hours (6 slots) for any BEST-tier slot
  const bestWithin3h=slots.slice(0,6).find(s=>getTier(s.fuel)==='best');

  // Step 3 — nearest BEST or GOOD slot anywhere in the forecast
  const nextGoodSlot=slots.find(s=>getTier(s.fuel)==='best'||getTier(s.fuel)==='good');

  // --- Decision tree ---

  // Always: if the very next slot (30 min) is meaningfully cheaper → WAIT no matter what current tier is
  if(soonBetter && soonBetter.absOffset<=1){
    box.className='reco reco-wait';
    txt.innerHTML=`🟡 <strong>WAIT ${soonBetter.absOffset*30||30}min.</strong> Price drops to $${soonBetter.fuel.toLocaleString()} (${tierLabel(getTier(soonBetter.fuel))}) at ${soonBetter.time} UTC — save $${(cur-soonBetter.fuel).toLocaleString()} per 1,000L. Hold refueling!`;
    return;
  }

  if(curTier==='best'){
    // Even at best, warn if somehow something even cheaper is next
    box.className='reco reco-buy';
    txt.innerHTML=`🟢 <strong>BUY NOW!</strong> $${cur.toLocaleString()} is BEST price tier. 12h avg: $${avg12.toLocaleString()}. This is as cheap as it gets — refuel your entire fleet!`;

  } else if(curTier==='good'){
    if(soonBetter){
      // Better price within 90 min
      box.className='reco reco-wait';
      txt.innerHTML=`🟡 <strong>WAIT ~${soonBetter.absOffset*30}min.</strong> A ${tierLabel(getTier(soonBetter.fuel))} price of $${soonBetter.fuel.toLocaleString()} is coming at ${soonBetter.time} UTC. Save $${(cur-soonBetter.fuel).toLocaleString()} per 1,000L.`;
    } else if(bestWithin3h){
      // Best price within 3 hours
      box.className='reco reco-wait';
      txt.innerHTML=`🟡 <strong>WAIT ~${bestWithin3h.absOffset*30}min.</strong> BEST price $${bestWithin3h.fuel.toLocaleString()} at ${bestWithin3h.time} UTC (${bestWithin3h.absOffset*30}min away). Worth the wait if you can hold.`;
    } else {
      // No better window soon — buy now
      box.className='reco reco-buy';
      txt.innerHTML=`🟢 <strong>BUY NOW!</strong> $${cur.toLocaleString()} is good. No significantly better window in the next 3 hours. 12h avg: $${avg12.toLocaleString()}.`;
    }

  } else if(curTier==='avg'){
    const betterSoon=slots.slice(0,12).find(s=>getTier(s.fuel)==='best'||getTier(s.fuel)==='good');
    if(betterSoon){
      box.className='reco reco-wait';
      txt.innerHTML=`🟡 <strong>WAIT ~${betterSoon.absOffset*30}min.</strong> ${tierLabel(getTier(betterSoon.fuel))} price $${betterSoon.fuel.toLocaleString()} at ${betterSoon.time} UTC. Current $${cur.toLocaleString()} is average — hold if possible.`;
    } else {
      box.className='reco reco-buy';
      txt.innerHTML=`🟡 <strong>ACCEPTABLE.</strong> $${cur.toLocaleString()} is average but no better window in the next 6h. Refuel now to avoid higher prices.`;
    }

  } else {
    // high or spike
    if(nextGoodSlot){
      box.className='reco reco-skip';
      txt.innerHTML=`🔴 <strong>AVOID!</strong> $${cur.toLocaleString()} is ${curTier==='spike'?'a price spike':'high'}. Next good window: $${nextGoodSlot.fuel.toLocaleString()} at ${nextGoodSlot.time} UTC (~${nextGoodSlot.absOffset*30}min). Wait!`;
    } else {
      box.className='reco reco-skip';
      txt.innerHTML=`🔴 <strong>AVOID!</strong> $${cur.toLocaleString()} is ${curTier==='spike'?'a price spike':'high'}. 12h min: $${min12.toLocaleString()}. Delay all refueling until prices drop!`;
    }
  }
}

// ---- FIXED: show CO₂ in each fuel slot window card (same as dark version) ----
function updateWindows(slots){
  const sorted=[...slots].sort((a,b)=>a.fuel-b.fuel);
  const g=document.getElementById('windowsGrid');g.innerHTML='';
  sorted.slice(0,3).forEach(s=>{
    const d=document.createElement('div');
    d.className='win-card';
    const localT=utcToLocal(s.time);
    d.innerHTML=`<div class="win-time">🕐 ${s.time} UTC · ${localT} Local</div><div class="win-price">$${s.fuel.toLocaleString()}</div><div class="win-lbl">${tierLabel(getTier(s.fuel))} · CO₂: ${s.co2}</div>`;
    g.appendChild(d);
  });
  sorted.slice(-3).reverse().forEach(s=>{
    const d=document.createElement('div');
    d.className='win-card high-card';
    const localT=utcToLocal(s.time);
    d.innerHTML=`<div class="win-time">⚠️ ${s.time} UTC · ${localT} Local</div><div class="win-price">$${s.fuel.toLocaleString()}</div><div class="win-lbl">AVOID · CO₂: ${s.co2}</div>`;
    g.appendChild(d);
  });
}

function filterSlots(slots){
  if(curMode==='all')return slots;
  return slots.filter(s=>{const t=getTier(s.fuel);
    if(curMode==='best')return t==='best';
    if(curMode==='good')return t==='best'||t==='good';
    if(curMode==='avg')return t==='avg';
    if(curMode==='high')return t==='high';
    if(curMode==='spike')return t==='spike';
    return true;
  });
}
function updateChart(slots){
  if(pChart)pChart.destroy();
  const ctx=document.getElementById('priceChart').getContext('2d');
  const g=ctx.createLinearGradient(0,0,0,220);g.addColorStop(0,'rgba(99,102,241,0.15)');g.addColorStop(1,'rgba(99,102,241,0)');
  pChart=new Chart(ctx,{type:'line',data:{labels:slots.map(s=>s.time),datasets:[{data:slots.map(s=>s.fuel),borderColor:'rgba(99,102,241,0.75)',borderWidth:2,backgroundColor:g,pointBackgroundColor:slots.map(s=>tierColor(getTier(s.fuel))),pointRadius:slots.length>30?1:3,pointHoverRadius:6,tension:0.3,fill:true}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#ffffff',borderColor:'rgba(99,102,241,0.25)',borderWidth:1,titleColor:'#1e293b',bodyColor:'#475569',titleFont:{family:'Space Mono',size:9},bodyFont:{family:'Space Mono',size:10},callbacks:{title:i=>'UTC '+i[0].label+' → '+utcToLocal(i[0].label),label:i=>'$'+i.raw.toLocaleString()+' ['+tierLabel(getTier(i.raw))+']'}}},scales:{x:{ticks:{color:'#94a3b8',font:{family:'Space Mono',size:9},maxTicksLimit:slots.length>30?10:20},grid:{color:'rgba(203,213,225,0.4)'}},y:{ticks:{color:'#94a3b8',font:{family:'Space Mono',size:9},callback:v=>'$'+v.toLocaleString()},grid:{color:'rgba(203,213,225,0.4)'},min:0}}}});
}
function updateCo2Chart(slots){
  const s12=slots.slice(0,24);const all=slots.map(s=>s.co2);
  document.getElementById('co2Min').textContent=Math.min(...all);
  document.getElementById('co2Avg').textContent=Math.round(all.reduce((a,b)=>a+b)/all.length);
  document.getElementById('co2Max').textContent=Math.max(...all);
  if(co2C)co2C.destroy();
  const ctx=document.getElementById('co2Chart').getContext('2d');
  const g=ctx.createLinearGradient(0,0,0,160);g.addColorStop(0,'rgba(59,130,246,0.15)');g.addColorStop(1,'rgba(59,130,246,0)');
  co2C=new Chart(ctx,{type:'line',data:{labels:s12.map(s=>s.time),datasets:[{data:s12.map(s=>s.co2),borderColor:'rgba(59,130,246,0.7)',borderWidth:1.5,backgroundColor:g,pointRadius:2,tension:0.4,fill:true}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false},tooltip:{backgroundColor:'#ffffff',borderColor:'rgba(59,130,246,0.25)',borderWidth:1,titleColor:'#1e293b',bodyColor:'#475569',callbacks:{label:i=>'CO₂: '+i.raw}}},scales:{x:{ticks:{color:'#94a3b8',font:{family:'Space Mono',size:8},maxTicksLimit:8},grid:{color:'rgba(203,213,225,0.4)'}},y:{ticks:{color:'#94a3b8',font:{family:'Space Mono',size:8}},grid:{color:'rgba(203,213,225,0.4)'}}}}});
}
function updateTable(slots){
  const vis=filterSlots(slots);const mx=Math.max(...slots.map(s=>s.fuel));
  const tb=document.getElementById('forecastBody');tb.innerHTML='';
  vis.forEach((s,i)=>{
    const t=getTier(s.fuel);const prev=i>0?vis[i-1].fuel:s.fuel;const d=s.fuel-prev;
    const tr=document.createElement('tr');
    tr.innerHTML=`<td class="ft-time">${s.time}</td><td class="ft-time" style="color:#4b5563">${utcToLocal(s.time)}</td>
    <td class="ft-price ${tierBadge(t)}">$${s.fuel.toLocaleString()}</td>
    <td class="col-co2" style="font-family:'Space Mono',monospace;font-size:11px;color:#3b82f6">${s.co2}</td>
    <td><span class="ft-badge ${badgeCls(t)}">${tierLabel(t)}</span></td>
    <td class="col-delta" style="font-family:'Space Mono',monospace;font-size:11px;color:${d>0?'#ef4444':d<0?'#22c55e':'#6b7280'}">${d===0?'—':d>0?'+'+d:d}</td>
    <td class="col-bar"><div class="bar-track"><div class="bar-fill" style="width:${Math.min(100,s.fuel/mx*100)}%;background:${tierColor(t)}"></div></div></td>`;
    tb.appendChild(tr);
  });
}
function buildHeatmap(){
  const hm=document.getElementById('heatmap');hm.innerHTML='';
  const tip=document.getElementById('hm-tooltip');
  for(let d=0;d<31;d++){
    const col=document.createElement('div');col.className='hm-col';
    RAW['Day'+(d+1)].forEach(s=>{
      const cell=document.createElement('div');cell.className='hm-cell';
      const t=getTier(s.fuel);cell.style.background=tierColor(t)+'99';
      cell.addEventListener('mousemove',e=>{tip.className='hm-tooltip show';tip.innerHTML=`<strong>Day ${d+1} · ${s.time} UTC</strong>Fuel: $${s.fuel.toLocaleString()}<br>CO₂: ${s.co2}<br>${tierLabel(t)}`;tip.style.left=(e.clientX+12)+'px';tip.style.top=(e.clientY-14)+'px';});
      cell.addEventListener('mouseleave',()=>tip.className='hm-tooltip');
      cell.addEventListener('touchstart',e=>{const tc=e.touches[0];tip.className='hm-tooltip show';tip.innerHTML=`<strong>Day ${d+1} · ${s.time}</strong>$${s.fuel.toLocaleString()} · ${tierLabel(t)}`;tip.style.left=Math.min(tc.clientX+10,window.innerWidth-160)+'px';tip.style.top=(tc.clientY-60)+'px';setTimeout(()=>tip.className='hm-tooltip',2200);},{passive:true});
      col.appendChild(cell);
    });
    hm.appendChild(col);
  }
  document.addEventListener('touchstart',()=>tip.className='hm-tooltip',{passive:true});
}
function setMode(m,btn){curMode=m;document.querySelectorAll('.mode-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');updateTable(getForecast(curHorizon));}
function setHorizon(h,btn){curHorizon=h;document.querySelectorAll('.h-tab').forEach(b=>b.classList.remove('active'));btn.classList.add('active');const s=getForecast(h);updateChart(s);updateTable(s);updateStats(s);updateReco(s);updateWindows(s);}
function updateClocks(){
  const n=new Date();
  document.getElementById('utcClock').textContent=n.toUTCString().split(' ')[4];
  document.getElementById('localClock').textContent=n.toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false});
  const tz=Intl.DateTimeFormat().resolvedOptions().timeZone;
  document.getElementById('tzChip').textContent='⊕ '+tz.split('/').pop().replace('_',' ');
}

// ============ COIN RAIN ANIMATION ============
let coinCount=0;
function spawnCoin(){
  const rain=document.getElementById('coinRain');
  if(!rain)return;
  const coin=document.createElement('div');
  coin.className='coin';
  const x=Math.random()*96;
  const dur=0.9+Math.random()*0.7;
  const delay=0;
  const spin=360+Math.round(Math.random()*720);
  const fall=85+Math.random()*20;
  coin.style.cssText=`left:${x}%;--dur:${dur}s;--delay:${delay}s;--spin:${spin}deg;--fall:${fall}vh;`;
  rain.appendChild(coin);
  coinCount++;
  document.getElementById('coinCounter').textContent=coinCount;

  // Sparkle burst at landing position after coin hits bottom
  const landDelay=(dur*0.87)*1000;
  setTimeout(()=>{
    const px=rain.getBoundingClientRect().width*(x/100);
    const py=window.innerHeight*0.9;
    for(let s=0;s<6;s++){
      const sp=document.createElement('div');
      sp.className='spark';
      const angle=Math.random()*Math.PI*2;
      const dist=20+Math.random()*30;
      sp.style.cssText=`left:${px}px;top:${py}px;--sx:${Math.cos(angle)*dist}px;--sy:${Math.sin(angle)*dist-30}px;--sd:${0.3+Math.random()*0.3}s;`;
      rain.appendChild(sp);
      setTimeout(()=>sp.remove(),700);
    }
  },landDelay);

  setTimeout(()=>coin.remove(),dur*1000+200);
}

window.addEventListener('load',()=>{
  const bar=document.getElementById('loadBar'),st=document.getElementById('loadStatus');
  const steps=[[20,'LOADING FUEL DATA…'],[45,'DETECTING TIMEZONE…'],[65,'BUILDING FORECAST…'],[85,'RENDERING CHARTS…'],[100,'SYSTEMS ONLINE']];
  let i=0;const iv=setInterval(()=>{if(i>=steps.length){clearInterval(iv);return;}bar.style.width=steps[i][0]+'%';st.textContent=steps[i][1];i++;},300);

  // Stagger coin drops — rapid burst at start, then steady rain
  const coinSchedule=[
    0,80,160,260,370,490,600,720,830,940,
    1050,1130,1220,1300,1400
  ];
  coinSchedule.forEach(t=>setTimeout(spawnCoin,t));

  setTimeout(()=>{
    const el=document.getElementById('fuel-loading');
    el.style.opacity='0';
    setTimeout(()=>el.style.display='none',600);
    buildHeatmap();
    const s12=getForecast(12);
    updatePrice();updateStats(s12);updateReco(s12);updateWindows(s12);updateChart(s12);updateTable(s12);updateCo2Chart(s12);updateClocks();updateCountdown();
    setInterval(()=>{updatePrice();updateClocks();},30000);
    setInterval(()=>{updateCountdown();},1000);
  },1800);
});
</script>
</div>
</body>
</html>
