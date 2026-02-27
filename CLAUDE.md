# CLAUDE.md — StaffPilot

## Projet

StaffPilot est un SaaS de gestion RH pour TPE/PME françaises. PWA mobile-first, hébergé sur o2switch (mutualisé PHP).

## Stack technique

- **Backend** : Laravel 11 (PHP 8.2+)
- **Frontend** : Vue.js 3 + Inertia.js + Tailwind CSS 3
- **Base de données** : MySQL 8.0
- **Auth** : Laravel Sanctum + Fortify (sessions, 2FA optionnel)
- **Queue** : Database driver (pas de Redis sur o2switch)
- **Cache** : File driver
- **Email** : SMTP (o2switch ou Brevo)
- **PDF** : DomPDF
- **Paiement** : Laravel Cashier + Stripe
- **PWA** : Manifest + Service Worker + bandeau d'installation custom
- **Tests** : Pest PHP
- **CI/CD** : GitHub Actions → SSH deploy o2switch

## Commandes de référence

```bash
# Installation
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# Développement
php artisan serve
npm run dev

# Tests
php artisan test
npm run lint

# Build production
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Déploiement
php artisan migrate --force
php artisan queue:restart
```

## Architecture

Architecture monolithique modulaire avec Inertia.js (pas d'API REST séparée).

### Multi-tenancy

Single-database avec isolation par `company_id`. Chaque modèle Eloquent utilise le trait `BelongsToCompany` qui applique un Global Scope automatique.

### Rôles (RBAC)

3 rôles : `admin` (Admin RH), `manager`, `employee`. Permissions gérées via middleware + policies Laravel.

### Structure des dossiers

```
staffpilot/
├── app/
│   ├── Actions/              # Classes d'action single-purpose
│   ├── Enums/                # PHP Enums (rôles, statuts, types)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── Admin/        # Controllers admin RH
│   │   │   ├── Manager/      # Controllers manager
│   │   │   └── Employee/     # Controllers employé
│   │   ├── Middleware/
│   │   │   ├── EnsureCompanyScope.php
│   │   │   └── CheckRole.php
│   │   └── Requests/         # Form Requests validation
│   ├── Models/
│   │   ├── Traits/
│   │   │   └── BelongsToCompany.php
│   │   ├── Company.php
│   │   ├── User.php
│   │   ├── Department.php
│   │   ├── LeaveType.php
│   │   ├── LeaveRequest.php
│   │   ├── LeaveBalance.php
│   │   ├── Schedule.php
│   │   ├── TimeEntry.php
│   │   ├── OvertimeEntry.php
│   │   ├── Document.php
│   │   ├── Signature.php
│   │   ├── Payslip.php
│   │   ├── PayrollExport.php
│   │   ├── PayrollExportLine.php
│   │   └── Notification.php
│   ├── Notifications/
│   ├── Observers/
│   ├── Policies/
│   └── Services/
│       ├── LeaveService.php
│       ├── OvertimeService.php
│       ├── ScheduleService.php
│       ├── PayrollExportService.php
│       ├── DocumentService.php
│       └── PayslipService.php
├── database/
│   ├── migrations/
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── DemoCompanySeeder.php
│   │   └── FrenchHolidaySeeder.php
│   └── factories/
├── resources/
│   ├── js/
│   │   ├── app.js
│   │   ├── Components/
│   │   │   ├── Layout/
│   │   │   │   ├── AppLayout.vue
│   │   │   │   ├── Sidebar.vue
│   │   │   │   ├── TopBar.vue
│   │   │   │   └── MobileNav.vue
│   │   │   ├── UI/
│   │   │   │   ├── Button.vue
│   │   │   │   ├── Modal.vue
│   │   │   │   ├── DataTable.vue
│   │   │   │   ├── Calendar.vue
│   │   │   │   ├── StatusBadge.vue
│   │   │   │   ├── FileUpload.vue
│   │   │   │   └── Toast.vue
│   │   │   ├── PWA/
│   │   │   │   └── InstallBanner.vue
│   │   │   └── Charts/
│   │   │       ├── StatsCard.vue
│   │   │       └── BarChart.vue
│   │   ├── Pages/
│   │   │   ├── Auth/
│   │   │   │   ├── Login.vue
│   │   │   │   ├── Register.vue
│   │   │   │   ├── ForgotPassword.vue
│   │   │   │   └── ResetPassword.vue
│   │   │   ├── Dashboard/
│   │   │   │   ├── AdminDashboard.vue
│   │   │   │   ├── ManagerDashboard.vue
│   │   │   │   └── EmployeeDashboard.vue
│   │   │   ├── Planning/
│   │   │   │   ├── Index.vue
│   │   │   │   ├── WeekView.vue
│   │   │   │   └── MonthView.vue
│   │   │   ├── Leaves/
│   │   │   │   ├── Index.vue
│   │   │   │   ├── Request.vue
│   │   │   │   ├── Approve.vue
│   │   │   │   └── Balances.vue
│   │   │   ├── Overtime/
│   │   │   │   ├── Index.vue
│   │   │   │   ├── Declare.vue
│   │   │   │   └── Approve.vue
│   │   │   ├── TimeTracking/
│   │   │   │   ├── Clock.vue
│   │   │   │   └── History.vue
│   │   │   ├── Documents/
│   │   │   │   ├── Index.vue
│   │   │   │   ├── Upload.vue
│   │   │   │   └── Sign.vue
│   │   │   ├── Payslips/
│   │   │   │   ├── Index.vue
│   │   │   │   └── Upload.vue
│   │   │   ├── PayrollExport/
│   │   │   │   ├── Index.vue
│   │   │   │   ├── Review.vue
│   │   │   │   └── History.vue
│   │   │   ├── Recruitment/
│   │   │   │   ├── Index.vue
│   │   │   │   └── Pipeline.vue
│   │   │   ├── Employees/
│   │   │   │   ├── Index.vue
│   │   │   │   ├── Show.vue
│   │   │   │   └── Create.vue
│   │   │   └── Settings/
│   │   │       ├── Company.vue
│   │   │       ├── LeaveTypes.vue
│   │   │       ├── Departments.vue
│   │   │       ├── Holidays.vue
│   │   │       ├── OvertimeRules.vue
│   │   │       ├── PayrollConfig.vue
│   │   │       └── Billing.vue
│   │   ├── Composables/
│   │   │   ├── usePermissions.js
│   │   │   ├── useToast.js
│   │   │   └── usePWA.js
│   │   └── Utils/
│   │       ├── dates.js
│   │       └── formatters.js
│   ├── css/
│   │   └── app.css
│   └── views/
│       └── app.blade.php
├── public/
│   ├── manifest.json
│   ├── sw.js
│   └── icons/
├── routes/
│   ├── web.php
│   ├── auth.php
│   └── api.php               # Uniquement pour webhooks Stripe
├── config/
├── tests/
│   ├── Feature/
│   └── Unit/
├── CLAUDE.md                  # Ce fichier
├── SPECS.md                   # Spécifications détaillées
└── README.md
```

## Conventions de code

### PHP / Laravel

- Utiliser les **Actions** pour toute logique métier (pas de logique dans les controllers)
- **Form Requests** pour toute validation
- **Policies** pour toute autorisation
- **Enums** PHP 8.1 pour les constantes (rôles, statuts)
- Nommage : `PascalCase` pour classes, `camelCase` pour méthodes, `snake_case` pour colonnes DB
- Typage strict : `declare(strict_types=1)` dans tous les fichiers PHP
- Docblocks pour les méthodes publiques des Services et Actions
- Retourner des `Inertia::render()` dans les controllers, jamais de JSON sauf webhooks

### Vue.js / Frontend

- Composition API (`<script setup>`) uniquement
- Nommage : `PascalCase` pour composants, `camelCase` pour composables
- Tailwind CSS uniquement (jamais de CSS custom sauf `app.css` pour les variables)
- Responsive mobile-first : commencer par mobile, puis `sm:`, `md:`, `lg:`
- Composants UI réutilisables dans `Components/UI/`
- Toujours utiliser `<Link>` d'Inertia (pas `<a>` ni `<router-link>`)

### Design system

- **Couleurs primaires** : Bleu marine `#1B4F72` + Bleu vif `#2E86C1`
- **Accent** : Vert `#27AE60` (succès, validations)
- **Danger** : Rouge `#E74C3C`
- **Warning** : Orange `#F39C12`
- **Fond** : `#F8FAFC` (gris très clair)
- **Texte** : `#1E293B` (quasi-noir)
- **Coins arrondis** : `rounded-lg` par défaut
- **Ombres** : `shadow-sm` pour cards, `shadow-lg` pour modals
- **Police** : `Inter` pour le body, `Plus Jakarta Sans` pour les titres
- **Spacing** : multiples de 4 (p-4, p-6, p-8, gap-4, gap-6)

### Base de données

- Toutes les tables ont : `id`, `created_at`, `updated_at`
- Tables multi-tenant : toujours `company_id` avec foreign key + index
- Soft deletes sur : `users`, `companies`, `documents`
- JSON columns pour les données flexibles : `settings`, `data`, `metadata`
- Migrations nommées : `create_xxx_table`, `add_xxx_to_yyy_table`

## Ordre d'implémentation

Suivre STRICTEMENT cet ordre. Ne pas sauter d'étapes.

### Phase 1 : Fondations (d'abord)
1. Setup Laravel 11 + Vite + Vue 3 + Inertia + Tailwind
2. PWA : manifest.json, service worker, composant InstallBanner.vue
3. Modèle Company + migration
4. Modèle User étendu (rôles, company_id, département)
5. Trait BelongsToCompany + middleware EnsureCompanyScope
6. Auth : inscription entreprise, login, logout, forgot password
7. Layout principal : AppLayout, Sidebar, TopBar, MobileNav
8. Dashboard : 3 vues selon le rôle (admin, manager, employé)

### Phase 2 : Planning & Congés
9. Modèles Department, LeaveType, LeaveBalance + migrations + seeders
10. CRUD Départements (admin)
11. CRUD Types de congés (admin) avec paramétrage
12. Modèle Schedule + planning calendrier (semaine/mois)
13. Modèle LeaveRequest + workflow demande/validation
14. Compteurs de congés + acquisition mensuelle automatique
15. Notifications (email + in-app) pour les congés

### Phase 3 : Pointeuse & Heures sup
16. Modèle TimeEntry + pointage (clock in/out/pause)
17. Historique pointage + rapports mensuels
18. Modèle OvertimeEntry + déclaration manuelle
19. Détection automatique heures sup via pointeuse
20. Workflow validation manager des heures sup
21. Compteurs et règles légales (25%/50%, contingent 220h)

### Phase 4 : Documents & Signatures
22. Modèle Document + upload chiffré (AES-256)
23. Gestion documentaire (catégories, filtres, recherche)
24. Modèle Signature + signature électronique simple
25. Workflow signature (envoi lien → signature → scellé)
26. Génération certificat de signature PDF

### Phase 5 : Bulletins & Export paie
27. Modèle Payslip + upload en lot avec reconnaissance nom fichier
28. Distribution + notifications + historique employé
29. Modèle PayrollExport + PayrollExportLine
30. Compilation automatique des variables mensuelles
31. Interface de contrôle RH (modification/ajout variables)
32. Export PDF/Excel/CSV + envoi email comptable

### Phase 6 : Recrutement (léger)
33. Modèle JobPosting + Candidate
34. Pipeline visuel (kanban)
35. Upload CV + notes

### Phase 7 : Finitions
36. Gestion jours fériés français (seeder + CRUD)
37. Import CSV des employés
38. Personnalisation entreprise (logo, couleurs)
39. Intégration Stripe Cashier (plans, facturation)
40. Landing page publique
41. Tests Pest (au minimum : auth, congés, heures sup, export paie)

## Règles strictes

- **JAMAIS** de logique métier dans les controllers. Utiliser Actions/ ou Services/.
- **JAMAIS** de requêtes Eloquent dans les vues Vue. Tout passe par les controllers Inertia.
- **TOUJOURS** vérifier company_id. Le trait BelongsToCompany doit être sur TOUS les modèles multi-tenant.
- **TOUJOURS** vérifier les permissions via Policies avant toute action.
- **TOUJOURS** valider via Form Requests, jamais $request->validate() inline.
- **TOUJOURS** utiliser des transactions DB pour les opérations multi-modèles.
- Les fichiers sensibles (bulletins, contrats) sont stockés HORS de `public/` et servis via un controller avec vérification d'accès.
- Les emails envoyés au comptable doivent utiliser une queue (job asynchrone).
- Tout texte visible par l'utilisateur est en français.

## Variables d'environnement requises

```env
APP_NAME=StaffPilot
APP_URL=https://staffpilot.fr
DB_CONNECTION=mysql
DB_DATABASE=staffpilot
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@staffpilot.fr
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```
