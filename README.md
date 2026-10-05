# EcoGarden API

API REST pour EcoGarden & co : conseils de jardinage par mois et météo par ville.

Symfony 8, Doctrine (SQLite), authentification JWT, météo via [open-meteo](https://open-meteo.com/) (gratuit, sans clé).

## Installation

Prérequis : PHP 8.4+, Composer.

```bash
git clone <url-du-depot>
cd <dossier>
composer install
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
php -S 127.0.0.1:8000 -t public
```

(ou `symfony serve` si la CLI Symfony est installée)

La clé de signature des tokens est dans `JWT_SECRET` (fichier `.env`). En production, la redéfinir dans `.env.local`.

## Comptes de test (fixtures)

| Login | Mot de passe | Rôle | Ville |
|-------|--------------|------|-------|
| admin | admin1234 | ROLE_ADMIN | Paris |
| enzo | enzo1234 | ROLE_USER | Arras |
| bob | bob1234 | ROLE_USER | Lyon |

## Authentification

```http
POST /auth
Content-Type: application/json

{"login": "enzo", "password": "enzo1234"}
```

Renvoie un token valable 24 h, à envoyer ensuite dans le header :

```
Authorization: Bearer <token>
```

## Routes

| Méthode | Route | Accès | Description |
|---------|-------|-------|-------------|
| POST | `/user` | Public | Créer un compte (`login`, `password`, `ville`) |
| POST | `/auth` | Public | Obtenir un token JWT |
| GET | `/conseil/` | Connecté | Conseils du mois en cours |
| GET | `/conseil/{mois}` | Connecté | Conseils d'un mois (1 à 12) |
| GET | `/meteo` | Connecté | Météo de la ville de l'utilisateur |
| GET | `/meteo/{ville}` | Connecté | Météo d'une ville |
| POST | `/conseil` | Admin | Ajouter un conseil (`contenu`, `mois`) |
| PUT | `/conseil/{id}` | Admin | Modifier un conseil (seulement les champs envoyés) |
| DELETE | `/conseil/{id}` | Admin | Supprimer un conseil |
| PUT | `/user/{id}` | Admin | Modifier un compte |
| DELETE | `/user/{id}` | Admin | Supprimer un compte |

Exemple de création de conseil :

```json
{"contenu": "Paillez les massifs pour garder l'humidité.", "mois": [5, 6, 7]}
```

## Erreurs

Toutes les erreurs sont renvoyées en JSON :

```json
{"error": "Conseil introuvable.", "status": 404}
```

| Code | Cas |
|------|-----|
| 400 | JSON invalide, champ manquant, mois invalide, login déjà utilisé |
| 401 | Token absent, invalide ou expiré, identifiants incorrects |
| 403 | Utilisateur connecté sans le rôle admin |
| 404 | Conseil, utilisateur ou ville introuvable |
| 503 | API météo indisponible |

## Météo et cache

Les réponses météo sont mises en cache 30 minutes par ville. Pour vider le cache :

```bash
php bin/console cache:pool:clear cache.app
```
