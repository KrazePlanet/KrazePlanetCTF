<?php
// avatar_modal.php: "Choose avatar" Modal with Categorized Anime & Character Avatars

$default_username = $_SESSION['username'] ?? 'User';
$current_avatar = $_SESSION['avatar'] ?? ('https://api.dicebear.com/7.x/adventurer/svg?seed=' . urlencode($default_username));

// Comprehensive avatar collection categorized by anime and style
$avatar_categories = [
    'DragonBall' => [
        ['name' => 'Goku Ultra Instinct', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=GokuUltra&hair=short01&hairColor=000000'],
        ['name' => 'Goku Super Saiyan', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=GokuSSJ&hair=short02&hairColor=ffd43b'],
        ['name' => 'Vegeta', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=VegetaPrince&hair=short03&hairColor=000000'],
        ['name' => 'Kid Goku', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=KidGoku'],
        ['name' => 'Gohan', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=GohanBeast&hairColor=ffffff'],
        ['name' => 'Trunks', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=FutureTrunks&hairColor=b197fc'],
        ['name' => 'Piccolo', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=PiccoloNamek&skinColor=8ce99a'],
        ['name' => 'Cell', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=PerfectCell&skinColor=69db7c'],
        ['name' => 'Krillin', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=KrillinZ']
    ],
    'OnePiece' => [
        ['name' => 'Luffy Gear 5', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=MonkeyDLuffy&hairColor=ffffff'],
        ['name' => 'Luffy', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=LuffyCaptain&hairColor=000000'],
        ['name' => 'Zoro', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=RoronoaZoro&hairColor=2b8a3e'],
        ['name' => 'Zoro Chibi', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=ZoroChibi&hairColor=40c057'],
        ['name' => 'Nami', 'url' => 'https://api.dicebear.com/7.x/lorelei/svg?seed=NamiNavigator&hairColor=f76707'],
        ['name' => 'Sanji', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=VinsmokeSanji&hairColor=ffd43b'],
        ['name' => 'Chopper', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=TonyTonyChopper'],
        ['name' => 'Nico Robin', 'url' => 'https://api.dicebear.com/7.x/lorelei/svg?seed=NicoRobinArchaeologist&hairColor=000000'],
        ['name' => 'Usopp', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=GodUsopp&hairColor=000000'],
        ['name' => 'Franky', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=FrankyCyborg&hairColor=339af0'],
        ['name' => 'Brook', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=SoulKingBrook'],
        ['name' => 'Portgas D. Ace', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=FireFistAce&hairColor=000000'],
        ['name' => 'Trafalgar Law', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=SurgeonOfDeathLaw&hairColor=000000']
    ],
    'Naruto' => [
        ['name' => 'Naruto Uzumaki', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=NarutoHokage&hairColor=ffd43b'],
        ['name' => 'Sasuke Uchiha', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=SasukeShadow&hairColor=000000'],
        ['name' => 'Kakashi Hatake', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=KakashiSensei&hairColor=ced4da'],
        ['name' => 'Itachi Uchiha', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=ItachiAkatsuki&hairColor=000000'],
        ['name' => 'Sakura Haruno', 'url' => 'https://api.dicebear.com/7.x/lorelei/svg?seed=SakuraMedic&hairColor=f783ac'],
        ['name' => 'Minato Namikaze', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=YellowFlashMinato&hairColor=ffd43b'],
        ['name' => 'Gaara', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=GaaraSand&hairColor=c92a2a']
    ],
    'DemonSlayer' => [
        ['name' => 'Tanjiro Kamado', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=TanjiroSun&hairColor=862e9c'],
        ['name' => 'Nezuko Kamado', 'url' => 'https://api.dicebear.com/7.x/lorelei/svg?seed=NezukoDemon&hairColor=000000'],
        ['name' => 'Zenitsu Agatsuma', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=ZenitsuThunder&hairColor=ffd43b'],
        ['name' => 'Inosuke Hashibira', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=InosukeBeast&hairColor=1971c2'],
        ['name' => 'Kyojuro Rengoku', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=FlameHashiraRengoku&hairColor=f76707'],
        ['name' => 'Giyu Tomioka', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=WaterHashiraGiyu&hairColor=000000']
    ],
    'JujutsuKaisen' => [
        ['name' => 'Gojo Satoru', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=GojoLimitless&hairColor=ffffff'],
        ['name' => 'Ryomen Sukuna', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=KingOfCursesSukuna&hairColor=f783ac'],
        ['name' => 'Yuji Itadori', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=YujiVessel&hairColor=f783ac'],
        ['name' => 'Megumi Fushiguro', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=MegumiShadows&hairColor=000000'],
        ['name' => 'Nobara Kugisaki', 'url' => 'https://api.dicebear.com/7.x/lorelei/svg?seed=NobaraStrawDoll&hairColor=d9480f']
    ],
    'SpyFamily' => [
        ['name' => 'Anya Forger', 'url' => 'https://api.dicebear.com/7.x/lorelei/svg?seed=AnyaTelepath&hairColor=f783ac'],
        ['name' => 'Loid Forger', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=TwilightLoid&hairColor=ffd43b'],
        ['name' => 'Yor Forger', 'url' => 'https://api.dicebear.com/7.x/lorelei/svg?seed=ThornPrincessYor&hairColor=000000']
    ],
    'AttackOnTitan' => [
        ['name' => 'Levi Ackerman', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=CaptainLevi&hairColor=000000'],
        ['name' => 'Eren Yeager', 'url' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=ErenFounding&hairColor=343a40'],
        ['name' => 'Mikasa Ackerman', 'url' => 'https://api.dicebear.com/7.x/lorelei/svg?seed=MikasaScout&hairColor=000000']
    ],
    'CyberBots' => [
        ['name' => 'Cyber Sentinel', 'url' => 'https://api.dicebear.com/7.x/bottts/svg?seed=KrazeCyberSentinel&colors=cyan'],
        ['name' => 'Matrix Glitch', 'url' => 'https://api.dicebear.com/7.x/bottts/svg?seed=MatrixGlitch&colors=green'],
        ['name' => 'Shadow Bot', 'url' => 'https://api.dicebear.com/7.x/bottts/svg?seed=ShadowBotX&colors=purple'],
        ['name' => 'Quantum Core', 'url' => 'https://api.dicebear.com/7.x/bottts/svg?seed=QuantumCore&colors=blue'],
        ['name' => 'Vulnera Guard', 'url' => 'https://api.dicebear.com/7.x/bottts/svg?seed=VulneraGuard&colors=orange']
    ]
];
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
              <img src="<?= htmlspecialchars($av['url']) ?>" alt="<?= htmlspecialchars($av['name']) ?>" loading="lazy">
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<script>
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
