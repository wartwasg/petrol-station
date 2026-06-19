# Petrol Station Management System

A comprehensive web-based petrol station management system built with PHP, MySQL, Bootstrap, and Chart.js.

## Features

### User Roles

1. **Chief Manager**
   - Assign roles to users
   - Create (add) users
   - View all daily sales and pump readings
   - View monthly income and sales statistics
   - View daily and monthly expenses
   - View all information about re-filling, costs and data
   - Manage all users

2. **Manager**
   - Assign roles to users (except chief-manager)
   - Enter fuel prices per litre
   - Record meter readings for morning and evening shifts
   - Calculate petrol used and total income
   - Record cash sales, bank payments, mobile payments
   - Create pumps and assign fuel type and attendant
   - Create tanks and assign fuel type and max volume
   - View archived information (read-only)
   - Record tank re-fills with cost and receipt image
   - Edit profile information of other users
   - Manage own profile

3. **Accountant**
   - Manage all transactions
   - Supervise all expenses
   - Manage office running costs
   - Manage daily income and expenses
   - Generate daily and monthly financial reports (PDF)
   - View profile (read-only), upload profile picture (auto-resize to <1MB)
   - View archived data (read-only)




## Tech Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5, Font Awesome 6, Chart.js
- **Backend**: PHP 8+
- **Database**: MySQL
- **Security**: CSRF protection, SQL injection prevention, password hashing

## Installation

1. **Database Setup**
   - Create a MySQL database named `petrol_station`
   - Import the `database.sql` file to create all tables and sample data

2. **Configuration**
   - Edit `config/database.php` to set your database credentials:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'petrol_station');
     define('DB_USER', 'root');
     define('DB_PASS', 'your_password');
     ```

3. **Default Login Credentials**
   - Chief Manager: `chief` / `12345678`
   - Manager: `meneja` / `12345678`
   - Accountant: `treasurer` / `12345678`


4. **Upload Directory**
   - Ensure the `uploads/` directory has write permissions

5. **Run the Application**
   - Place the files in your web server's document root (e.g., XAMPP htdocs, Apache www)
   - Access via `http://localhost/petrol-station/`

## Security Features

- **CSRF Protection**: All forms include CSRF tokens
- **SQL Injection Prevention**: Prepared statements used throughout
- **Password Hashing**: bcrypt password hashing
- **Session Security**: Secure session configuration
- **Input Sanitization**: All user input is sanitized
- **Role-Based Access Control**: Strict role verification for all pages
- **Image Upload Validation**: File type and size validation for profile pictures
- **Auto Image Compression**: Profile images are automatically resized and compressed to <1MB

## Directory Structure

```
petrol-station/
├── config/
│   └── database.php         # Database connection and security utilities
├── chief_manager/
│   └── dashboard.php         # Chief Manager dashboard
├── manager/
│   └── dashboard.php         # Manager dashboard
├── accountant/
│   └── dashboard.php         # Accountant dashboard
├── pump_attendant/
│   └── dashboard.php         # Pump Attendant dashboard
├── security/
│   └── dashboard.php         # Security dashboard
├── reports/
│   ├── daily_report.php      # Daily report generator
│   └── monthly_report.php    # Monthly report generator
├── uploads/
│   ├── profiles/              # Profile images
│   └── receipts/             # Receipt images
├── index.php                # Login page
├── logout.php               # Logout handler

```

## Screenshots

The system includes:
- Modern, responsive Bootstrap-based UI
- Interactive Chart.js visualizations
- Real-time sales and expense tracking
- PDF report generation
- Profile image upload with automatic compression

## License

This project is for educational and commercial use.
