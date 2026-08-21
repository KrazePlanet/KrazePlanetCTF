            </div>
            
            <div class="px-6 py-4 border-t border-gray-200 bg-white/90 rounded-b-lg">
                <?php if (basename($_SERVER['PHP_SELF']) === 'login.php'): ?>
                    <p class="text-center text-sm text-gray-700">
                        Pas encore de compte ? 
                        <a href="signup.php" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                            S'inscrire
                        </a>
                    </p>
                <?php else: ?>
                    <p class="text-center text-sm text-gray-700">
                        Déjà un compte ? 
                        <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500 transition-colors">
                            Se connecter
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
