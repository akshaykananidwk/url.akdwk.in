<?php

namespace Addons\HelloWorld;

use App\Support\Hook;
use Illuminate\Support\ServiceProvider;

/**
 * Example addon service provider.
 *
 * Everything an addon can do is shown here:
 *  - listen to action hooks fired by the core (click_recorded, user_registered,
 *    before_redirect, payment_completed, link_created, ...)
 *  - modify data through filter hooks (redirect_destination, user_menu,
 *    admin_menu, payment_gateways, addon_settings_pages, ...)
 *  - register routes (routes/web.php in the addon folder is auto-loaded)
 *  - ship views (the views/ folder is registered under the addon slug
 *    namespace: view('hello-world::page'))
 *  - ship migrations (migrations/ runs when the addon is enabled)
 */
class HelloWorldServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Add a menu item to the user panel sidebar.
        Hook::addFilter('user_menu', function (array $items) {
            $items[] = [
                'route' => 'hello-world.index',
                'icon' => 'sparkles',
                'label' => 'Hello World',
                'match' => 'hello-world*',
            ];

            return $items;
        });

        // Log every recorded click (example of an action hook).
        Hook::addAction('click_recorded', function ($click, $link) {
            logger()->debug("[hello-world] click #{$click->id} on {$link->alias}");
        });
    }
}
