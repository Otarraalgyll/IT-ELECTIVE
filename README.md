# Common Goods — PHP ecommerce starter

PHP 8.1+ with PDO SQLite. No Composer or JavaScript dependencies.

## Run

```sh
cd /data/data/com.termux/files/home/php-ecommerce
php -d opcache.enable=0 -d opcache.jit=disable -S 127.0.0.1:8000 -t public
```

Open http://127.0.0.1:8000 in your browser.

Includes a responsive sample catalog, session cart, quantity updates/removal, delivery form, server-side totals, shipping calculation, cash-on-delivery checkout, and SQLite order persistence. Prices are PHP pesos; shipping is ₱120 below ₱2,000 and free above that threshold. Quantities are limited to 20 per product.

Edit products in `app.php` and styling in `public/style.css`. Orders are stored in `storage/shop.sqlite`, created automatically on first request. Back up this file and restrict access: it contains customer delivery information. The web root must be `public/`, never the project root.

Each product has **Add to bag** and **Buy now** options. Buy now adds one of that item to the current bag and opens checkout, retaining other items. Review the order summary before placing your cash-on-delivery order.

This is a starter with sample products and illustrative emoji artwork. It does not send emails, process online payments, manage inventory, or include an admin dashboard. Order confirmation means the order was saved locally; fulfillment is manual. For production, configure an HTTPS PHP web server with PDO SQLite, disable public error display, and replace the sample catalog. The built-in server is for local development only.
