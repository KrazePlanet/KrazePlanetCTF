# Lab Author Notes — PadHub

> **Instructor-only document. Do not share with students.**

## Learning Objective

Students should discover that the cart quantity field is accepted as any integer by the server,
including negative values, while the product price is always retrieved from the database and
cannot be tampered with by the client.

## The Flaw Location

File: `index.php` — function `action_cart_update()`

```php
function action_cart_update(): void {
    $u = require_login();
    verify_csrf();
    $pid = (int)($_POST['product_id'] ?? 0);
    // Intentionally only checks that quantity is a number, not that it is positive.
    $qty = filter_var($_POST['quantity'] ?? '', FILTER_VALIDATE_INT);
    if ($qty === false) { flash('error', 'Quantity must be a number.'); redirect('cart'); }
    $qty = (int)$qty;
    // Note: no check for qty > 0 here by design.
    ...
```

The server validates that `quantity` is an integer but does **not** validate that it is positive.

## What is Protected (Price Integrity)

The `cart_items` table has no `price` column. Every cart read in `cart_lines()` does:

```sql
SELECT c.quantity, p.price, (p.price * c.quantity) AS subtotal
FROM cart_items c JOIN products p ON p.id = c.product_id
```

The price always comes from `products.price`. There is no way for a client POST to change it.

## Intended Attack Flow

1. Log in and add **NiTHO V80 Bluetooth Gamepad** (₹2,299) to the cart with quantity 1.
2. Add **Ant Esports MG15 Super Cube Wireless Gamepad** (₹979) with quantity 1 (intercept with Burp).
3. In Burp, intercept `POST /subdomains/controllers/cart/add` and forward it normally — the item is now in the cart.
4. Go to Cart. Click **Update** on the MG15 row.
5. Intercept the `POST /subdomains/controllers/cart/update` request:
   ```
   product_id=2&quantity=1&_csrf=...
   ```
6. Change `quantity=1` to `quantity=-2` and forward.
7. The cart now shows:
   ```
   NiTHO V80     ×  1  =  ₹2,299
   MG15 Super    × -2  =  -₹1,958
   ──────────────────────────────
   Total              =  ₹341
   ```
8. Proceed through Checkout → Payment. The order is saved and confirmed at ₹341.

## Demonstrating That Price Tampering Fails

Ask the student to also try changing the POST body at `cart/add` to include `price=1`.
Because `cart_items` stores no price and `action_cart_add()` ignores any client-supplied
price field, the attempt has no effect. The cart will show the correct database price.

## Reset

To reset the database between student runs, visit `/opt/lampp/htdocs/subdomains/controllers/`
and execute the SQL manually, or add a one-time reset call in `lib/bootstrap.php`:

```php
install_schema(db(), reset: true);
```

Remove it again after the first request.
