<?php

declare(strict_types=1);

/**
 * Example: Working with the ps_specials PrestaShop module.
 *
 * ps_specials displays a block of products that are currently on sale
 * (have an active specific price reduction). It hooks into the storefront
 * widget system to render discounted product listings.
 *
 * This file documents common usage patterns.
 */

// --- Widget invocation in Smarty/Twig template ---
// {widget name="ps_specials" hook="displayHome"}

// --- Querying products on sale programmatically ---
// PrestaShop's SpecificPrice system manages discounts:
//
// $specials = Product::getPricesDrop(
//     id_lang: (int) Context::getContext()->language->id,
//     pageNumber: 0,
//     nbProducts: 8,
//     orderBy: 'name',
//     orderWay: 'ASC',
// );
//
// foreach ($specials as $product) {
//     $discount = round(
//         ($product['price'] - $product['price_tax_exc']) / $product['price'] * 100
//     );
//     echo $product['name'] . ' — ' . $discount . '% off' . "\n";
//     echo '  Was: €' . $product['price'] . ', Now: €' . $product['specific_prices']['reduction'] . "\n";
// }

// --- Back Office: creating a specific price (sale) ---
// Catalog > Products > {Product} > Pricing > Specific Prices
//   - Set reduction type: percentage or amount
//   - Set reduction value (e.g., 20%)
//   - Set start/end dates for the sale window
//   - Apply to: all customers, specific group, or specific customer

// --- Back Office configuration ---
// Modules > Specials:
//   - Number of products to display
//   - Hook placement

// --- Template override ---
// themes/{theme}/modules/ps_specials/views/templates/hook/ps_specials.tpl
