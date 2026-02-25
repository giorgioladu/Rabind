# Rabind

![PHP](https://img.shields.io/badge/PHP-8+-blue)
![FreeRADIUS](https://img.shields.io/badge/FreeRADIUS-3.x-green)
![MikroTik](https://img.shields.io/badge/MikroTik-Compatible-orange) 

Rabind is a lightweight web interface for managing WiFi voucher users powered by FreeRADIUS and MikroTik.

It allows you to:

- Create and manage voucher users
- Monitor active sessions
- View failed logins
- Track traffic consumption
- Disconnect active users (CoA)
- Manage MAC binding
- View user statistics and logs

Designed for small hotels, B&Bs, hotspots and local WiFi infrastructures.

---

## 🏗 Architecture

Rabind works with:

- FreeRADIUS (authentication & accounting)
- MikroTik RouterOS (NAS)
- MySQL/MariaDB
- PHP 8+
- Bootstrap 5

Flow:

Client → MikroTik → FreeRADIUS → MySQL (radacct)  
Admin → Rabind Web Panel → MySQL + CoA → MikroTik

---

## ✨ Features

### User Management
- Create voucher users
- Enable / Disable accounts
- Delete users (from both local DB and RADIUS)
- MAC address binding
- Notes and metadata

### Monitoring
- Online users (auto refresh)
- Active sessions
- Traffic usage
- Top users by traffic
- Users with most login/logout events
- Failed login attempts

### Live Controls
- Force user disconnect (CoA via port 3799)
- Session details modal
- Historical traffic view

---

## ⚙ Requirements

- PHP 8.0+
- MySQL / MariaDB
- FreeRADIUS 3.x
- MikroTik RouterOS with:
  /radius set incoming=yes
  
Port 3799 must be open between server and MikroTik.

---

## 🔧 Installation

1. Clone repository:
git clone [https://github.com/giorgioladu/rabind.git](https://github.com/giorgioladu/Rabind.git)

2. Place inside your web server directory:
   es. /var/www/html/rabind

3. Configure Rabind and database connection:
    edit rabind/lib/config.php
   
Set:

-  App database credentials
-  Radius database credentials
-  NAS IP
-  CoA secret
-  Make sure radacct table exists in radius database.

📊 Database Structure

Rabind uses:
-  radius.radacct
-  radius.radcheck
-  rabind_users (local metadata)

