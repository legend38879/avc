# AM4 Alliance Web Application — Setup & Deployment Guide

## ✅ What's Included

```
am4alliance/
├── index.php               ← Main homepage (integrated frontend)
├── .htaccess               ← Security & routing rules
├── database.sql            ← Full database schema + default data
├── includes/
│   ├── config.php          ← Database config (EDIT THIS FIRST)
│   └── auth.php            ← Session, auth helpers, sanitization
├── pages/
│   ├── login.php           ← Login & Signup page (tabbed)
│   ├── logout.php          ← Logout handler
│   ├── fuel.php            ← Fuel Forecast (login required)
│   ├── chat.php            ← Chat (public + alliance rooms)
│   ├── join.php            ← Alliance application form
│   └── banned.php          ← Banned page + appeal form
├── api/
│   ├── chat.php            ← Chat GET/POST API (AJAX polling)
│   └── notifications.php   ← Notifications API
└── admin/
    └── index.php           ← Full admin panel (admin/owner only)
```

---

## 🚀 InfinityFree Deployment Steps

### Step 1: Create InfinityFree Account
1. Go to https://infinityfree.com and create a free account
2. Create a new hosting account from the dashboard
3. Note your **FTP credentials** and **cPanel URL**

### Step 2: Create Database
1. Open your InfinityFree **cPanel**
2. Click **MySQL Databases**
3. Create a new database (e.g., `if3691234_am4`)
4. Create a new database user with a strong password
5. Add the user to the database with **All Privileges**
6. Click **phpMyAdmin** in cPanel
7. Select your database, click the **SQL** tab
8. **Paste the entire contents of `database.sql`** and click Go

### Step 3: Configure the App
Open `includes/config.php` and edit:

```php
define('DB_HOST', 'sql200.infinityfree.com'); // Check your cPanel for exact host
define('DB_USER', 'if3691234_username');       // Your DB username
define('DB_PASS', 'yourpassword');             // Your DB password  
define('DB_NAME', 'if3691234_am4');            // Your DB name
define('SITE_URL', 'https://yourdomain.infinityfreeapp.com'); // Your actual URL
```

> ⚠️ **IMPORTANT**: The DB host varies — check cPanel → MySQL Databases for your exact host

### Step 4: Upload Files
Using **FileZilla** (free FTP client):
1. Connect with your InfinityFree FTP credentials from cPanel
2. Navigate to `htdocs/` folder (this is your web root)
3. Upload ALL files from the `am4alliance/` folder INTO `htdocs/`
4. Your structure should be: `htdocs/index.php`, `htdocs/includes/`, etc.

### Step 5: First Login
Default owner account:
- **Username**: `owner`
- **Password**: `password`

> 🔴 **CHANGE THIS PASSWORD IMMEDIATELY** after first login!
> Go to MySQL in cPanel → phpMyAdmin → users table → edit the owner row
> Generate a new bcrypt hash at: https://bcrypt-generator.com
> Paste the hash into the `password_hash` column

---

## 🔐 Security Checklist

- [ ] Changed default owner password
- [ ] Updated DB credentials in config.php
- [ ] Verified .htaccess is blocking `/includes/` access
- [ ] Set SITE_URL to your actual domain with HTTPS

---

## 📋 Feature Overview

| Feature | Guest | Logged In | Admin | Owner |
|---------|-------|-----------|-------|-------|
| View alliances | ✅ | ✅ | ✅ | ✅ |
| Fuel Forecast | ❌ | ✅ | ✅ | ✅ |
| Public Chat | ❌ | ✅ | ✅ | ✅ |
| Alliance Chat | ❌ | ✅ (own alliance) | ✅ | ✅ |
| Join Alliance | ❌ | ✅ | ✅ | ✅ |
| Manage Applications | ❌ | ❌ | ✅ | ✅ |
| Ban/Unban Users | ❌ | ❌ | ✅ | ✅ |
| Edit Alliance Data | ❌ | ❌ | ✅ | ✅ |
| Add Custom Members | ❌ | ❌ | ✅ | ✅ |
| Manage Admins | ❌ | ❌ | ❌ | ✅ |
| Unban Appeals | ❌ | ❌ | ❌ | ✅ |

---

## 💬 Chat System

- **Public Chat**: All logged-in users, polls every 3 seconds (AJAX)
- **Alliance Chat**: Only accessible by members of that specific alliance
- Messages are stored in MySQL and fetched via `/api/chat.php`

---

## 🔔 Notifications

Triggered automatically for:
- Alliance application approved/rejected
- Account banned/unbanned
- Appeal approved/rejected

Shown in the notification bell (top-right), polled on demand.

---

## 🛡️ Admin Panel

Access at: `https://yourdomain.com/admin/`

**Admin features**:
- Approve/reject alliance applications
- Edit alliance names, tags, values, member counts, ranks
- Add custom members (non-website airlines) to alliances
- Ban/unban users with reason

**Owner-only features**:
- Everything admins can do
- Approve/reject unban appeals
- Promote users to admin
- Revoke admin status

---

## ❓ Troubleshooting

**White screen / 500 error**:
- Check DB credentials in `config.php`
- Make sure all tables exist (re-run database.sql)
- Ensure PHP 7.4+ is being used

**Can't connect to DB**:
- Verify the DB host — InfinityFree uses `sql200.infinityfree.com` or similar. Check cPanel.
- Ensure the DB user has full privileges

**Chat not working**:
- Check your browser console for AJAX errors
- Ensure SITE_URL in config.php exactly matches your domain (no trailing slash)

**Login not working**:
- Clear browser cookies and try again
- Make sure PHP sessions are enabled (they are by default on InfinityFree)
