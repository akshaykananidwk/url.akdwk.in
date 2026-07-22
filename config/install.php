<?php

return [

    // Application version — bump on each release; /update compares against
    // the installed_version setting to offer pending migrations.
    'version' => '1.0.0',

    // Require a purchase code during installation (step 2). Off by default;
    // set INSTALL_REQUIRE_LICENSE=true to enable the license step.
    'require_license' => env('INSTALL_REQUIRE_LICENSE', false),

];
