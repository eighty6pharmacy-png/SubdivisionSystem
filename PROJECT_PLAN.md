# Subdivision System Revision & Refactoring Plan

As your strict project manager and lead developer, I have conducted a preliminary code review of the `SubdivisionSystem` repository. While the UI and system flow represented in the mockups are promising, the underlying architecture requires an immediate and rigorous overhaul. 

Currently, the entire application's logic, mock data, and routing are crammed into a single file (`routes/web.php`). This violates fundamental Laravel best practices and the Model-View-Controller (MVC) architectural pattern. It is unscalable, unmaintainable, and prone to severe bugs.

This implementation plan outlines the steps required to elevate this prototype into a production-ready system.

> [!WARNING]
> **Major Architectural Refactor Needed**
> The current codebase uses inline closures and hardcoded arrays in `web.php` instead of proper databases, models, and controllers. We must refactor this to use Laravel's standard MVC architecture.

## User Review Required

Before we proceed, I need your approval on the proposed architectural changes. This will involve significant modifications to how the data is structured and accessed. 

## Open Questions

1. **Database Engine**: We will need a database for this. Should we default to SQLite for simplicity and immediate testing, or are you planning to use MySQL/PostgreSQL?
2. **Authentication**: Currently, there is no real authentication system. Should we implement Laravel Breeze or standard authentication to manage the Resident, Admin, Guard, and Finance roles?
3. **Scope of Refactor**: Do you want me to refactor the entire system at once, or focus on a specific module first (e.g., the User/Resident portal)?

## Proposed Changes

We will systematically dismantle the monolithic `web.php` and distribute its responsibilities across the appropriate Laravel layers.

### 1. Database & Migrations
We will create actual database schemas instead of relying on arrays.
#### [NEW] Database Migrations for:
- `users` (Residents, Admins, Guards, Finance)
- `incidents` (Maintenance, Security reports)
- `bills` (Electricity, Water, Dues)
- `announcements`
- `appointments`
- `visitor_pins`
- `lots` (Adding `connection_status` for electricity disconnect feature)

### 2. Eloquent Models
We will create models to interact with the database tables.
#### [NEW] `app/Models/Incident.php`
#### [NEW] `app/Models/Bill.php`
#### [NEW] `app/Models/Announcement.php`
#### [NEW] `app/Models/Appointment.php`
#### [NEW] `app/Models/VisitorPin.php`
#### [NEW] `app/Models/Lot.php`

### 3. Database Seeders
We will migrate all the hardcoded array data currently residing in `web.php` into Laravel Seeders so that the mock data can be properly injected into the database.
#### [NEW] `database/seeders/DatabaseSeeder.php` (and related module seeders)

### 4. Controllers
We will create dedicated controllers to handle the business logic, replacing the inline closures in `web.php`.
#### [NEW] `app/Http/Controllers/Admin/DashboardController.php`
#### [NEW] `app/Http/Controllers/Admin/IncidentController.php`
#### [NEW] `app/Http/Controllers/Resident/BillingController.php`
#### [NEW] `app/Http/Controllers/Resident/VisitorController.php`
#### [NEW] `app/Http/Controllers/Guard/SecurityController.php`
#### [NEW] `app/Http/Controllers/Api/EmailController.php` (for the live email dispatch)

### 5. Routing Cleanup
#### [MODIFY] `routes/web.php`
- [DELETE] All mock data arrays and global functions (e.g., `getIncidents()`, `getElectricalBills()`).
- [MODIFY] Replace inline closures with standard controller route definitions (e.g., `Route::get('/admin/incidents', [IncidentController::class, 'index']);`).

### 6. New Features Integration
#### [NEW] Disconnect Account Logic
- Update the Admin/Finance dashboards to include a `Disconnect` button for each resident/lot.
- Update the backend controllers to toggle the `connection_status` in the `lots` table.
- Update the Admin Billing views to filter out disconnected accounts by default, and add a toggle to view disconnected lots.

#### [NEW] QGIS Map Integration
- Add Leaflet.js to the Admin GIS page view.
- Create a mechanism to load exported QGIS GeoJSON files and render them on the browser map.
- Link the map polygons (lots) to the new `lots` database table so clicking a house reveals live resident/billing data.

## Verification Plan

### Automated Tests
- If needed, run `php artisan test` to ensure basic routing still functions.

### Manual Verification
- Run `php artisan migrate:fresh --seed` to populate the new database.
- Navigate through the application (Admin, Resident, Guard, Finance portals) to ensure all views render correctly using the database-driven data instead of the old array-driven data.
- Verify that forms and data mutations (if any) interact with the database successfully.
