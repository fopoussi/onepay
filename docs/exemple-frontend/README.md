# Exemple d'Application Frontend OnePay

Cette application de démonstration montre comment intégrer l'API OnePay dans une application web frontend.

## Fonctionnalités

- Authentification (Email/Mot de passe et Google)
- Affichage du solde utilisateur
- Transfert d'argent
- Liste des transactions
- Déconnexion

## Installation

1. Copiez les fichiers dans votre serveur web
2. Modifiez la constante `API_URL` dans `app.js` pour pointer vers votre instance de l'API
3. Ouvrez `index.html` dans un navigateur

## Structure

- `index.html` : Structure de l'interface utilisateur
- `styles.css` : Styles CSS personnalisés
- `app.js` : Logique JavaScript et intégration API

## Utilisation de l'API

### Endpoints Utilisés

1. Authentication
   - POST `/api/login_check` : Connexion avec email/mot de passe
   - GET `/connect/google` : Connexion avec Google

2. Utilisateur
   - GET `/api/users/me` : Récupération des informations de l'utilisateur connecté

3. Transactions
   - GET `/api/transactions` : Liste des transactions
   - POST `/api/transactions` : Création d'une nouvelle transaction

### Gestion des Tokens

- Le token JWT est stocké dans le localStorage
- Il est automatiquement inclus dans les en-têtes des requêtes API
- La déconnexion supprime le token du localStorage

## Sécurité

Cette démo implémente :
- Stockage sécurisé du token JWT
- Gestion des erreurs
- Validation des formulaires
- Protection CSRF (à implémenter selon vos besoins)

## Personnalisation

### Styles
Vous pouvez personnaliser l'apparence en modifiant `styles.css`. Les principales classes sont :
- `.section` : Conteneurs principaux
- `.transaction-item` : Éléments de la liste des transactions
- `.transaction-amount` : Montants des transactions

### Configuration API
Dans `app.js`, modifiez :
```javascript
const API_URL = 'http://localhost:8000/api';
```

### Interface
L'interface utilise Bootstrap 5. Vous pouvez modifier `index.html` pour :
- Ajouter de nouvelles fonctionnalités
- Modifier la mise en page
- Personnaliser les formulaires

## Bonnes Pratiques

1. Sécurité
   - Utilisez HTTPS en production
   - Implémentez une gestion des tokens expirés
   - Ajoutez une validation côté client

2. Performance
   - Mettez en cache les données appropriées
   - Utilisez la pagination pour les transactions
   - Optimisez les requêtes API

3. Expérience Utilisateur
   - Ajoutez des indicateurs de chargement
   - Améliorez la gestion des erreurs
   - Implémentez des confirmations pour les actions importantes

## Support

Pour toute question :
1. Consultez la documentation API complète
2. Vérifiez les exemples de code
3. Contactez le support technique

## Notes de Développement

- Cette démo utilise les Fetch API natives
- Compatible avec les navigateurs modernes
- Nécessite une configuration CORS appropriée côté API
