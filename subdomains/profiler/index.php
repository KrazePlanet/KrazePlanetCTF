<?php
require_once __DIR__ . '/includes/profiles_data.php';

$profile_id = isset($_GET['id']) && $_GET['id'] !== '' ? trim((string) $_GET['id']) : '1024';
$all_profiles = get_all_member_profiles();

$active_nav = 'profile';
$page_title = 'Peerstack — Member Profile';
require_once __DIR__ . '/includes/header.php';
?>

<div class="profile-subnav">
    <div class="container subnav-tabs">
        <button type="button" class="subnav-tab active" data-tab="overview">Overview</button>
        <button type="button" class="subnav-tab" data-tab="repositories">
            Repositories <span class="count-badge" id="repo-count">0</span>
        </button>
        <button type="button" class="subnav-tab" data-tab="activity">Activity</button>
        <a href="/explore.php" class="subnav-tab">Directory</a>
    </div>
</div>

<main class="container">
    <div class="workspace-grid">
        <aside>
            <div class="avatar-wrap">
                <img id="profile-avatar" class="profile-avatar" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='260' height='260'/%3E" alt="Member portrait">
                <div class="status-pill">
                    <span class="status-dot"></span>
                    <span id="profile-status">Active</span>
                </div>
            </div>

            <div class="profile-names">
                <h1 id="profile-name">Loading...</h1>
                <div class="profile-handle" id="profile-username">@...</div>
            </div>

            <p class="profile-bio" id="profile-bio"></p>

            <div class="profile-actions">
                <button type="button" class="btn btn-accent" id="follow-btn">Follow</button>
                <a href="/explore.php" class="btn">Browse All</a>
            </div>

            <div class="follow-stats">
                <span><strong id="stat-followers">0</strong> followers</span>
                <span>&middot;</span>
                <span><strong id="stat-following">0</strong> following</span>
            </div>

            <ul class="vcard-list">
                <li><strong>Company:</strong> <span id="profile-company">&mdash;</span></li>
                <li><strong>Location:</strong> <span id="profile-location">&mdash;</span></li>
                <li><strong>Website:</strong> <a href="#" id="profile-website" target="_blank" rel="noopener">&mdash;</a></li>
            </ul>

            <hr class="sidebar-divider">

            <h2 class="sidebar-heading">Core Stack</h2>
            <div class="skill-tags" id="profile-skills"></div>

            <hr class="sidebar-divider">

            <h2 class="sidebar-heading">Suggested Builders</h2>
            <div class="mini-people">
                <?php foreach ($all_profiles as $other): ?>
                    <?php if ((string) $other['id'] === $profile_id) continue; ?>
                    <a href="/?id=<?= urlencode((string) $other['id']) ?>" class="mini-person">
                        <img src="<?= htmlspecialchars($other['avatar_url']) ?>" alt="<?= htmlspecialchars($other['name']) ?>">
                        <div>
                            <div style="font-size:13px;font-weight:600;line-height:1.2;"><?= htmlspecialchars($other['name']) ?></div>
                            <div style="font-size:11.5px;color:var(--fg-muted);">@<?= htmlspecialchars($other['username']) ?> &middot; <?= htmlspecialchars($other['role_category']) ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <section>
            <div id="tab-overview">
                <div class="panel-box">
                    <div class="panel-header">
                        <span class="mono" id="readme-header">README.md</span>
                        <span id="profile-headline" style="color:var(--fg-subtle);"></span>
                    </div>
                    <div class="panel-body">
                        <p id="profile-about" style="margin:0;color:var(--fg);line-height:1.65;">Loading profile summary...</p>
                    </div>
                </div>

                <div class="section-title-row">
                    <h2>Pinned Repositories</h2>
                    <a href="/projects.php" style="font-size:12.5px;color:var(--accent);">View all open-source work &rarr;</a>
                </div>
                <div class="pinned-grid" id="pinned-repos"></div>

                <div class="section-title-row">
                    <h2>Contribution Activity (Last 10 Months)</h2>
                </div>
                <div class="contrib-card">
                    <div class="contrib-grid" id="contrib-heatmap"></div>
                    <div class="contrib-legend">
                        <span>Verified commits, releases &amp; package reviews</span>
                        <span style="display:flex;align-items:center;gap:4px;">
                            Less
                            <span class="contrib-cell" style="display:inline-block;"></span>
                            <span class="contrib-cell c-1" style="display:inline-block;"></span>
                            <span class="contrib-cell c-2" style="display:inline-block;"></span>
                            <span class="contrib-cell c-3" style="display:inline-block;"></span>
                            <span class="contrib-cell c-4" style="display:inline-block;"></span>
                            More
                        </span>
                    </div>
                </div>

                <div class="section-title-row">
                    <h2>Recent Activity</h2>
                </div>
                <div class="activity-list" id="activity-feed"></div>
            </div>

            <div id="tab-repositories" style="display:none;">
                <div class="section-title-row">
                    <h2>Public Repositories</h2>
                </div>
                <div class="pinned-grid" id="all-repos-grid"></div>
            </div>

            <div id="tab-activity" style="display:none;">
                <div class="section-title-row">
                    <h2>Full Event Log</h2>
                </div>
                <div class="activity-list" id="activity-feed-full"></div>
            </div>
        </section>
    </div>
</main>

<script>
(function () {
    var profileId = <?= json_encode($profile_id) ?>;
    var followingState = false;

    function buildHeatmap(seed) {
        var grid = document.getElementById("contrib-heatmap");
        grid.innerHTML = "";
        var totalCells = 44 * 7;
        var hash = parseInt(seed, 10) || 1024;
        for (var i = 0; i < totalCells; i++) {
            hash = (hash * 1664525 + 1013904223) >>> 0;
            var r = (hash >>> 16) % 10;
            var cell = document.createElement("div");
            cell.className = "contrib-cell";
            if (r >= 9) cell.classList.add("c-4");
            else if (r >= 7) cell.classList.add("c-3");
            else if (r >= 5) cell.classList.add("c-2");
            else if (r >= 3) cell.classList.add("c-1");
            grid.appendChild(cell);
        }
    }

    function renderRepoCards(targetEl, repos) {
        targetEl.innerHTML = "";
        (repos || []).forEach(function (repo) {
            var card = document.createElement("div");
            card.className = "repo-card";

            var top = document.createElement("div");
            var title = document.createElement("div");
            title.className = "repo-name";
            title.textContent = repo.name;
            var desc = document.createElement("p");
            desc.className = "repo-desc";
            desc.textContent = repo.desc;
            top.appendChild(title);
            top.appendChild(desc);

            var meta = document.createElement("div");
            meta.className = "repo-meta";

            var lang = document.createElement("span");
            var dot = document.createElement("span");
            dot.className = "lang-dot";
            dot.style.background = repo.lang_color || "#8b949e";
            lang.appendChild(dot);
            lang.appendChild(document.createTextNode(repo.lang || "Code"));

            var stars = document.createElement("span");
            stars.textContent = "\u2605 " + (repo.stars || 0);

            var forks = document.createElement("span");
            forks.textContent = "Forks " + (repo.forks || 0);

            meta.appendChild(lang);
            meta.appendChild(stars);
            meta.appendChild(forks);

            card.appendChild(top);
            card.appendChild(meta);
            targetEl.appendChild(card);
        });
    }

    function renderActivity(targetEl, items) {
        targetEl.innerHTML = "";
        (items || []).forEach(function (item) {
            var wrap = document.createElement("div");
            wrap.className = "activity-item";

            var title = document.createElement("div");
            title.className = "activity-title";
            title.textContent = item.action;

            var detail = document.createElement("div");
            detail.className = "activity-detail";
            detail.textContent = item.detail;

            var time = document.createElement("div");
            time.className = "activity-time";
            time.textContent = item.time;

            wrap.appendChild(title);
            wrap.appendChild(detail);
            wrap.appendChild(time);
            targetEl.appendChild(wrap);
        });
    }

    fetch("/api/profile.php?id=" + encodeURIComponent(profileId))
        .then(function (r) {
            if (!r.ok) throw new Error("Not found");
            return r.json();
        })
        .then(function (data) {
            document.title = data.name + " (@" + data.username + ") · Peerstack";
            document.getElementById("profile-name").textContent = data.name;
            document.getElementById("profile-username").textContent = "@" + (data.username || "member");
            document.getElementById("profile-status").textContent = data.status_text || "Active";
            document.getElementById("profile-bio").textContent = data.bio || "";
            document.getElementById("profile-about").textContent = data.about_markdown || data.bio || "";
            document.getElementById("profile-headline").textContent = data.headline || "";
            document.getElementById("readme-header").textContent = (data.username || "user") + " / README.md";

            document.getElementById("profile-company").textContent = data.company || "Independent";
            document.getElementById("profile-location").textContent = data.location || "Remote";
            var siteEl = document.getElementById("profile-website");
            siteEl.textContent = (data.website || "").replace(/^https?:\/\//, "") || "—";
            siteEl.href = data.website || "#";

            document.getElementById("stat-followers").textContent = (data.followers || 0).toLocaleString();
            document.getElementById("stat-following").textContent = (data.following || 0).toLocaleString();
            document.getElementById("repo-count").textContent = (data.pinned_projects || []).length;

            if (data.avatar_url) {
                var img = document.getElementById("profile-avatar");
                img.src = data.avatar_url;
                img.alt = data.name;
            }

            var skillsEl = document.getElementById("profile-skills");
            skillsEl.innerHTML = "";
            (data.skills || []).forEach(function (skill) {
                var tag = document.createElement("span");
                tag.className = "skill-tag";
                tag.textContent = skill;
                skillsEl.appendChild(tag);
            });

            renderRepoCards(document.getElementById("pinned-repos"), data.pinned_projects);
            renderRepoCards(document.getElementById("all-repos-grid"), data.pinned_projects);
            renderActivity(document.getElementById("activity-feed"), data.recent_activity);
            renderActivity(document.getElementById("activity-feed-full"), data.recent_activity);
            buildHeatmap(data.id);

            document.getElementById("follow-btn").addEventListener("click", function () {
                followingState = !followingState;
                this.textContent = followingState ? "Following" : "Follow";
                var base = data.followers || 0;
                document.getElementById("stat-followers").textContent = (base + (followingState ? 1 : 0)).toLocaleString();
            });
        })
        .catch(function () {
            document.getElementById("profile-name").textContent = "Member not found";
            document.getElementById("profile-username").textContent = "404";
            document.getElementById("profile-about").textContent = "No member record matched this profile ID. Return to Explore to browse verified contributors.";
        });

    document.querySelectorAll(".subnav-tab[data-tab]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.querySelectorAll(".subnav-tab[data-tab]").forEach(function (b) { b.classList.remove("active"); });
            btn.classList.add("active");
            var tab = btn.getAttribute("data-tab");
            document.getElementById("tab-overview").style.display = tab === "overview" ? "block" : "none";
            document.getElementById("tab-repositories").style.display = tab === "repositories" ? "block" : "none";
            document.getElementById("tab-activity").style.display = tab === "activity" ? "block" : "none";
        });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
