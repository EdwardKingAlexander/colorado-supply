<!--
Validation Checklist
- resources/images/** contains the moved assets
- No remaining refs to /img/ or /images/ unless intentionally left in public/
- All Vue image uses import URL variables
- All background images use JS imports + :style
- Blade uses Vite::asset() for images under resources/images
- vite.config.js has aliases for @ (resources/js) and @images (resources/images)
- public/build/manifest.json includes the new assets after build
-->

<!--
Commands to run:
npm ci
npm run build
php artisan optimize:clear
-->

