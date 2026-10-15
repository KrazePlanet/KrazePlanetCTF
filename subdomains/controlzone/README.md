# PadHub — Gaming Controller Store

A local e-commerce training application running on PHP 8 + MySQL.

## Requirements

* XAMPP (Apache + MySQL + PHP 8.x) running on the local machine.

## Installation

1. Place the `controllers/` folder inside `htdocs/subdomains/` (already done if you are reading this).
2. The database `controllers_lab` is created automatically on the first request — no manual SQL import needed.
3. Make sure Apache `mod_rewrite` is enabled and `AllowOverride All` is set for the `htdocs` directory tree.

## Starting the Application

```
sudo /opt/lampp/lampp startapache
sudo /opt/lampp/lampp startmysql
```

Then open:

```
http://127.0.0.1/subdomains/controlzone/
```

## Default Account

| Field    | Value               |
|----------|---------------------|
| Email    | student@lab.local   |
| Password | student123          |

## Application URL Map

| Page           | URL                                          |
|----------------|----------------------------------------------|
| Home           | /subdomains/controlzone/                     |
| Register       | /subdomains/controlzone/register             |
| Login          | /subdomains/controlzone/login                |
| Product detail | /subdomains/controlzone/product/{id}         |
| Cart           | /subdomains/controlzone/cart                 |
| Cart update    | /subdomains/controlzone/cart/update  (POST)  |
| Cart remove    | /subdomains/controlzone/cart/remove  (POST)  |
| Checkout       | /subdomains/controlzone/checkout             |
| Orders         | /subdomains/controlzone/orders               |
| Order detail   | /subdomains/controlzone/order/{id}           |
| Profile        | /subdomains/controlzone/profile              |

## Database Configuration

Override the defaults with environment variables if needed:

| Variable     | Default          |
|--------------|------------------|
| LAB_DB_HOST  | 127.0.0.1        |
| LAB_DB_USER  | root             |
| LAB_DB_PASS  | (empty)          |
| LAB_DB_NAME  | controllers_lab  |
