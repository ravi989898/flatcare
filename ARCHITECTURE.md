# FlatCare - Complete System Architecture

## 1. System Overview

**FlatCare** is a multi-tenant Society/Apartment Maintenance Management System with:
- Centralized Super Admin platform
- Separate isolated environments per society
- REST APIs for mobile applications
- Role-based access control
- Secure payment integration
- Push notifications
- Audit logging

## 2. Multi-Tenant Architecture

### 2.1 Database Structure

```
┌─────────────────────────────────────────┐
│     MAIN DATABASE (flatcare_main)       │
│  Central platform management database   │
└─────────────────────────────────────────┘
            │
    ┌───────┼───────┬─────────┐
    │       │       │         │
    ▼       ▼       ▼         ▼
┌────────┬────────┬────────┬────────┐
│Society │Society │Society │Society │
│001 DB  │002 DB  │003 DB  │004 DB  │
└────────┴────────┴────────┴────────┘
```

### 2.2 Main Database (flatcare_main)

**Purpose**: Super Admin platform, society management, multi-tenant orchestration

**Tables**:
```
users                           - Super admin users
super_admins                    - Super admin profiles
societies                       - Society registry
society_databases              - Society DB credentials
modules                        - Platform modules (Maintenance, Visitor, etc)
permissions                    - Global permissions
super_admin_permissions        - Super admin permissions
society_modules                - Enabled modules per society
audit_logs                     - Central audit trail
notifications_queue            - Notification queue
payment_webhooks              - Razorpay webhook logs
```

### 2.3 Society Database (Multiple - one per society)

**Purpose**: Isolated operational data for each society

**Pattern**: `society_{unique_id}_db`

**Tables**:
```
[Users & Roles]
users
roles
permissions
user_roles
user_permissions

[Society Structure]
blocks
flats
flat_residents
family_members
vehicles

[Maintenance]
maintenance_settings
maintenance
maintenance_payments
payment_transactions
payment_receipts
maintenance_adjustments
late_fees

[Complaints]
complaints
complaint_comments
complaint_attachments

[Communications]
notices
notice_attachments
announcements
events
event_attachments

[Visitor Management]
visitors
visitor_entries
default_persons
gate_keepers
gate_keeper_history

[Committee]
committee_members
committee_block_assignments

[Water Readings]
water_readings

[Elections]
elections
election_candidates
election_votes
election_results

[Accounts]
income_transactions
expense_transactions

[System]
notifications
notification_logs
audit_logs
settings
```

## 3. Main Database ER Diagram

```
SUPER ADMIN PLATFORM:

users ◄─────────────────────┐
  │ (email, password)       │
  │                         │
  ├──► super_admins         │
  │    (user_id)            │
  │                         │
  ├──► user_roles ──────────┤
  │    (role_id)            │
  │                         │
  └──► user_permissions ────┘
       (permission_id)

societies
  │ (name, email, phone, status)
  │
  ├──► society_databases ─────────────┐
  │    (db_host, db_user, db_pass)   │
  │    (encrypted)                    │
  │                                   │
  ├──► society_modules               │ Tenant Selection
  │    (module_id, enabled)           │ Logic
  │                                   │
  └──► audit_logs ────────────────────┘
       (society_id, action)

modules (Maintenance, Visitor, etc)
  │
  └──► permissions
       (module_id, permission_name)

payment_webhooks
  (society_id, payment_id, razorpay_response, verified)
```

## 4. Society Database ER Diagram (Example)

```
[USERS & ROLES]
users ◄────────────────────┐
  │                        │
  ├──► user_roles         │
  │    (role_id)          │
  │                        │
  └──► user_permissions ──┘
       (permission_id)

roles
  │
  └──► permissions
       (permission_id)

[SOCIETY STRUCTURE]
blocks
  │
  └──► flats
       │
       └──► flat_residents
            │
            ├──► family_members
            │
            ├──► vehicles
            │
            └──► users (foreign key)

[MAINTENANCE]
maintenance_settings
  (society_id, monthly_amount, due_date, late_fee)

maintenance
  (flat_id, month, year, amount, status)
  │
  ├──► maintenance_payments
  │    (maintenance_id, amount, date)
  │
  ├──► payment_transactions
  │    (razorpay_order_id, razorpay_payment_id)
  │
  ├──► payment_receipts
  │    (generated from transaction)
  │
  └──► maintenance_adjustments
       (adjustment_type, amount)

[COMPLAINTS]
complaints ◄──────────┐
  │                   │
  └──► comments       │
       └──► attachments
            (file_path, type)

[VISITOR MANAGEMENT]
visitors
  │
  └──► visitor_entries
       (status: pending/approved/rejected/exited)

default_persons
  (status: active/inactive)

gate_keepers
  │
  └──► gate_keeper_history
       (joining_date, shift, active_from)

[ELECTIONS]
elections ◄─────────────────┐
  │                         │
  ├──► election_candidates  │
  │                         │
  ├──► election_votes       │
  │    UNIQUE(election, user)
  │                         │
  └──► election_results ────┘
       (published_at)
```

## 5. Role-Based Access Control (RBAC)

### 5.1 Roles

```
1. SUPER_ADMIN
   - Platform management
   - Society management
   - Module configuration
   - All permissions
   - Web only

2. SOCIETY_ADMIN
   - Society operations
   - Resident management
   - Maintenance
   - Committee
   - Financial
   - Web + Mobile

3. COMMITTEE_MEMBER
   - Block-level access
   - Maintenance reports
   - Water readings
   - Block notifications
   - Mobile primarily

4. RESIDENT
   - Own profile
   - Maintenance viewing/payment
   - Complaint management
   - Directory (masked)
   - Visitor approval
   - Election voting
   - Mobile primarily

5. SECURITY / GATE_KEEPER
   - Visitor management
   - Entry/exit tracking
   - Default persons list
   - Mobile primarily
```

### 5.2 Permission Structure

```
Main Database Permissions:
- society.create
- society.read
- society.update
- society.delete
- society.activate
- society.deactivate
- module.assign
- database.create
- database.delete

Society Database Permissions:
- maintenance.view
- maintenance.create
- maintenance.update
- payment.verify
- payment.approve
- resident.manage
- committee.manage
- visitor.approve
- complaint.reply
- election.create
- notification.send
- report.generate
- audit.view
```

## 6. Module Architecture

```
Modules (Enable/Disable per Society):
├── maintenance       - Core maintenance tracking
├── visitor          - Visitor management
├── complaint        - Complaint system
├── election         - Voting system
├── events           - Event management
├── water_reading    - Water meter tracking
├── accounts         - Financial tracking
└── committee        - Committee management
```

## 7. API Architecture

### 7.1 Base Structure

```
/api/v1/
├── auth/
│   ├── login                    [POST]
│   ├── logout                   [POST]
│   ├── register                 [POST]
│   └── refresh-token           [POST]
│
├── super-admin/
│   ├── societies               [GET, POST]
│   ├── societies/{id}          [GET, PUT, DELETE]
│   ├── societies/{id}/activate [POST]
│   ├── societies/{id}/deactivate [POST]
│   ├── societies/{id}/modules  [GET, POST]
│   ├── audit-logs              [GET]
│   └── dashboard               [GET]
│
├── admin/
│   ├── residents               [GET, POST, PUT]
│   ├── maintenance             [GET, POST, PUT]
│   ├── complaints              [GET, PUT]
│   ├── reports                 [GET]
│   └── settings                [GET, PUT]
│
├── residents/
│   ├── profile                 [GET, PUT]
│   ├── maintenance             [GET]
│   ├── maintenance/{id}/pay   [POST]
│   ├── payments                [GET]
│   ├── receipts                [GET]
│   ├── directory               [GET]
│   ├── complaints              [GET, POST]
│   ├── visitors                [GET]
│   └── notifications           [GET]
│
├── security/
│   ├── visitors                [GET, POST]
│   ├── visitors/{id}/approve  [POST]
│   ├── visitors/{id}/reject   [POST]
│   ├── visitors/{id}/exit     [POST]
│   └── dashboard               [GET]
│
└── common/
    ├── notices                 [GET]
    ├── announcements           [GET]
    ├── events                  [GET]
    └── elections               [GET]
```

### 7.2 Response Format

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {},
    "pagination": {
        "current_page": 1,
        "per_page": 20,
        "total": 100,
        "last_page": 5
    }
}
```

## 8. Multi-Tenant Connection Switching

### 8.1 Strategy

```
Request with Token
    ↓
Middleware: Auth Check
    ↓
Extract User + Society from Token
    ↓
Middleware: Tenant Setup
    ↓
Switch DB Connection to society_{society_id}_db
    ↓
All Eloquent Models use default 'society' connection
    ↓
Response
```

### 8.2 Implementation

```
// config/database.php
'connections' => [
    'main' => [...],  // flatcare_main
    'society' => [    // Dynamic tenant connection
        'driver' => 'mysql',
        'host' => env('DB_SOCIETY_HOST'),
        'database' => env('DB_SOCIETY_NAME'),
        'username' => env('DB_SOCIETY_USER'),
        'password' => env('DB_SOCIETY_PASSWORD'),
    ]
]

// app/Http/Middleware/SetTenantConnection.php
// Intercepts authenticated request
// Fetches society DB credentials from main DB
// Sets environment variables
// Reconnects DB to society database
```

## 9. Authentication Architecture

### 9.1 Flow

```
Mobile Device
    ↓
    POST /api/v1/auth/login
    {email, password, device_id}
    ↓
Backend: Authenticate against society DB
    ↓
Backend: Create Sanctum token with society context
    {
        user_id,
        society_id,
        role,
        permissions,
        device_id
    }
    ↓
Return Token to Mobile
    ↓
Subsequent Requests
    ↓
Header: Authorization: Bearer {token}
    ↓
Middleware: Validate token + society context
    ↓
Proceed with request
```

### 9.2 Token Structure

```
Token includes:
- user_id
- society_id
- role
- device_id
- permissions (cached)
- issued_at
- expires_at

Every API request validates:
1. Token validity
2. User status (active/blocked)
3. Society status (active/expired)
4. Role permissions
5. Module access
6. Tenant isolation
```

## 10. Payment Architecture (Razorpay)

### 10.1 Payment Flow

```
Resident initiates payment
    ↓
Backend: Create Razorpay Order
    {
        maintenance_id,
        amount,
        resident_id,
        society_id
    }
    ↓
Return order_id to mobile
    ↓
Mobile: Open Razorpay checkout
    ↓
Resident: Complete payment
    ↓
Razorpay: Send webhook
    {
        event: payment.authorized,
        payment_id,
        order_id,
        signature
    }
    ↓
Backend: Webhook handler
    1. Verify signature
    2. Check for duplicates (idempotency)
    3. Verify payment with Razorpay
    4. Update maintenance as paid
    5. Generate receipt
    6. Queue notification
    ↓
Success response to Razorpay
```

### 10.2 Idempotency Implementation

```
payment_transactions table:
- razorpay_payment_id (unique)
- razorpay_order_id
- maintenance_id
- amount
- status
- processed_at
- signature_verified

If webhook received multiple times:
1. Check if razorpay_payment_id exists
2. If exists → return success (idempotent)
3. If not exists → process payment
4. Use database transaction
```

## 11. Notification Architecture

### 11.1 Channels

```
FCM (Firebase Cloud Messaging)
    ↓
Push notifications to mobile

In-app Notifications
    ↓
Stored in DB

Email
    ↓
Optional

SMS
    ↓
Optional
```

### 11.2 Notification Events

```
Maintenance:
- maintenance.due
- maintenance.overdue
- payment.successful
- payment.failed

Visitor:
- visitor.waiting
- visitor.approved
- visitor.rejected

Complaints:
- complaint.created
- complaint.replied
- complaint.resolved

Communications:
- notice.published
- announcement.published
- event.reminder

Elections:
- election.published
- election.results

Blocks:
- block_notification.sent (committee only)
```

## 12. File Storage Architecture

### 12.1 Structure

```
storage/app/
├── private/                      # Non-public files
│   ├── visitor-photos/
│   ├── complaint-attachments/
│   ├── payment-receipts/
│   └── documents/
│
└── public/                       # Public CDN
    ├── society-logos/
    ├── notice-attachments/
    ├── event-images/
    └── profiles/
```

### 12.2 Security Rules

```
1. All uploads validated:
   - MIME type check
   - File size limit
   - Extension whitelist
   - Image dimension check (if image)

2. File naming:
   - Use random UUID
   - Never expose original filename

3. Access control:
   - Private files require authorization check
   - Public files still check ownership

4. Secure URLs:
   - Generate signed URLs for downloads
   - URLs expire after 30 minutes
```

## 13. Audit Log Architecture

### 13.1 Main Database Audit

```
audit_logs (main DB):
- super_admin_id
- society_id
- action (society.created, society.updated)
- module
- timestamp
- ip_address
- user_agent
- old_values (JSON)
- new_values (JSON)
```

### 13.2 Society Audit

```
audit_logs (society DB):
- user_id
- role
- action
- module
- record_type (maintenance, complaint, etc)
- record_id
- old_values
- new_values
- ip_address
- timestamp

Immutable: Normal users cannot modify audit logs
```

## 14. Recommended Laravel Folder Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── V1/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── Admin/
│   │   │   │   │   ├── ResidentController.php
│   │   │   │   │   ├── MaintenanceController.php
│   │   │   │   │   └── ReportController.php
│   │   │   │   ├── Resident/
│   │   │   │   │   ├── ProfileController.php
│   │   │   │   │   ├── MaintenanceController.php
│   │   │   │   │   └── PaymentController.php
│   │   │   │   ├── Security/
│   │   │   │   │   └── VisitorController.php
│   │   │   │   └── Common/
│   │   │   │       ├── NoticeController.php
│   │   │   │       └── EventController.php
│   │   │   └── SuperAdmin/
│   │   │       ├── SocietyController.php
│   │   │       └── ModuleController.php
│   │   │
│   │   └── Web/
│   │       ├── SuperAdmin/
│   │       ├── Admin/
│   │       └── Dashboard/
│   │
│   ├── Middleware/
│   │   ├── SetTenantConnection.php
│   │   ├── CheckTenantAccess.php
│   │   ├── AuthenticateApi.php
│   │   ├── CheckModuleAccess.php
│   │   ├── CheckPermission.php
│   │   └── CheckSocietyActive.php
│   │
│   ├── Requests/
│   │   ├── Api/
│   │   │   ├── V1/
│   │   │   │   ├── Auth/
│   │   │   │   ├── Admin/
│   │   │   │   ├── Resident/
│   │   │   │   └── Security/
│   │   │   └── SuperAdmin/
│   │   └── Web/
│   │
│   ├── Resources/
│   │   ├── ResidentResource.php
│   │   ├── MaintenanceResource.php
│   │   ├── PaymentResource.php
│   │   ├── ComplaintResource.php
│   │   ├── VisitorResource.php
│   │   └── ReportResource.php
│   │
│   └── Exceptions/
│       ├── TenantException.php
│       ├── PaymentException.php
│       ├── VisitorException.php
│       └── Handler.php
│
├── Models/
│   ├── MainApp/
│   │   ├── User.php
│   │   ├── SuperAdmin.php
│   │   ├── Society.php
│   │   ├── SocietyDatabase.php
│   │   ├── Module.php
│   │   └── AuditLog.php
│   │
│   └── Tenant/
│       ├── User.php
│       ├── Block.php
│       ├── Flat.php
│       ├── Resident.php
│       ├── Maintenance.php
│       ├── Payment.php
│       ├── Complaint.php
│       ├── Visitor.php
│       ├── Committee.php
│       ├── Election.php
│       └── AuditLog.php
│
├── Services/
│   ├── TenantService.php
│   ├── Auth/
│   │   ├── AuthService.php
│   │   └── PasswordService.php
│   ├── Maintenance/
│   │   ├── MaintenanceService.php
│   │   ├── MaintenanceGeneratorService.php
│   │   └── LateFeService.php
│   ├── Payment/
│   │   ├── PaymentService.php
│   │   ├── RazorpayService.php
│   │   └── PaymentVerificationService.php
│   ├── Visitor/
│   │   └── VisitorService.php
│   ├── Complaint/
│   │   └── ComplaintService.php
│   ├── Notification/
│   │   ├── NotificationService.php
│   │   └── FCMService.php
│   ├── Report/
│   │   ├── ReportService.php
│   │   └── PDFService.php
│   └── Audit/
│       └── AuditService.php
│
├── Repositories/
│   ├── MaintenanceRepository.php
│   ├── PaymentRepository.php
│   ├── ResidentRepository.php
│   ├── ComplaintRepository.php
│   └── VisitorRepository.php
│
├── Policies/
│   ├── MaintenancePolicy.php
│   ├── ComplaintPolicy.php
│   ├── VisitorPolicy.php
│   ├── ReportPolicy.php
│   └── ResidentPolicy.php
│
├── Jobs/
│   ├── SendNotificationJob.php
│   ├── GenerateMaintenanceJob.php
│   ├── CalculateLateFeeJob.php
│   ├── GenerateReceiptJob.php
│   ├── GenerateReportJob.php
│   └── ProcessWebhookJob.php
│
├── Events/
│   ├── PaymentProcessed.php
│   ├── MaintenanceGenerated.php
│   ├── VisitorApproved.php
│   ├── ComplaintCreated.php
│   └── ElectionPublished.php
│
├── Listeners/
│   ├── SendPaymentNotification.php
│   ├── SendVisitorNotification.php
│   ├── SendComplaintNotification.php
│   └── LogAuditTrail.php
│
├── Notifications/
│   ├── MaintenanceDueNotification.php
│   ├── PaymentSuccessNotification.php
│   ├── VisitorApprovedNotification.php
│   ├── ComplaintReplyNotification.php
│   └── AnnouncementNotification.php
│
└── Exceptions/
    ├── TenantException.php
    ├── PaymentException.php
    └── ValidationException.php

config/
├── database.php              # Multi-connection setup
├── auth.php
├── filesystems.php
├── queue.php
├── cache.php
└── razorpay.php            # Payment config

database/
├── migrations/
│   ├── main/               # Main DB migrations
│   │   └── xxxx_*.php
│   └── tenant/             # Tenant DB migrations
│       └── xxxx_*.php
├── seeders/
│   ├── MainAppSeeder.php
│   └── TenantSeeder.php
└── factories/
    ├── UserFactory.php
    ├── MaintenanceFactory.php
    └── VisitorFactory.php

routes/
├── api.php                 # API routes (/api/v1/*)
├── web.php                 # Web routes
├── api/
│   └── v1.php             # API v1 routes
└── console.php

storage/
├── app/
│   ├── private/           # Private files (auth required)
│   └── public/            # Public files
└── logs/

tests/
├── Feature/
│   ├── Auth/
│   ├── Maintenance/
│   ├── Payment/
│   ├── Visitor/
│   ├── Complaint/
│   └── TenantIsolation/
├── Unit/
│   ├── Services/
│   └── Models/
└── TestCase.php
```

## 15. Security Architecture

### 15.1 Layers

```
1. Authentication Layer
   - Sanctum token verification
   - Token expiry check
   - Device binding (optional)

2. Authorization Layer
   - Role check
   - Permission check
   - Policy check

3. Multi-Tenancy Layer
   - Society context validation
   - Database connection verification
   - Data isolation enforcement

4. Module Access Layer
   - Module enabled check
   - Feature toggle

5. Business Logic Layer
   - Data ownership verification
   - Soft permission checks

6. Data Layer
   - Query scoping to society
   - Foreign key constraints
```

### 15.2 Encryption Strategy

```
Encrypted Fields (in database):
- society_databases.db_password
- settings.razorpay_key_secret
- settings.bank_account_details
- payment_transactions (sensitive data)

Use Laravel encryption:
#[Encrypted] attribute
```

## 16. Development Roadmap

### Phase 1: Foundation (Current)
- [ ] Laravel project setup
- [ ] Main database schema
- [ ] Tenant connection switching
- [ ] Super Admin authentication
- [ ] Middleware for tenant isolation

### Phase 2: Super Admin Module
- [ ] Society management CRUD
- [ ] Database creation script
- [ ] Module assignment
- [ ] Super Admin dashboard

### Phase 3: Society Structure
- [ ] Block management
- [ ] Flat management
- [ ] User roles and permissions
- [ ] Committee assignments

### Phase 4: Maintenance
- [ ] Maintenance configuration
- [ ] Auto-generation
- [ ] Late fee calculation
- [ ] Razorpay integration
- [ ] Receipt generation

### Phase 5-11: Feature Modules
- Resident features
- Visitor management
- Complaints
- Elections
- Communications
- Reports

### Phase 12: Production Ready
- Security audit
- Performance testing
- Load testing
- Deployment scripts

## 17. Key Security Principles

```
1. Never trust client input for IDs
   - Always derive from authenticated user
   
2. Tenant isolation is mandatory
   - Enforce at database level
   - Not just code level
   
3. Payment verification
   - Always verify with Razorpay
   - Never trust frontend confirmation
   
4. Sensitive data
   - Mask personal information
   - Encrypt credentials
   - Log without exposing secrets
   
5. Audit everything
   - Keep immutable logs
   - Track data changes
   
6. API security
   - Rate limiting
   - Input validation
   - Authorization checks
   - Proper error messages
```

---

**Next Step**: Start Phase 1 Implementation with complete code for:
1. Laravel project setup
2. Main database migrations
3. Tenant service with connection switching
4. Authentication foundation
5. Middleware for tenant isolation
