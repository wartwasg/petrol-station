# TODO
- [x] Locate fuel price update handler in `manager/dashboard.php`.
- [x] Add restriction: allow fuel price update only on the first Wednesday of the current month.(very important)
- [x] Add restriction: allow only one fuel price change per month (block if any `fuel_prices.effective_date` exists in current month).
- [x] Add popup/alert feedback when blocked.
- [x] Implement changes in `manager/dashboard.php` without affecting other page functionality.
- [ ] Quick manual test: submit update on non-allowed day and allowed day; verify DB insert is refused when blocked.


