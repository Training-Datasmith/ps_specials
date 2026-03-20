# Architecture: ps_specials

## Purpose

A PrestaShop front-office widget module that displays products currently on special offer (with an active price reduction) on the homepage or in widget positions.

## Directory Structure

```
ps_specials.php                - Module class; hook listeners, widget rendering, cache invalidation
views/templates/hook/          - Smarty template for the specials widget
upgrade/                       - SQL/PHP migration scripts
tests/                         - PHPUnit test stubs and PHPStan bootstrap
translations/                  - Locale string overrides
```

## Key Design Decisions

- **WidgetInterface**: Implements `WidgetInterface` for theme-editor positioning.
- **Cache-invalidating hooks**: Listens to specific price/product mutation events to flush the template cache when specials change.
- **Query via Product ORM**: Delegates special product fetching to PrestaShop's `Product::getPricesDrop()` method.

## Extension Points

- Override `getWidgetVariables()` to filter specials by category or add custom sorting.

## Dependency Flow

```
ps_specials (Module + WidgetInterface)
  └─> renderWidget()            — renders the specials template
        └─> getWidgetVariables()
              └─> Product::getPricesDrop()
  └─> hookAction*()             — cache invalidation
```
