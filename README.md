# 🚀 ID Platform

<p align="center">
<img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="300" alt="Laravel Logo">
</p>

<p align="center">
<img src="https://img.shields.io/badge/Laravel-Framework-red" />
<img src="https://img.shields.io/badge/PHP-8.x-blue" />
<img src="https://img.shields.io/badge/Status-Active-success" />
</p>

---

## 📌 Overview

**ID Platform** is a backend system built with Laravel for managing:

* Companies
* Departments
* Employees
* Projects & Assignments
* Roles & Permissions (RBAC)
* Media handling (Company logos, files)

The system is designed with clean architecture and scalability in mind.

---

## 🧱 Core Modules

### 🏢 Company Management

* Manage company data
* Upload company logo (via Media Library)
* Soft delete support

### 🏬 Department Management

* Departments linked to companies
* Employees assigned to departments

### 👨‍💼 Employee Management

* Employee profiles
* Department association
* Project assignments

### 📁 Project Assignment

* Many-to-Many (Employee ↔ Project)
* Tracks assignment date

### 🔐 Roles & Permissions (RBAC)

* Role-based access control
* Many-to-Many (Role ↔ Permission)
* Scalable permission structure

### 🖼️ Media Handling

* Powered by Spatie Media Library
* Supports:

    * File uploads
    * Image conversions
    * Single/multiple files

---

## 🗄️ Database Structure (Simplified)

* companies
* departments
* employees
* projects
* employee_projects
* roles
* permissions
* role_permissions
* media

---

## ⚙️ Installation

```bash
git clone <repo-url>
cd id-platform
composer install
cp .env.example .env
php artisan key:generate
```

---

## 🛠️ Setup Database

```bash
php artisan migrate
```

---

## 📦 Install Media Library

```bash
composer require spatie/laravel-medialibrary

php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"

php artisan migrate
```

---

## 🖼️ Company Logo Upload Example

```php
$company->addMediaFromRequest('logo')
        ->toMediaCollection('company_logo');
```

Retrieve:

```php
$company->getFirstMediaUrl('company_logo');
```

---

## 🔐 RBAC Example

Assign permission to role:

```php
$role->permissions()->attach($permissionId);
```

Check permission:

```php
Gate::allows('employee.create');
```

---

## 🔁 Relationships Overview

* Company → hasMany Departments
* Department → hasMany Employees
* Employee → belongsTo Department
* Employee ↔ Project → belongsToMany
* Role ↔ Permission → belongsToMany

---

## 🧪 Running Tests

```bash
php artisan test
```

---

## 📁 Project Structure

```
app/
 ├── Models/
 ├── Http/
 ├── Services/
database/
 ├── migrations/
 ├── seeders/
```

---

## ⚡ Best Practices Used

* Eloquent Relationships
* Soft Deletes
* Pivot Tables with Constraints
* Clean Fillable Models
* Modular Structure
* Media Separation (DB vs Files)

---

## 🔒 Security

* Mass assignment protection via `$fillable`
* Foreign key constraints
* Unique indexes on critical relations

---

## 📌 Future Enhancements

* API authentication (Sanctum / JWT)
* Multi-tenancy support
* Audit logs
* Notifications system
* Dashboard & analytics

---


## 📄 License

This project is open-sourced under the MIT license.

---

## 👨‍💻 Author

Developed by **Omar**
Senior Backend Developer (Laravel)

---
