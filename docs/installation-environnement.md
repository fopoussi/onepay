# Guide d'Installation et Configuration - OnePay API

## Prérequis
- PHP 8.2 ou supérieur
- Composer
- PostgreSQL 16
- Node.js (pour le développement frontend)
- Git

## Installation

### 1. Cloner le Projet
```bash
git clone [url-du-projet]
cd onepay
```

### 2. Installation des Dépendances
```bash
# Installation des dépendances PHP
composer install

# Installation des dépendances Node.js (si nécessaire pour le frontend)
npm install
```

### 3. Configuration de l'Environnement

#### Configuration de la Base de Données
Créez un fichier `.env.local` à la racine du projet :
```env
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/onepay?serverVersion=16&charset=utf8"
```

#### Configuration OAuth Google
```env
GOOGLE_CLIENT_ID="votre-client-id"
GOOGLE_CLIENT_SECRET="votre-client-secret"
```

#### Configuration JWT
```bash
# Génération des clés JWT
php bin/console lexik:jwt:generate-keypair
```

### 4. Création de la Base de Données
```bash
# Création de la base de données
php bin/console doctrine:database:create

# Exécution des migrations
php bin/console doctrine:migrations:migrate
```

### 5. Démarrage du Serveur de Développement
```bash
# Démarrer le serveur Symfony
symfony server:start

# Dans un autre terminal, si vous avez un frontend
npm run dev
```

## Configuration Docker (Optionnel)

Si vous préférez utiliser Docker, un fichier `compose.yaml` est fourni :

```bash
# Démarrer les conteneurs
docker-compose up -d

# Arrêter les conteneurs
docker-compose down
```

## Structure du Projet

```
onepay/
├── config/                 # Configuration Symfony
├── docs/                  # Documentation
├── migrations/            # Migrations de base de données
├── public/               # Point d'entrée public
├── src/                  # Code source
│   ├── Controller/      # Contrôleurs
│   ├── Entity/          # Entités Doctrine
│   ├── Repository/      # Repositories
│   ├── Security/        # Classes de sécurité
│   └── Service/         # Services métier
└── tests/                # Tests unitaires et fonctionnels
```

## Configuration IDE

### VSCode
Extensions recommandées :
- PHP Intelephense
- Symfony for VSCode
- Docker
- GitLens

### PhpStorm
Plugins recommandés :
- Symfony Support
- PHP Annotations
- PHP Toolbox

## Tests

### Configuration des Tests
```bash
# Création de la base de données de test
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:schema:create
```

### Exécution des Tests
```bash
# Exécuter tous les tests
php bin/phpunit

# Exécuter une suite de tests spécifique
php bin/phpunit tests/Controller

# Exécuter un test spécifique
php bin/phpunit tests/Controller/SecurityControllerTest.php
```

## Débogage

### Logs
Les logs sont disponibles dans :
- `var/log/dev.log` (environnement de développement)
- `var/log/test.log` (environnement de test)
- `var/log/prod.log` (environnement de production)

### Profiler Symfony
Le profiler est accessible en développement à l'URL `/_profiler`

### Xdebug
Configuration recommandée pour `php.ini` :
```ini
[xdebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_port=9003
```

## Déploiement

### Préparation pour la Production
```bash
# Vider le cache
php bin/console cache:clear --env=prod

# Optimiser l'autoloader
composer dump-autoload --optimize --no-dev --classmap-authoritative

# Compiler les assets (si applicable)
npm run build
```

### Variables d'Environnement Production
Assurez-vous de configurer correctement les variables d'environnement en production :
- `APP_ENV=prod`
- `APP_SECRET`
- `DATABASE_URL`
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `JWT_SECRET_KEY`
- `JWT_PUBLIC_KEY`
- `JWT_PASSPHRASE`

## Support

Pour toute question ou problème :
1. Consultez la documentation API : `/api/doc`
2. Vérifiez les logs dans `var/log/`
3. Contactez l'équipe de développement

## Contribution

1. Créez une branche pour votre fonctionnalité
2. Écrivez des tests
3. Suivez les standards PSR-12
4. Soumettez une Pull Request

## Ressources Utiles
- [Documentation Symfony](https://symfony.com/doc)
- [Documentation API Platform](https://api-platform.com/docs)
- [Documentation JWT](https://github.com/lexik/LexikJWTAuthenticationBundle/blob/master/Resources/doc/index.md)
