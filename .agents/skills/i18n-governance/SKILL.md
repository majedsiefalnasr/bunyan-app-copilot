---
name: i18n-governance
description: Arabic/English, RTL layout, translation keys
---

# i18n Governance — Bunyan

## Language Configuration

- **Primary**: Arabic (ar) — RTL
- **Secondary**: English (en) — LTR
- **Default locale**: ar

## Translation File Structure

### Backend (Laravel)

```
backend/resources/lang/
├── ar/
│   ├── auth.php
│   ├── pagination.php
│   ├── validation.php
│   ├── projects.php
│   ├── orders.php
│   └── common.php
└── en/
    ├── auth.php
    ├── pagination.php
    ├── validation.php
    ├── projects.php
    ├── orders.php
    └── common.php
```

### Frontend (Nuxt)

```
frontend/locales/
├── ar.json
└── en.json
```

## Translation Key Rules

1. **Dot notation** with category prefix: `projects.status.active` → `نشط`
2. **Snake_case** for keys: `validation.field_required`
3. **Never hardcode** Arabic/English text in templates or code
4. **Always provide both** ar + en translations
5. **Use parameters** for dynamic values: `projects.budget_remaining` → `الميزانية المتبقية: :amount ريال`

## RTL Layout Rules

1. All layouts must use `dir="rtl"` as default
2. Use CSS logical properties:
   - `margin-inline-start` / `margin-inline-end`
   - `padding-inline-start` / `padding-inline-end`
   - `border-inline-start` / `border-inline-end`
3. Number formatting: Use Arabic-Hindi numerals optionally (٠١٢٣٤٥٦٧٨٩)
4. Currency: Saudi Riyal (SAR / ر.س)
5. Date format: Arabic locale — `Intl.DateTimeFormat('ar-SA')`

## Validation Messages

All validation error messages must be provided in Arabic:

```php
'name.required' => 'حقل الاسم مطلوب',
'email.email' => 'البريد الإلكتروني غير صالح',
'budget.min' => 'الميزانية يجب أن تكون أكبر من :min',
```
