# Daily Log - Mileage Tracker

A simple, efficient PHP/MySQL application for tracking vehicle mileage, fuel consumption, and maintenance costs.

## Features
- **Multi-Vehicle Support**: Manage multiple vehicles with ease.
- **Fuel Log**: Track fuel entries, costs, and consumption stats.
- **Maintenance Tracker**: Keep a record of maintenance costs and services.
- **Analytics**: Visual charts for cost and consumption analysis.
- **Mobile Friendly**: Responsive design for use on any device.

## Requirements
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Web Server (Apache, Nginx, or Laravel Valet)

## Installation

1. **Database Setup**
   - Create a MySQL database named `dailylogdb`.
   - Import the `schema.sql` file into your database.
     ```bash
     mysql -u root -p dailylogdb < schema.sql
     ```

2. **Configuration**
   - Open `config/db.php`.
   - Update the database credentials if necessary (default is `root`/`root` for local development).

3. **Running the Application**
   - **Using Laravel Valet**:
     - Park or link the directory.
     - Visit `http://dailylog.test`.
   - **Using PHP Built-in Server**:
     ```bash
     php -S localhost:8000
     ```
     - Visit `http://localhost:8000`.

## Directory Structure
- `api/`: Backend API endpoints (JSON).
- `assets/`: Static assets (JS, CSS).
- `config/`: Configuration files (Database).
- `static/`: Publicly accessible files (Images, Uploads).
- `*.php`: Frontend pages (Login, Register, Dashboard).
