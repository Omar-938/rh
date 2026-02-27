# SPECS.md — Spécifications détaillées StaffPilot

---

## 1. Base de données — Schéma complet

### 1.1 Table `companies`

```sql
CREATE TABLE companies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    siret VARCHAR(14) NULL,
    address TEXT NULL,
    city VARCHAR(255) NULL,
    postal_code VARCHAR(10) NULL,
    phone VARCHAR(20) NULL,
    logo_path VARCHAR(255) NULL,
    primary_color VARCHAR(7) DEFAULT '#1B4F72',
    plan ENUM('trial', 'starter', 'business', 'enterprise') DEFAULT 'trial',
    trial_ends_at TIMESTAMP NULL,
    settings JSON NULL,
    -- settings JSON structure:
    -- {
    --   "timezone": "Europe/Paris",
    --   "work_hours_per_day": 7,
    --   "work_days_per_week": 5,
    --   "overtime_auto_detect": true,
    --   "overtime_threshold_alert": 10,
    --   "overtime_rate_25": 25,
    --   "overtime_rate_50": 50,
    --   "overtime_annual_quota": 220,
    --   "leave_carryover": false,
    --   "leave_carryover_max_days": 0,
    --   "payroll_reminder_day": 3,
    --   "accountant_emails": ["comptable@cabinet.fr"],
    --   "payroll_export_format": "pdf"
    -- }
    stripe_id VARCHAR(255) NULL,
    pm_type VARCHAR(255) NULL,
    pm_last_four VARCHAR(4) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
);
```

### 1.2 Table `users`

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    manager_id BIGINT UNSIGNED NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'employee') DEFAULT 'employee',
    phone VARCHAR(20) NULL,
    avatar_path VARCHAR(255) NULL,
    hire_date DATE NULL,
    contract_type ENUM('cdi', 'cdd', 'interim', 'stage', 'alternance') DEFAULT 'cdi',
    contract_end_date DATE NULL,
    trial_end_date DATE NULL,
    weekly_hours DECIMAL(4,1) DEFAULT 35.0,
    employee_id VARCHAR(50) NULL,              -- Matricule interne
    birth_date DATE NULL,
    address TEXT NULL,
    city VARCHAR(255) NULL,
    postal_code VARCHAR(10) NULL,
    social_security_number VARCHAR(15) NULL,   -- Chiffré en DB
    iban VARCHAR(34) NULL,                      -- Chiffré en DB
    emergency_contact_name VARCHAR(255) NULL,
    emergency_contact_phone VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    two_factor_secret TEXT NULL,
    two_factor_recovery_codes TEXT NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token VARCHAR(100) NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    UNIQUE KEY unique_email_company (email, company_id),
    INDEX idx_company (company_id),
    INDEX idx_department (department_id),
    INDEX idx_manager (manager_id),
    INDEX idx_role (company_id, role),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### 1.3 Table `departments`

```sql
CREATE TABLE departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    manager_id BIGINT UNSIGNED NULL,
    color VARCHAR(7) DEFAULT '#2E86C1',
    description TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);
```

### 1.4 Table `leave_types`

```sql
CREATE TABLE leave_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,                -- "Congés payés", "RTT", "Sans solde", etc.
    slug VARCHAR(255) NOT NULL,
    color VARCHAR(7) DEFAULT '#3498DB',
    days_per_year DECIMAL(5,2) DEFAULT 25.00,  -- Allocation annuelle
    requires_approval BOOLEAN DEFAULT TRUE,
    is_paid BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    acquisition_type ENUM('annual', 'monthly', 'none') DEFAULT 'monthly',
    -- monthly = 2.08j/mois pour 25j/an
    -- annual = tout alloué au 1er janvier
    -- none = pas d'acquisition automatique (ex: sans solde)
    max_consecutive_days INT NULL,              -- Limite jours consécutifs
    notice_days INT DEFAULT 0,                  -- Délai de prévenance minimum
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_slug_company (slug, company_id),
    INDEX idx_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

### 1.5 Table `leave_balances`

```sql
CREATE TABLE leave_balances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    leave_type_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    year INT NOT NULL,
    allocated DECIMAL(5,2) DEFAULT 0,          -- Jours alloués
    used DECIMAL(5,2) DEFAULT 0,               -- Jours utilisés (validés)
    pending DECIMAL(5,2) DEFAULT 0,            -- Jours en attente de validation
    carried_over DECIMAL(5,2) DEFAULT 0,       -- Report année précédente
    adjustment DECIMAL(5,2) DEFAULT 0,         -- Ajustement manuel admin
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_type_year (user_id, leave_type_id, year),
    INDEX idx_company_year (company_id, year),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

### 1.6 Table `leave_requests`

```sql
CREATE TABLE leave_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    leave_type_id BIGINT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    start_half ENUM('morning', 'afternoon') NULL,  -- Demi-journée début
    end_half ENUM('morning', 'afternoon') NULL,    -- Demi-journée fin
    days_count DECIMAL(4,1) NOT NULL,              -- Nombre de jours ouvrés calculé
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    employee_comment TEXT NULL,
    reviewer_comment TEXT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_company_status (company_id, status),
    INDEX idx_dates (company_id, start_date, end_date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### 1.7 Table `schedules`

```sql
CREATE TABLE schedules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    type ENUM('work', 'remote', 'off', 'leave', 'sick', 'training') DEFAULT 'work',
    start_time TIME NULL,
    end_time TIME NULL,
    break_minutes INT DEFAULT 60,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_company_date (company_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### 1.8 Table `time_entries`

```sql
CREATE TABLE time_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    clock_in TIMESTAMP NULL,
    break_start TIMESTAMP NULL,
    break_end TIMESTAMP NULL,
    clock_out TIMESTAMP NULL,
    total_hours DECIMAL(4,2) NULL,              -- Calculé automatiquement
    total_break_minutes INT DEFAULT 0,
    overtime_minutes INT DEFAULT 0,             -- Minutes sup détectées
    source ENUM('manual', 'clock', 'import') DEFAULT 'clock',
    ip_address VARCHAR(45) NULL,
    location_lat DECIMAL(10,8) NULL,
    location_lng DECIMAL(11,8) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_company_date (company_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

### 1.9 Table `overtime_entries`

```sql
CREATE TABLE overtime_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    date DATE NOT NULL,
    hours DECIMAL(4,2) NOT NULL,                -- Ex: 2.50 = 2h30
    rate ENUM('25', '50') DEFAULT '25',         -- Taux de majoration
    source ENUM('manual', 'auto') DEFAULT 'manual',
    -- manual = déclaré par l'employé
    -- auto = détecté par la pointeuse
    time_entry_id BIGINT UNSIGNED NULL,         -- Lien vers le pointage source
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reason TEXT NULL,                           -- Motif des heures sup
    reviewer_comment TEXT NULL,
    reviewed_by BIGINT UNSIGNED NULL,
    reviewed_at TIMESTAMP NULL,
    compensation ENUM('payment', 'rest') DEFAULT 'payment',
    -- payment = paiement majoré
    -- rest = repos compensateur
    included_in_export_id BIGINT UNSIGNED NULL, -- Lien vers l'export de paie
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_user_status (user_id, status),
    INDEX idx_user_date (user_id, date),
    INDEX idx_company_status (company_id, status),
    INDEX idx_company_date (company_id, date),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (time_entry_id) REFERENCES time_entries(id) ON DELETE SET NULL,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### 1.10 Table `documents`

```sql
CREATE TABLE documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,               -- NULL = document entreprise (règlement, charte)
    uploaded_by BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    category ENUM('contract', 'amendment', 'certificate', 'rules', 'medical', 'identity', 'rib', 'review', 'other') DEFAULT 'other',
    mime_type VARCHAR(100) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,            -- Hors public/
    is_encrypted BOOLEAN DEFAULT TRUE,
    requires_signature BOOLEAN DEFAULT FALSE,
    signature_status ENUM('none', 'pending', 'partial', 'completed') DEFAULT 'none',
    expires_at DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_company (company_id),
    INDEX idx_user (user_id),
    INDEX idx_category (company_id, category),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);
```

### 1.11 Table `signatures`

```sql
CREATE TABLE signatures (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    document_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,          -- Token unique pour le lien de signature
    status ENUM('pending', 'signed', 'declined') DEFAULT 'pending',
    signed_at TIMESTAMP NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    signature_data TEXT NULL,                    -- Base64 de la signature dessinée ou texte
    signature_type ENUM('draw', 'type') NULL,
    hash VARCHAR(64) NULL,                      -- SHA-256 du document au moment de la signature
    declined_reason TEXT NULL,
    expires_at TIMESTAMP NULL,
    reminder_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_document (document_id),
    INDEX idx_user (user_id),
    INDEX idx_token (token),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

### 1.12 Table `payslips`

```sql
CREATE TABLE payslips (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    company_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    period VARCHAR(7) NOT NULL,                 -- Format: "2026-01"
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    is_encrypted BOOLEAN DEFAULT TRUE,
    notified_at TIMESTAMP NULL,
    first_downloaded_at TIMESTAMP NULL,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_user_period (user_id, period),
    INDEX idx_company_period (company_id, period),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);
```

### 1.13 Table `payroll_exports`

```sql
CREATE TABLE payroll_exports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    period VARCHAR(7) NOT NULL,                 -- Format: "2026-01"
    status ENUM('draft', 'validated', 'sent', 'corrected') DEFAULT 'draft',
    validated_by BIGINT UNSIGNED NULL,
    validated_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    sent_to JSON NULL,                          -- ["comptable@cabinet.fr"]
    format ENUM('pdf', 'xlsx', 'csv') DEFAULT 'pdf',
    file_path VARCHAR(500) NULL,
    is_correction BOOLEAN DEFAULT FALSE,
    correction_of_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_company_period (company_id, period),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (correction_of_id) REFERENCES payroll_exports(id) ON DELETE SET NULL
);
```

### 1.14 Table `payroll_export_lines`

```sql
CREATE TABLE payroll_export_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payroll_export_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    data JSON NOT NULL,
    -- Structure JSON :
    -- {
    --   "employee_id": "MAT001",
    --   "full_name": "Martin Dupont",
    --   "department": "Commercial",
    --   "contract_type": "cdi",
    --   "days_worked": 22,
    --   "days_absent": 0,
    --   "absences": [
    --     {"type": "CP", "days": 0},
    --     {"type": "RTT", "days": 0},
    --     {"type": "Maladie", "days": 0},
    --     {"type": "Sans solde", "days": 0}
    --   ],
    --   "overtime": {
    --     "hours_25": 4.5,
    --     "hours_50": 0,
    --     "total_hours": 4.5,
    --     "compensation": "payment"
    --   },
    --   "variables": [
    --     {"label": "Prime exceptionnelle", "amount": 200},
    --     {"label": "Acompte", "amount": -500},
    --     {"label": "Tickets restaurant", "quantity": 18}
    --   ],
    --   "notes": "Changement d'adresse ce mois"
    -- }
    is_modified BOOLEAN DEFAULT FALSE,          -- TRUE si le RH a modifié manuellement
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_export (payroll_export_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (payroll_export_id) REFERENCES payroll_exports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 1.15 Table `holidays`

```sql
CREATE TABLE holidays (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    date DATE NOT NULL,
    is_recurring BOOLEAN DEFAULT TRUE,          -- Revient chaque année
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY unique_company_date (company_id, date),
    INDEX idx_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

### 1.16 Table `notifications`

```sql
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,                    -- UUID Laravel
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data JSON NOT NULL,
    -- {
    --   "title": "Nouvelle demande de congé",
    --   "message": "Martin Dupont demande 3 jours de CP du 15 au 17 mars",
    --   "action_url": "/leaves/requests/42",
    --   "icon": "calendar",
    --   "type": "leave_request"    // leave_request, leave_approved, leave_rejected,
    --                               // overtime_pending, overtime_approved, document_sign,
    --                               // payslip_available, payroll_reminder, schedule_changed
    -- }
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_notifiable (notifiable_type, notifiable_id, read_at)
);
```

### 1.17 Table `job_postings`

```sql
CREATE TABLE job_postings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    department_id BIGINT UNSIGNED NULL,
    description TEXT NULL,
    requirements TEXT NULL,
    contract_type ENUM('cdi', 'cdd', 'interim', 'stage', 'alternance') DEFAULT 'cdi',
    location VARCHAR(255) NULL,
    salary_range VARCHAR(100) NULL,
    status ENUM('draft', 'open', 'closed') DEFAULT 'draft',
    created_by BIGINT UNSIGNED NOT NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_company (company_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);
```

### 1.18 Table `candidates`

```sql
CREATE TABLE candidates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    job_posting_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    cv_path VARCHAR(500) NULL,
    stage ENUM('received', 'shortlisted', 'interview', 'selected', 'hired', 'rejected') DEFAULT 'received',
    notes TEXT NULL,
    interview_date TIMESTAMP NULL,
    rating INT NULL,                            -- 1-5
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_company (company_id),
    INDEX idx_job (job_posting_id),
    INDEX idx_stage (company_id, stage),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    FOREIGN KEY (job_posting_id) REFERENCES job_postings(id) ON DELETE CASCADE
);
```

### 1.19 Table `audit_logs`

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,               -- 'leave.approved', 'document.signed', etc.
    auditable_type VARCHAR(255) NOT NULL,        -- Modèle concerné
    auditable_id BIGINT UNSIGNED NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NULL,
    INDEX idx_company (company_id),
    INDEX idx_auditable (auditable_type, auditable_id),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);
```

---

## 2. Routes — Listing complet

### 2.1 Routes publiques

```
GET    /                           → LandingController@index          (landing page)
GET    /pricing                    → LandingController@pricing
POST   /register                   → Auth\RegisterController@store    (inscription entreprise + admin)
GET    /login                      → Auth\LoginController@create
POST   /login                      → Auth\LoginController@store
POST   /logout                     → Auth\LoginController@destroy
GET    /forgot-password             → Auth\ForgotPasswordController@create
POST   /forgot-password             → Auth\ForgotPasswordController@store
GET    /reset-password/{token}      → Auth\ResetPasswordController@create
POST   /reset-password              → Auth\ResetPasswordController@store
GET    /documents/sign/{token}      → DocumentSignController@show      (lien signature publique)
POST   /documents/sign/{token}      → DocumentSignController@store
```

### 2.2 Routes authentifiées (middleware: auth, verified, company.scope)

```
# ─── DASHBOARD ───
GET    /dashboard                   → DashboardController@index        (redirige selon rôle)

# ─── PROFIL ───
GET    /profile                     → ProfileController@edit
PUT    /profile                     → ProfileController@update
PUT    /profile/password            → ProfileController@updatePassword
PUT    /profile/avatar              → ProfileController@updateAvatar

# ─── NOTIFICATIONS ───
GET    /notifications               → NotificationController@index
POST   /notifications/{id}/read     → NotificationController@markAsRead
POST   /notifications/read-all      → NotificationController@markAllAsRead

# ─── PLANNING ───
GET    /planning                    → PlanningController@index
GET    /planning/week/{date?}       → PlanningController@week
GET    /planning/month/{date?}      → PlanningController@month
POST   /planning/schedules          → PlanningController@store         (admin/manager)
PUT    /planning/schedules/{id}     → PlanningController@update        (admin/manager)
DELETE /planning/schedules/{id}     → PlanningController@destroy       (admin/manager)
GET    /planning/export/{format}    → PlanningController@export        (pdf)

# ─── CONGÉS ───
GET    /leaves                      → LeaveController@index
GET    /leaves/request              → LeaveController@create
POST   /leaves/request              → LeaveController@store
GET    /leaves/requests/{id}        → LeaveController@show
POST   /leaves/requests/{id}/cancel → LeaveController@cancel
GET    /leaves/balances             → LeaveController@balances
GET    /leaves/calendar             → LeaveController@calendar
# Manager/Admin
GET    /leaves/pending              → LeaveController@pending
POST   /leaves/requests/{id}/approve → LeaveController@approve
POST   /leaves/requests/{id}/reject  → LeaveController@reject

# ─── POINTEUSE ───
GET    /time-tracking               → TimeTrackingController@index
POST   /time-tracking/clock-in      → TimeTrackingController@clockIn
POST   /time-tracking/break-start   → TimeTrackingController@breakStart
POST   /time-tracking/break-end     → TimeTrackingController@breakEnd
POST   /time-tracking/clock-out     → TimeTrackingController@clockOut
GET    /time-tracking/history       → TimeTrackingController@history
GET    /time-tracking/report/{month?} → TimeTrackingController@report   (admin/manager)

# ─── HEURES SUPPLÉMENTAIRES ───
GET    /overtime                    → OvertimeController@index
GET    /overtime/declare            → OvertimeController@create
POST   /overtime/declare            → OvertimeController@store
GET    /overtime/{id}               → OvertimeController@show
# Manager/Admin
GET    /overtime/pending            → OvertimeController@pending
POST   /overtime/{id}/approve       → OvertimeController@approve
POST   /overtime/{id}/reject        → OvertimeController@reject
GET    /overtime/report/{month?}    → OvertimeController@report

# ─── DOCUMENTS ───
GET    /documents                   → DocumentController@index
POST   /documents/upload            → DocumentController@store
GET    /documents/{id}              → DocumentController@show
GET    /documents/{id}/download     → DocumentController@download
DELETE /documents/{id}              → DocumentController@destroy       (admin)
# Signature workflow
POST   /documents/{id}/request-signature → DocumentController@requestSignature (admin)
POST   /documents/{id}/remind       → DocumentController@remindSigners

# ─── BULLETINS DE PAIE ───
GET    /payslips                    → PayslipController@index
GET    /payslips/{id}/download      → PayslipController@download
# Admin
GET    /payslips/upload             → PayslipController@uploadForm
POST   /payslips/upload             → PayslipController@upload
POST   /payslips/distribute         → PayslipController@distribute

# ─── EXPORT VARIABLES DE PAIE ───
GET    /payroll-exports             → PayrollExportController@index
GET    /payroll-exports/current     → PayrollExportController@current  (mois en cours)
POST   /payroll-exports/generate    → PayrollExportController@generate
GET    /payroll-exports/{id}        → PayrollExportController@show
PUT    /payroll-exports/{id}/lines/{lineId} → PayrollExportController@updateLine
POST   /payroll-exports/{id}/validate → PayrollExportController@validate
POST   /payroll-exports/{id}/send   → PayrollExportController@send
GET    /payroll-exports/{id}/download/{format} → PayrollExportController@download
POST   /payroll-exports/{id}/correct → PayrollExportController@correct

# ─── RECRUTEMENT ───
GET    /recruitment                 → RecruitmentController@index      (admin)
GET    /recruitment/postings/create → RecruitmentController@createPosting
POST   /recruitment/postings        → RecruitmentController@storePosting
GET    /recruitment/postings/{id}   → RecruitmentController@showPosting
PUT    /recruitment/postings/{id}   → RecruitmentController@updatePosting
POST   /recruitment/candidates      → RecruitmentController@storeCandidate
PUT    /recruitment/candidates/{id}/stage → RecruitmentController@updateStage
PUT    /recruitment/candidates/{id} → RecruitmentController@updateCandidate
DELETE /recruitment/candidates/{id} → RecruitmentController@destroyCandidate

# ─── GESTION EMPLOYÉS (Admin) ───
GET    /employees                   → EmployeeController@index
GET    /employees/create            → EmployeeController@create
POST   /employees                   → EmployeeController@store
GET    /employees/{id}              → EmployeeController@show
PUT    /employees/{id}              → EmployeeController@update
POST   /employees/{id}/deactivate   → EmployeeController@deactivate
POST   /employees/{id}/reactivate   → EmployeeController@reactivate
POST   /employees/import            → EmployeeController@import        (CSV)

# ─── PARAMÈTRES (Admin) ───
GET    /settings                    → SettingsController@index
PUT    /settings/company            → SettingsController@updateCompany
PUT    /settings/logo               → SettingsController@updateLogo
# Départements
GET    /settings/departments        → DepartmentController@index
POST   /settings/departments        → DepartmentController@store
PUT    /settings/departments/{id}   → DepartmentController@update
DELETE /settings/departments/{id}   → DepartmentController@destroy
# Types de congés
GET    /settings/leave-types        → LeaveTypeController@index
POST   /settings/leave-types        → LeaveTypeController@store
PUT    /settings/leave-types/{id}   → LeaveTypeController@update
DELETE /settings/leave-types/{id}   → LeaveTypeController@destroy
# Jours fériés
GET    /settings/holidays           → HolidayController@index
POST   /settings/holidays           → HolidayController@store
PUT    /settings/holidays/{id}      → HolidayController@update
DELETE /settings/holidays/{id}      → HolidayController@destroy
POST   /settings/holidays/import-french → HolidayController@importFrench
# Règles heures sup
GET    /settings/overtime-rules     → OvertimeRulesController@index
PUT    /settings/overtime-rules     → OvertimeRulesController@update
# Config export paie
GET    /settings/payroll            → PayrollConfigController@index
PUT    /settings/payroll            → PayrollConfigController@update
# Facturation
GET    /settings/billing            → BillingController@index
POST   /settings/billing/subscribe  → BillingController@subscribe
POST   /settings/billing/cancel     → BillingController@cancel
PUT    /settings/billing/payment-method → BillingController@updatePaymentMethod

# ─── WEBHOOK STRIPE (pas de middleware auth) ───
POST   /stripe/webhook              → StripeWebhookController@handle
```

---

## 3. Spécifications fonctionnelles détaillées

### 3.1 PWA — Bandeau d'installation

**Composant** : `Components/PWA/InstallBanner.vue`

**Comportement** :
1. Au chargement, intercepter l'événement `beforeinstallprompt` du navigateur
2. Stocker l'événement dans une ref reactive
3. Afficher un bandeau fixe en bas de l'écran (mobile) ou en haut (desktop)
4. Design : fond bleu `#1B4F72`, texte blanc, icône de l'app, bouton "Installer" vert, bouton "Plus tard" discret
5. Au clic sur "Installer" : appeler `prompt()` sur l'événement stocké
6. Au clic sur "Plus tard" : masquer le bandeau, stocker dans localStorage un timestamp
7. Ré-afficher après 3 jours si pas installé
8. Ne plus afficher si l'app est en mode standalone (installée)
9. Sur iOS (pas de `beforeinstallprompt`) : afficher un bandeau spécial avec instructions "Appuyez sur Partager puis Ajouter à l'écran d'accueil"

**manifest.json** :
```json
{
  "name": "StaffPilot - Gestion RH",
  "short_name": "StaffPilot",
  "description": "Gérez vos RH simplement",
  "start_url": "/dashboard",
  "display": "standalone",
  "background_color": "#F8FAFC",
  "theme_color": "#1B4F72",
  "orientation": "any",
  "icons": [
    { "src": "/icons/icon-72.png", "sizes": "72x72", "type": "image/png" },
    { "src": "/icons/icon-96.png", "sizes": "96x96", "type": "image/png" },
    { "src": "/icons/icon-128.png", "sizes": "128x128", "type": "image/png" },
    { "src": "/icons/icon-144.png", "sizes": "144x144", "type": "image/png" },
    { "src": "/icons/icon-152.png", "sizes": "152x152", "type": "image/png" },
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-384.png", "sizes": "384x384", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

**Service Worker** (`public/sw.js`) :
- Cache-first pour les assets statiques (CSS, JS, images, fonts)
- Network-first pour les requêtes API/pages
- Offline fallback : page simple "Vous êtes hors connexion"
- Pas de cache agressif sur les données dynamiques

### 3.2 Inscription entreprise

**Flow** :
1. Formulaire : nom entreprise, SIRET (optionnel), nom admin, email, mot de passe
2. Créer Company (plan: 'trial', trial_ends_at: +14 jours)
3. Créer User admin (role: 'admin')
4. Seed les types de congés par défaut (CP 25j, RTT 0j, Sans solde 0j, Maladie 0j)
5. Seed les jours fériés français de l'année en cours
6. Rediriger vers /dashboard
7. Envoyer email de bienvenue

### 3.3 Dashboard selon rôle

**Admin** : widgets effectif, demandes en attente (congés + HS), calendrier absences du mois, statut export paie, alertes RH (fins de PE, contrats), activité récente, graphique absentéisme

**Manager** : demandes de son équipe en attente, planning de la semaine de son équipe, compteur HS équipe du mois, absents aujourd'hui dans son département

**Employé** : pointeuse (bouton clock in/out), compteur congés restants, prochains jours de congé, derniers bulletins de paie, documents à signer, notifications récentes

### 3.4 Export variables de paie — Détails

**Génération automatique** :
- Un cron Laravel (1er de chaque mois à 7h) crée automatiquement un `PayrollExport` en statut `draft`
- Pour chaque employé actif, crée une `PayrollExportLine` avec les données compilées :
  - Jours travaillés = jours ouvrés du mois - jours d'absence
  - Absences : agrégées par type depuis `leave_requests` (status=approved)
  - Heures sup : agrégées depuis `overtime_entries` (status=approved)
  - Variables : vide par défaut (le RH les ajoute manuellement)
- Le RH peut aussi déclencher la génération manuellement via le bouton "Générer l'export"

**Interface de contrôle RH** (page `PayrollExport/Review.vue`) :
- Tableau avec une ligne par salarié
- Colonnes : Nom, Département, Jours travaillés, Absences (détail), HS 25%, HS 50%, Variables, Notes
- Chaque cellule est éditable inline (clic pour modifier)
- Bouton "Ajouter une variable" par salarié (prime, acompte, ticket resto, etc.)
- Champ "Notes pour le comptable" par salarié
- En bas : bouton "Valider l'export" (verrouille les données) et "Envoyer au comptable"
- Le RH voit l'email du comptable configuré et peut le modifier avant envoi
- Prévisualisation du PDF/Excel avant envoi

**Email au comptable** :
- Objet : "StaffPilot - Variables de paie [NomEntreprise] - [Période]"
- Corps : message professionnel avec récap (nombre de salariés, période)
- Pièce jointe : le fichier export (PDF, Excel ou CSV selon config)
- Copie au RH qui a envoyé

### 3.5 Heures supplémentaires — Règles

**Détection automatique** (si activée dans settings) :
- Après chaque `clock_out`, comparer `total_hours` avec `weekly_hours / work_days_per_week`
- Si dépassement > 15 minutes : créer automatiquement une `OvertimeEntry` (source: 'auto', status: 'pending')
- Le calcul du taux (25% ou 50%) se fait sur la base hebdomadaire :
  - Heures 36 à 43 : taux 25%
  - Heures 44+ : taux 50%

**Compteur annuel** :
- Accessible dans le profil employé et le dashboard manager
- Alerte quand l'employé approche du contingent (220h par défaut)
- Alerte quand un employé dépasse le seuil mensuel configuré

### 3.6 Signature électronique — Détails

**Création** :
1. Admin upload un PDF
2. Désigne 1 ou plusieurs signataires (employés de l'entreprise)
3. Pour chaque signataire : créer une entrée `signatures` avec un token unique (64 chars, cryptographiquement sûr)

**Processus de signature** :
1. Le signataire reçoit un email avec le lien `/documents/sign/{token}`
2. Cette route est PUBLIQUE (pas besoin d'être connecté, le token suffit)
3. La page affiche le PDF en lecture seule + zone de signature
4. Le signataire peut dessiner sa signature (canvas) ou taper son nom
5. Au clic "Signer" : enregistrer signature_data, IP, user_agent, hash SHA-256 du PDF
6. Mettre à jour le statut du document (partial → completed quand tous ont signé)
7. Générer un certificat de signature en annexe du PDF (page supplémentaire avec les détails de chaque signature)

### 3.7 Upload bulletins en lot

**Interface** :
1. Zone de drag-and-drop acceptant plusieurs PDF
2. Pour chaque fichier, tenter de matcher le nom avec un employé :
   - Pattern attendu : `NOM_Prenom_MMYYYY.pdf` ou `NOM_Prenom_MM-YYYY.pdf`
   - Matching flou : insensible à la casse, tolère les accents
3. Afficher un tableau récapitulatif : fichier → employé détecté → période détectée
4. Si non détecté : dropdown pour attribution manuelle
5. Bouton "Distribuer" : chiffre et stocke les fichiers, envoie les notifications

### 3.8 Pointeuse

**Interface** :
- Gros bouton central sur le dashboard employé
- 4 états : "Pointer mon arrivée" (vert) → "Commencer ma pause" (jaune) → "Reprendre" (jaune) → "Pointer mon départ" (rouge)
- Affichage du temps écoulé en temps réel
- Historique de la semaine en dessous

**Règles** :
- Un seul pointage par jour
- Pas de pointage rétroactif (sauf par le manager/admin)
- Détection oubli de pointage : si clock_in existe mais pas clock_out après 14h, notification à l'employé

---

## 4. Services (logique métier)

### 4.1 LeaveService

```php
class LeaveService
{
    // Calculer le nombre de jours ouvrés entre deux dates (exclut weekends + jours fériés)
    public function calculateWorkingDays(Carbon $start, Carbon $end, int $companyId): float;

    // Vérifier si l'employé a assez de solde
    public function checkBalance(User $user, LeaveType $type, float $days): bool;

    // Créer une demande + mettre à jour le pending dans leave_balances
    public function createRequest(User $user, array $data): LeaveRequest;

    // Approuver : déplacer pending → used, mettre à jour le planning
    public function approve(LeaveRequest $request, User $reviewer): void;

    // Refuser : remettre les jours pending à 0
    public function reject(LeaveRequest $request, User $reviewer, string $comment): void;

    // Annuler : remettre pending ou used → available
    public function cancel(LeaveRequest $request): void;

    // Acquisition mensuelle : ajouter X jours au solde (cron le 1er de chaque mois)
    public function processMonthlyAcquisition(Company $company): void;

    // Report annuel (cron le 1er janvier)
    public function processYearlyCarryover(Company $company): void;
}
```

### 4.2 OvertimeService

```php
class OvertimeService
{
    // Détecter automatiquement les HS après pointage
    public function detectFromTimeEntry(TimeEntry $entry): ?OvertimeEntry;

    // Calculer le taux applicable (25% ou 50%) basé sur le cumul hebdomadaire
    public function calculateRate(User $user, Carbon $date, float $hours): string;

    // Approuver une entrée HS
    public function approve(OvertimeEntry $entry, User $reviewer): void;

    // Refuser
    public function reject(OvertimeEntry $entry, User $reviewer, string $comment): void;

    // Obtenir le cumul mensuel d'un employé
    public function getMonthlyTotal(User $user, string $period): array;

    // Obtenir le cumul annuel (pour surveiller le contingent)
    public function getYearlyTotal(User $user, int $year): float;

    // Vérifier les alertes (seuil mensuel, contingent annuel)
    public function checkAlerts(User $user): array;
}
```

### 4.3 PayrollExportService

```php
class PayrollExportService
{
    // Générer un export pour un mois donné
    public function generate(Company $company, string $period): PayrollExport;

    // Compiler les données d'un employé pour un mois
    public function compileEmployeeData(User $user, string $period): array;

    // Mettre à jour une ligne manuellement
    public function updateLine(PayrollExportLine $line, array $data): void;

    // Valider l'export (verrouiller)
    public function validate(PayrollExport $export, User $validator): void;

    // Générer le fichier PDF
    public function generatePDF(PayrollExport $export): string;

    // Générer le fichier Excel
    public function generateExcel(PayrollExport $export): string;

    // Générer le fichier CSV
    public function generateCSV(PayrollExport $export): string;

    // Envoyer par email au comptable
    public function sendToAccountant(PayrollExport $export): void;

    // Créer un export correctif
    public function createCorrection(PayrollExport $original): PayrollExport;
}
```

### 4.4 DocumentService

```php
class DocumentService
{
    // Upload et chiffrement AES-256
    public function upload(UploadedFile $file, Company $company, ?User $user, array $meta): Document;

    // Déchiffrement et téléchargement
    public function download(Document $document): StreamedResponse;

    // Créer les entrées signature + envoyer les emails
    public function requestSignatures(Document $document, array $signerIds): void;

    // Traiter une signature
    public function processSignature(Signature $signature, array $data): void;

    // Générer le certificat de signature (page PDF additionnelle)
    public function generateSignatureCertificate(Document $document): string;

    // Supprimer (soft delete) + nettoyage fichier si toutes les copies supprimées
    public function delete(Document $document): void;
}
```

---

## 5. Seeders

### 5.1 DemoCompanySeeder

Pour le développement et la démo. Crée :
- 1 entreprise "Démo SARL" (plan: business)
- 1 admin : admin@demo.staffpilot.fr / password
- 2 managers : manager1@demo, manager2@demo
- 10 employés répartis dans 3 départements (Commercial, Technique, Admin)
- Types de congés : CP (25j), RTT (10j), Sans solde (0j), Maladie (0j)
- Jours fériés 2026
- Quelques demandes de congés (pending, approved, rejected)
- Quelques entrées de pointage
- Quelques heures sup (pending, approved)
- 2-3 documents uploadés

### 5.2 FrenchHolidaySeeder

Jours fériés français pour l'année donnée :
- 1er janvier, Lundi de Pâques, 1er mai, 8 mai, Ascension, Lundi de Pentecôte, 14 juillet, 15 août, 1er novembre, 11 novembre, 25 décembre
- Calculer les dates mobiles (Pâques, Ascension, Pentecôte) via algorithme

---

## 6. Cron jobs (Laravel Scheduler)

```php
// Dans app/Console/Kernel.php ou routes/console.php (Laravel 11)
Schedule::command('leaves:monthly-acquisition')->monthlyOn(1, '06:00');
Schedule::command('leaves:yearly-carryover')->yearlyOn(1, 1, '06:00');
Schedule::command('payroll:generate-monthly')->monthlyOn(1, '07:00');
Schedule::command('payroll:send-reminder')->monthlyOn(3, '09:00'); // Rappel si pas envoyé
Schedule::command('time-tracking:detect-missing')->dailyAt('21:00'); // Oublis de pointage
Schedule::command('overtime:check-alerts')->weeklyOn(5, '17:00');   // Alertes HS vendredi
Schedule::command('documents:remind-signers')->dailyAt('10:00');     // Rappel signatures
```

---

## 7. Sécurité

### 7.1 Chiffrement des fichiers

```php
// Utiliser openssl_encrypt / openssl_decrypt avec AES-256-CBC
// Clé de chiffrement : APP_KEY de Laravel (ou clé dédiée DOCUMENT_ENCRYPTION_KEY)
// Stocker dans storage/app/private/documents/{company_id}/{hash}
// Jamais dans public/
```

### 7.2 Données sensibles en DB

Champs chiffrés via le cast `encrypted` de Laravel :
- `users.social_security_number`
- `users.iban`
- `signatures.signature_data`

### 7.3 Middleware de sécurité

```php
// EnsureCompanyScope : appliqué à TOUTES les routes auth
// - Vérifie que l'utilisateur a un company_id
// - Partage company avec Inertia (pour le frontend)
// - Active le global scope sur tous les modèles

// CheckRole : vérifie le rôle minimum
// Usage : ->middleware('role:admin') ou ->middleware('role:manager')

// EnsureActiveSubscription : vérifie que l'entreprise a un plan actif
// Redirige vers /settings/billing si trial expiré et pas de plan payant
```

---

## 8. Tests minimum requis

```
tests/Feature/
├── Auth/
│   ├── RegistrationTest.php      # Inscription entreprise
│   ├── LoginTest.php             # Connexion / déconnexion
│   └── RoleAccessTest.php        # Vérifier que chaque rôle accède uniquement à ses routes
├── Leaves/
│   ├── LeaveRequestTest.php      # Création, validation, refus, annulation
│   ├── LeaveBalanceTest.php      # Calcul soldes, acquisition mensuelle
│   └── LeaveCalendarTest.php     # Affichage calendrier
├── Overtime/
│   ├── OvertimeDeclareTest.php   # Déclaration manuelle
│   ├── OvertimeAutoDetectTest.php # Détection via pointeuse
│   └── OvertimeApprovalTest.php  # Validation/refus manager
├── PayrollExport/
│   ├── PayrollGenerateTest.php   # Génération automatique
│   ├── PayrollEditTest.php       # Modification lignes par RH
│   └── PayrollSendTest.php       # Envoi email comptable
├── Documents/
│   ├── DocumentUploadTest.php    # Upload et chiffrement
│   ├── DocumentDownloadTest.php  # Déchiffrement et accès
│   └── SignatureTest.php         # Workflow signature
├── MultiTenancy/
│   └── CompanyScopeTest.php      # Vérifier isolation des données
└── TimeTracking/
    └── ClockTest.php             # Pointage in/out/pause
```
