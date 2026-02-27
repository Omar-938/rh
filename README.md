# SimpliRH 🚀

**SaaS de gestion RH pour les TPE/PME françaises**

SimpliRH simplifie la gestion quotidienne des salariés : planning, congés, heures supplémentaires, documents, bulletins de paie et export comptable. Pas de fiche de paie, pas de complexité inutile — juste ce dont vous avez besoin.

## Fonctionnalités

- 📅 **Planning du personnel** — Vue calendrier semaine/mois, drag-and-drop
- 🏖️ **Gestion des congés** — Demande, validation, compteurs automatiques
- ⏱️ **Pointeuse digitale** — Clock in/out en un clic
- ⏰ **Heures supplémentaires** — Déclaration, validation manager, règles légales françaises
- 📄 **Documents & Signatures** — Coffre-fort chiffré, signature électronique
- 💰 **Bulletins de paie** — Upload en lot, distribution sécurisée
- 📊 **Export variables de paie** — Compilation, contrôle RH, envoi au comptable
- 👥 **Recrutement** — Pipeline candidats, suivi des embauches
- 📱 **PWA** — Installable sur mobile, notifications push

## Stack technique

| Couche | Technologie |
|--------|------------|
| Backend | Laravel 11 (PHP 8.2+) |
| Frontend | Vue.js 3 + Inertia.js |
| CSS | Tailwind CSS 3 |
| Base de données | MySQL 8.0 |
| Hébergement | o2switch (mutualisé, France) |

## Installation

```bash
# Cloner
git clone https://github.com/votre-compte/simplirh.git
cd simplirh

# Backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Frontend
npm install
npm run dev

# Lancer
php artisan serve
```

## Documentation

- **[CLAUDE.md](CLAUDE.md)** — Instructions pour Claude Code (conventions, architecture, ordre d'implémentation)
- **[SPECS.md](SPECS.md)** — Spécifications complètes (schéma DB, routes, logique métier, tests)

## Compte démo

Après le seeding :
- **Admin** : admin@demo.simplirh.fr / password
- **Manager** : manager1@demo.simplirh.fr / password
- **Employé** : employe1@demo.simplirh.fr / password

## Licence

Propriétaire — Tous droits réservés.
