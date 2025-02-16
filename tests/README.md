# Tests de l'API OnePay

Ce dossier contient tous les tests automatisés pour l'API OnePay.

## Structure des Tests

- `Controller/` : Tests des contrôleurs
  - `SecurityControllerTest.php` : Tests d'authentification (login, register, etc.)
  - `OnepayUserControllerTest.php` : Tests de gestion des utilisateurs
  - `OnepayTransactionControllerTest.php` : Tests des transactions

- `Security/`
  - `GoogleAuthenticatorTest.php` : Tests de l'authentification Google

- `EventSubscriber/`
  - `JwtSubscriberTest.php` : Tests de la gestion des tokens JWT

## Prérequis

1. Base de données de test configurée
```bash
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
```

2. Générer les clés JWT de test
```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private-test.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private-test.pem -out config/jwt/public-test.pem -pubout
```

## Exécution des Tests

### Exécuter tous les tests
```bash
php bin/phpunit
```

### Exécuter une suite de tests spécifique
```bash
php bin/phpunit tests/Controller/SecurityControllerTest.php
php bin/phpunit tests/Controller/OnepayUserControllerTest.php
php bin/phpunit tests/Controller/OnepayTransactionControllerTest.php
```

### Exécuter un test spécifique
```bash
php bin/phpunit --filter testInscription tests/Controller/SecurityControllerTest.php
```

### Générer un rapport de couverture
```bash
php bin/phpunit --coverage-html coverage
```

## Notes Importantes

1. Les tests utilisent une base de données dédiée (`onepay_test`)
2. Les données de test sont automatiquement nettoyées après chaque test
3. Les clés JWT de test sont différentes des clés de production
4. Les identifiants OAuth de test sont des valeurs factices

## Couverture des Tests

Les tests couvrent :
- Authentification (JWT et Google OAuth)
- Gestion des utilisateurs
- Transactions
- Sécurité et autorisations
- Événements et subscribers

## Dépannage

1. Si les tests échouent avec des erreurs de base de données :
```bash
php bin/console doctrine:database:drop --force --env=test
php bin/console doctrine:database:create --env=test
php bin/console doctrine:schema:create --env=test
```

2. Si les tests JWT échouent :
```bash
# Régénérer les clés JWT de test
rm config/jwt/*-test.pem
# Puis suivre les étapes de génération des clés ci-dessus
```

3. Pour les problèmes de permissions :
```bash
chmod -R 777 var/cache/test
chmod -R 777 var/log
