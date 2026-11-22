# Product Search Pro — Phase 2

## Contents
- `product-search-phase2.php` — WordPress plugin entry point.
- `assets/style.css` and `assets/script.js` — public product search interface.
- `standalone-demo.html` — local browser demo with a local Owner/Product Manager panel.

## WordPress installation (live site)
1. Back up your WordPress site first.
2. In WordPress Dashboard, open **Plugins → Add New Plugin → Upload Plugin**.
3. Upload `product-search-phase2.zip`, install, and activate it.
4. Create or edit a page and place this shortcode in the page content: `[product_search]`.
5. Publish the page. Open **Products** in the dashboard to add/edit products.
6. For each product, fill in its title and description, 8-digit Product ID, category, price, optional sale price, stock quantity, and optional SKU. Set a Featured Image. Publish to make it searchable; Draft products remain hidden.

## Local demo
Open `standalone-demo.html` in a browser. Press **Owner: Manage Products** to add/edit/delete sample products. The local demo saves to that browser's Local Storage on that device. It is only a demonstration; it does not have real authentication and does not sync with WordPress or other devices.

## Notes
- Public search matches product name, category, SKU, and exact 8-digit Product ID.
- Product IDs are stored as strings so leading zeros are preserved.
- Sample products appear on the WordPress search page until the owner publishes their first real product.
- WordPress product management is protected by WordPress login/capability checks and nonce validation.
- This ZIP does not implement checkout, payment processing, orders, shipping, or inventory synchronization with third-party systems.
