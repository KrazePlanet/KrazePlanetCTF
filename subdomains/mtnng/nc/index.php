<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MTN NG</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/static/css/nc.css?v=<?= filemtime(__DIR__ . '/../static/css/nc.css'); ?>">
</head>
<body>

<div class="portal-shell">
    <header class="mtn-hero">
        <div class="mtn-hero-top">
            <div class="mtn-brand-left">
                <div class="mtn-oval">MTN</div>
                <div class="hero-msg">
                    <div>
                        <strong>Oops, you are out of balance.</strong>
                        <span>This page is free for you.</span>
                    </div>
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#111111" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22v-5"></path>
                        <path d="M9 8V2"></path>
                        <path d="M15 8V2"></path>
                        <path d="M18 8v5a4 4 0 0 1-4 4h-4a4 4 0 0 1-4-4V8Z"></path>
                    </svg>
                </div>
            </div>
            <div class="hero-actions">
                <a href="/admin/" class="btn-login">Login</a>
            </div>
        </div>

        <div class="mtn-subscriber-bar">
            <div>
                <div class="sub-name">Chidi Nwosu</div>
                <div class="sub-msisdn">08037194208</div>
            </div>
            <div class="sub-balance">
                <strong id="balance-val">&#8358; -749.9</strong>
                <span>Current Balance</span>
            </div>
        </div>
    </header>

    <section class="portal-section">
        <div class="sec-head">
            <h2>Take a Loan</h2>
            <button type="button" class="sec-link" id="toggle-loans">View All &rsaquo;</button>
        </div>

        <div class="loan-box">
            <div class="loan-amount">
                <strong id="selected-loan-title">Borrowing &#8358;200</strong>
                <span id="selected-loan-net">You get: &#8358;170</span>
            </div>
            <button type="button" class="btn-mtn" id="activate-loan-btn">Activate Loan</button>
        </div>

        <div class="loan-tiers" id="loan-tiers">
            <div class="tier-chip active" data-gross="200" data-net="170"><strong>&#8358;200</strong><br>Net &#8358;170</div>
            <div class="tier-chip" data-gross="500" data-net="425"><strong>&#8358;500</strong><br>Net &#8358;425</div>
            <div class="tier-chip" data-gross="1000" data-net="850"><strong>&#8358;1,000</strong><br>Net &#8358;850</div>
        </div>
    </section>

    <section class="portal-section">
        <div class="sec-head">
            <h2>Apps Club - the best apps available without airtime and internet</h2>
        </div>

        <div class="apps-strip">
            <div class="app-tile" data-app="Typing Fighter">
                <div class="app-thumb" style="background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;font-weight:800;font-size:13px;flex-direction:column;gap:4px;">
                    <span>TF</span>
                    <span style="font-size:9px;letter-spacing:1px;opacity:.85;">ARCADE</span>
                </div>
                <div class="app-name">Typing Fighter</div>
            </div>

            <div class="app-tile" data-app="Mortal Cage Fighter">
                <div class="app-thumb" style="background:linear-gradient(135deg,#1e293b,#475569);color:#facc15;font-weight:800;font-size:13px;flex-direction:column;gap:4px;">
                    <span>MCF</span>
                    <span style="font-size:9px;color:#fff;opacity:.8;">ACTION</span>
                </div>
                <div class="app-name">Mortal Cage Fi...</div>
            </div>

            <div class="app-tile" data-app="Commando Boat">
                <div class="app-thumb" style="background:linear-gradient(135deg,#0284c7,#0369a1);color:#fff;font-weight:800;font-size:13px;flex-direction:column;gap:4px;">
                    <span>CB</span>
                    <span style="font-size:9px;opacity:.85;">RACING</span>
                </div>
                <div class="app-name">Commando Boat</div>
            </div>

            <div class="app-tile" data-app="Unblock it HD!">
                <div class="app-thumb" style="background:linear-gradient(135deg,#d97706,#92400e);color:#fff;font-weight:800;font-size:13px;flex-direction:column;gap:4px;">
                    <span>UHD</span>
                    <span style="font-size:9px;opacity:.85;">PUZZLE</span>
                </div>
                <div class="app-name">Unblock it HD!</div>
            </div>

            <div class="app-tile" data-app="Zombie Hunter">
                <div class="app-thumb" style="background:linear-gradient(135deg,#111827,#374151);color:#fca5a5;font-weight:800;font-size:13px;flex-direction:column;gap:4px;">
                    <span>ZH</span>
                    <span style="font-size:9px;color:#fff;opacity:.8;">SURVIVAL</span>
                </div>
                <div class="app-name">Zombie Hunter</div>
            </div>
        </div>
    </section>

    <section class="portal-section">
        <div class="sec-head">
            <h2>MTN Services</h2>
        </div>

        <div class="services-strip">
            <div class="service-card" data-service="Game World (&#8358;50/day)">
                <div class="service-media">
                    <img src="https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=500&q=80" alt="MTN Game World">
                    <span class="service-badge">MORE INFO</span>
                </div>
                <div class="service-body">
                    <strong>Game World</strong>
                    <span>Enter the world of games. Play anywhere anytime</span>
                </div>
            </div>

            <div class="service-card" data-service="Kids Club (&#8358;100/week)">
                <div class="service-media">
                    <img src="https://images.unsplash.com/photo-1503454537195-1dcabb73ffb9?auto=format&fit=crop&w=500&q=80" alt="MTN Kids Club">
                    <span class="service-badge">SUBSCRIBE</span>
                </div>
                <div class="service-body">
                    <strong>Kids Club</strong>
                    <span>Mobile Games for Kids. No ads! No in-app purchases</span>
                </div>
            </div>

            <div class="service-card" data-service="Ayoba Zero-Rated Chat">
                <div class="service-media">
                    <img src="https://images.unsplash.com/photo-1511632765486-a01980e01a18?auto=format&fit=crop&w=500&q=80" alt="MTN Ayoba">
                    <span class="service-badge">FREE CHAT</span>
                </div>
                <div class="service-body">
                    <strong>Ayoba</strong>
                    <span>Enjoy messaging, news and channels zero-rated</span>
                </div>
            </div>
        </div>
    </section>

    <div id="mtn20238" class="ad-slot"><a href="/api/mobucks_click.php?plid=73" class="ad-link">Discover MTN Zero-Rated Data Deals</a></div>

    <div class="portal-footer">
        MTN Nigeria Communications PLC &middot; XtraTime &amp; Captive Access Portal
    </div>
</div>

<div class="modal-backdrop" id="portal-modal">
    <div class="modal-box">
        <h3 id="modal-title">XtraTime Loan</h3>
        <p id="modal-body"></p>
        <button type="button" class="btn-mtn" id="modal-close" style="width:100%;">Close</button>
    </div>
</div>

<script src="/static/js/mobucks-sdk.js"></script>
<script>
(function () {
    var selectedGross = 200;
    var selectedNet = 170;
    var currentBalance = -749.9;

    var tiersBox = document.getElementById("loan-tiers");
    document.getElementById("toggle-loans").addEventListener("click", function () {
        tiersBox.classList.toggle("open");
    });

    document.querySelectorAll(".tier-chip").forEach(function (chip) {
        chip.addEventListener("click", function () {
            document.querySelectorAll(".tier-chip").forEach(function (c) { c.classList.remove("active"); });
            chip.classList.add("active");
            selectedGross = parseInt(chip.getAttribute("data-gross"), 10);
            selectedNet = parseInt(chip.getAttribute("data-net"), 10);
            document.getElementById("selected-loan-title").textContent = "Borrowing \u20A6" + selectedGross.toLocaleString();
            document.getElementById("selected-loan-net").textContent = "You get: \u20A6" + selectedNet.toLocaleString();
        });
    });

    function showModal(title, body) {
        document.getElementById("modal-title").textContent = title;
        document.getElementById("modal-body").textContent = body;
        document.getElementById("portal-modal").classList.add("open");
    }

    document.getElementById("modal-close").addEventListener("click", function () {
        document.getElementById("portal-modal").classList.remove("open");
    });

    document.getElementById("activate-loan-btn").addEventListener("click", function () {
        currentBalance = currentBalance + selectedNet;
        document.getElementById("balance-val").textContent = "\u20A6 " + currentBalance.toFixed(1);
        showModal("XtraTime Request Submitted", "Credited \u20A6" + selectedNet + " emergency airtime to 08037194208. Repayment due on next recharge.");
    });

    document.querySelectorAll(".app-tile").forEach(function (el) {
        el.addEventListener("click", function () {
            showModal(el.getAttribute("data-app"), "Zero-rated preview available on MTN Apps Club (1st 24h free for eligible prepaid lines).");
        });
    });

    document.querySelectorAll(".service-card").forEach(function (el) {
        el.addEventListener("click", function () {
            showModal("MTN Service Opt-In", "Service selected: " + el.getAttribute("data-service") + ". Top up your main line or activate XtraTime to subscribe.");
        });
    });
})();
</script>
<script>
(function () {
    const plid = 73;
    const mtnContainer = document.getElementById("mtn20238");
    const mtnUri = mtnContainer.childNodes[0].getAttribute("href");
    mtnContainer.addEventListener("click", () => fetch(mtnUri).catch(() => {}));
    window.mobucksApi.placeAd({
        containerElementId: "mtn20238",
        uid: "mtnng",
        passwd: "5858ea228cc2edf88721699b2c8638e5",
        plid: plid
    }, () => {
        typeof mtnNoBanner == "function" && mtnNoBanner(plid, mtnContainer);
    });
})();
</script>

</body>
</html>

