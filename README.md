# JTMK Go!

> Developed by JTMK for JTMK

Version: `pulut-sekaya`

---

## Overview

JTMK Go! is a centralized intranet portal developed for JTMK POLIMAS staff.  
The platform acts as a single point of access for departmental systems and future internal applications.

The system is designed with a modular and scalable architecture using Laravel, allowing new systems/modules to be integrated into the ecosystem over time.

---

## Core Objectives

- Centralized staff access portal
- Single Sign-On experience
- Modular Laravel ecosystem
- Role-based access control
- Scalable future system integration
- Internal intranet productivity platform

---

## Current Modules

### 1. Ganti Go
Manage semester-based lecturer class replacement workflows.

### 2. Passport Photo System
Manage lecturer passport photo uploads and downloads.

---

## System Features

- Staff self-enrolment
- Admin approval workflow
- Super admin access control
- Module-based permissions
- Module admin management
- Dynamic dashboard visibility
- Clean modern UI/UX
- Linear / Notion inspired interface

---

## Technology Stack

| Component | Technology |
|---|---|
| Backend | Laravel |
| Frontend | Blade + Tailwind CSS |
| Database | MySQL |
| Local Environment | Laravel Herd |
| Database Server | XAMPP MySQL |
| Build Tool | Vite |
| IDE | VS Code + Codex |

---

## UI Design Philosophy

The interface is inspired by:
- Linear
- Notion

Design principles:
- Minimal
- Clean
- Productivity-focused
- Lightweight
- Modern intranet experience

---

## User Roles

### Super Admin
- Full system management
- User approval
- Module management
- Access control

### Module Admin
- Manage access for assigned modules
- Manage module users

### Staff User
- Access assigned modules only

---

## Authentication Flow

1. Staff self-register using IC number
2. Account remains in `pending` state
3. Super admin approves account
4. User gains access to assigned modules

---

## Future Expansion

Planned future integrations:
- Lab Booking System
- Asset Management
- Attendance System
- Internal Helpdesk
- AI-powered analytics
- WhatsApp integration
- MQTT real-time services

---

## Development Notes

This project follows:
- Modular monolithic architecture
- Laravel best practices
- Scalable access-control structure
- Clean maintainable codebase

---

## Local Development

### Run Laravel

```powershell
php artisan serve
```

### Run Migration

```powershell
php artisan migrate
```

### Run Vite

```powershell
npm run dev
```

---

## Author

Developed and maintained by:

**JTMK POLIMAS**  
Department of Information and Communication Technology  
Politeknik Sultan Abdul Halim Mu’adzam Shah (POLIMAS)

Project Lead:
- Peterpan
- Torn


