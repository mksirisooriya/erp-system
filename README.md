# ERP System - Full Stack Web Application

A comprehensive Enterprise Resource Planning (ERP) system built with PHP, MySQL, Bootstrap, and JavaScript for managing customers, items, and generating reports.

## Features

### Customer Management
- Add, edit, delete, and view customers
- Form validation with error handling
- Customer search functionality
- District-based customer organization

### Item Management
- Add, edit, delete, and view inventory items
- Category and subcategory organization
- Stock quantity tracking
- Item code uniqueness validation

### Reporting System
- **Invoice Report**: Date-range based invoice analysis
- **Invoice Item Report**: Detailed line-item reporting
- **Item Report**: Current inventory status with stock indicators

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5.3, HTML5, CSS3
- **JavaScript**: Vanilla JS for client-side interactions
- **Icons**: Font Awesome 6.0

## Installation & Setup

### Prerequisites
- XAMPP/WAMP/MAMP (PHP 7.4+ and MySQL 5.7+)
- Web browser (Chrome, Firefox, Safari, Edge)

### Local Environment Setup

1. **Clone or Download the Project**
   ```bash
   git clone [your-repository-url]
   cd erp-system
   ```

2. **Copy to Web Server Directory**
   
   **For XAMPP Users:**
   - Copy the entire project folder to your XAMPP installation directory
   - Default path: `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac)
   - The project should be located at: `C:\xampp\htdocs\erp-system\`
   
   **For WAMP Users:**
   - Copy to: `C:\wamp64\www\erp-system\`
   
   **For MAMP Users:**
   - Copy to: `/Applications/MAMP/htdocs/erp-system/`

3. **Start Your Local Server**
   - Start XAMPP/WAMP/MAMP
   - Ensure Apache and MySQL services are running
   - Access phpMyAdmin at: `http://localhost/phpmyadmin`

4. **Set Up Database**
   - Create a new database (e.g., `erp_system`)
   - Import the provided SQL file or create tables as needed
   - Update database connection settings in your PHP configuration files

5. **Access the Application**
   - Open your web browser
   - Navigate to: `http://localhost/erp-system/`
   - The application should now be accessible

## Configuration

Make sure to update your database connection settings in the configuration files to match your local environment:

```php
$host = 'localhost';
$username = 'root';
$password = ''; // Default for XAMPP
$database = 'erp_system';
```

## Usage

Once installed and configured, you can access the different modules through the main navigation:
- Customer Management
- Item Management  
- Reporting System

## Support

For issues or questions, please refer to the documentation or create an issue in the project repository.