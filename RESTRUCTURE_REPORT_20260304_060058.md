# Restructure report – 20260304_060058

## Summary
- All Laravel root files moved into `Backend/`.
- Safety backup: `_restructure_backup_20260304_060058/` (Backend snapshot, excluding vendor/node_modules).
- Conflicted files (existing in Backend before move): `Backend/_conflicts_backup_20260304_060058/`.
- `Flutter-App/` unchanged.

## Moved items (root → Backend)
- Directories: app, bootstrap, config, database, docs, public, resources, routes, storage, tests, postman, vendor, node_modules
- Files: artisan, composer.json, composer.lock, phpunit.xml, vite.config.js, package.json, package-lock.json, .env.example, .env_server, .env.testing, boost.json, DATABASE_MIGRATION_GUIDE.md, deploy-fix-permissions.sh, DEPLOYMENT.md, FILAMENT_TABS_GUIDE.md, google_auth_test.html, I18N_GUIDE.md, Noorly_API_v1.postman_collection.json, Noorly_Local.postman_environment.json, POSTMAN_README.md, README.md

## Conflicts (Backend copy preserved in `_conflicts_backup_20260304_060058`)
Run: `ls -laR Backend/_conflicts_backup_20260304_060058` to list.

## Next steps
- `cd Backend && composer install && php artisan --version`
- `cd Backend && php artisan serve`
- `cd Flutter-App && flutter pub get`
