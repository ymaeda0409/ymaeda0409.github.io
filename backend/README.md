# Malawi Bento — Backend (Laravel 13)

See the [root README](../README.md) for setup, migrations, seeding, tests and the language-addition guide.

Key entry points:

| Area | Location |
|---|---|
| Routes | `routes/api.php` |
| Locale resolution | `app/Http/Middleware/SetLocale.php`, `app/Services/LocaleService.php` |
| DB translations | `app/Models/Concerns/HasTranslations.php`, `app/Services/TranslationService.php` |
| Tenant scoping / RBAC | `app/Models/Concerns/BelongsToTenant.php`, `app/Enums/UserRole.php`, `app/Enums/Permission.php`, `app/Policies/` |
| Store search | `app/Services/Delivery/StoreLocatorService.php` |
| OTP auth | `app/Services/Auth/` |
| Error envelope | `bootstrap/app.php`, `app/Support/ApiResponse.php`, `app/Enums/ErrorCode.php` |
| Translation files | `lang/{en,ny,ja}/` |
| Platform config | `config/bento.php` |
