# MarketNearMe - Local Marketplace Platform

A full-featured, secure, and responsive online marketplace platform built with PHP and MySQL. MarketNearMe connects local buyers and sellers within their communities, supporting multiple currencies and providing a seamless trading experience.

**PHP Version:** 8.0+
**MySQL Version:** 5.7+
**Bootstrap Version:** 5.1
**License:** MIT

---

## Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [Project Structure](#project-structure)
- [Security Features](#security-features)
- [User Roles](#user-roles)
- [Currency Support](#currency-support)
- [Usage Guide](#usage-guide)
- [Admin Panel](#admin-panel)
- [Database Schema](#database-schema)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

---

## Features

### User Features
- Secure authentication system (registration, login, password reset)
- Create and manage listings with images, descriptions, and pricing
- Advanced search with filters (category, price, location, currency, condition)
- Real-time messaging between buyers and sellers
- Favorites system to save listings for later
- User dashboard with listing statistics and message tracking
- Multi-currency support for 14+ currencies
- Fully responsive design for mobile, tablet, and desktop
- Image upload support (up to 5 images per listing)
- Report system for flagging suspicious listings
- Profile management with avatar upload
- Password strength meter and validation

### Admin Features
- Comprehensive admin dashboard with platform statistics
- User management (view, suspend, activate, delete users)
- Listing management (review, toggle status, remove listings)
- Featured listings management with expiry dates
- Category management (add, edit, delete categories)
- Report management (review and resolve user reports)
- Contact message management from contact form
- Security log monitoring
- Monthly listing trends and analytics
- Top sellers tracking

---

## Technology Stack

### Backend
- PHP 8.0+ (server-side scripting)
- MySQL 5.7+ (database management)
- PDO (database abstraction layer with prepared statements)
- Bcrypt (password hashing with cost factor 12)

### Frontend
- Bootstrap 5.1 (CSS framework)
- Font Awesome 6 (icon library)
- Vanilla JavaScript (client-side functionality)
- CSS3 (custom styles, animations, and responsive design)

### Security
- CSRF protection tokens on all forms
- XSS prevention through input sanitization and output escaping
- SQL injection protection via PDO prepared statements
- Session security with IP validation and session regeneration
- Rate limiting on login attempts
- File upload validation with MIME type and size verification
- Security event logging

---


