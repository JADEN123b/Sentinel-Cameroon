# Sentinel Cameroon - Community Safety Platform

## Overview
Sentinel Cameroon is a modern, PHP-based community safety platform that enables citizens to report incidents, track safety alerts, and connect with verified partners across Cameroon.

## ✨ Features
- **Incident Reporting**: Easy-to-use forms for reporting various types of incidents
- **Live Incident Map**: Interactive map showing real-time incident locations
- **Community Partners**: Directory of verified safety partners and organizations
- **User Authentication**: Secure login/registration system with role-based access
- **Real-time Updates**: Status tracking and notifications for incidents
- **Mobile Responsive**: Works seamlessly on desktop and mobile devices
- **File Uploads**: Support for images, videos, and documents with incident reports

## 🏗️ Project Structure

```
stitch/
├── database/                    # Database configuration and schema
│   ├── config.php              # Database connection class
│   └── schema.sql              # MySQL database structure
├── includes/                    # Reusable components
│   ├── header.php              # Navigation header with authentication
│   └── footer.php              # Common footer
├── api/                         # API endpoints
│   ├── contact.php            # Partner contact API
│   └── update_status.php     # Incident status update API
├── assets/                      # Static assets
│   └── css/
│       └── main.css          # Main stylesheet (replaces Tailwind)
├── uploads/                     # File upload directory
│   └── incidents/           # Incident attachment uploads
├── index.php                   # Landing page
├── login.php                   # User login
├── register.php                # User registration
├── dashboard.php               # User dashboard
├── incidents.php               # Incident listing and filtering
├── incident_detail.php          # Individual incident view
├── report_incident.php         # New incident reporting
├── map.php                    # Live incident map
├── partners.php                # Community partners directory
├── profile.php                 # User profile management
└── logout.php                 # User logout
```

## 🚀 Quick Start

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- PHP extensions: PDO, MySQLi, GD

### Installation

1. **Database Setup**
   ```bash
   mysql -u root -p < database/schema.sql
   ```

2. **Configure Database**
   Edit `database/config.php` with your database credentials:
   ```php
   private $host = 'localhost';
   private $dbname = 'sentinel_cameroon';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

3. **Web Server Setup**
   - Place files in your web root directory
   - Ensure `uploads/` directory is writable by web server
   - Set up virtual host if needed

4. **Access Application**
   - Navigate to `http://localhost/` in your browser
   - Register a new account or use existing credentials

## 🎯 Key Features

### User Roles
- **Citizen**: Report incidents, view map, access community partners
- **Authority**: Manage incidents, update status, access admin features
- **Admin**: Full system administration capabilities

### Incident Management
- **Types**: Theft, Assault, Accident, Fire, Medical, Other
- **Severity Levels**: Low, Medium, High, Critical
- **Status Tracking**: Reported, Verified, Investigating, Resolved
- **Location Support**: GPS coordinates and address input

### Security Features
- **Password Hashing**: Secure password storage using PHP's password_hash()
- **Session Management**: Secure session handling with automatic cleanup
- **Input Validation**: Comprehensive validation and sanitization
- **SQL Injection Protection**: Prepared statements throughout
- **File Upload Security**: Type validation and secure storage

## 🗄️ Database Schema

### Core Tables
- **users**: User accounts and authentication
- **incidents**: Incident reports with full details
- **incident_attachments**: File attachments for incidents
- **partners**: Verified community partners
- **alerts**: System-wide alerts and notifications
- **user_sessions**: Secure session management

## 🎨 Frontend Features

### Responsive Design
- **Mobile-First**: Optimized for mobile devices
- **Progressive Enhancement**: Works without JavaScript, enhanced with it
- **Modern CSS**: Clean, maintainable CSS without Tailwind
- **Accessibility**: Semantic HTML5, ARIA labels, keyboard navigation

### Interactive Elements
- **Live Maps**: OpenStreetMap integration for incident visualization
- **Real-time Updates**: AJAX-based status updates
- **File Previews**: Image and document preview capabilities
- **Form Validation**: Client-side and server-side validation

## 🔧 API Endpoints

### Contact API
```
POST /api/contact.php
- Send messages to community partners
- Validates input and stores contact requests
```

### Status Update API
```
POST /api/update_status.php
- Update incident status (authority users only)
- Real-time status changes
```

## 📱 Mobile Compatibility

The platform is fully responsive and works on:
- **Smartphones**: iOS Safari, Chrome Mobile
- **Tablets**: iPad, Android tablets
- **Desktop**: Chrome, Firefox, Safari, Edge

## 🔒 Security Considerations

- **Input Sanitization**: All user inputs are sanitized
- **Authentication**: Secure session-based authentication
- **Authorization**: Role-based access control
- **File Security**: Upload validation and secure storage
- **CSRF Protection**: Token-based form protection (recommended for production)

## 🚀 Performance Optimization

- **Database Indexes**: Optimized queries for large datasets
- **Lazy Loading**: Efficient data loading for maps and lists
- **Caching**: Session and query result caching
- **Minified Assets**: Optimized CSS and JavaScript

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is open-source and available under the MIT License.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation for common issues

---

**Built with ❤️ for the safety of Cameroon communities**
