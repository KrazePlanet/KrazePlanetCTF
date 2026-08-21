        </main>
    </div>
    <footer class="bg-gray-800 text-white p-6 w-full">
        <div class="container mx-auto text-center">
            <p>&copy; <?= date('Y') ?> ReadSphere - Tous droits réservés</p>
            <p class="text-gray-400 text-sm mt-2">Une application de partage de lectures et de critiques</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Afficher les messages flash
        <?php if (isset($_SESSION['flash'])): ?>
            Swal.fire({
                icon: '<?= $_SESSION['flash']['type'] === 'error' ? 'error' : 'success' ?>',
                title: '<?= addslashes($_SESSION['flash']['message']) ?>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
