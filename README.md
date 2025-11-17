GENERAL INFORMATION
Title of Dataset:
Speedy Wheels Car Rental Management System Dataset

Author
Name: Sakhile Basi
ORCID: N/A
Institution: Speedy Wheels
Email: kopano.basi21@gmail.com

Date of data collection: 2023-01-01 to 2024-01-15
Geographic location of data collection: Johannesburg, Gauteng, South Africa
Information about funding sources that supported the collection of the data: Internal corporate funding from Speedy Wheels Holdings

SHARING/ACCESS INFORMATION
Licenses/restrictions placed on the data: Proprietary - Internal business data. Not for public distribution.
Links to publications that cite or use the data: N/A - Internal business system
Links to other publicly accessible locations of the data: N/A - Private repository
Links/relationships to ancillary data sets: Connected to vehicle inventory, customer database, and rental transaction systems
Was data derived from another source?
If yes, list source(s): Vehicle specifications from manufacturer databases, customer information from rental agreements
Recommended citation for this dataset: Speedy Wheels Car Rental Management Dataset (2024). Internal Business Data.

DATA & FILE OVERVIEW
File List:
/includes/config.php - Session configuration and security settings

/includes/db.php - Database connection configuration

/includes/login.model.php - Authentication data layer

/includes/login.contr.php - Authentication business logic

/includes/login.view.php - Authentication presentation layer

/includes/login.php - Login processing script

/includes/navigation.php - Shared navigation component

/includes/search_customers.php - AJAX customer search endpoint

/includes/get_employee_stats.php - Employee statistics API endpoint

/index.php - Login page

/pages/employeeProfile.php - Employee dashboard

/pages/cars.php - Vehicle fleet management

/pages/customers.php - Customer management

/pages/purchases.php - Rental processing system

/Images/ - Directory containing vehicle images (35+ models)

Relationship between files, if important: Core application files depend on includes/ directory for shared functionality. Frontend pages rely on backend API endpoints for dynamic data.
Additional related data collected that was not included in the current data package: Financial transaction records, maintenance logs, employee payroll data
Are there multiple versions of the dataset?
If yes, name of file(s) that was updated: All files underwent iterative development
Why was the file updated? Feature enhancements, security improvements, bug fixes
When was the file updated? Ongoing updates from 2023-01-01 to 2024-01-15

METHODOLOGICAL INFORMATION
Description of methods used for collection/generation of data:
Data collected through web-based rental management system. Vehicle data imported from manufacturer specifications. Customer data collected through rental agreement forms. Rental transaction data generated through daily business operations.

Methods for processing the data:
Raw data processed through PHP application layer with MySQL database backend. Data validated through both client-side (JavaScript) and server-side (PHP) validation. Data sanitized using prepared statements and output escaping.

Instrument- or software-specific information needed to interpret the data:
PHP 8.0+ with PDO extension

MySQL 5.7+ database server

Apache/Nginx web server

Bootstrap 5.3.0 frontend framework

Font Awesome 6.4.0 icon library

Standards and calibration information, if appropriate: Data follows automotive industry standards for vehicle specifications. Currency values in South African Rand (ZAR).
Environmental/experimental conditions: Production business environment, live rental operations
Describe any quality-assurance procedures performed on the data: Input validation, data type checking, foreign key constraints, transaction rollback capabilities
People involved with sample collection, processing, analysis and/or submission: Rental agents, database administrators, system developers, quality assurance team

DATA-SPECIFIC INFORMATION FOR: cars table
Number of variables: 10
Number of cases/rows: 36 vehicles
Variable List:

id (INT): Primary key, auto-incrementing vehicle identifier

make (VARCHAR): Vehicle manufacturer (e.g., Toyota, BMW, Mercedes)

model (VARCHAR): Vehicle model name (e.g., Corolla, X5, C-Class)

year (INT): Manufacturing year (2023)

color (VARCHAR): Exterior color

license_plate (VARCHAR): Vehicle registration number

daily_rate (DECIMAL): Rental cost per day in ZAR

status (ENUM): Current availability ('Available', 'Rented', 'Maintenance')

image_path (VARCHAR): Path to vehicle image file

created_at (TIMESTAMP): Record creation timestamp

Missing data codes: NULL values indicate missing or uncollected data
Specialized formats or other abbreviations used: ZAR currency format, PNG image format

DATA-SPECIFIC INFORMATION FOR: customers table
Number of variables: 7
Number of cases/rows: 50+ customer records
Variable List:

id (INT): Primary key, auto-incrementing customer identifier

name (VARCHAR): Customer full name

email (VARCHAR): Customer email address (unique)

phone (VARCHAR): Customer contact number

address (TEXT): Customer physical address

created_at (TIMESTAMP): Record creation timestamp

Missing data codes: NULL values for optional fields (phone, address)
Specialized formats or other abbreviations used: Email validation, South African phone number format

DATA-SPECIFIC INFORMATION FOR: employees table
Number of variables: 6
Number of cases/rows: 5+ employee records
Variable List:

id (INT): Primary key, auto-incrementing employee identifier

name (VARCHAR): Employee full name

email (VARCHAR): Employee email address (unique)

password (VARCHAR): Hashed password using password_hash()

position (VARCHAR): Job title/position

created_at (TIMESTAMP): Record creation timestamp

Missing data codes: N/A - All fields required
Specialized formats or other abbreviations used: BCrypt password hashing

DATA-SPECIFIC INFORMATION FOR: rentals table
Number of variables: 12
Number of cases/rows: 100+ rental transactions
Variable List:

id (INT): Primary key, auto-incrementing rental identifier

customer_id (INT): Foreign key to customers table

car_id (INT): Foreign key to cars table

employee_id (INT): Foreign key to employees table

start_date (DATE): Rental start date

end_date (DATE): Rental end date

pickup_location (VARCHAR): Vehicle pickup location

return_location (VARCHAR): Vehicle return location

total_cost (DECIMAL): Calculated rental cost in ZAR

status (ENUM): Rental status ('Ongoing', 'Returned', 'Cancelled')

additional_services (JSON): Optional services selected

created_at (TIMESTAMP): Record creation timestamp

Missing data codes: NULL for optional additional_services
Specialized formats or other abbreviations used: JSON format for additional services, date formats (YYYY-MM-DD)