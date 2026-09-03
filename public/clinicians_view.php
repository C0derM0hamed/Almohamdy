<?php

// Keep the legacy bookmark compatible with Nginx PHP locations. Laravel's
// route for /clinicians_view.php performs the authenticated redirect to the
// new clinics directory screen.
require __DIR__.'/index.php';
