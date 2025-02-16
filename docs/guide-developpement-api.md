# Guide de Développement - API OnePay

## Introduction
Ce document explique comment développer une application qui utilise l'API OnePay. L'API permet de gérer les transferts d'argent et les recharges téléphoniques.

## Configuration de Base

### Points d'Entrée de l'API
- Base URL: `/api`
- Documentation Swagger: `/api/doc.json`
- Documentation Interface: `/api/doc`

### Authentification
L'API utilise deux méthodes d'authentification :
1. JWT (JSON Web Token)
2. OAuth Google

#### Obtenir un Token JWT
```http
POST /api/login_check
Content-Type: application/json

{
    "username": "email@example.com",
    "password": "votre_mot_de_passe"
}
```

#### Authentification avec Google
```http
GET /connect/google
```

### Utilisation du Token
Pour toutes les requêtes API protégées, incluez le token dans le header :
```http
Authorization: Bearer <votre_token>
```

## Endpoints Principaux

### 1. Gestion des Utilisateurs
```http
# Créer un compte
POST /api/users
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password",
    "firstName": "John",
    "lastName": "Doe",
    "phoneNumber": "+33612345678"
}

# Obtenir le profil utilisateur
GET /api/users/{id}
```

### 2. Transactions
```http
# Créer une transaction
POST /api/transactions
Content-Type: application/json

{
    "amount": 100.00,
    "type": "TRANSFER",
    "recipientPhone": "+33612345678"
}

# Lister les transactions
GET /api/transactions

# Obtenir une transaction spécifique
GET /api/transactions/{id}
```

### 3. Notifications
```http
# Lister les notifications
GET /api/notifications

# Marquer comme lue
PATCH /api/notifications/{id}
```

## Modèles de Données

### Utilisateur (OnepayUser)
```json
{
    "id": "uuid",
    "email": "string",
    "firstName": "string",
    "lastName": "string",
    "phoneNumber": "string",
    "balance": "float",
    "status": "string"
}
```

### Transaction (OnepayTransaction)
```json
{
    "id": "uuid",
    "amount": "float",
    "type": "string",
    "status": "string",
    "createdAt": "datetime",
    "sender": "OnepayUser",
    "recipient": "OnepayUser"
}
```

## Gestion des Erreurs
L'API retourne des codes HTTP standards :
- 200: Succès
- 201: Création réussie
- 400: Requête invalide
- 401: Non authentifié
- 403: Non autorisé
- 404: Ressource non trouvée
- 500: Erreur serveur

Format des erreurs :
```json
{
    "code": "ERROR_CODE",
    "message": "Description de l'erreur"
}
```

## Bonnes Pratiques
1. Toujours vérifier le statut des transactions
2. Implémenter une gestion des erreurs robuste
3. Stocker le token JWT de manière sécurisée
4. Rafraîchir le token avant expiration
5. Utiliser HTTPS en production

## Exemples d'Intégration

### JavaScript (Fetch API)
```javascript
// Exemple d'authentification
async function login(email, password) {
    const response = await fetch('/api/login_check', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            username: email,
            password: password
        })
    });
    
    const data = await response.json();
    return data.token;
}

// Exemple de création de transaction
async function createTransaction(token, amount, recipientPhone) {
    const response = await fetch('/api/transactions', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            amount: amount,
            type: 'TRANSFER',
            recipientPhone: recipientPhone
        })
    });
    
    return await response.json();
}
```

## Support et Ressources
- Documentation API complète: `/api/doc`
- Environnement de test: Disponible sur demande
- Contact support: support@onepay.com
