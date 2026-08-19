# Society Admin Module Implementation

## Overview
The **Society Admin** module enables super admins to create and manage administrator users within each individual society (tenant). This is a critical component of the multi-tenant architecture.

## Architecture

### Models (Tenant Database)

#### `App\Models\Tenant\User`
- Represents users within a society's database
- Uses `'society'` connection (dynamic per tenant)
- Relationships:
  - `roles()` - Many-to-many with Role
  - `permissions()` - Direct permissions (many-to-many with Permission)
  - `directPermissions()` - Direct user-specific permissions
- Methods:
  - `allPermissions()` - Combines role + direct permissions
  - `hasPermission(string)` - Check permission
  - `hasRole(string)` - Check role
  - `assignRole()`, `removeRole()` - Manage roles
  - `grantPermission()`, `revokePermission()` - Manage permissions
- Scopes:
  - `active()` - Only active users
  - `admins()` - Only admin role users

#### `App\Models\Tenant\Role`
- Defines role groups (admin, manager, user, etc.)
- System roles cannot be deleted
- Priority field for role hierarchy
- Relationships:
  - `permissions()` - Many-to-many with Permission
  - `users()` - Many-to-many with User
- Methods:
  - `grantPermission()`, `revokePermission()` - Manage role permissions

#### `App\Models\Tenant\Permission`
- Granular permissions per module
- Format: `{module}.{action}` (e.g., `maintenance.view`, `payment.create`)
- Relationships:
  - `roles()` - Many-to-many with Role
  - `users()` - Many-to-many with User (direct permissions)
- Scopes:
  - `byModule(string)` - Filter by module
  - `byAction(string)` - Filter by action (view, create, edit, delete)

#### `App\Models\Tenant\AdminUser`
- Tracks admin-specific metadata (last login, 2FA status, etc.)
- One-to-one with User
- Methods:
  - `getRoles()` - Get user roles
  - `hasPermission(string)` - Check admin permissions

### Controllers

#### `App\Http\Controllers\Admin\SocietyAdminController`
Manages society admins from the super admin panel

**Routes:**
- `GET /admin/societies/{id}/admins` → `index()` - List admins for a society
- `GET /admin/societies/{id}/admins/create` → `create()` - Show create form
- `POST /admin/societies/{id}/admins` → `store()` - Create admin
- `GET /admin/societies/{id}/admins/{adminId}/edit` → `edit()` - Show edit form
- `PUT /admin/societies/{id}/admins/{adminId}` → `update()` - Update admin
- `POST /admin/societies/{id}/admins/{adminId}/deactivate` → `deactivate()` - Deactivate admin
- `DELETE /admin/societies/{id}/admins/{adminId}` → `destroy()` - Delete admin

**Key Features:**
- Uses `TenantService` to switch to society's database
- Validates all inputs (name, email, phone, password)
- Creates user and assigns role
- Supports password updates without requiring confirmation
- Soft deletes admins (status = 'deleted')

### Database Schema

#### Main Database Tables
- `users` - Super admin users
- `roles` - Platform-level roles
- `permissions` - Platform-level permissions
- `societies` - Society registry
- `society_modules` - Module allocation

#### Tenant Database Tables
- `users` - Society members and admins
- `roles` - Society-specific roles (admin, manager, user)
- `permissions` - Society module permissions
- `role_user` - Role assignments
- `permission_role` - Role-permission mappings
- `permission_user` - Direct user permissions
- `admin_users` - Admin metadata (optional)

### Views

#### `/admin/societies/admins/index.blade.php`
Lists all admins for a society with:
- Admin name, email, phone
- Assigned role (badge)
- Status (Active/Inactive/Deleted)
- Last login info
- Action buttons (Edit, Deactivate, Delete)
- Add Admin button

#### `/admin/societies/admins/create.blade.php`
Form to create new society admin:
- Full name (required)
- Email (required, unique)
- Phone (required, 10 digits, unique)
- Password (required, min 10 characters)
- Password confirmation
- Role selection dropdown
- Cancel/Create buttons

#### `/admin/societies/admins/edit.blade.php`
Form to update existing admin:
- Editable fields (name, email, phone)
- Optional password change
- Role reassignment
- Admin status display
- Created and last login info

### Seeders

#### `Database\Seeders\TenantRoleSeeder`
Runs once per society database to:
1. Create default roles:
   - **Admin** (priority: 100) - Full access
   - **Manager** (priority: 50) - Manage operations
   - **User** (priority: 1) - Regular user access

2. Create permissions for all modules:
   - For each module: `.view`, `.create`, `.edit`, `.delete`
   - Common permissions: `user.view`, `user.create`, `user.edit`, `user.delete`

3. Assign all permissions to Admin role

#### `Database\Seeders\TenantSeeder`
Orchestrates tenant seeding:
- Calls `TenantRoleSeeder`
- Called via `TenantService::seedTenantDatabase()`

### Integration Points

#### TenantService Updates
The service already supports seeding:
```php
public function seedTenantDatabase(int $societyId): bool
```
This is called after migrations to populate default roles and permissions.

#### Routes
Added to `/routes/web.php` in admin prefix group:
```php
Route::get('/societies/{id}/admins', [SocietyAdminController::class, 'index'])
Route::get('/societies/{id}/admins/create', [SocietyAdminController::class, 'create'])
Route::post('/societies/{id}/admins', [SocietyAdminController::class, 'store'])
Route::get('/societies/{id}/admins/{adminId}/edit', [SocietyAdminController::class, 'edit'])
Route::put('/societies/{id}/admins/{adminId}', [SocietyAdminController::class, 'update'])
Route::post('/societies/{id}/admins/{adminId}/deactivate', [...])
Route::delete('/societies/{id}/admins/{adminId}', [...])
```

## Usage Flow

### Creating a Society Admin
1. Super admin navigates to `/admin/societies/{id}/admins`
2. Clicks "Add Admin" button
3. Fills form with admin details
4. Selects role (Admin, Manager, User)
5. Submits
6. Admin user created in society's database
7. Role assigned via `role_user` junction table

### Managing Permissions
1. Admin has role-based permissions
2. Permissions are checked via `User::hasPermission()`
3. Can also assign direct permissions to individual users
4. Admin role automatically gets all permissions on seeding

### Admin Access Control
- Each admin is tied to a role
- Roles have granular module permissions
- Permissions follow format: `{module}.{action}`
- System checks permissions before allowing operations

## Security Features

1. **Database Isolation**: Each society has separate database
2. **Role-Based Access**: Permissions assigned at role level
3. **Multi-Tenancy Enforcement**: TenantService ensures correct DB context
4. **Encrypted Passwords**: Bcrypt hashing for all user passwords
5. **Status Tracking**: Active/Inactive/Deleted status per admin
6. **Audit Trail**: Timestamps on all operations
7. **Soft Deletes**: Admins marked as deleted, not removed

## Next Steps (Phase 2.1)

1. **Implement Society CRUD**
   - Create society management interface
   - Provision new society databases
   - Manage subscription periods and modules

2. **Implement Society Authentication**
   - Login for society admins
   - Token-based API auth (Sanctum)
   - Session management

3. **Build Tenant Dashboard**
   - Society admin dashboard
   - Permission-based module access
   - Analytics and reports

4. **Role Management UI**
   - Create/edit custom roles
   - Assign permissions to roles
   - Permission matrix editor

## Files Created

### Models
- `app/Models/Tenant/User.php`
- `app/Models/Tenant/Role.php`
- `app/Models/Tenant/Permission.php`
- `app/Models/Tenant/AdminUser.php`

### Controllers
- `app/Http/Controllers/Admin/SocietyAdminController.php`

### Views
- `resources/views/admin/societies/admins/index.blade.php`
- `resources/views/admin/societies/admins/create.blade.php`
- `resources/views/admin/societies/admins/edit.blade.php`

### Seeders
- `database/seeders/TenantRoleSeeder.php`
- `database/seeders/TenantSeeder.php`

### Routes (Updated)
- `/routes/web.php` - Added 7 routes for admin management

## Database Diagram

```
Main Database (flatcare_main):
┌─────────────┐
│  societies  │
│  (registry) │
└──────┬──────┘
       │
       ├──► society_modules
       │    (enable/disable)
       │
       └──► society_databases
            (connection creds)
            │
            ├──┐
            └──┼──────────────────────┐
               │                      │
            Tenant DB 1           Tenant DB 2
            (society_1)           (society_2)
            ┌─────────────┐       ┌─────────────┐
            │   users     │       │   users     │
            │   roles     │       │   roles     │
            │ permissions │       │ permissions │
            │  role_user  │       │  role_user  │
            │perm_role    │       │perm_role    │
            │perm_user    │       │perm_user    │
            └─────────────┘       └─────────────┘
```

## Testing Checklist

- [ ] Create society admin from super admin panel
- [ ] Verify admin user created in society database
- [ ] Verify role assigned correctly
- [ ] Edit admin details (name, email, phone)
- [ ] Change admin role
- [ ] Deactivate admin
- [ ] Delete admin (soft delete)
- [ ] Verify permissions granted to admin role
- [ ] Test with multiple societies
- [ ] Verify database switching per society
- [ ] Test password update for admin
