# Clinic System (PHP + MySQL)

A minimal, clean CRUD system to manage Patients, Check-ups, BP Monitoring, and In-Patient Admissions. Designed around your spreadsheets.

## Quick start

1. Create the database and tables:
   - Open MySQL and run the contents of `schema.sql`.
   - Adjust database name/credentials if needed.

2. Configure database connection:
   - Edit `config.php` (or set environment variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

3. Deploy the code:
   - Place the `clinic-system` folder into your PHP server's web root.
   - Ensure your server points to `/public` (or access `/public/index.php` in the browser).

4. Use it:
   - Add a patient first (Patients → + Add Patient).
   - Log Check-ups, BP readings, or In-Patient admissions.

## Notes
- Built with PDO + prepared statements.
- Bootstrap 5 from CDN for styling.
- Simple, readable structure for easy extension (e.g., printing, CSV import, user auth).

## Structure
```
clinic-system/
  assets/           # CSS/JS
  partials/         # header/footer
  public/           # app pages
  schema.sql        # DB schema
  config.php        # DB connection
```
