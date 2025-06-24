## 📦 Inventory Management System

A simple, functional Inventory Management System built from scratch using **PHP (MySQLi - OOP style)**, **MySQL**, **HTML/CSS**, and **Vanilla JavaScript** — no frameworks or libraries.

Designed to demonstrate clean, structured coding practices for managing product stock and inventory movements.

---

### 🚀 Features

* ✅ User authentication (Login/Logout)
* ✅ Dashboard with real-time inventory stats
* ✅ Product CRUD (Create, Read, Update, Delete)
* ✅ Inventory Movements (Stock In/Out tracking)
* ✅ Simple and clean responsive UI
* ✅ MySQLi (Object-Oriented) prepared statements for security

---

### 📁 Project Structure

```
    inventory-management-system/
    ├── assets/
    │   ├── css/style.css
    │   └── js/script.js
    ├── auth/
    │   ├── login.php
    │   ├── logout.php
    │   └── middleware.php
    ├── config/
    │   └── connection.php
    ├── dashboard/
    │   └── index.php
    ├── movements/
    │   ├── index.php
    │   └── new.php
    ├── products/
    │   ├── add.php
    │   ├── edit.php
    │   ├── delete.php
    │   └── list.php
    ├── sql/
    │   └── inventory_management_system.sql
    ├── index.php
    ├── .gitignore
    └── README.md
```

---

### 🛠️ Installation

1. **Clone this repo:**

```bash
    git clone https://github.com/haadygordon/inventory-management-system.git
    cd inventory-management-system
```

2. **Import the database:**

* Open **phpMyAdmin**
* Create a new database: `inventory_management_system`
* Import the SQL file from `/sql/inventory_management_system.sql`

3. **Configure your DB connection:**

Edit `connection.php`:

```php
    $mysqli = new mysqli("localhost", "root", "", "inventory_management_system");
```

4. **Run with XAMPP:**

* Start Apache and MySQL from XAMPP Control Panel
* Access in browser: `http://localhost/inventory-management-system/index.php`

---

### 🔐 Default Admin Login

```txt
    Username: admin
    Password: admin123
```

*(Or use the one defined in your seed data)*

---

### 📸 Screenshots

*Add screenshots here if desired*

---

### 📄 License

This project is for educational purposes. You are free to use and modify it as needed.