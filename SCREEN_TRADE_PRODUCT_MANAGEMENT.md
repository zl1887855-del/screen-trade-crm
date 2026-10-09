# Screen Trade CRM: phone screen product management

This phase extends the existing Krayin product module for phone screen trading. It reuses the current `products`, `product_inventories`, dynamic product attributes and quote/product relationships. It does not create a separate product catalog or reset existing CRM product data.

## Fields

| Business field | Implementation | Notes |
| --- | --- | --- |
| Brand | `screen_brand` product attribute | iPhone, Samsung, OPPO, vivo, Xiaomi, Honor, Huawei, Tecno, Infinix or Other. |
| Model | `screen_model` product attribute | For example iPhone 13, Samsung A12 or OPPO A57. |
| Quality grade | `screen_quality` product attribute | Original, pulled, OLED, Incell, TFT, refurbished, with frame or without frame. |
| Color | `screen_color` product attribute | Optional text. |
| Compatible models | `compatible_models` product attribute | Optional text for alternate model names or shared assemblies. |
| Supplier | `supplier_name` product attribute | Optional supplier text for first-phase tracking. |
| Cost price | `cost_price` product attribute | Decimal price. |
| Export price | `export_price_usd` product attribute | Decimal USD export price. |
| MOQ | `moq` product attribute | Numeric minimum order quantity. |
| Quote currency | `quote_currency` product attribute | USD, CNY, EUR, GBP, NGN or AED. |
| Quote valid until | `quote_valid_until` product attribute | Optional date. |
| Product notes | `product_notes` product attribute | Optional notes. |

Existing SKU, name, description, quantity, price, tags, activities and warehouse inventory continue to work. Stock quantity and warehouse location remain in the existing product inventory flow.

## Installation

Use an independent test database first. Do not run destructive installer commands against a customer database.

For an existing local Krayin test database, run:

```powershell
php artisan migrate
php artisan screen-trade:install-product-attributes
php artisan optimize:clear
```

For a brand-new empty local test database, `php artisan krayin-crm:install` may be used first, then run:

```powershell
php artisan screen-trade:install-product-attributes
```

The command is repeatable. It adds missing definitions and missing select options while preserving existing product values.

## Manual verification

1. Open Products and create a product for a phone screen, for example `IP13-OLED-BLK`.
2. Fill brand, model, quality grade, color, cost price, USD export price, MOQ, quote currency and notes.
3. Save, reopen edit, and confirm the values are still present.
4. Add warehouse inventory through the existing inventory UI and confirm stock is unchanged by editing product attributes.
5. Try invalid decimal or numeric values for cost/export price/MOQ and confirm validation rejects them.
6. Create a quote using the product and confirm the existing quote/product flow still works.

## Scope

This first product-management phase adds structured product and export quotation metadata. It does not yet add inventory movement history, tiered price tables, PDF quotation output, WhatsApp sending, AI price recommendations or supplier purchase orders.
