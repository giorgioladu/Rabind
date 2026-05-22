# Rabind - RADIUS Management Control Panel

[![PHP](https://img.shields.io/badge/PHP-8+-blue)](https://www.php.net/)
[![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-3.x-green)](https://freeradius.org/)
[![MikroTik](https://img.shields.io/badge/MikroTik-Compatible-orange)](https://mikrotik.com/)
[![License: AGPL-3.0](https://img.shields.io/badge/License-AGPL%203.0-blue.svg)](LICENSE)

**Rabind** is a lightweight, production-ready web control panel for managing WiFi voucher users and RADIUS authentication infrastructure. It provides real-time monitoring, user session management, and administrative controls for small to medium-sized hotspot deployments, hotels, B&Bs, and ISP guest networks.

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Features](#features)
  - [v0.6.0 New Features](#v060-new-features)
- [Technical Stack](#technical-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
  - [Prerequisites](#prerequisites)
  - [Quick Start](#quick-start)
  - [Configuration](#configuration)
  - [Database Setup](#database-setup)
- [API Integration](#api-integration)
  - [RADIUS Accounting](#radius-accounting)
  - [Change of Authorization (CoA)](#change-of-authorization-coa)
- [User Management](#user-management)
  - [Voucher Creation](#voucher-creation)
  - [User States](#user-states)
  - [MAC Binding](#mac-binding)
- [Monitoring & Analytics](#monitoring--analytics)
- [Security Features](#security-features)
  - [CSRF Protection](#csrf-protection)
  - [Brute-Force Protection](#brute-force-protection)
  - [Audit Logging](#audit-logging)
  - [Session Management](#session-management)
- [Administrative Features](#administrative-features)
  - [Admin Management](#admin-management)
  - [Audit Trail](#audit-trail)
- [File Structure](#file-structure)
- [Configuration Reference](#configuration-reference)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

Rabind bridges FreeRADIUS and MikroTik RouterOS to provide a unified interface for:

- **User Management**: Create, enable, disable, and delete voucher accounts
- **Real-Time Monitoring**: Track active sessions, traffic consumption, and login attempts
- **Operational Control**: Force user disconnects (CoA), reset MAC bindings, manage traffic quotas
- **Audit & Compliance**: Comprehensive logging of all administrative actions
- **Authentication**: Multi-admin support with granular permissions and audit trails

The system is designed with simplicity and minimal dependencies in mind, making it ideal for deployment on modest hardware while maintaining full functionality.

**Target Users**: Network administrators, ISP support teams, hotel IT staff, hotspot operators

---

## Architecture

### System Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                     USER AUTHENTICATION FLOW                     │
└─────────────────────────────────────────────────────────────────┘

Client Device
    ↓ (WiFi Connect)
    ├─→ MikroTik RouterOS (NAS)
    │   ├─→ RADIUS Authentication Request (UDP:1812)
    │   │
    │   └─→ FreeRADIUS Server
    │       ├─→ Query radcheck/radusergroup
    │       └─→ Return Accept/Reject
    │
    ├─→ Session Accounting (RADIUS Acct, UDP:1813)
    │   └─→ FreeRADIUS → MySQL (radacct table)
    │
    └─→ Online Session Active

┌─────────────────────────────────────────────────────────────────┐
│                    ADMIN MANAGEMENT FLOW                        │
└─────────────────────────────────────────────────────────────────┘

Admin Panel (Rabind)
    ├─→ Web Interface (PHP)
    │   ├─→ App Database (rabind_users, audit_log, login_attempts)
    │   └─→ RADIUS Database (radcheck, radacct, radusergroup)
    │
    ├─→ User Operations
    │   ├─→ Create/Edit/Delete users (radcheck insert/update)
    │   ├─→ MAC Binding (radreply configuration)
    │   └─→ Group Management (radusergroup relationships)
    │
    ├─→ Monitoring
    │   ├─→ Query radacct for active sessions
    │   ├─→ Track traffic consumption
    │   └─→ Audit all administrative changes
    │
    └─→ Live Control
        ├─→ Change of Authorization (CoA) via UDP:3799
        └─→ Force disconnection/reset commands
```

### Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| **Web Server** | Apache/Nginx | 2.4+ / 1.18+ |
| **Application** | PHP | 8.0+ |
| **Authentication** | FreeRADIUS | 3.x |
| **NAS/Router** | MikroTik RouterOS | 6.x+ |
| **Database** | MySQL / MariaDB | 5.7+ / 10.3+ |
| **Frontend** | HTML5 / CSS3 / Bootstrap | 5.x |
| **AJAX** | Vanilla JavaScript | ES6+ |

---

## Features

### Core Functionality

#### User Management
- ✅ Create unlimited voucher accounts with custom credentials
- ✅ Enable / Disable user accounts (soft delete)
- ✅ Permanent account deletion (removes from both app and RADIUS DB)
- ✅ MAC address binding per user (hardware-level access control)
- ✅ User metadata: notes, creation timestamp, assigned groups
- ✅ Batch operations support

#### Session Monitoring
- ✅ Real-time active users list (auto-refresh every 5 seconds)
- ✅ Per-user session details: IP, MAC, connected duration
- ✅ Traffic consumption tracking (upload/download bytes)
- ✅ Top users by traffic volume
- ✅ Users with highest login/logout frequency
- ✅ Failed login attempt logs with timestamp

#### Operational Control
- ✅ **Change of Authorization (CoA)**: Force user disconnection
- ✅ MAC address reset (unbind device)
- ✅ Traffic quota reset
- ✅ Session timeout enforcement
- ✅ Disconnect-All button for emergency situations

#### Network Administration
- ✅ NAS (Network Access Server) management
- ✅ RADIUS group configuration (check & reply attributes)
- ✅ User group membership assignment
- ✅ Dynamic attributes per group

#### Analytics
- ✅ Login attempt tracking (including failed attempts)
- ✅ User activity timeline
- ✅ Export session data to CSV/JSON
- ✅ Historical traffic reports

---

### v0.6.0 New Features

Version 0.6.0 introduces **enterprise-grade security and administrative controls**, making Rabind suitable for multi-admin deployments and compliance-heavy environments.

#### 🔐 Security Enhancements

**CSRF Token Protection**
- All state-modifying operations (POST/PUT/DELETE) are protected with cryptographically secure CSRF tokens
- Tokens are generated per-session using `random_bytes(32)` and stored in the session superglobal
- Automatic token validation on form submission prevents cross-site request forgery attacks
- Token stored in `$_SESSION['csrf_token']` and transmitted via hidden form field

**Brute-Force Attack Mitigation**
- Login page now tracks failed authentication attempts per IP address
- Configurable attempt threshold (default: 5 attempts per 15 minutes)
- Automatic temporary lockout after threshold exceeded
- Failed attempt logging with timestamp, IP, username, and user agent
- Stored in `login_attempts` table for audit trail
- Auto-cleanup of old entries to prevent table bloat

**Implementation Details**:
```php
// Brute-force check on login attempt
$loginAttempts = checkLoginAttempts($ip_address);
if ($loginAttempts >= LOGIN_ATTEMPT_THRESHOLD) {
    // Lockout for LOCKOUT_DURATION (default: 900 seconds)
    redirectWithError('Too many login attempts. Try again later.');
}

// Log failed attempt
logLoginAttempt($username, $ip_address, false);
```

#### 👥 Multi-Admin Support

**Admin Management System**
- Create, edit, and delete administrator accounts
- Store admin credentials with bcrypt hashing (`password_hash()`)
- Admin panel with full CRUD operations
- Admin activity audit trail
- Permission-level distinction (planned for future versions)

**Admin Account Structure**:
```sql
CREATE TABLE rabind.admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,  -- bcrypt hash
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);
```

#### 📋 Audit Logging System

**Comprehensive Activity Tracking**
- All administrative actions are logged to `audit_log` table
- Tracked events: user create/edit/delete, admin operations, CoA actions, MAC resets
- Each log entry includes: admin username, action type, target object, timestamp, IP address
- 30-day audit trail retention (configurable)
- Full-text search across audit logs
- Export audit reports to CSV for compliance verification

**Audit Log Structure**:
```sql
CREATE TABLE rabind.audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(255),        -- 'user_create', 'user_delete', 'coa_sent', etc.
    target_username VARCHAR(255),
    details JSON,               -- Additional context
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);
```

**Logged Actions**:
- User creation with assigned groups
- User account disable/enable
- User deletion with confirmation
- MAC address reset
- Traffic quota reset
- CoA commands executed
- Admin login/logout
- Admin account creation/modification
- Failed login attempts

#### 🎨 UI/UX Improvements

**Enhanced Interface**
- Redesigned admin panel with Bootstrap 5.x styling
- Improved responsiveness for mobile/tablet access
- Better visual hierarchy and information organization
- Modal dialogs for session details and confirmations
- Inline editing for user metadata
- Search and filter capabilities across tables
- Color-coded status indicators (online/offline/disabled)

**Admin Dashboard**
- Quick statistics: active users, failed logins, admin actions
- Recent activity timeline
- System health monitoring
- Admin list with last login info

---

## Technical Stack

### Backend Architecture

```
PHP 8.0+ Application Layer
├── Authentication Module (lib/auth.php)
│   ├── Login/Logout handling
│   ├── Session management
│   ├── CSRF token generation/validation
│   └── Brute-force detection
│
├── Database Layer (lib/db.php)
│   ├── PDO abstraction for MySQL/MariaDB
│   ├── Prepared statements (SQL injection protection)
│   ├── Transaction support
│   └── Connection pooling
│
├── RADIUS Integration (lib/radius_pod.php)
│   ├── FreeRADIUS protocol implementation
│   ├── Accounting record parsing
│   ├── Group/attribute management
│   └── Stale session cleanup
│
└── CoA Module (lib/coa.php)
    ├── UDP packet construction
    ├── Disconnect-Request commands
    └── MikroTik RouterOS integration
```

### Database Schema

**RADIUS Database** (read/write to existing FreeRADIUS installation)
```
radcheck      - User authentication credentials
radreply      - User-specific RADIUS attributes
radusergroup  - User-to-group mappings
radgroupcheck - Group authentication attributes
radgroupreply - Group-level RADIUS attributes
radacct       - Session accounting records (authentication, disconnects, traffic)
```

**Application Database** (Rabind-specific)
```
rabind_users  - Metadata: notes, type (active/disabled), timestamps
admins        - Administrator accounts with bcrypt passwords
audit_log     - Complete audit trail of all admin actions
login_attempts - Failed login tracking for brute-force protection
```

### Frontend Architecture

- **Framework**: Bootstrap 5.x (no build tools required)
- **JavaScript**: Vanilla ES6+ (no jQuery dependencies)
- **AJAX**: Fetch API for real-time updates
- **Templates**: PHP-based server-side rendering with template inheritance
- **Charts**: Simple canvas/SVG for traffic visualization

---

## System Requirements

### Minimum Hardware
- **CPU**: 1 core @ 1 GHz
- **RAM**: 512 MB
- **Storage**: 5 GB (grows with accounting records)
- **Network**: 100 Mbps Ethernet

### Recommended Hardware
- **CPU**: 2 cores @ 2 GHz
- **RAM**: 2 GB
- **Storage**: 20 GB SSD
- **Network**: 1 Gbps Ethernet

### Software Requirements

| Component | Minimum | Recommended | Note |
|-----------|---------|-------------|------|
| **PHP** | 8.0.0 | 8.2+ | With PDO MySQL extension |
| **MySQL** | 5.7.0 | 8.0+ | Or MariaDB 10.3+ |
| **FreeRADIUS** | 3.0.0 | 3.2+ | Must have SQL support |
| **MikroTik RouterOS** | 6.0 | 7.x | With RADIUS enabled |
| **Web Server** | Apache 2.4 or Nginx 1.18 | Apache 2.4.41+ | With PHP-FPM for Nginx |

### Required PHP Extensions
- `php-pdo`
- `php-pdo-mysql`
- `php-json`
- `php-mbstring`
- `php-session`

### Network Requirements
- **Port 3799/UDP**: RADIUS Change of Authorization (MikroTik to Rabind)
- **Port 80 or 443**: HTTP/HTTPS (admin interface)
- **Port 3306**: MySQL database connection (if not local)

---

## Installation

### Prerequisites

1. **FreeRADIUS Installation** (if not already present)
   ```bash
   # Debian/Ubuntu
   sudo apt-get install freeradius freeradius-mysql

   # RHEL/CentOS
   sudo yum install freeradius freeradius-mysql
   ```

2. **Verify FreeRADIUS SQL Module**
   ```bash
   # Check if sql module is loaded
   sudo freeradius -X 2>&1 | grep -i "sql\|mysql"

   # Should show: Loading module "sql" from file ...
   ```

3. **MySQL/MariaDB Running**
   ```bash
   sudo systemctl start mysql  # or mariadb
   sudo systemctl enable mysql
   ```

4. **Web Server with PHP 8+**
   ```bash
   # For Apache
   sudo apt-get install apache2 php php-mysql php-pdo libapache2-mod-php

   # For Nginx with PHP-FPM
   sudo apt-get install nginx php-fpm php-mysql php-pdo
   ```

### Quick Start

**Step 1: Clone Repository**
```bash
cd /var/www/html
sudo git clone https://github.com/giorgioladu/Rabind.git rabind
sudo chown -R www-data:www-data rabind
sudo chmod -R 755 rabind
```

**Step 2: Create Databases**
```bash
# Create application database
mysql -u root -p << EOF
CREATE DATABASE rabind CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rabind'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON rabind.* TO 'rabind'@'localhost';
FLUSH PRIVILEGES;
EOF

# Import Rabind schema
mysql -u rabind -p rabind < /var/www/html/rabind/DB/DB.sql

# Create user for RADIUS database access
# (FreeRADIUS will have its own database setup)
mysql -u root -p << EOF
GRANT SELECT, INSERT, UPDATE, DELETE ON radius.* TO 'rabind'@'localhost';
FLUSH PRIVILEGES;
EOF
```

**Step 3: Configure Rabind**
```bash
# Edit configuration file
sudo nano /var/www/html/rabind/lib/config.php
```

**Step 4: Create Initial Admin Account**
```php
<?php
// Run from command line to create first admin
php -r "
    require_once('/var/www/html/rabind/lib/db.php');
    \$username = 'admin';
    \$password = 'YourSecurePassword123!';
    \$passwordHash = password_hash(\$password, PASSWORD_DEFAULT);
    
    \$stmt = \$appDb->prepare('INSERT INTO admins (username, password, email) VALUES (?, ?, ?)');
    \$stmt->execute([\$username, \$passwordHash, 'admin@example.com']);
    echo \"Admin account created: \$username\\n\";
?>
"
```

### Configuration

Edit `/var/www/html/rabind/lib/config.php`:

```php
<?php

/* RADIUS DATABASE (existing FreeRADIUS installation) */
define('RADIUS_DB_HOST', 'localhost');
define('RADIUS_DB_NAME', 'radius');
define('RADIUS_DB_USER', 'radius');
define('RADIUS_DB_PASS', 'radius_password');

/* RABIND APPLICATION DATABASE */
define('APP_DB_HOST', 'localhost');
define('APP_DB_NAME', 'rabind');
define('APP_DB_USER', 'rabind');
define('APP_DB_PASS', 'rabind_password');

/* APPLICATION SETTINGS */
define('APP_NAME', 'RaBind');
define('APP_VERSION', '0.6.0');
define('APP_ENV', 'production');          // or 'development'
define('APP_DEBUG', false);                // or true for debugging
define('BASE_URL', '/rabind/');

/* MIKROTIK / NAS CONFIGURATION */
define('RADIUS_NAS_IP', '192.168.88.1');   // MikroTik IP
define('RADIUS_NAS_PORT', '3799');         // CoA port
define('RADIUS_SECRET', 'your_radius_secret');  // Must match MikroTik setting

/* WiFi CREDENTIALS (for display/documentation) */
define('SITE_WIFI_SSID', 'GuestNetwork');
define('SITE_WIFI_PASSWORD', 'GuestPassword123');

/* SECURITY */
define('SESSION_TIMEOUT', 1800);  // 30 minutes
// Uncomment for HTTPS-only cookies
// ini_set('session.cookie_secure', 1);
```

### Database Setup

**Import Database Schema**
```bash
mysql -u rabind -p rabind < DB/DB.sql
```

**Schema Overview**
```sql
-- Application tables
CREATE TABLE rabind_users (
    username VARCHAR(255) PRIMARY KEY,
    type ENUM('active', 'disabled') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) UNIQUE,
    password VARCHAR(255),          -- bcrypt hash
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE
);

CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    action VARCHAR(255),
    target_username VARCHAR(255),
    details JSON,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45),
    username VARCHAR(255),
    success BOOLEAN,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, timestamp)
);
```

**Verify Database Permissions**
```bash
mysql -u radius -p radius -e "SELECT * FROM radacct LIMIT 1;"
mysql -u rabind -p rabind -e "SELECT * FROM rabind_users LIMIT 1;"
```

---

## API Integration

### RADIUS Accounting

**Connection Flow**
```
MikroTik (RADIUS Client)
    ↓ (UDP:1813)
FreeRADIUS Server
    ↓ (SQL Insert)
MySQL: radacct table
    ↓ (SELECT via Rabind)
Admin Dashboard
```

**Accounting Fields Tracked** (in radacct table)
```sql
acctessionid      - Session ID
username          - User identifier
nasipaddress      - NAS IP (MikroTik router)
callingstationid  - User MAC address
acctinputoctets   - Bytes downloaded
acctoutputoctets  - Bytes uploaded
acctsessiontime   - Connection duration (seconds)
acctstartime      - Connection start time
acctstoptime      - Connection stop time (NULL if active)
acctstatustype    - Start/Stop/Interim-Update
```

**Example Query** (Active Users)
```sql
SELECT 
    username,
    nasipaddress,
    callingstationid as mac,
    acctinputoctets + acctoutputoctets as total_bytes,
    UNIX_TIMESTAMP() - UNIX_TIMESTAMP(acctstartime) as duration_sec,
    acctstartime as session_start
FROM radacct
WHERE acctstoptime IS NULL
ORDER BY acctstartime DESC;
```

### Change of Authorization (CoA)

CoA allows the RADIUS server to modify user sessions in real-time without requiring re-authentication.

**Protocol**: RFC 5407 (RADIUS Change of Authorization)

**Implementation** (lib/coa.php)
```php
<?php
class RadiusCoA {
    private $nas_ip;
    private $nas_port = 3799;
    private $secret;
    
    public function __construct($nas_ip, $nas_port, $secret) {
        $this->nas_ip = $nas_ip;
        $this->nas_port = $nas_port;
        $this->secret = $secret;
    }
    
    /**
     * Send Disconnect-Request to terminate user session
     * 
     * @param string $username Username to disconnect
     * @param string $nasip Session NAS IP
     * @return bool Success status
     */
    public function disconnectUser($username, $nasip) {
        $packet = $this->buildDisconnectRequest($username, $nasip);
        $socket = udp_socket();
        $sent = sendto($socket, $packet, $this->nas_ip, $this->nas_port);
        close($socket);
        
        return $sent > 0;
    }
    
    private function buildDisconnectRequest($username, $nasip) {
        // RADIUS packet construction per RFC 5407
        // Code: Disconnect-Request (40)
        // Attributes: User-Name, Acct-Session-Id
    }
}
?>
```

**MikroTik Configuration** (Required)
```mikrotik
/radius
set incoming=yes
/ip firewall nat
add protocol=udp dst-port=3799 in-interface=<INTERFACE> action=accept
```

**Disconnect Operations**
- **Force Disconnect**: Immediately terminates user session
- **MAC Reset**: Clears MAC binding, allows reconnection with different device
- **Traffic Reset**: Zeroes quota counters for testing
- **Emergency Disconnect-All**: Disconnects all users (for network maintenance)

---

## User Management

### Voucher Creation

**User Creation Dialog**
```
Parameters:
├── Username (required): Unique identifier
├── Password (required): Authentication credential
├── Initial Group (optional): User group assignment
├── Notes (optional): Admin metadata
└── MAC Binding (optional): Hardware access control
```

**Creating a Voucher User** (via Admin Panel)
1. Navigate to "Users" → "New Voucher"
2. Enter username (e.g., `guest_room_101`)
3. Generate secure password (system provides suggestion)
4. Assign to group (optional): `guests`, `premium`, `bandwidth-limited`, etc.
5. Add notes: `Hotel Room 101 - Valid until 2026-06-30`
6. (Optional) Bind to MAC address: `AA:BB:CC:DD:EE:FF`
7. Click "Create"

**Database Operations**
```sql
-- Insert user into application DB
INSERT INTO rabind.rabind_users (username, type, notes, created_at)
VALUES ('guest_room_101', 'active', 'Hotel Room 101', NOW());

-- Insert credentials into RADIUS DB
INSERT INTO radius.radcheck (username, attribute, op, value)
VALUES ('guest_room_101', 'Cleartext-Password', ':=', 'SecurePassword123');

-- Assign to user group
INSERT INTO radius.radusergroup (username, groupname, priority)
VALUES ('guest_room_101', 'guests', 1);

-- Bind MAC address (optional)
INSERT INTO radius.radreply (username, attribute, op, value)
VALUES ('guest_room_101', 'Restrict-To-MAC', ':=', 'AA:BB:CC:DD:EE:FF');

-- Log audit entry
INSERT INTO rabind.audit_log (admin_id, action, target_username, timestamp, ip_address)
VALUES (1, 'user_create', 'guest_room_101', NOW(), '192.168.1.100');
```

### User States

**Active User**
- Can authenticate via WiFi
- Appears in "Online Users" and "All Users" lists
- Full session accounting
- Subject to traffic quotas/time limits

**Disabled User**
- Cannot authenticate (credentials rejected by RADIUS)
- Existing sessions not terminated automatically
- Appears in "Disabled Users" list
- Can be re-enabled (no data loss)
- Useful for temporary deactivation

**Deleted User**
- Permanently removed from both application and RADIUS databases
- Cannot be recovered
- Session history retained in radacct (cannot be undone)
- Recommended: disable first, verify, then delete

### MAC Binding

**Purpose**: Restrict user account to specific hardware devices

**Typical Use Cases**
- Hotel: One voucher per room TV
- Hotspot: Prevent credential sharing
- ISP: Device-locked promotional packages

**Implementation**
```sql
-- Single MAC binding
INSERT INTO radius.radreply (username, attribute, op, value)
VALUES ('user1', 'Restrict-To-MAC', ':=', 'AA:BB:CC:DD:EE:FF');

-- Multiple MACs (MikroTik extension)
INSERT INTO radius.radreply (username, attribute, op, value)
VALUES 
('user1', 'Restrict-To-MAC', ':=', 'AA:BB:CC:DD:EE:FF'),
('user1', 'Restrict-To-MAC', '+=', 'AA:BB:CC:DD:EE:00');
```

**Reset MAC Binding** (via Admin Panel)
1. Click user → "Session Details"
2. Click "Reset MAC Binding"
3. Confirm action
4. Current MAC restriction cleared; user can reconnect from new device

---

## Monitoring & Analytics

### Real-Time Monitoring

**Online Users Panel** (Auto-Refresh: 5 seconds)
```
Online Users (8)
┌─────────────────────────────────────┐
│ Username    │ IP        │ Duration  │
├─────────────────────────────────────┤
│ guest_001   │ 10.0.0.45 │ 0:23:14   │ ← Most recent
│ guest_002   │ 10.0.0.46 │ 1:45:03   │
│ premium_1   │ 10.0.0.47 │ 5:32:18   │
└─────────────────────────────────────┘

Traffic Summary
Total Download: 2.4 GB
Total Upload: 340 MB
```

**Session Details Modal**
```
Username: guest_001
Status: Online
Session Start: 2026-05-22 14:35:22
Duration: 23 minutes 14 seconds
MAC Address: 52:54:00:12:34:56
IP Address: 10.0.0.45
Download: 847.3 MB
Upload: 112.5 MB
Average Speed: 2.1 Mbps

[Disconnect] [Reset MAC] [View Logs]
```

### Traffic Analytics

**Top Users by Traffic** (Sortable)
```sql
SELECT 
    username,
    SUM(acctinputoctets) as total_download,
    SUM(acctoutputoctets) as total_upload,
    SUM(acctinputoctets + acctoutputoctets) as total_bytes,
    COUNT(*) as session_count
FROM radacct
WHERE acctstoptime > DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY username
ORDER BY total_bytes DESC
LIMIT 20;
```

**Login Statistics**
```
Failed Login Attempts (Last 24h): 12
Top Failed IPs: 203.0.113.45 (5 attempts)
Failed Users: admin (attempted 3x)
Current Lockout Status: None

Login Attempt Timeline (GraphQL-style)
00:00-06:00: 2 failed
06:00-12:00: 5 failed
12:00-18:00: 3 failed
18:00-24:00: 2 failed
```

### Export & Reporting

**Session Export** (CSV)
```csv
username,status,start_time,stop_time,duration_sec,input_octets,output_octets,mac,nas_ip
guest_001,stop,2026-05-22 10:00:00,2026-05-22 12:30:00,9000,1048576,524288,AA:BB:CC:DD:EE:FF,192.168.88.1
guest_002,stop,2026-05-22 11:15:00,2026-05-22 13:45:00,9000,2097152,1048576,AA:BB:CC:DD:EE:00,192.168.88.1
```

**Audit Log Export** (JSON)
```json
[
  {
    "timestamp": "2026-05-22T14:35:22Z",
    "admin": "admin1",
    "action": "user_create",
    "target": "guest_001",
    "ip_address": "192.168.1.100",
    "details": {
      "group": "guests",
      "mac_binding": "AA:BB:CC:DD:EE:FF"
    }
  }
]
```

---

## Security Features

### CSRF Protection

**Implementation**
- Session-based token generation
- Cryptographically secure random bytes (`random_bytes(32)`)
- Token validation on form submission
- One token per session (refresh on each request optional)

**Token Usage**
```html
<form method="POST" action="/rabind/user_create.php">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <input type="text" name="username" required>
    <button type="submit">Create User</button>
</form>
```

**Server Validation**
```php
<?php
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
// Process form
?>
```

### Brute-Force Protection

**Algorithm**
```
If login attempt from IP:
    ├─ Check failed attempts in last 15 minutes
    ├─ If count >= 5:
    │  ├─ Log attempt with timestamp
    │  ├─ Return error: "Too many attempts"
    │  └─ Require wait 900 seconds (15 min)
    ├─ Else:
    │  ├─ Validate credentials
    │  ├─ If failed: log attempt, increment counter
    │  └─ If success: reset counter, create session
    └─ End
```

**Configuration** (lib/config.php)
```php
define('LOGIN_ATTEMPT_THRESHOLD', 5);      // Attempts before lockout
define('LOGIN_ATTEMPT_WINDOW', 900);       // 15 minutes in seconds
define('LOGIN_LOCKOUT_DURATION', 900);     // Same as window
define('LOGIN_ATTEMPT_CLEANUP', 86400);    // Clean old entries after 24h
```

**Database Table**
```sql
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(255),
    success BOOLEAN,
    user_agent TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, timestamp),
    INDEX idx_cleanup (timestamp)
);
```

**Cleanup Job** (Cron)
```bash
# Daily cleanup of old entries (older than 24h)
0 0 * * * /usr/bin/php /var/www/html/rabind/stale_cleanup.php
```

### Audit Logging

**Logged Events**
| Event | Trigger | Data Captured |
|-------|---------|---------------|
| User Create | New voucher created | username, group, mac_binding, notes |
| User Edit | Metadata updated | field changes, old/new values |
| User Delete | Permanent removal | username, previous settings |
| User Disable/Enable | State change | username, reason |
| MAC Reset | Unbinding device | username, previous MAC |
| CoA Sent | Disconnect command | username, command type, result |
| Admin Login | Successful auth | admin username, ip_address |
| Admin Failed Login | Auth failure | username, ip_address, attempt # |
| Admin Create | New admin created | username, email |
| Admin Delete | Admin removed | username, email |

**Query Audit Logs** (Admin Panel)
```sql
SELECT 
    al.timestamp,
    ad.username as admin,
    al.action,
    al.target_username,
    al.ip_address,
    al.details
FROM audit_log al
JOIN admins ad ON al.admin_id = ad.id
WHERE al.timestamp > DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY al.timestamp DESC;
```

### Session Management

**Session Configuration**
```php
/* Session timeout: 30 minutes of inactivity */
define('SESSION_TIMEOUT', 1800);

/* Security flags */
ini_set('session.cookie_httponly', 1);       // No JavaScript access
ini_set('session.use_strict_mode', 1);       // Reject uninitialized SID
ini_set('session.cookie_samesite', 'Lax');   // CSRF protection
// ini_set('session.cookie_secure', 1);      // HTTPS only (uncomment in production)

/* Session regeneration on privilege escalation */
session_regenerate_id(true);  // Called after successful login
```

**Session Fixation Prevention**
- Session ID regenerated after login
- Session cookies are HttpOnly (XSS protection)
- Session uses Lax SameSite policy
- Strict mode rejects uninitialized sessions

---

## Administrative Features

### Admin Management

**Admin CRUD Operations**
- Create new administrators with email
- Edit admin email and status
- Soft delete (disable) administrator access
- View admin login history and activity
- Reset admin passwords (admin-only)

**Admin Panel Structure**
```
Administration
├── Admin Management
│   ├── Add New Admin
│   ├── Edit Admin
│   ├── Delete Admin
│   └── Admin Audit Log
├── System Settings
│   ├── WiFi SSID/Password (for display)
│   ├── NAS Configuration
│   ├── Session Timeout
│   └── Debug Mode
└── Database Maintenance
    ├── Cleanup Stale Sessions
    ├── Export Audit Logs
    └── Backup/Restore
```

**Create Admin Account** (Database)
```sql
INSERT INTO admins (username, password, email, created_at)
VALUES ('newadmin', '$2y$10$...hash...', 'admin@example.com', NOW());
```

**Generate Password Hash** (PHP CLI)
```bash
php -r "echo password_hash('SecurePassword123!', PASSWORD_DEFAULT);"
# Output: $2y$10$N9qo8uLOickgx2ZMRZoMye...
```

### Audit Trail

**Real-Time Audit Dashboard**
```
Recent Actions (Last 24 hours)
┌──────────────────────────────────────────────────┐
│ 14:35  │ admin1   │ user_create     │ guest_001   │
│ 14:32  │ admin2   │ coa_sent        │ guest_002   │
│ 14:20  │ admin1   │ mac_reset       │ guest_003   │
│ 13:45  │ admin3   │ user_disable    │ expired_user│
│ 13:40  │ admin1   │ login_success   │ admin1      │
└──────────────────────────────────────────────────┘

Total Events Today: 247
Total Admin Actions: 45
Failed Logins: 3
```

**Audit Log Search**
```
Filters:
├─ Date Range: [2026-05-22] to [2026-05-23]
├─ Admin Username: [dropdown]
├─ Action Type: [user_create, user_delete, coa_sent, etc.]
├─ Target User: [search field]
└─ IP Address: [optional filter]

Results: 23 records | [Export CSV] [Print]
```

---

## File Structure

```
rabind/
├── index.php                      # Login page (GET) / Auth handler (POST)
├── login.php                      # Legacy login (compatibility)
├── logout.php                     # Session destroy
│
├── dashboard.php                  # Main dashboard (authenticated)
├── online_users.php               # Active users panel
├── users.php                      # User management (CRUD)
├── user_create.php                # User creation form
├── user_delete.php                # User deletion handler
├── user_disable.php               # Enable/disable toggle
├── user_disconnect.php            # CoA disconnect handler
├── user_log.php                   # User history/audit trail
├── user_reset_mac.php             # MAC binding reset
├── user_reset_traffic.php         # Traffic quota reset
│
├── logs.php                       # System logs (login attempts, etc.)
├── nas.php                        # NAS management
├── rad_main.php                   # RADIUS group/attribute config
├── radgroupcheck.php              # Group authentication attrs
├── radgroupreply.php              # Group reply attributes
│
├── admins/                        # Admin management (v0.6.0+)
│   ├── index.php                  # Admin list
│   ├── create.php                 # Create admin form
│   ├── save.php                   # Save admin data
│   ├── edit.php                   # Edit admin form
│   ├── delete.php                 # Delete admin handler
│   └── audit.php                  # Audit log viewer (v0.6.0+)
│
├── ajax/                          # AJAX endpoints (no auth needed if CSRF-protected)
│   ├── online_users.php           # Real-time user list
│   └── user_details.php           # Session details modal
│
├── lib/                           # Core libraries
│   ├── config.php                 # Configuration constants
│   ├── db.php                     # Database abstraction (PDO)
│   ├── auth.php                   # Authentication & session mgmt
│   ├── coa.php                    # Change of Authorization (CoA)
│   └── radius_pod.php             # RADIUS protocol handling
│
├── templates/                     # PHP template inheritance
│   ├── header.php                 # HTML header, CSS
│   ├── menu.php                   # Navigation menu
│   └── footer.php                 # HTML footer, scripts
│
├── js/                            # Frontend JavaScript
│   ├── rabind_refresh.js          # Auto-refresh logic
│   └── user_modal.js              # Modal dialogs
│
├── DB/                            # Database schemas
│   ├── DB.sql                     # Application tables
│   ├── radius.sql                 # RADIUS schema (reference)
│   ├── rabind.sql                 # Tables for v0.6.0+
│   ├── DATABASE.md                # Schema documentation
│   └── schematics.txt             # ASCII diagrams
│
├── README.md                      # Documentation
├── LICENSE                        # AGPL-3.0
└── .gitignore                     # Git ignore rules
```

---

## Configuration Reference

### Core Configuration (lib/config.php)

```php
/* Database Credentials */
define('RADIUS_DB_HOST', 'localhost');        // FreeRADIUS database host
define('RADIUS_DB_NAME', 'radius');           // FreeRADIUS database name
define('RADIUS_DB_USER', 'radius');           // FreeRADIUS user
define('RADIUS_DB_PASS', 'password');         // FreeRADIUS password

define('APP_DB_HOST', 'localhost');           // Rabind database host
define('APP_DB_NAME', 'rabind');              // Rabind database name
define('APP_DB_USER', 'rabind');              // Rabind user
define('APP_DB_PASS', 'password');            // Rabind password

/* Application Settings */
define('APP_NAME', 'RaBind');                 // Display name
define('APP_VERSION', '0.6.0');               // Current version
define('APP_TAGLINE', 'Lightweight RADIUS Control Panel');
define('APP_ENV', 'production');              // production / development
define('APP_MAINTENANCE', false);             // Enable maintenance mode
define('APP_DEBUG', false);                   // Enable debug logging
define('BASE_URL', '/rabind/');               // Base URL path

/* NAS Configuration */
define('RADIUS_NAS_IP', '192.168.88.1');      // MikroTik IP (CoA target)
define('RADIUS_NAS_PORT', '3799');            // RADIUS CoA port
define('RADIUS_SECRET', 'secret123');         // Shared RADIUS secret

/* WiFi Credentials (for display) */
define('SITE_WIFI_SSID', 'GuestNetwork');     // Network SSID to display
define('SITE_WIFI_PASSWORD', 'password');     // Network password to display

/* Security */
define('SESSION_TIMEOUT', 1800);              // 30 minutes inactivity

/* Brute-Force Protection (v0.6.0+) */
define('LOGIN_ATTEMPT_THRESHOLD', 5);         // Lockout after 5 failures
define('LOGIN_ATTEMPT_WINDOW', 900);          // 15 minutes window
define('LOGIN_LOCKOUT_DURATION', 900);        // Lockout for 15 minutes

/* PHP Session Hardening */
ini_set('session.cookie_httponly', 1);        // HttpOnly flag
ini_set('session.use_strict_mode', 1);        // Strict SID validation
// ini_set('session.cookie_secure', 1);        // HTTPS-only (uncomment for HTTPS)
```

### FreeRADIUS Configuration

**Enable RADIUS Accounting** (`/etc/freeradius/mods-enabled/sql`)
```sql
sql {
    database = "mysql"
    server = "localhost"
    login = "radius"
    password = "radius_password"
    radius_db = "radius"
    
    # Use SQL for accounting
    accounting {
        query = "${confdir}/mods-config/sql/mysql/accounting.conf"
    }
}
```

**Enable SQL Module** (`/etc/freeradius/radiusd.conf`)
```
modules {
    $INCLUDE mods-enabled/
}

instantiate {
    sql
}
```

**Restart FreeRADIUS**
```bash
sudo systemctl restart freeradius
sudo freeradius -X  # Test configuration
```

### MikroTik RouterOS Configuration

**Enable RADIUS for Authentication**
```mikrotik
/radius
add service=login address=<FREERADIUS_IP> secret=your_secret timeout=3s
add service=ppp address=<FREERADIUS_IP> secret=your_secret timeout=3s

# Enable RADIUS for hotspot
/ip hotspot
set <HOTSPOT_NAME> radius-accounting=yes
```

**Enable CoA (Change of Authorization)**
```mikrotik
/radius
set incoming=yes

# Firewall rules to allow CoA from Rabind server
/ip firewall nat
add chain=dstnat protocol=udp in-interface=ether1 dst-port=3799 action=accept
```

**Verify RADIUS Connection**
```mikrotik
# Test FreeRADIUS connectivity
/tool fetch url=http://<FREERADIUS_IP>:8000/
# OR
:log info "RADIUS Test - checking connectivity"
```

### Web Server Configuration

**Apache Configuration** (`/etc/apache2/sites-available/rabind.conf`)
```apache
<VirtualHost *:80>
    ServerName hotspot.example.com
    DocumentRoot /var/www/html/rabind
    
    <Directory /var/www/html/rabind>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Deny access to sensitive files
        <Files "lib/config.php">
            Deny from all
        </Files>
    </Directory>
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/rabind_error.log
    CustomLog ${APACHE_LOG_DIR}/rabind_access.log combined
</VirtualHost>
```

Enable and restart:
```bash
sudo a2ensite rabind
sudo systemctl restart apache2
```

**Nginx Configuration** (`/etc/nginx/sites-available/rabind`)
```nginx
server {
    listen 80;
    server_name hotspot.example.com;
    root /var/www/html/rabind;
    
    location ~ /lib/config\.php$ {
        deny all;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
    
    location ~ /\.git {
        deny all;
    }
}
```

Enable and restart:
```bash
sudo ln -s /etc/nginx/sites-available/rabind /etc/nginx/sites-enabled/
sudo systemctl restart nginx
```

---

## Troubleshooting

### Common Issues

#### 1. "Connection to RADIUS database failed"

**Symptoms**: White screen, database errors in logs

**Solutions**:
```bash
# 1. Verify MySQL is running
sudo systemctl status mysql

# 2. Test database connection
mysql -h localhost -u radius -p -D radius -e "SELECT COUNT(*) FROM radacct;"

# 3. Verify credentials in config.php
cat /var/www/html/rabind/lib/config.php | grep RADIUS_DB

# 4. Check MySQL user permissions
mysql -u root -p -e "SHOW GRANTS FOR 'radius'@'localhost';"

# 5. Restart MySQL service
sudo systemctl restart mysql
```

#### 2. "Users not appearing in Online Users list"

**Symptoms**: Dashboard shows 0 active users even though WiFi is connected

**Solutions**:
```bash
# 1. Verify FreeRADIUS is processing accounting
tail -f /var/log/freeradius/radius.log | grep acct

# 2. Check radacct table for recent records
mysql -u radius -p radius -e "
SELECT username, acctstartime 
FROM radacct 
WHERE acctstartime > DATE_SUB(NOW(), INTERVAL 1 HOUR) 
LIMIT 5;
"

# 3. Verify MikroTik is sending accounting packets
:log info "RADIUS Acct Test"  # In MikroTik terminal

# 4. Check UFW/Firewall rules
sudo ufw status
sudo ufw allow 1813/udp from <MIKROTIK_IP>  # Allow accounting
```

#### 3. "CoA disconnect not working (users stay connected)"

**Symptoms**: 'Disconnect' button has no effect, users remain online

**Solutions**:
```bash
# 1. Verify port 3799 is open and listening
sudo netstat -tulpn | grep 3799
sudo ss -tulpn | grep 3799

# 2. Check firewall allows outbound UDP 3799
sudo ufw status numbered
sudo ufw allow out 3799/udp to <MIKROTIK_IP>

# 3. Verify RADIUS secret matches MikroTik
# In Rabind config.php
grep RADIUS_SECRET /var/www/html/rabind/lib/config.php

# In MikroTik
/radius print
# Verify 'secret' field matches

# 4. Test CoA manually
php -r "
require '/var/www/html/rabind/lib/coa.php';
\$coa = new RadiusCoA('192.168.88.1', 3799, 'secret123');
\$result = \$coa->disconnectUser('testuser', '192.168.88.1');
var_dump(\$result);
"

# 5. Enable CoA in MikroTik
/radius set incoming=yes
```

#### 4. "Login page shows 'CSRF token validation failed'"

**Symptoms**: Users cannot login, token error appears

**Solutions**:
```bash
# 1. Verify session storage is writable
ls -la /var/lib/php/sessions/
chmod 733 /var/lib/php/sessions/

# 2. Check PHP session configuration
php -i | grep "session.save_path"

# 3. Verify session.use_strict_mode is enabled
php -i | grep "session.use_strict_mode"

# 4. Check for cookie blocking in browser
# Browser console: document.cookie should show PHPSESSID

# 5. Restart PHP-FPM
sudo systemctl restart php-fpm
```

#### 5. "Audit logs empty or not recording actions"

**Symptoms**: admin_audit table remains empty despite user creation

**Solutions**:
```bash
# 1. Verify audit_log table exists
mysql -u rabind -p rabind -e "DESC audit_log;"

# 2. Check if admin_id is being passed
tail -f /var/log/apache2/rabind_error.log

# 3. Verify admin is logged in correctly
mysql -u rabind -p rabind -e "
SELECT * FROM admins WHERE username = 'admin';
"

# 4. Manually test audit logging
php -r "
require '/var/www/html/rabind/lib/db.php';
\$stmt = \$appDb->prepare(
    'INSERT INTO audit_log (admin_id, action, target_username, timestamp, ip_address)
     VALUES (?, ?, ?, NOW(), ?)'
);
\$stmt->execute([1, 'test_action', 'test_user', '127.0.0.1']);
echo 'Audit log test entry created';
"
```

### Log Files

**Check Application Logs**
```bash
# Apache error log
tail -50 /var/log/apache2/error.log

# Apache access log (successful requests)
tail -50 /var/log/apache2/access.log

# FreeRADIUS debug
tail -50 /var/log/freeradius/radius.log

# PHP-FPM (if using Nginx)
tail -50 /var/log/php-fpm.log
```

**Enable Debug Mode** (lib/config.php)
```php
define('APP_DEBUG', true);
error_reporting(E_ALL);
ini_set('display_errors', 'On');
ini_set('log_errors', 'On');
ini_set('error_log', '/var/log/php-rabind-errors.log');
```

### Database Diagnostics

```bash
# Test RADIUS database
mysql -u radius -p -D radius << EOF
SELECT COUNT(*) as total_accounting_records FROM radacct;
SELECT COUNT(*) as active_sessions FROM radacct WHERE acctstoptime IS NULL;
SELECT DISTINCT attribute FROM radcheck LIMIT 10;
EOF

# Test Rabind application database
mysql -u rabind -p -D rabind << EOF
SELECT COUNT(*) as total_users FROM rabind_users;
SELECT COUNT(*) as admin_count FROM admins;
SELECT COUNT(*) as audit_entries FROM audit_log WHERE timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR);
SELECT COUNT(*) as failed_logins FROM login_attempts WHERE success = FALSE AND timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR);
EOF
```

---

## Contributing

Contributions are welcome! Please follow these guidelines:

1. **Report Issues**: Use GitHub Issues with detailed reproduction steps
2. **Security Vulnerabilities**: Email privately to maintainer (do not disclose publicly)
3. **Feature Requests**: Open issue with detailed use case and benefits
4. **Code Changes**:
   - Fork repository
   - Create feature branch: `git checkout -b feature/my-feature`
   - Follow existing code style (PSR-12 for PHP)
   - Commit with clear messages: `git commit -m "Add feature X"`
   - Push to branch: `git push origin feature/my-feature`
   - Open Pull Request with description

### Development Setup

```bash
# Clone with development tools
git clone https://github.com/giorgioladu/Rabind.git
cd Rabind

# Create development branch
git checkout -b dev/my-feature

# Enable debug mode for development
sed -i "s/APP_DEBUG', false/APP_DEBUG', true/" lib/config.php
sed -i "s/APP_ENV', 'production/APP_ENV', 'development/" lib/config.php

# Commit and push
git add .
git commit -m "Feature: Add real-time traffic charting"
git push origin dev/my-feature
```

---

## License

Rabind is released under the **GNU Affero General Public License v3.0** (AGPL-3.0).

**Key Points**:
- Free to use, modify, and distribute
- Any modifications must be open-sourced
- If used as a network service, source must be provided to users
- See [LICENSE](LICENSE) file for full text

**For proprietary use**, contact the author for alternative licensing.

---

## Support & Contact

- **Documentation**: [GitHub Wiki](#)
- **Issue Tracker**: [GitHub Issues](https://github.com/giorgioladu/Rabind/issues)
- **Author**: Giorgio Ladu ([@giorgioladu](https://github.com/giorgioladu))

---

## Changelog

### Version 0.6.0 (May 2026) - Enterprise Security Release
- ✨ **CSRF Token Protection**: Session-based tokens on all state-modifying operations
- ✨ **Brute-Force Protection**: Login attempt tracking with configurable lockout
- ✨ **Multi-Admin Support**: Create, manage, and audit multiple administrator accounts
- ✨ **Comprehensive Audit Logging**: Track all administrative actions with timestamps and IP addresses
- ✨ **Audit Trail Viewer**: Search, filter, and export audit logs
- ✨ **Enhanced UI**: Bootstrap 5.x styling, improved responsiveness
- 🔧 **Database**: New tables: `admins`, `audit_log`, `login_attempts`
- 🔒 **Security Hardening**: Session fixation prevention, strict mode enabled by default
- 📝 **Documentation**: Detailed technical documentation and troubleshooting guide

### Version 0.4.0 (Previous)
- User management interface
- Real-time session monitoring
- CoA implementation for force disconnect
- MAC binding

### Version 0.3.x (Legacy)
- Initial RADIUS management interface
- Basic user CRUD operations

---

**Last Updated**: May 22, 2026  
**Maintained By**: Giorgio Ladu  
**Repository**: https://github.com/giorgioladu/Rabind
