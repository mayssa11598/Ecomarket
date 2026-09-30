<div align="center">

# 🌿 EcoMarket

**An eco-friendly e-commerce marketplace with a full customer storefront and admin back-office.**

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-4479A1?logo=mysql&logoColor=white)
![HTML5](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?logo=css3&logoColor=white)
![License](https://img.shields.io/badge/license-MIT-green)

</div>

---

## 📖 Table of Contents

- [About](#-about)
- [Screenshots](#-screenshots)
- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Database Schema](#-database-schema)
- [Project Structure](#-project-structure)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Security](#-security)
- [Author](#-author)

---

## 🌱 About

EcoMarket is a full-stack web application where customers can browse and buy organic, artisanal, and local products, and where administrators manage the whole business: catalog, orders, shipments, customers, and sales reports.

It was built as a academic project to practice relational database design, session-based authentication, and business-oriented SQL reporting in plain PHP (no framework).

---

## 📸 Screenshots

> Add your screenshots to a `docs/` folder and update the paths below.

| Home | Product catalog |
|------|-----------------|
| ![Home](docs/home.png) | ![Catalog](docs/catalog.png) |

| Cart | Admin dashboard |
|------|-----------------|
| ![Cart](docs/cart.png) | ![Admin](docs/admin.png) |

---

## ✨ Features

### Customer storefront
- Home page with featured products and top categories
- Product catalog with category filtering and pagination
- Product detail pages with stock availability
- Session-based shopping cart
- Checkout with transactional stock update
- Registration, login, and account management
- Order history with detailed order view
- Shipment tracking (carrier, tracking number, status)

### Admin back-office
- Role-based admin login
- CRUD for **products** and **categories**
- **Order management** with status workflow:
  `en_attente → validee → expediee → livree` (or `annulee`)
- **Shipment management** (`preparation`, `en_transit`, `livre`, `echec`)
- Customer management
- **Reports** with date filters:
  - Sales and revenue by category
  - Orders awaiting delivery
  - Top 5 best-selling products

---

## 🛠 Tech Stack

| Layer     | Technology                              |
|-----------|-----------------------------------------|
| Backend   | PHP 8.2, PDO (singleton connection)     |
| Database  | MySQL / MariaDB (utf8mb4)               |
| Frontend  | HTML5, custom responsive CSS            |
| Server    | Apache (XAMPP, WAMP, or LAMP)           |

---

## 🗄 Database Schema

```
categories ──< produits ──< commandes_produits >── commandes >── clients
                                                      │
                                                      └──< expeditions
```

| Table                | Purpose                                                      |
|----------------------|--------------------------------------------------------------|
| `categories`         | Product categories (name, description, icon)                 |
| `produits`           | Products (price with `CHECK >= 0`, stock, image, featured)   |
| `clients`            | Customers and admins (`is_admin`, bcrypt-hashed passwords)   |
| `commandes`          | Orders (status enum, total, notes)                           |
| `commandes_produits` | Order lines (quantity and unit price at time of purchase)    |
| `expeditions`        | Shipments (carrier, tracking number, delivery address)       |

The SQL dump (`ecomarketnv.sql`) includes the schema and sample data.

---

## 📁 Project Structure

```
ecomarket/
├── index.php               # Home page
├── ecomarketnv.sql         # Database schema + sample data
├── includes/
│   ├── config.php          # DB connection, session, helper functions
│   ├── header.php
│   └── footer.php
├── pages/                  # Customer-facing pages
│   ├── produits.php, produit_detail.php, categories.php
│   ├── panier.php, commander.php
│   ├── commandes.php, commande_detail.php, expeditions.php
│   └── login.php, inscription.php, compte.php, logout.php
├── admin/                  # Back-office
│   ├── produits.php, categories.php, clients.php
│   ├── commandes.php, expeditions.php, rapports.php
│   └── login.php, logout.php
└── assets/css/style.css
```

---

## 🚀 Installation

**Prerequisites:** PHP 8.0+, MySQL/MariaDB, Apache (or XAMPP/WAMP).

```bash
# 1. Clone the repo into your web root (e.g. htdocs for XAMPP)
git clone https://github.com/<your-username>/ecomarket.git
cd ecomarket

# 2. Import the database (the dump creates the `ecomarket` database)
mysql -u root -p < ecomarketnv.sql
```

You can also import `ecomarketnv.sql` through **phpMyAdmin**.

```text
# 3. Start Apache + MySQL, then visit:
http://localhost/ecomarket
```

**Admin panel:** `http://localhost/ecomarket/admin/login.php`
Use the admin account from the sample data (`admin@ecomarket.tn`). Change its password after your first login.

---

## ⚙ Configuration

Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecomarket');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost/ecomarket');
define('PER_PAGE', 9);   // products per page
```

> ⚠️ Never deploy with the default `root` user and an empty password.

---

## 🔒 Security

- PDO **prepared statements** everywhere (SQL injection protection)
- Passwords hashed with **bcrypt**
- Output escaped through an `e()` helper (`htmlspecialchars`) against XSS
- Session-based auth with a `requireAdmin()` guard on every admin page
- **Database transactions** on checkout to keep orders and stock consistent
- Integrity check before deleting products referenced by orders

---

## 👩‍💻 Author

**Mayssa Ahmed**,  Computer Science student at ISI (Institut Supérieur d'Informatique)

[![LinkedIn](https://www.linkedin.com/in/mayssa-ahmed-12ab59339/)

---

<div align="center">

⭐ If you found this project useful, consider giving it a star!

</div>
