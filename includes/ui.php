<?php
// includes/ui.php — Shared UI helpers, CSS variables theme system, and nav
// Theme: CSS custom properties + data-theme="dark" on <html>
// Toggle is in the nav on every page. localStorage persists. System pref detected on first visit.

if (!function_exists('navAvatarLight')) {
    function navAvatarLight($u, $size = 30) {
        if (!$u) return '';
        $zd = isset($u['logo_zoom']) && $u['logo_zoom'] ? json_decode($u['logo_zoom'], true) : null;
        $s  = $zd['scale']   ?? 1;
        $ox = $zd['offsetX'] ?? 0;
        $oy = $zd['offsetY'] ?? 0;
        if (!empty($u['profile_photo'])) {
            $url = SITE_URL . '/' . htmlspecialchars($u['profile_photo']);
            return "<div style='width:{$size}px;height:{$size}px;border-radius:50%;overflow:hidden;position:relative;flex-shrink:0;border:2px solid var(--border-input);'>
                <img src='$url' style='position:absolute;width:".($s*100)."%;height:".($s*100)."%;object-fit:cover;top:50%;left:50%;transform:translate(calc(-50% + {$ox}px),calc(-50% + {$oy}px));'>
            </div>";
        }
        $initials = strtoupper(substr($u['airline_name'] ?? $u['username'] ?? '?', 0, 2));
        $fs = round($size / 2.8, 1);
        return "<div style='width:{$size}px;height:{$size}px;border-radius:50%;background:linear-gradient(135deg,#3b82f6,#6366f1);display:flex;align-items:center;justify-content:center;font-size:{$fs}px;font-weight:800;color:white;flex-shrink:0;font-family:Outfit,sans-serif;'>{$initials}</div>";
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// darkModeScript() — Must appear in <head> BEFORE CSS to prevent FOUC.
// Reads localStorage, falls back to system preference on first visit.
// ─────────────────────────────────────────────────────────────────────────────
function darkModeScript() { ?>
<script>
(function(){
  var s = localStorage.getItem('avc_theme');
  if (s === 'dark') {
    document.documentElement.setAttribute('data-theme','dark');
  } else if (!s) {
    // First visit: detect system preference
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
      document.documentElement.setAttribute('data-theme','dark');
    }
  }
  // 'light' → no attribute needed (default)
})();
</script>
<?php }

// ─────────────────────────────────────────────────────────────────────────────
// lightThemeCSS() — Full CSS variable system for both themes.
// Components use var(--*) so switching data-theme changes everything instantly.
// ─────────────────────────────────────────────────────────────────────────────
function lightThemeCSS() { ?>
<style>
/* ══════════════════════════════════════════════════════════════
   THEME VARIABLES — Light (default) and Dark
   All components use var(--*). No !important overrides needed.
   ══════════════════════════════════════════════════════════════ */
:root {
  /* Backgrounds */
  --bg:           #f7f9fc;
  --bg-alt:       #ffffff;
  --bg-card:      #ffffff;
  --bg-hover:     #f8fafc;
  --bg-input:     #ffffff;
  --bg-muted:     #f1f5f9;

  /* Borders */
  --border:       #e8edf3;
  --border-input: #e2e8f0;
  --border-focus: #93c5fd;

  /* Text */
  --text:         #1e293b;
  --text-heading: #0f172a;
  --text-muted:   #64748b;
  --text-subtle:  #94a3b8;
  --text-faint:   #cbd5e1;

  /* Navigation */
  --nav-bg:             rgba(255,255,255,0.96);
  --nav-border:         #e8edf3;
  --nav-link:           #475569;
  --nav-link-hover:     #1d4ed8;
  --nav-link-hover-bg:  #f0f7ff;
  --nav-btn-bg:         #f8fafc;
  --nav-btn-border:     #e2e8f0;

  /* Buttons */
  --btn-ghost-bg:           #ffffff;
  --btn-ghost-border:       #e2e8f0;
  --btn-ghost-text:         #374151;
  --btn-ghost-hover-bg:     #eff6ff;
  --btn-ghost-hover-border: #93c5fd;
  --btn-ghost-hover-text:   #1d4ed8;

  /* Cards */
  --card-shadow:        0 1px 4px rgba(15,23,42,0.04);
  --card-hover-border:  #bfdbfe;
  --card-hover-shadow:  0 4px 20px rgba(37,99,235,0.08);

  /* Overlays / panels */
  --modal-overlay:    rgba(15,23,42,0.4);
  --panel-bg:         #ffffff;
  --panel-border:     #e8edf3;
  --panel-shadow:     0 8px 40px rgba(15,23,42,0.12);

  /* Mobile menu */
  --mobile-menu-bg:     #ffffff;
  --mobile-menu-border: #e8edf3;
  --mobile-menu-link:   #475569;

  /* Inputs */
  --input-focus-ring: rgba(59,130,246,0.10);

  /* Bars */
  --stat-bar-track: #e2e8f0;

  /* Chat */
  --chat-bar-bg:        rgba(255,255,255,0.6);
  --chat-bar-border:    rgba(255,255,255,0.5);
  --chat-input-bg:      rgba(255,255,255,0.8);
  --chat-input-border:  rgba(203,213,225,0.5);
  --bubble-other-bg:    #f1f5f9;
  --bubble-other-border:#e2e8f0;
  --bubble-other-text:  #1e293b;

  /* Tables */
  --table-th-bg:     #fafbfc;
  --table-row-hover: #f8fafc;
  --table-border:    #e8edf3;
  --table-td-border: #f1f5f9;

  /* Toast */
  --toast-bg:     #ffffff;
  --toast-border: #e8edf3;
  --toast-shadow: 0 4px 24px rgba(15,23,42,0.10);
  --toast-text:   #334155;

  /* Fuel page */
  --fuel-bg:             #f0f5ff;
  --fuel-card-accent:    rgba(99,102,241,0.12);
  --fuel-stat-bg:        #f8fafc;
  --fuel-stat-border:    rgba(203,213,225,0.6);
  --fuel-stat-text:      #1e293b;
  --fuel-mode-btn-bg:    #f8fafc;
  --fuel-mode-btn-border:rgba(203,213,225,0.6);
  --fuel-mode-btn-text:  #94a3b8;
  --fuel-htabs-bg:       #f8fafc;
  --fuel-htabs-border:   rgba(203,213,225,0.6);
  --fuel-h-tab-text:     #94a3b8;
  --fuel-reco-bg:        rgba(99,102,241,0.05);
  --fuel-chip-bg:        #ffffff;
  --fuel-chip-border:    rgba(203,213,225,0.8);
  --fuel-chip-text:      #475569;
  --fuel-th-bg:          #f8fafc;
  --fuel-td-color:       #334155;
  --fuel-tooltip-bg:     #ffffff;
  --fuel-hero-h1:        #1e293b;
  --fuel-hero-sub:       #64748b;
  --fuel-card-title:     #94a3b8;
  --fuel-price-label:    #64748b;
  --fuel-table-td-border:rgba(226,232,240,0.4);

  /* Loading screen */
  --loading-bg:           linear-gradient(160deg,#f0f5ff 0%,#dbeafe 50%,#f0f5ff 100%);
  --loading-grid-color:   rgba(99,102,241,0.05);
  --loading-title-color:  #1e293b;
  --loading-sub-color:    #64748b;
  --loading-bar-track:    rgba(99,102,241,0.12);
  --loading-badge-bg:     rgba(99,102,241,0.06);
  --loading-badge-border: rgba(99,102,241,0.18);
  --loading-badge-text:   rgba(99,102,241,0.85);

  /* Scrollbar */
  --scrollbar-thumb:       #e2e8f0;
  --scrollbar-thumb-hover: #cbd5e1;

  /* Misc */
  --section-border: #e8edf3;
  --separator:      #f1f5f9;
  --live-dot:       #3b82f6;

  /* Smooth transitions for everything */
  --t: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
}

/* ── DARK OVERRIDES ── */
[data-theme="dark"] {
  color-scheme: dark;

  --bg:           #0d1117;
  --bg-alt:       #161b22;
  --bg-card:      #161b22;
  --bg-hover:     #1c2128;
  --bg-input:     #161b22;
  --bg-muted:     #0d1117;

  --border:       #21262d;
  --border-input: #30363d;
  --border-focus: #388bfd;

  --text:         #c9d1d9;
  --text-heading: #e6edf3;
  --text-muted:   #8b949e;
  --text-subtle:  #6e7681;
  --text-faint:   #484f58;

  --nav-bg:             rgba(13,17,23,0.97);
  --nav-border:         #21262d;
  --nav-link:           #8b949e;
  --nav-link-hover:     #58a6ff;
  --nav-link-hover-bg:  rgba(56,139,253,0.08);
  --nav-btn-bg:         #161b22;
  --nav-btn-border:     #30363d;

  --btn-ghost-bg:           #161b22;
  --btn-ghost-border:       #30363d;
  --btn-ghost-text:         #58a6ff;
  --btn-ghost-hover-bg:     #1c2128;
  --btn-ghost-hover-border: #388bfd;
  --btn-ghost-hover-text:   #79c0ff;

  --card-shadow:        0 1px 4px rgba(0,0,0,0.4);
  --card-hover-border:  #4a5568;
  --card-hover-shadow:  0 4px 20px rgba(0,0,0,0.3);

  --modal-overlay:    rgba(0,0,0,0.70);
  --panel-bg:         #161b22;
  --panel-border:     #30363d;
  --panel-shadow:     0 8px 40px rgba(0,0,0,0.4);

  --mobile-menu-bg:     #161b22;
  --mobile-menu-border: #30363d;
  --mobile-menu-link:   #8b949e;

  --input-focus-ring: rgba(56,139,253,0.15);

  --stat-bar-track: #30363d;

  --chat-bar-bg:        rgba(22,27,34,0.9);
  --chat-bar-border:    rgba(48,54,61,0.8);
  --chat-input-bg:      rgba(22,27,34,0.8);
  --chat-input-border:  rgba(48,54,61,0.6);
  --bubble-other-bg:    #21262d;
  --bubble-other-border:#30363d;
  --bubble-other-text:  #e6edf3;

  --table-th-bg:     #161b22;
  --table-row-hover: #1c2128;
  --table-border:    #21262d;
  --table-td-border: #21262d;

  --toast-bg:     #161b22;
  --toast-border: #30363d;
  --toast-shadow: 0 4px 24px rgba(0,0,0,0.5);
  --toast-text:   #e6edf3;

  --fuel-bg:             #0d1117;
  --fuel-card-accent:    rgba(99,102,241,0.06);
  --fuel-stat-bg:        #0d1117;
  --fuel-stat-border:    rgba(48,54,61,0.6);
  --fuel-stat-text:      #e6edf3;
  --fuel-mode-btn-bg:    #161b22;
  --fuel-mode-btn-border:rgba(48,54,61,0.6);
  --fuel-mode-btn-text:  #6e7681;
  --fuel-htabs-bg:       #161b22;
  --fuel-htabs-border:   rgba(48,54,61,0.6);
  --fuel-h-tab-text:     #6e7681;
  --fuel-reco-bg:        rgba(22,27,34,0.8);
  --fuel-chip-bg:        #161b22;
  --fuel-chip-border:    rgba(48,54,61,0.6);
  --fuel-chip-text:      #8b949e;
  --fuel-th-bg:          #161b22;
  --fuel-td-color:       #c9d1d9;
  --fuel-tooltip-bg:     #161b22;
  --fuel-hero-h1:        #e6edf3;
  --fuel-hero-sub:       #8b949e;
  --fuel-card-title:     #6e7681;
  --fuel-price-label:    #6e7681;
  --fuel-table-td-border:rgba(48,54,61,0.4);

  --loading-bg:           linear-gradient(160deg,#0f172a 0%,#1e1b4b 50%,#0f172a 100%);
  --loading-grid-color:   rgba(99,102,241,0.04);
  --loading-title-color:  #f8fafc;
  --loading-sub-color:    rgba(148,163,184,0.8);
  --loading-bar-track:    rgba(255,255,255,0.06);
  --loading-badge-bg:     rgba(251,191,36,0.08);
  --loading-badge-border: rgba(251,191,36,0.2);
  --loading-badge-text:   rgba(251,191,36,0.7);

  --scrollbar-thumb:       #30363d;
  --scrollbar-thumb-hover: #484f58;

  --section-border: #21262d;
  --separator:      #21262d;
  --live-dot:       #3b82f6;
}

/* ══════════════════════════════════════════════════════════════
   BASE — Global transitions enable smooth theme switching
   ══════════════════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; }
html, body { height: 100%; margin: 0; }

body {
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  transition: var(--t);
}
h1,h2,h3,h4,h5,h6 { color: var(--text-heading); transition: color 0.25s ease; }
a { transition: color 0.2s ease; }
input, textarea, select { transition: var(--t); }

/* ── Backgrounds ── */
.bg-mesh { min-height:100vh; background:var(--bg); }

/* ── Cards ── */
.glass {
  background: var(--bg-card);
  border: 1px solid var(--border);
  box-shadow: var(--card-shadow);
  transition: var(--t);
}
.glass-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  box-shadow: var(--card-shadow);
  transition: border-color 0.18s, box-shadow 0.18s, transform 0.15s, background-color 0.25s;
  will-change: transform;
}
.glass-card:hover {
  border-color: var(--card-hover-border);
  box-shadow: var(--card-hover-shadow);
  transform: translateY(-2px);
}
.page-card {
  background: var(--bg-card);
  border: 1px solid var(--border);
  box-shadow: var(--card-shadow);
  transition: var(--t);
}
.card-glow { background:var(--bg-card); border:1px solid var(--border); transition:var(--t); }

/* ── Nav ── */
.nav-glass {
  background: var(--nav-bg);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid var(--nav-border);
  transition: background-color 0.25s, border-color 0.25s;
}

/* ── Buttons ── */
.btn-primary {
  background: #2563eb; color: white;
  transition: all 0.2s; border: none;
  font-family: 'Outfit', sans-serif;
}
.btn-primary:hover {
  background: #1d4ed8;
  box-shadow: 0 4px 14px rgba(37,99,235,0.3);
  transform: translateY(-1px);
}
.btn-ghost {
  background: var(--btn-ghost-bg);
  border: 1px solid var(--btn-ghost-border);
  color: var(--btn-ghost-text);
  transition: var(--t);
  font-family: 'Outfit', sans-serif;
}
.btn-ghost:hover {
  border-color: var(--btn-ghost-hover-border);
  color: var(--btn-ghost-hover-text);
  background: var(--btn-ghost-hover-bg);
}
.btn-danger { background:#ef4444; color:white; border:none; transition:all 0.2s; }
.btn-danger:hover { background:#dc2626; box-shadow:0 4px 14px rgba(239,68,68,0.25); }

/* ── Inputs ── */
.input-light {
  width:100%; padding:10px 14px; border-radius:10px;
  background:var(--bg-input); border:1px solid var(--border-input);
  font-size:14px; color:var(--text); outline:none;
  font-family:'Plus Jakarta Sans',sans-serif;
  transition: var(--t), box-shadow 0.2s;
}
.input-light:focus { border-color:var(--border-focus); box-shadow:0 0 0 3px var(--input-focus-ring); }
.input-light::placeholder { color:var(--text-subtle); }
select { background:var(--bg-input); color:var(--text); border-color:var(--border-input); }

/* ── Animations ── */
@keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
.anim-up { animation:fadeUp 0.4s ease-out forwards; opacity:0; }
.d1{animation-delay:0.05s}.d2{animation-delay:0.10s}.d3{animation-delay:0.15s}
.d4{animation-delay:0.20s}.d5{animation-delay:0.25s}.d6{animation-delay:0.30s}

/* ── Badges ── */
.badge-blue   {background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;}
.badge-indigo {background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe;}
.badge-emerald{background:#f0fdf4;color:#059669;border:1px solid #a7f3d0;}
.badge-amber  {background:#fffbeb;color:#d97706;border:1px solid #fde68a;}
.badge-rose   {background:#fff1f2;color:#e11d48;border:1px solid #fecdd3;}
.badge-slate  {background:var(--bg-hover);color:var(--text-muted);border:1px solid var(--border);}
[data-theme="dark"] .badge-blue   {background:rgba(37,99,235,0.12);}
[data-theme="dark"] .badge-indigo {background:rgba(99,102,241,0.12);}
[data-theme="dark"] .badge-emerald{background:rgba(5,150,105,0.12);}
[data-theme="dark"] .badge-amber  {background:rgba(217,119,6,0.12);}
[data-theme="dark"] .badge-rose   {background:rgba(225,29,72,0.12);}

.tag { display:inline-block; padding:2px 10px; border-radius:99px; font-size:11px; font-weight:600; }

/* ── Pill tabs ── */
.pill-tab { padding:7px 14px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:var(--t); background:transparent; color:var(--text-muted); }
.pill-tab:hover { background:var(--bg-hover); color:var(--text); }
.pill-tab.active { background:#eff6ff; color:#2563eb; }
[data-theme="dark"] .pill-tab.active { background:rgba(56,139,253,0.12); color:#58a6ff; }

/* ── Nav links ── */
.nav-link { padding:6px 12px; border-radius:8px; font-size:13px; font-weight:500; color:var(--nav-link); transition:all 0.18s; text-decoration:none; }
.nav-link:hover { color:var(--nav-link-hover); background:var(--nav-link-hover-bg); }
.nav-link.active { color:#2563eb; background:#eff6ff; }
[data-theme="dark"] .nav-link.active { color:#58a6ff; background:rgba(56,139,253,0.08); }

/* ── Stat bar ── */
.stat-bar-light { height:4px; border-radius:2px; background:var(--stat-bar-track); overflow:hidden; transition:background-color 0.25s; }
.stat-bar-fill-light { height:100%; border-radius:2px; transition:width 1s ease; }

/* ── Toast ── */
.toast-light { position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(20px); z-index:9999; pointer-events:none; opacity:0; transition:all 0.3s ease; }
.toast-light.show { opacity:1; transform:translateX(-50%) translateY(0); }
.toast-inner {
  background: var(--toast-bg);
  border: 1px solid var(--toast-border);
  border-radius: 12px; padding: 12px 20px;
  box-shadow: var(--toast-shadow);
  display: flex; align-items: center; gap: 8px;
  transition: var(--t);
}
.toast-inner span { font-size:14px; font-weight:500; color:var(--toast-text); font-family:'Plus Jakarta Sans',sans-serif; }

/* ── Misc ── */
.modal-overlay { background:var(--modal-overlay); backdrop-filter:blur(4px); }
.live-dot { width:7px; height:7px; border-radius:50%; background:var(--live-dot); display:inline-block; animation:pulse2 1.5s ease-in-out infinite; }
@keyframes pulse2 { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:0.5;transform:scale(1.5)} }

::-webkit-scrollbar { width:5px; height:5px; }
::-webkit-scrollbar-track { background:transparent; }
::-webkit-scrollbar-thumb { background:var(--scrollbar-thumb); border-radius:3px; }
::-webkit-scrollbar-thumb:hover { background:var(--scrollbar-thumb-hover); }

/* ── Chat bubbles ── */
.bubble-me { background:#2563eb; color:white; border-radius:18px 18px 4px 18px; }
.bubble-other { background:var(--bubble-other-bg); color:var(--bubble-other-text); border-radius:18px 18px 18px 4px; border:1px solid var(--bubble-other-border); transition:var(--t); }

/* ── Tables ── */
.table-light th { font-size:11px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:var(--text-subtle); padding:10px 14px; border-bottom:1px solid var(--table-border); background:var(--table-th-bg); position:sticky; top:0; z-index:2; transition:var(--t); }
.table-light td { padding:10px 14px; border-bottom:1px solid var(--table-td-border); font-size:13px; color:var(--text); transition:var(--t); }
.table-light tr:hover td { background:var(--table-row-hover); }

/* ── Member card ── */
.member-card { background:var(--bg-card); border:1px solid var(--border); transition:var(--t); }
.member-card:hover { background:var(--bg-hover); border-color:var(--card-hover-border); }
.member-action-btn { background:var(--bg-hover); border:1px solid var(--border); color:var(--text-muted); transition:var(--t); }

/* ── Filter/search ── */
.filter-select, .search-input { background:var(--bg-input); border:1px solid var(--border-input); color:var(--text); transition:var(--t); }

/* ── Font heading ── */
.font-heading { font-family:'Outfit',sans-serif; }

/* ── Theme toggle button ── */
.theme-toggle-btn {
  width:36px; height:36px; border-radius:9px;
  background:var(--nav-btn-bg); border:1px solid var(--nav-btn-border);
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; flex-shrink:0;
  transition: background-color 0.25s, border-color 0.18s;
}
.theme-toggle-btn:hover { border-color:var(--border-focus); }

/* ── Homepage cards (use CSS vars) ── */
.alliance-card {
  background:var(--bg-card); border:1px solid var(--border); border-radius:18px;
  padding:24px; position:relative; overflow:hidden;
  transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s, background-color 0.25s;
}
.alliance-card:hover { border-color:var(--card-hover-border); box-shadow:var(--card-hover-shadow); transform:translateY(-2px); }
.feature-card {
  background:var(--bg-card); border:1px solid var(--border); border-radius:14px;
  padding:20px; text-align:center; text-decoration:none; display:block;
  transition: border-color 0.18s, box-shadow 0.18s, transform 0.15s, background-color 0.25s;
}
.feature-card:hover { border-color:var(--card-hover-border); box-shadow:var(--card-hover-shadow); transform:translateY(-2px); }

/* Feature icon boxes adapt in dark */
[data-theme="dark"] .feat-icon-blue   { background:rgba(37,99,235,0.15)!important; }
[data-theme="dark"] .feat-icon-amber  { background:rgba(217,119,6,0.15)!important; }
[data-theme="dark"] .feat-icon-indigo { background:rgba(99,102,241,0.15)!important; }
[data-theme="dark"] .feat-icon-green  { background:rgba(16,185,129,0.15)!important; }
[data-theme="dark"] .feat-icon-rose   { background:rgba(225,29,72,0.15)!important; }
[data-theme="dark"] .feat-icon-violet { background:rgba(139,92,246,0.15)!important; }
[data-theme="dark"] .feat-icon-sky    { background:rgba(14,165,233,0.15)!important; }

/* Separators */
.section-sep { border-top:1px solid var(--section-border); }
.separator    { background:var(--separator); }

/* Community social buttons */
.social-btn {
  width:56px; height:56px; border-radius:14px;
  background:var(--bg-card); border:1px solid var(--border);
  display:flex; align-items:center; justify-content:center;
  text-decoration:none; transition:var(--t);
}
.social-btn:hover { border-color:var(--border-focus); }
</style>
<?php }

// ─────────────────────────────────────────────────────────────────────────────
// lightNavHTML() — Nav with theme toggle. Renders on every page.
// ─────────────────────────────────────────────────────────────────────────────
function lightNavHTML($user, $isAdmin = false, $unreadDM = 0, $unreadNotif = 0) {
    ob_start();
    $allianceRooms = ['SKY TEAM 2.0','Aura Union','Prime United'];
    $userHasAlliance = $user && $user['alliance'] && in_array($user['alliance'], $allianceRooms);
    ?>
<nav class="nav-glass sticky top-0 z-50 w-full">
  <div style="max-width:1200px;margin:0 auto;padding:0 20px;">
    <div style="display:flex;align-items:center;justify-content:space-between;height:60px;">

      <!-- Logo -->
      <a href="<?= SITE_URL ?>/index.php" style="display:flex;align-items:center;gap:9px;text-decoration:none;flex-shrink:0;">
        <div style="width:32px;height:32px;border-radius:9px;background:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <svg width="17" height="17" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><path d="M17.8 19.2 16 11l3.5-3.5C21 6 21 4 21 4s-2 0-3.5 1.5L14 9 5.8 7.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 3.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/></svg>
        </div>
        <span style="font-family:'Outfit',sans-serif;font-weight:700;font-size:15px;color:var(--text-heading);letter-spacing:-0.01em;transition:color 0.25s;">Aero Vibes Central</span>
      </a>

      <!-- Desktop nav links -->
      <div class="hidden md:flex items-center" style="gap:2px;">
        <a href="<?= SITE_URL ?>/index.php" class="nav-link">Home</a>
        <a href="<?= SITE_URL ?>/index.php#alliances" class="nav-link">Alliances</a>
        <?php if ($user): ?>
          <a href="<?= SITE_URL ?>/pages/members.php" class="nav-link">Members</a>
          <a href="<?= SITE_URL ?>/pages/messages.php?room=public" class="nav-link">Chat</a>
          <a href="<?= SITE_URL ?>/pages/fuel.php" class="nav-link">Fuel</a>
          <?php if ($userHasAlliance): ?>
            <a href="<?= SITE_URL ?>/pages/inactivity.php" class="nav-link">Inactivity</a>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Right side -->
      <div style="display:flex;align-items:center;gap:8px;">
        <?php if ($user): ?>
          <!-- DM button -->
          <a href="<?= SITE_URL ?>/pages/dm.php" style="position:relative;width:36px;height:36px;border-radius:9px;background:var(--nav-btn-bg);border:1px solid var(--nav-btn-border);display:flex;align-items:center;justify-content:center;text-decoration:none;transition:background-color 0.25s,border-color 0.18s;">
            <svg width="15" height="15" fill="none" stroke="var(--text-muted)" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <?php if ($unreadDM > 0): ?>
            <span style="position:absolute;top:-4px;right:-4px;min-width:15px;height:15px;background:#6366f1;color:white;border-radius:8px;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 3px;border:2px solid var(--bg);"><?= $unreadDM ?></span>
            <?php endif; ?>
          </a>

          <!-- Notifications -->
          <div style="position:relative;">
            <button onclick="toggleNotifPanel()" id="notif-btn" style="width:36px;height:36px;border-radius:9px;background:var(--nav-btn-bg);border:1px solid var(--nav-btn-border);display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;transition:background-color 0.25s,border-color 0.18s;">
              <svg width="15" height="15" fill="none" stroke="var(--text-muted)" stroke-width="2" viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
              <?php if ($unreadNotif > 0): ?>
              <span style="position:absolute;top:-4px;right:-4px;min-width:15px;height:15px;background:#ef4444;color:white;border-radius:8px;font-size:9px;font-weight:700;display:flex;align-items:center;justify-content:center;padding:0 3px;border:2px solid var(--bg);"><?= $unreadNotif ?></span>
              <?php endif; ?>
            </button>
          </div>

          <!-- Avatar + name -->
          <a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $user['id'] ?>" style="display:flex;align-items:center;gap:7px;padding:4px 10px 4px 4px;border-radius:10px;background:var(--nav-btn-bg);border:1px solid var(--nav-btn-border);text-decoration:none;transition:background-color 0.25s,border-color 0.18s;">
            <?= navAvatarLight($user, 28) ?>
            <span style="font-size:13px;font-weight:600;color:var(--text);max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" class="hidden sm:inline"><?= htmlspecialchars($user['airline_name']) ?></span>
          </a>

          <?php if ($isAdmin): ?>
          <a href="<?= SITE_URL ?>/admin/index.php" class="hidden md:flex btn-primary px-3 py-2 rounded-xl text-sm font-semibold items-center gap-1.5" style="font-family:'Outfit',sans-serif;text-decoration:none;">
            <svg width="12" height="12" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Admin
          </a>
          <?php endif; ?>
          <a href="<?= SITE_URL ?>/pages/logout.php" class="hidden md:block btn-ghost px-3 py-2 rounded-xl text-sm font-semibold" style="font-family:'Outfit',sans-serif;text-decoration:none;">Logout</a>

        <?php else: ?>
          <a href="<?= SITE_URL ?>/pages/login.php" class="btn-ghost px-4 py-2 rounded-xl text-sm font-semibold hidden sm:block" style="text-decoration:none;">Login</a>
          <a href="<?= SITE_URL ?>/pages/login.php?tab=signup" class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold" style="text-decoration:none;font-family:'Outfit',sans-serif;">Get Started</a>
        <?php endif; ?>

        <!-- Hamburger -->
        <button onclick="toggleMobileMenu()" style="padding:7px;border-radius:8px;background:var(--nav-btn-bg);border:1px solid var(--nav-btn-border);cursor:pointer;transition:background-color 0.25s,border-color 0.25s;" class="md:hidden">
          <svg width="18" height="18" fill="none" stroke="var(--text-muted)" stroke-width="2" viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>

        <!-- Theme toggle button -->
        <button class="theme-toggle-btn" onclick="avcToggleTheme()" title="Toggle dark / light mode" aria-label="Toggle theme">
          <svg id="avc-icon-moon" width="15" height="15" fill="none" stroke="var(--text-muted)" stroke-width="2" viewBox="0 0 24 24" style="transition:opacity 0.2s;"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
          <svg id="avc-icon-sun" width="15" height="15" fill="none" stroke="#d97706" stroke-width="2" viewBox="0 0 24 24" style="display:none;transition:opacity 0.2s;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu-panel" style="display:none;padding:0 16px 16px;">
    <div style="background:var(--mobile-menu-bg);border:1px solid var(--mobile-menu-border);border-radius:14px;padding:10px;display:flex;flex-direction:column;gap:2px;box-shadow:0 4px 20px rgba(0,0,0,0.12);transition:background-color 0.25s,border-color 0.25s;">
      <?php if ($user): ?>
        <a href="<?= SITE_URL ?>/pages/profile.php?id=<?= $user['id'] ?>" style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;background:var(--bg-hover);margin-bottom:4px;text-decoration:none;border:1px solid var(--border);transition:background-color 0.25s,border-color 0.25s;">
          <?= navAvatarLight($user, 34) ?>
          <div>
            <p style="font-weight:700;font-size:14px;color:var(--text-heading);margin:0;"><?= htmlspecialchars($user['airline_name']) ?></p>
            <p style="font-size:11px;color:var(--text-subtle);margin:0;">@<?= htmlspecialchars($user['username']) ?></p>
          </div>
        </a>
        <a href="<?= SITE_URL ?>/index.php" style="padding:10px 12px;border-radius:8px;color:var(--mobile-menu-link);font-size:14px;font-weight:500;text-decoration:none;display:block;">Home</a>
        <a href="<?= SITE_URL ?>/pages/messages.php?room=public" style="padding:10px 12px;border-radius:8px;color:var(--mobile-menu-link);font-size:14px;font-weight:500;text-decoration:none;display:block;">Global Chat</a>
        <a href="<?= SITE_URL ?>/pages/fuel.php" style="padding:10px 12px;border-radius:8px;color:var(--mobile-menu-link);font-size:14px;font-weight:500;text-decoration:none;display:block;">Fuel Forecast</a>
        <a href="<?= SITE_URL ?>/pages/members.php" style="padding:10px 12px;border-radius:8px;color:var(--mobile-menu-link);font-size:14px;font-weight:500;text-decoration:none;display:block;">Members Hub</a>
        <a href="<?= SITE_URL ?>/pages/dm.php" style="padding:10px 12px;border-radius:8px;color:var(--mobile-menu-link);font-size:14px;font-weight:500;text-decoration:none;display:block;">Direct Messages</a>
        <?php if ($userHasAlliance): ?>
          <a href="<?= SITE_URL ?>/pages/messages.php?room=<?= urlencode($user['alliance']) ?>" style="padding:10px 12px;border-radius:8px;color:var(--mobile-menu-link);font-size:14px;font-weight:500;text-decoration:none;display:block;">Alliance Chat</a>
          <a href="<?= SITE_URL ?>/pages/inactivity.php" style="padding:10px 12px;border-radius:8px;color:var(--mobile-menu-link);font-size:14px;font-weight:500;text-decoration:none;display:block;">Inactivity Notice</a>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
          <a href="<?= SITE_URL ?>/admin/index.php" style="padding:10px 12px;border-radius:8px;color:#2563eb;font-size:14px;font-weight:600;text-decoration:none;display:block;background:rgba(37,99,235,0.08);">Admin Panel</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/pages/login.php" style="padding:10px 12px;border-radius:8px;color:#2563eb;font-size:14px;font-weight:600;text-decoration:none;display:block;">Login</a>
        <a href="<?= SITE_URL ?>/pages/login.php?tab=signup" style="padding:10px 12px;border-radius:8px;color:#2563eb;font-size:14px;font-weight:600;text-decoration:none;display:block;">Create Account</a>
      <?php endif; ?>
      <!-- Dark mode toggle row -->
      <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border-radius:8px;margin-top:2px;">
        <span style="font-size:14px;font-weight:500;color:var(--mobile-menu-link);">Dark Mode</span>
        <button onclick="avcToggleTheme()" id="mob-theme-toggle" style="width:44px;height:24px;border-radius:99px;border:none;cursor:pointer;position:relative;background:#e2e8f0;transition:background 0.25s;" aria-label="Toggle theme">
          <span id="mob-theme-knob" style="position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:white;box-shadow:0 1px 4px rgba(0,0,0,0.2);transition:transform 0.25s;"></span>
        </button>
      </div>
      <?php if ($user): ?>
      <div style="height:1px;background:var(--separator);margin:4px 0;"></div>
      <a href="<?= SITE_URL ?>/pages/logout.php" style="padding:10px 12px;border-radius:8px;color:var(--text-subtle);font-size:14px;font-weight:500;text-decoration:none;display:block;">Logout</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Notification Panel -->
<div id="notif-panel" style="display:none;position:fixed;top:68px;right:16px;width:300px;z-index:500;border-radius:14px;box-shadow:var(--panel-shadow);background:var(--panel-bg);border:1px solid var(--panel-border);max-height:70vh;overflow-y:auto;transition:background-color 0.25s,border-color 0.25s;">
  <div style="padding:14px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--separator);">
    <span style="font-family:'Outfit',sans-serif;font-size:13px;font-weight:700;color:var(--text-heading);">Notifications</span>
    <div style="display:flex;gap:8px;align-items:center;">
      <?php if ($user): ?><button onclick="markAllRead()" style="font-size:11px;color:var(--text-subtle);background:none;border:none;cursor:pointer;font-family:'Plus Jakarta Sans',sans-serif;">Mark all read</button><?php endif; ?>
      <button onclick="closeNotifPanel()" style="background:none;border:none;cursor:pointer;color:var(--text-subtle);display:flex;padding:2px;">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
  </div>
  <div id="notif-list" style="padding:10px;">
    <?php if (!$user): ?>
      <p style="text-align:center;font-size:12px;color:var(--text-subtle);padding:16px;">Sign in to see notifications</p>
    <?php else: ?>
      <p style="text-align:center;font-size:12px;color:var(--text-subtle);padding:16px;">Loading…</p>
    <?php endif; ?>
  </div>
</div>

<script>
/* ═══════════════════════════════════════
   AVC THEME SYSTEM
   Instant toggle, localStorage persist,
   system preference on first visit.
   ═══════════════════════════════════════ */
function avcToggleTheme() {
  var dark = document.documentElement.getAttribute('data-theme') === 'dark';
  if (dark) {
    document.documentElement.removeAttribute('data-theme');
    localStorage.setItem('avc_theme', 'light');
  } else {
    document.documentElement.setAttribute('data-theme', 'dark');
    localStorage.setItem('avc_theme', 'dark');
  }
  _avcSyncIcons();
}

function _avcSyncIcons() {
  var dark  = document.documentElement.getAttribute('data-theme') === 'dark';
  var moon  = document.getElementById('avc-icon-moon');
  var sun   = document.getElementById('avc-icon-sun');
  var knob  = document.getElementById('mob-theme-knob');
  var tog   = document.getElementById('mob-theme-toggle');
  if (moon) moon.style.display = dark ? 'none'  : 'block';
  if (sun)  sun.style.display  = dark ? 'block' : 'none';
  if (knob) knob.style.transform = dark ? 'translateX(20px)' : 'translateX(0)';
  if (tog)  tog.style.background  = dark ? '#2563eb' : '#e2e8f0';
}

/* Sync icons immediately (before DOMContentLoaded) */
_avcSyncIcons();
document.addEventListener('DOMContentLoaded', _avcSyncIcons);

/* ── Mobile menu ── */
function toggleMobileMenu() {
  var m = document.getElementById('mobile-menu-panel');
  m.style.display = m.style.display === 'none' ? 'block' : 'none';
}

/* ── Notification panel ── */
var _notifOpen = false;
function toggleNotifPanel() {
  _notifOpen = !_notifOpen;
  document.getElementById('notif-panel').style.display = _notifOpen ? 'block' : 'none';
  if (_notifOpen) loadNotifs();
}
function closeNotifPanel() {
  _notifOpen = false;
  document.getElementById('notif-panel').style.display = 'none';
}
document.addEventListener('click', function(e) {
  var panel = document.getElementById('notif-panel');
  var btn   = document.getElementById('notif-btn');
  if (_notifOpen && panel && !panel.contains(e.target) && btn && !btn.contains(e.target)) {
    closeNotifPanel();
  }
});

<?php if ($user): ?>
function loadNotifs() {
  fetch('<?= SITE_URL ?>/api/notifications.php')
    .then(function(r){ return r.json(); })
    .then(function(data) {
      var list = document.getElementById('notif-list');
      if (!data.notifications || !data.notifications.length) {
        list.innerHTML = '<p style="text-align:center;font-size:12px;color:var(--text-subtle);padding:16px;">No notifications yet.</p>';
        return;
      }
      list.innerHTML = data.notifications.map(function(n) {
        var bg     = n.is_read ? 'var(--bg-hover)' : 'rgba(37,99,235,0.06)';
        var border = n.is_read ? 'var(--border)'   : 'rgba(37,99,235,0.15)';
        return '<div style="display:flex;align-items:flex-start;gap:10px;padding:10px;border-radius:10px;margin-bottom:6px;background:'+bg+';border:1px solid '+border+';">'
          + '<div style="width:28px;height:28px;border-radius:8px;background:rgba(37,99,235,0.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;">🔔</div>'
          + '<div><p style="font-size:12px;color:var(--text);font-weight:500;margin:0;">'+n.message+'</p>'
          + '<p style="font-size:11px;color:var(--text-subtle);margin:2px 0 0;">'+n.time+'</p></div></div>';
      }).join('');
    }).catch(function(){});
}
function markAllRead() {
  fetch('<?= SITE_URL ?>/api/notifications.php', { method:'POST' })
    .then(function(r){ return r.json(); })
    .then(function() {
      var badge = document.querySelector('#notif-btn span');
      if (badge) badge.remove();
      loadNotifs();
    }).catch(function(){});
}
<?php endif; ?>
</script>
    <?php
    return ob_get_clean();
}

// ─────────────────────────────────────────────────────────────────────────────
// showToastJS() — Toast that adapts to theme via CSS variables
// ─────────────────────────────────────────────────────────────────────────────
function showToastJS() {
    return '
function showToast(msg, type) {
  var t = document.getElementById("_avc_toast");
  if (!t) {
    t = document.createElement("div");
    t.id = "_avc_toast";
    t.className = "toast-light";
    t.innerHTML = \'<div class="toast-inner"><span id="_avc_toast_msg"></span></div>\';
    document.body.appendChild(t);
  }
  document.getElementById("_avc_toast_msg").textContent = msg || "";
  t.classList.add("show");
  clearTimeout(t._tmr);
  t._tmr = setTimeout(function(){ t.classList.remove("show"); }, 3000);
}';
}
