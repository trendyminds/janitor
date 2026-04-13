# 🧹 Janitor
Make block organization and presentation a snap!

## Features
- Organize your blocks alphabetically: `php artisan janitor`
- Creates screenshot previews for your blocks: `php artisan janitor:previews`

## Installing
1. Run `composer require trendyminds/janitor --dev`
2. Install Puppeteer by running `php artisan janitor:install`

## Notes

Before running the preview command locally, ensure the following steps have been taken to prevent issues:
1. You've added the proper configuration to `config/statamic/assets.php` available [here](https://github.com/trendyminds/statamic-starter/blob/main/config/statamic/assets.php#L227-L241)
2. You update the search index via `php please search:update --all`
3. You run `npm run dev` to ensure vite is running
