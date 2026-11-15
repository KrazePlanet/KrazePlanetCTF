<?php
// avatar_modal.php: "Choose avatar" modal, avatars grouped by art style

$default_username = $_SESSION['username'] ?? 'User';
$current_avatar = $_SESSION['avatar'] ?? ('https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($default_username));

// Original cartoon avatars from DiceBear (dicebear.com), grouped by art style.
// They are generated from a "seed" word: they are NOT any real character, so the
// groups are named after the style, never after an anime or a franchise.
// Seeds that students already picked are kept exactly as they were, so nobody's
// current avatar changes.
$dicebear = fn($style, $seed, $extra = '') => 'https://api.dicebear.com/7.x/' . $style . '/svg?seed=' . $seed . $extra;
$avatar_groups = [
    'Cartoon' => ['adventurer', [
        ['GokuUltra', '&hair=short01&hairColor=000000'], ['GokuSSJ', '&hair=short02&hairColor=ffd43b'], ['VegetaPrince', '&hair=short03&hairColor=000000'],
        ['KidGoku', ''], ['GohanBeast', '&hairColor=ffffff'], ['FutureTrunks', '&hairColor=b197fc'], ['PiccoloNamek', '&skinColor=8ce99a'],
        ['PerfectCell', '&skinColor=69db7c'], ['KrillinZ', ''], ['MonkeyDLuffy', '&hairColor=ffffff'], ['LuffyCaptain', '&hairColor=000000'],
        ['RoronoaZoro', '&hairColor=2b8a3e'], ['ZoroChibi', '&hairColor=40c057'], ['VinsmokeSanji', '&hairColor=ffd43b'], ['TonyTonyChopper', ''],
        ['GodUsopp', '&hairColor=000000'], ['FrankyCyborg', '&hairColor=339af0'], ['SoulKingBrook', ''], ['FireFistAce', '&hairColor=000000'],
        ['SurgeonOfDeathLaw', '&hairColor=000000'], ['NarutoHokage', '&hairColor=ffd43b'], ['SasukeShadow', '&hairColor=000000'],
        ['KakashiSensei', '&hairColor=ced4da'], ['ItachiAkatsuki', '&hairColor=000000'], ['YellowFlashMinato', '&hairColor=ffd43b'],
        ['GaaraSand', '&hairColor=c92a2a'], ['TanjiroSun', '&hairColor=862e9c'], ['ZenitsuThunder', '&hairColor=ffd43b'],
        ['InosukeBeast', '&hairColor=1971c2'], ['FlameHashiraRengoku', '&hairColor=f76707'], ['WaterHashiraGiyu', '&hairColor=000000'],
        ['GojoLimitless', '&hairColor=ffffff'], ['KingOfCursesSukuna', '&hairColor=f783ac'], ['YujiVessel', '&hairColor=f783ac'],
        ['MegumiShadows', '&hairColor=000000'], ['TwilightLoid', '&hairColor=ffd43b'], ['CaptainLevi', '&hairColor=000000'],
        ['ErenFounding', '&hairColor=343a40'],
    ]],
    'Portrait' => ['lorelei', [
        ['NamiNavigator', '&hairColor=f76707'], ['NicoRobinArchaeologist', '&hairColor=000000'], ['SakuraMedic', '&hairColor=f783ac'],
        ['NezukoDemon', '&hairColor=000000'], ['NobaraStrawDoll', '&hairColor=d9480f'], ['AnyaTelepath', '&hairColor=f783ac'],
        ['ThornPrincessYor', '&hairColor=000000'], ['MikasaScout', '&hairColor=000000'], ['PortraitRiver', ''], ['PortraitEmber', ''],
    ]],
    'Robot' => ['bottts', [
        ['KrazeCyberSentinel', '&colors=cyan'], ['MatrixGlitch', '&colors=green'], ['ShadowBotX', '&colors=purple'],
        ['QuantumCore', '&colors=blue'], ['VulneraGuard', '&colors=orange'], ['RobotPatch', ''], ['RobotNull', ''], ['RobotPayload', ''],
    ]],
    'Pixel' => ['pixel-art', [
        ['PixelRoot', ''], ['PixelShell', ''], ['PixelPacket', ''], ['PixelCookie', ''], ['PixelToken', ''],
        ['PixelBuffer', ''], ['PixelCipher', ''], ['PixelSocket', ''], ['PixelKernel', ''], ['PixelProxy', ''],
        ['PixelHash', ''], ['PixelNonce', ''], ['PixelSalt', ''], ['PixelHeader', ''], ['PixelQuery', ''], ['PixelRoute', ''],
    ]],
    'Doodle' => ['notionists', [
        ['DoodleHunter', ''], ['DoodleScout', ''], ['DoodleTinker', ''], ['DoodleRecon', ''], ['DoodleFuzzer', ''],
        ['DoodleAudit', ''], ['DoodleTrace', ''], ['DoodleSignal', ''], ['DoodleBeacon', ''], ['DoodleVector', ''],
        ['DoodleSandbox', ''], ['DoodleCanary', ''], ['DoodleHoneypot', ''], ['DoodlePatch', ''], ['DoodleExploit', ''], ['DoodleScanner', ''],
    ]],
];

// More styles: 12 avatars each, seeded with security words (Prefix + word),
// e.g. "ComicPayload". Adding a word here adds one avatar to every style below.
$seedWords = ['Payload', 'Cookie', 'Token', 'Cipher', 'Exploit', 'Firewall', 'Packet', 'Kernel', 'Sandbox', 'Honeypot', 'Bounty', 'Recon'];
foreach ([
    'Comic'       => 'avataaars',
    'Smiley'      => 'big-smile',
    'Illustrated' => 'micah',
    'Mini'        => 'miniavs',
    'Sketch'      => 'open-peeps',
    'Persona'     => 'personas',
    'Emoji'       => 'fun-emoji',
    'Scribble'    => 'croodles',
    'Big Ears'    => 'big-ears',
    'Thumbs'      => 'thumbs',
] as $group => $style) {
    $avatar_groups[$group] = [$style, array_map(fn($w) => [str_replace(' ', '', $group) . $w, ''], $seedWords)];
}
$avatar_categories = [];
foreach ($avatar_groups as $group => [$style, $items]) {
    foreach ($items as $i => [$seed, $extra]) {
        $avatar_categories[$group][] = ['name' => $group . ' avatar ' . ($i + 1), 'url' => $dicebear($style, $seed, $extra)];
    }
}
?>

<style>
  /* "Choose avatar" Modal Styles matching Screenshot 3 */
  .avatar-modal-content {
    background: #0d1527;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 20px;
    box-shadow: 0 25px 75px rgba(0, 0, 0, 0.95), 0 0 30px rgba(56, 189, 248, 0.2);
    color: #f8fafc;
    overflow: hidden;
  }

  .avatar-modal-header {
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding: 1.25rem 1.5rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .avatar-modal-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #ffffff;
    font-family: 'Outfit', sans-serif;
  }

  .avatar-tags-scroll {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: center;
    padding: 1rem 1.25rem 0.75rem;
    background: rgba(7, 11, 20, 0.4);
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  }

  .avatar-tag-btn {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-size: 12.5px;
    font-weight: 500;
    padding: 3px 8px;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .avatar-tag-btn:hover {
    color: #38bdf8;
    background: rgba(56, 189, 248, 0.1);
  }

  .avatar-tag-btn.active {
    color: #38bdf8;
    font-weight: 700;
    background: rgba(56, 189, 248, 0.18);
    box-shadow: 0 0 10px rgba(56, 189, 248, 0.25);
  }

  .avatar-grid-container {
    max-height: 480px;
    overflow-y: auto;
    padding: 1.5rem 1.25rem;
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    place-items: center;
  }

  @media (max-width: 576px) {
    .avatar-grid-container {
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
    }
  }

  .avatar-choice-card {
    position: relative;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    cursor: pointer;
    padding: 2px;
    background: rgba(30, 41, 59, 0.6);
    border: 2px solid transparent;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .avatar-choice-card img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    background: #1e293b;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: transform 0.2s ease;
  }

  .avatar-choice-card:hover {
    transform: scale(1.12);
    border-color: #38bdf8;
    box-shadow: 0 0 16px rgba(56, 189, 248, 0.5);
  }

  .avatar-choice-card.selected {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.35), 0 0 20px rgba(56, 189, 248, 0.6);
    transform: scale(1.08);
  }

  .avatar-choice-card.selected::after {
    content: '✓';
    position: absolute;
    bottom: -2px;
    right: -2px;
    background: #0284c7;
    color: #ffffff;
    font-size: 10px;
    font-weight: 800;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #0d1527;
  }
</style>

<!-- CHOOSE AVATAR MODAL -->
<div class="modal fade" id="avatarModal" tabindex="-1" aria-labelledby="avatarModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
    <div class="modal-content avatar-modal-content">
      
      <!-- Modal Header -->
      <div class="avatar-modal-header">
        <h5 class="avatar-modal-title mb-0" id="avatarModalLabel">Choose avatar</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Tag Filter Chips matching Screenshot 3 -->
      <div class="avatar-tags-scroll">
        <button type="button" class="avatar-tag-btn active" data-category="all"># All</button>
        <?php foreach (array_keys($avatar_categories) as $cat): ?>
          <button type="button" class="avatar-tag-btn" data-category="<?= htmlspecialchars($cat) ?>"># <?= htmlspecialchars($cat) ?></button>
        <?php endforeach; ?>
      </div>

      <div id="avatarAlert" style="display:none;" class="mx-3 mt-2 alert py-2 px-3 small border-0"></div>

      <!-- Avatar Grid -->
      <div class="avatar-grid-container" id="avatarGridContainer">
        <?php foreach ($avatar_categories as $cat => $avatars): ?>
          <?php foreach ($avatars as $av): ?>
            <?php 
              $isSelected = ($current_avatar === $av['url']);
            ?>
            <div class="avatar-choice-card <?= $isSelected ? 'selected' : '' ?>" data-category="<?= htmlspecialchars($cat) ?>" data-avatar-url="<?= htmlspecialchars($av['url']) ?>" title="<?= htmlspecialchars($av['name']) ?>" onclick="selectAvatar('<?= htmlspecialchars(addslashes($av['url'])) ?>', this)">
              <img src="<?= htmlspecialchars($av['url']) ?>" alt="<?= htmlspecialchars($av['name']) ?>" loading="lazy" onerror="retryAvatarImg(this)">
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<script>
// DiceBear sometimes refuses a burst of requests (the picker loads ~75 at once).
// Try a failed avatar again, waiting a little longer each time (up to 3 tries).
function retryAvatarImg(img) {
  const tries = Number(img.dataset.tries || 0);
  if (tries >= 3) return;
  img.dataset.tries = tries + 1;
  const src = img.getAttribute('src');
  setTimeout(() => { img.src = ''; img.src = src; }, 700 * (tries + 1) + Math.random() * 400);
}

function openAvatarModal(e) {
  if (e) e.preventDefault();
  const el = document.getElementById('avatarModal');
  if (el) {
    bootstrap.Modal.getOrCreateInstance(el).show();
  }
}

function selectAvatar(avatarUrl, cardEl) {
  // Update UI selection immediately
  document.querySelectorAll('.avatar-choice-card').forEach(c => c.classList.remove('selected'));
  if (cardEl) cardEl.classList.add('selected');

  // Update navbar avatar preview live
  document.querySelectorAll('.navbar-user-avatar, .user-menu-avatar-img').forEach(img => {
    img.src = avatarUrl;
  });

  // Save to database via AJAX
  const formData = new FormData();
  formData.append('action', 'update_avatar');
  formData.append('avatar_url', avatarUrl);

  fetch('/api/auth_api.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        // Auto-close modal after slight delay
        setTimeout(() => {
          const el = document.getElementById('avatarModal');
          if (el) {
            bootstrap.Modal.getInstance(el)?.hide();
          }
        }, 350);
      }
    })
    .catch(err => console.error('Failed to update avatar:', err));
}

// Category filter tag clicks
document.addEventListener('DOMContentLoaded', () => {
  const tagBtns = document.querySelectorAll('.avatar-tag-btn');
  const avatarCards = document.querySelectorAll('.avatar-choice-card');

  tagBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tagBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const selectedCategory = btn.getAttribute('data-category');
      avatarCards.forEach(card => {
        if (selectedCategory === 'all' || card.getAttribute('data-category') === selectedCategory) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
});
</script>
