<nav class="navbar navbar-expand-lg ">
        <div class="container-fluid">
            <a class="navbar-brand text-warning" href="<?= $dir ?>/index.php" style="font-family: Arial, Helvetica, sans-serif;">YUMMY😋</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="<?= $dir ?>/index.php">Acceuil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $dir ?>/app/view/cart/cart.php">Votre Commande</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $dir ?>/index.php#about">A propos de nous</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center gap-3" role="search" style="cursor: pointer;" onclick="if(confirm('Vous voulez vraiment vous deconnecter ?')){ location.href='<?= $dir ?>/deconnexion.php'; }">
                    <h6 class="mt-2 text-dark font-weight-bold"><?= htmlspecialchars($_SESSION['users']['name'] ?? 'User') ?></h6>
                    <img src="<?= $dir . '/app/images/' . htmlspecialchars($_SESSION['users']['profil'] ?? 'default.jpg') ?>" onerror="this.src='<?= $dir ?>/assets/img/pizza.jpg'" style="width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 2px solid #ffc107;" alt="Profile">
                </div>
            </div>
        </div>
    </nav>
