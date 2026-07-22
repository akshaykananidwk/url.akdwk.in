# Themes

Place theme folders here. A theme mirrors `resources/views` — any Blade file
you provide overrides the stock view; everything else falls back to default.

    resources/themes/
      my-theme/
        theme.json                {"name": "My Theme", "author": "You"}
        landing/index.blade.php   overrides the landing page
        layouts/app.blade.php     overrides the user panel layout

Activate a theme in Admin → Settings → General.
