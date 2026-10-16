<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bill of Materials
    |--------------------------------------------------------------------------
    |
    | max_depth limits how many levels a nested BOM (sub-assemblies of
    | configurable child products) may have, counting the top level as 1.
    |
    */

    'bom' => [
        'max_depth' => (int) env('BOM_MAX_DEPTH', 5),
    ],

];
