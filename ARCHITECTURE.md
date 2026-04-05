# 🏗️ Sentinel Cameroon Technical Architecture

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    DEPLOYMENT ARCHITECTURE                       │
└─────────────────────────────────────────────────────────────────┘

╔════════════════════════════════════════════════════════════════╗
║                  RENDER PRODUCTION (Live)                      ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  ┌──────────────────────────────────────────────────────────┐ ║
║  │ Render Web Service (Free/Paid Plan)                     │ ║
║  │ Container: Phil 8.2 + Apache                            │ ║
║  │ Region: Frankfurt                                       │ ║
║  │ URL: https://sentinel-cameroon.onrender.com            │ ║
║  └──────────────────────────────────────────────────────────┘ ║
║           │                                  │                 ║
║           ├─ Port 80 ────────────────────────┤                ║
║           │                                  │                 ║
║  ┌────────▼──────────────┐      ┌──────────▼────────────┐    ║
║  │ Apache Web Server     │      │ PHP 8.2 Runtime      │    ║
║  │ • Rewrite Module      │      │ • PDO Extension       │    ║
║  │ • Headers Module      │      │ • SQLite Support      │    ║
║  │ • .htaccess Support   │      │ • Session Handler     │    ║
║  └───────┬──────────────┘      └──────────┬────────────┘    ║
║          │                               │                   ║
║  ┌───────▼───────────────────────────────▼────────────────┐  ║
║  │ Sentinel Cameroon Application                          │  ║
║  │ • index.php, login.php, register.php                   │  ║
║  │ • api/ endpoints                                       │  ║
║  │ • includes/ utilities                                  │  ║
║  │ • assets/ (CSS, JS)                                    │  ║
║  └───────────────────────┬─────────────────────────────────┘  ║
║                          │                                     ║
║  ┌───────────────────────▼──────────────────────────────────┐ ║
║  │ Database Layer (database/config.php)                    │ ║
║  │ • Auto-detects SQLite for Render                        │ ║
║  │ • PDO abstraction layer                                 │ ║
║  │ • Connection pooling                                    │ ║
║  └───────────────────────┬──────────────────────────────────┘ ║
║                          │                                     ║
║  ┌───────────────────────▼──────────────────────────────────┐ ║
║  │ PERSISTENT VOLUME: sentinel-db-volume                   │ ║
║  │ Mount Path: /var/www/html/database/data                 │ ║
║  │ Database File: sentinel_cameroon.sqlite                 │ ║
║  │ Survives: Container restarts, redeployments             │ ║
║  └──────────────────────────────────────────────────────────┘ ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝


╔════════════════════════════════════════════════════════════════╗
║              LOCAL DEVELOPMENT (Docker Compose)                ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  ┌──────────────────────────────────────────────────────────┐ ║
║  │ Developer Machine                                       │ ║
║  │ • VS Code / IDE                                         │ ║
║  │ • Git / Terminal                                        │ ║
║  │ • Docker Desktop                                        │ ║
║  └──────────────────────────────────────────────────────────┘ ║
║           │                                                    ║
║           │ docker-compose up -d                              ║
║           ▼                                                    ║
║  ┌──────────────────────────────────────────────────────────┐ ║
║  │ Docker Container (sentinel-cameroon)                    │ ║
║  │ Image: php:8.2-apache                                   │ ║
║  │                                                         │ ║
║  │  ┌──────────────────────────────────────────────────┐  ║
║  │  │ Apache + PHP 8.2                                │  ║
║  │  └──────────────────────────────────────────────────┘  ║
║  │           │ (Port 80)                                   ║
║  │           ▼                                             ║
║  │  ┌──────────────────────────────────────────────────┐  ║
║  │  │ Sentinel Cameroon Application                   │  ║
║  │  │ (/var/www/html)                                 │  ║
║  │  └──────────────────────────────────────────────────┘  ║
║  │           │                                             ║
║  │           ▼                                             ║
║  │  ┌──────────────────────────────────────────────────┐  ║
║  │  │ SQLite Database                                 │  ║
║  │  │ (/var/www/html/database/data)                   │  ║
║  │  └──────────────────────────────────────────────────┘  ║
║  │                                                         ║
║  └──────────────────────────────────────────────────────────┘ ║
║           │ (Port 8080:80)                                   ║
║           ▼                                                    ║
║  http://localhost:8080                                        ║
║  (Browser)                                                    ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝


╔════════════════════════════════════════════════════════════════╗
║              DATABASE SELECTION LOGIC                           ║
╠════════════════════════════════════════════════════════════════╣
║                                                                ║
║  START                                                         ║
║    │                                                           ║
║    ├─ Is DB_PATH env var set?                                ║
║    │  ├─ YES → Use SQLite at $DB_PATH ✓                     ║
║    │  └─ NO  → Continue                                      ║
║    │                                                           ║
║    ├─ Is RENDER environment detected?                         ║
║    │  ├─ YES → Use SQLite (Render default) ✓                ║
║    │  └─ NO  → Continue (Local Dev)                          ║
║    │                                                           ║
║    └─ Use MySQL with env vars                                ║
║       • DB_HOST (default: 127.0.0.1)                          ║
║       • DB_USER (default: root)                               ║
║       • DB_PASS                                               ║
║       • DB_NAME (default: sentinel_cameroon)                  ║
║       • DB_PORT (default: 3307)                               ║
║                                                                ║
╚════════════════════════════════════════════════════════════════╝
```

## Component Relationships

```
┌─────────────────────────────────────────────┐
│         Sentinel Cameroon Modules            │
├─────────────────────────────────────────────┤
│                                             │
│  ┌─────────────────────────────────────┐   │
│  │ Public Pages                        │   │
│  │ • index.php (Landing)              │   │
│  │ • login.php                        │   │
│  │ • register.php                     │   │
│  └─────────────────────────────────────┘   │
│           │                                 │
│  ┌────────▼────────────────────────────┐   │
│  │ includes/auth.php                   │   │
│  │ • Session management                │   │
│  │ • User authentication               │   │
│  │ • Permission checks                 │   │
│  └────────┬─────────────────────────────┘   │
│           │                                 │
│  ┌────────▼─────────────────────────────┐   │
│  │ database/config.php                  │   │
│  │ • PDO Connection                     │   │
│  │ • Query execution                    │   │
│  │ • Transaction management             │   │
│  └────────┬──────────────────────────────┘  │
│           │                                 │
│  ┌────────▼──────────────────────────────┐  │
│  │ Database (SQLite or MySQL)            │  │
│  │ • Users table                        │  │
│  │ • Incidents table                    │  │
│  │ • Communities table                  │  │
│  │ • Marketplace table                  │  │
│  │ • Activity logs                      │  │
│  └────────────────────────────────────────┘  │
│                                             │
└─────────────────────────────────────────────┘
```

## Deployment Flow

```
Developer's Machine
    │
    ├─ Make code changes
    ├─ Test locally (docker-compose up)
    ├─ Commit changes (git add/commit)
    │
    ▼
GitHub Repository
    │
    ├─ git push origin main
    │
    ▼
Render (Webhook Triggered)
    │
    ├─ Clone repo
    ├─ Read render.yaml
    │
    ▼
Build Stage
    │
    ├─ docker build -t sentinel-cameroon .
    ├─ Install dependencies
    ├─ Compile PHP extensions
    │
    ▼
Deploy Stage
    │
    ├─ Start container
    ├─ Create persistent volume
    ├─ Copy application files
    │
    ▼
Initialize Stage
    │
    ├─ docker-entrypoint.sh runs
    ├─ Create database directory
    ├─ Initialize SQLite database
    ├─ Load schema from schema-sqlite.sql
    │
    ▼
Health Check
    │
    ├─ Verify Apache is running
    ├─ Verify database access
    ├─ Check HTTP response
    │
    ▼
Live
    │
    └─ Application ready at https://sentinel-cameroon.onrender.com
```

## File Persistence

```
Render Persistent Volume
│
├─ /var/www/html/database/data/
│  │
│  ├─ sentinel_cameroon.sqlite (Database file)
│  │  • Contains ALL data
│  │  • Persists across restarts
│  │  • Survives redeployments
│  │  • Backed up by Render
│  │
│  └─ (Other future database files)
│
│ Survives:
│ ✓ Container restart
│ ✓ New deployment
│ ✓ Service plan upgrade
│ ✓ Manual Render actions
│
│ Destroyed by:
│ ✗ Explicit volume deletion
│ ✗ Service removal
│ ✗ Render account deletion
```

## Docker Image Layers

```
Alpine/Debian Base
    │
    ├─ Install system packages
    │  • build-essential
    │  • sqlite3, libsqlite3-dev
    │  • curl, git, wget, zip
    │
    ▼
Add PHP Extensions
    │
    ├─ pdo
    ├─ pdo_sqlite
    ├─ pdo_mysql
    ├─ session
    │
    ▼
Configure Apache
    │
    ├─ Enable rewrite module
    ├─ Enable headers module
    ├─ Configure document root
    │
    ▼
Copy Application
    │
    ├─ COPY . /var/www/html/
    │
    ▼
Set Permissions
    │
    ├─ chown www-data:www-data
    ├─ chmod 755 directories
    ├─ chmod 644 files
    │
    ▼
Final Image (~800MB)
```

## Data Flow

```
User Browser Request
    │
    ├─ HTTPS/HTTP
    │
    ▼
Render (SSL/TLS termination)
    │
    ├─ Port 80
    │
    ▼
Apache Container
    │
    ├─ Route request (.htaccess)
    │
    ▼
PHP Application
    │
    ├─ includes/auth.php (Session check)
    │
    ├─ index.php / login.php / etc
    │
    ▼
Database Query
    │
    ├─ database/config.php
    │
    ├─ Create PDO connection
    │
    ├─ Prepare & execute SQL
    │
    ▼
SQLite Database
    │
    ├─ Query execution
    │
    ├─ Return results
    │
    ▼
PHP Processing
    │
    ├─ Render HTML/Template
    │
    ▼
HTTP Response
    │
    ├─ Send to browser
    │
    ▼
User's Browser
    │
    └─ Display content
```

---

This architecture ensures:

- ✅ **High Availability**: Auto-restart on failure
- ✅ **Data Persistence**: Persistent volumes survive restarts
- ✅ **Security**: HTTPS/SSL, proper permissions
- ✅ **Scalability**: Can upgrade Render plan anytime
- ✅ **Simplicity**: Single container, no complex setup
- ✅ **Cost-Effective**: Free tier available at Render
- ✅ **Easy Deployment**: Git push to deploy
