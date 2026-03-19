<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Geocoder Service Provider
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default geocoder service provider that will be
    | used by the Geocoder library. A list of supported providers can be
    | found in the documentation.
    |
    */

    'google_api_key' => env('GOOGLE_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Geocoder API Key
    |--------------------------------------------------------------------------
    |
    | Here you may specify your API key for the geocoder service provider.
    | This key will be used to authenticate requests to the geocoding
    | service.
    |
    */

    'graph_api_key' => env('GRAPH_API_KEY', ''),

];