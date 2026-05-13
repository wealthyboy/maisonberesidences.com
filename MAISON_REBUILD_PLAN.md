# Maison Beresidences Rebuild Plan

Fresh Laravel 12 app for `maisonberesidences.com`.

## Source Of Truth

- Functional reference: `/Applications/XAMPP/xamppfiles/htdocs/myshortlet.com`
- Maison database: `maisonbe`
- Maison UI direction: Tailwind for both backend/admin and frontend

## Porting Order

1. Authentication and admin access
2. Core models and migrations from myshortlet
3. Admin layout with Tailwind sidebar/navigation
4. Dashboard
5. Apartments/properties CRUD
6. Image/video uploader
7. Reservations and check-in
8. Invoices
9. Vouchers
10. Peak periods
11. Public frontend pages

## Guiding Rule

Keep the business logic and URL behavior from myshortlet where possible, but rebuild Blade/UI surfaces with Tailwind instead of Bootstrap/material-dashboard.
