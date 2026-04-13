# 🧹 Janitor
Make block organization and presentation a snap!

## Features
- Organize your blocks alphabetically: `php artisan janitor`
- Creates screenshot previews for your blocks: `php artisan janitor:previews`

## Installing
1. Run `composer require trendyminds/janitor --dev`
2. Install Puppeteer by running `php artisan janitor:install`
3. Ensure your sets are configured to use the preview folder in `config/statamic/assets.php`:
```php
  /*
  |--------------------------------------------------------------------------
  | Replicator and Bard Set Preview Images
  |--------------------------------------------------------------------------
  |
  | Replicator and Bard sets may have preview images to give users a visual
  | representation of the content within. Here you may specify the asset
  | container and folder where these preview images are to be stored.
  |
  */

  'set_preview_images' => [
      'container' => 'uploads',
      'folder' => '_janitor',
  ],
```
