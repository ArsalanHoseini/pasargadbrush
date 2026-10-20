<?php

return [
    // Absolute path to the local WordPress copy's wp-content/uploads directory,
    // used to copy real media files during import. Never the live site.
    'uploads_path' => env('WP_UPLOADS_PATH'),
];
