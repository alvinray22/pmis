PROPERTY MANAGEMENT INFORMATION SYSTEM
PHP + MySQL + XAMPP

Based on the uploaded specification:
- Dashboard
- Status counters: Condemned, Unserviceable, Not usable, Repair, Missing,
  For Verification, Good/Issued, Serviceable
- Total Properties
- Property database fields:
  PROPERTY NO., DESCRIPTION, DATE ACQUIRED, ACCOUNTABLE PERSON,
  LOCATION, STATUS, COST
- Login page

INSTALLATION
1. Install XAMPP.
2. Start Apache and MySQL in the XAMPP Control Panel.
3. Extract this folder to:
   C:\xampp\htdocs\property_management
4. Open http://localhost/phpmyadmin
5. Click Import and import database.sql.
   (Or open the SQL tab and paste its contents.)
6. Visit:
   http://localhost/property_management/login.php
7. Login:
   Username: admin
   Password: admin123

IMPORTANT
- This is a starter/local XAMPP system.
- Change the default password before production use.
- The database is named property_management.
- Edit config.php if your MySQL root password is not blank.


LOGIN FIX
The login page now accepts admin / admin123 even if an older imported database has an invalid admin hash. After the first successful fallback login, it automatically stores a fresh secure password hash.


REPORTS
The Properties page now has separate Generate Excel Report and Generate PDF Report buttons.
Excel downloads as an Excel-compatible .xls file. PDF opens in a new browser tab for viewing/printing.
Reports respect the current Search and Status filters.


PDF REPORT UPDATE
The PDF report is now A4 landscape and keeps all seven categories on one line per record: Property No., Description, Date Acquired, Accountable Person, Location, Status, Cost.


PDF SPACING UPDATE
The PDF table categories/header were moved lower so they no longer overlap the Generated date line.
