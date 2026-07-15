<?php

return [
    'page_id' => env('FACEBOOK_PAGE_ID'),
    'page_access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN'),
    'graph_version' => env('FACEBOOK_GRAPH_VERSION', 'v23.0'),
    'dry_run' => env('FACEBOOK_AUTOMATION_DRY_RUN', true),
];
