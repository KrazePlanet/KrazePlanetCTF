# Système de journalisation

Ce document décrit comment utiliser le système de journalisation centralisé de l'application.

## Fonctionnalités

- Journalisation centralisée dans des fichiers journaux
- Rotation automatique des fichiers de log
- Niveaux de log : debug, info, warning, error
- Gestion des exceptions et erreurs PHP
- Pages d'erreur personnalisées
- Configuration flexible selon l'environnement (développement/production)

## Utilisation

### Journalisation de base

```php
// Journaliser un message d'information
log_message("L'utilisateur s'est connecté avec succès", 'info');

// Journaliser un avertissement
log_message("Tentative de connexion échouée pour l'utilisateur", 'warning');

// Journaliser une erreur
try {
    // Code qui peut échouer
} catch (Exception $e) {
    log_exception($e);
}
```

### Niveaux de log

- `LOG_LEVEL_DEBUG` : Informations de débogage détaillées
- `LOG_LEVEL_INFO` : Informations générales sur le fonctionnement de l'application
- `LOG_LEVEL_WARNING` : Situations potentiellement problématiques
- `LOG_LEVEL_ERROR` : Erreurs qui nécessitent une attention immédiate

### Configuration

La configuration se trouve dans `includes/logger.php` :

```php
$logger_config = [
    'log_dir' => LOGS_PATH,          // Répertoire des logs
    'log_file' => 'app_'.date('Y-m-d').'.log', // Format du fichier de log
    'error_log_file' => 'php_errors.log',      // Fichier d'erreurs PHP
    'max_file_size' => 5 * 1024 * 1024, // 5MB par fichier de log
    'max_files' => 30,                // Conserver les logs sur 30 jours
];
```

### Pages d'erreur

Des pages d'erreur personnalisées sont disponibles dans `templates/errors/` :

- `400.php` - Mauvaise requête
- `403.php` - Accès refusé
- `404.php` - Page non trouvée
- `500.php` - Erreur serveur

## Bonnes pratiques

1. **Messages clairs** : Rédigez des messages de log explicites et informatifs.
2. **Contexte** : Incluez suffisamment de contexte pour comprendre l'erreur.
3. **Niveaux appropriés** : Utilisez le bon niveau de log pour chaque message.
4. **Données sensibles** : Ne journalisez jamais de mots de passe ou d'informations sensibles.
5. **Performances** : Évitez les logs excessifs dans les parties critiques des performances.

## Dépannage

- **Problème** : Les logs ne s'écrivent pas
  - Vérifiez les permissions du répertoire `logs/`
  - Vérifiez que le disque n'est pas plein
  - Activez le mode debug pour plus d'informations

- **Problème** : Les fichiers de log deviennent trop gros
  - Ajustez `max_file_size` et `max_files` dans la configuration
  - Mettez en place une rotation des logs au niveau du système si nécessaire
