# PCHub — Coupon Business Logic Lab

**URL:** http://127.0.0.1/subdomains/controllers/

## Lab Objective

Exploit a coupon stacking / business-logic bypass vulnerability to drastically reduce the cart total on a PC hardware e-commerce store.

## Vulnerability

Endpoint: `POST /subdomains/controllers/cart/apply-coupon`

**Bug 1 — Coupon Stacking:** The `WELCOME20` coupon (20% off) can be applied multiple times. Each application multiplies the price factor by 0.8, stacking discounts:

| Applications | Effective Discount | Factor |
|---|---|---|
| 1× | 20%  | 0.80 |
| 2× | 36%  | 0.64 |
| 3× | 48.8%| 0.512|
| 5× | 67.2%| 0.328|
| 10×| 89.3%| 0.107|

**Bug 2 — Scope Bypass:** The coupon is intended for Accessories category only. No server-side category check is enforced, so it applies to any product.

## Burp Suite Exploit

1. Add any expensive item (e.g. RTX 5090) to cart
2. Apply coupon once via the UI
3. Intercept subsequent POST requests with Burp Repeater:
   ```
   POST /subdomains/controllers/cart/apply-coupon HTTP/1.1
   Host: 127.0.0.1
   Content-Type: application/x-www-form-urlencoded

   _csrf=<your_token>&coupon_code=WELCOME20
   ```
4. Send the same request 3–5 more times
5. Proceed to checkout — the effective discount stacks
6. After order confirmation, flag appears on the order page

## Flag

`KP{coupon_business_logic_bypass}` — revealed on the order confirmation page when coupon was applied 3+ times.

## Demo Accounts

| Role    | Email                  | Password    |
|---------|------------------------|-------------|
| Admin   | admin@pchub.lab        | admin123    |
| Student | student@pchub.lab      | student123  |

## Reset

Visit http://127.0.0.1/subdomains/controllers/install to reset the database.
