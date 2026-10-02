# Lab Author Notes — PCHub Coupon Stacking Lab

## Vulnerability Design

### What is intentionally broken

1. **Coupon stacking** (`action_apply_coupon()` in `index.php`):
   - Session stores `['code', 'pct', 'factor', 'count']`
   - Each re-application multiplies factor: `$new_factor = $prev_factor * 0.8`
   - No server-side check: "has this coupon already been applied?"
   - No limit on application count

2. **Category scope bypass**:
   - DB has `coupons.intended_category_id = 10` (Accessories)
   - No code enforces this — the server never checks cart item categories before applying

### What is intentionally secure

- **Price integrity**: `cart_items` has no `price` column. All prices JOIN from `products.price`. Impossible to tamper via cart manipulation.
- **Password security**: bcrypt via `password_hash()` / `password_verify()`
- **CSRF protection**: All POST actions call `verify_csrf()` (students must capture the CSRF token)
- **SQL injection prevention**: All DB queries use PDO prepared statements
- **Auth**: Session-based; `require_login()` guards all protected routes; `require_admin()` guards admin
- **Input validation**: Quantities sanitized; email validated; no file upload attack surface

### Why CSRF token doesn't prevent the exploit

The CSRF token is scoped to the session and does NOT rotate per-request. A student can:
1. Log in and get a CSRF token
2. Use that token in Burp Repeater to send the same `apply-coupon` POST multiple times in the same session
This is by design — the training focus is on business logic, not CSRF.

## Database Schema Notes

- `coupons.intended_category_id` — populated but not enforced (the bug)
- `orders.coupon_applications` — records how many times the coupon was applied (used to trigger flag display)
- `orders.discount` — the total discount amount captured at checkout

## Flag Trigger

The flag `KP{coupon_business_logic_bypass}` is shown on the order detail page (`page_order()`) when:
```php
$flag_shown = (bool)$order['coupon_code'] && (int)$order['coupon_applications'] >= 3;
```

Students need to apply the coupon at least 3× before checkout to see the flag.

## Resetting the Lab

Visit `/install` (POST) to drop all tables and reseed. Demo accounts are re-created automatically.

## Environment

- PHP 8.2 on XAMPP (Apache + MySQL)
- URL: http://127.0.0.1/subdomains/controllers/
- Database: `pchub_lab` (auto-created on first request)
- Sessions: `PCHUBBID` cookie, path-scoped to `/subdomains/controllers`
