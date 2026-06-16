<?php

return [
    /*
     * Full ingestion endpoint for this site, e.g.
     * https://deploy.davismw.com/api/projects/1/servers/11/sites/70/errors
     */
    'endpoint' => env('VITO_ERROR_REPORTER_ENDPOINT'),

    /*
     * Per-site ingestion token (NOT the Laravel APP_KEY). Generate/reveal it
     * from the error-monitoring screen for the site.
     */
    'token' => env('VITO_ERROR_REPORTER_TOKEN'),

    'environment' => env('VITO_ERROR_REPORTER_ENV', env('APP_ENV')),

    'release' => env('VITO_ERROR_REPORTER_RELEASE'),
];
