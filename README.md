# SuperMart POS System

A full-featured web-based Point of Sale system built with PHP 8+, MySQL, JavaScript (AJAX), and Bootstrap 5.

---

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server: Apache (with mod_rewrite) or Nginx
- PHP Extensions: PDO, PDO_MySQL, GD (for image uploads)

---

## 🚀 Installation

### Step 1 — Set up the Database

1. Open **phpMyAdmin** or your MySQL client
2. Create a new database (or import the SQL file — it creates the DB automatically)
3. Run the SQL file:
   ```
   mysql -u root -p < database.sql
   ```

### Step 2 — Configure Database Connection

Edit `/includes/config.php`:

```php
define('DB_HOST', 'localhost');       // Your MySQL host
define('DB_USER', 'root');            // Your MySQL username
define('DB_PASS', '');                // Your MySQL password
define('DB_NAME', 'supermarket_pos'); // Database name
define('APP_URL', 'http://localhost/pos'); // Your app URL (no trailing slash)
```

### Step 3 — Deploy Files

Place the entire `pos/` folder in your web server's root:
- **XAMPP**: `C:\xampp\htdocs\pos\`
- **WAMP**: `C:\wamp64\www\pos\`
- **Linux**: `/var/www/html/pos/`

### Step 4 — Set Permissions (Linux/Mac)

```bash
chmod -R 755 /var/www/html/pos/
chmod -R 777 /var/www/html/pos/uploads/
```

### Step 5 — Access the System

Open your browser and go to:
```
http://localhost/pos/
```

---

## 🔐 Default Login

| Role  | Username | Password  |
|-------|----------|-----------|
| Admin | `admin`  | `admin123` |

> ⚠️ **Change the admin password immediately after first login!**

---

## 📁 File Structure

```
pos/
├── index.php              ← Auto-redirect
├── login.php              ← Login page
├── logout.php             ← Logout handler
├── database.sql           ← Database schema + seed data
│
├── includes/
│   ├── config.php         ← DB config & constants
│   ├── auth.php           ← Auth functions
│   ├── header.php         ← Navbar template
│   └── footer.php         ← Footer template
│
├── admin/
│   ├── dashboard.php      ← Admin dashboard
│   ├── users.php          ← User management (CRUD)
│   ├── products.php       ← Product management (CRUD)
│   ├── reports.php        ← Sales reports
│   ├── audit.php          ← Audit log viewer
│   └── settings.php       ← System settings
│
├── cashier/
│   └── pos.php            ← Main POS interface
│
├── api/
│   ├── product_search.php ← AJAX: search products
│   ├── process_payment.php← AJAX: complete transaction
│   ├── admin_approve.php  ← AJAX: admin approval for item removal
│   ├── hold_order.php     ← AJAX: hold/resume orders
│   └── cash_drawer.php    ← AJAX: cash drawer control
│
├── assets/
│   ├── css/style.css      ← Main stylesheet
│   └── js/main.js         ← Shared JavaScript
│
└── uploads/
    └── products/          ← Product images (auto-created)
```

---

## 👤 User Roles

### Admin
- Full system access
- Manage users, products, settings
- View all reports and audit logs
- Apply discounts
- Open cash drawer manually
- Approve item removal from cashier cart

### Cashier
- POS interface only
- Scan/search products
- Process payments (Cash, Card, eWallet, QR)
- Hold & resume orders
- Print receipts
- **Cannot** remove cart items without admin approval
- **Cannot** apply discounts (admin only)

---

## 💳 Supported Payment Methods

- 💵 Cash (with change calculation)
- 💳 Credit Card
- 💳 Debit Card
- 📱 DuitNow QR
- 💰 Touch 'n Go eWallet
- 🚗 GrabPay
- ⚡ Boost

---

## 🖨️ Receipt & Printer

The system supports ESC/POS thermal printers.

- Receipts are displayed on-screen after each transaction
- Use the **Print** button in the receipt modal
- Configure printer name in **Admin → Settings**
- The API returns ESC/POS byte commands for direct printer integration

---

## 🔒 Security Features

- Password hashing with `password_hash()` (bcrypt)
- PDO prepared statements (SQL injection prevention)
- Session-based authentication
- Role-based access control (RBAC)
- Session timeout (1 hour by default)
- All admin actions logged in audit trail
- Admin approval required for cart item removal

---

## ⚙️ System Settings (Admin → Settings)

| Setting | Description |
|---------|-------------|
| Store Name | Displayed on receipts and navbar |
| Tax Rate (%) | Applied to all transactions |
| Auto Cash Drawer | Open drawer automatically after cash payment |
| Cashier Manual Drawer | Allow cashiers to open drawer manually |
| Drawer Cooldown | Minimum seconds between drawer openings |
| Receipt Footer | Custom message on receipts |
| Printer Name | Printer model for reference |

---

## 🛠️ Customization

### Change Tax Rate
Go to **Admin → Settings → Tax & Currency**

### Add Product Categories
When adding products, type any category name — it auto-populates the datalist

### Currency
Change the currency symbol in **Admin → Settings** (default: RM)

---

## 📞 Support

For issues or customization requests, check the source code comments or contact your system administrator.

---

*Built with ❤️ using PHP 8, MySQL, Bootstrap 5, and Vanilla JS*
