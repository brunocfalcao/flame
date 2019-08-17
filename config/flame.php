<?php

use Brunocfalcao\Flame\PathFinders\FlamePathFinder;

return [

    'groups' => [
        /* Flame namespace groups.
            You need to specify your flame groups that you want to create features inside.
            Accepts 1 group key (namespace group) and 2 keys inside (namespace and path).

            '<namespace_group>' => [
                'namespace' => '<your feature namespace root>',
                'path' => '<the physical path to your feature namespace root>'
            ]
        */

        'demo' => [
            'namespace' => 'Brunocfalcao\Flame\Features',
            'path'      => FlamePathFinder::class,
        ],

        /* Suggested namespace group for your Laravel app.
            It will create features in your Your/Application/Features folder.
            You need to replace the \Your\Application\Features to your base
            namespace where you want the Features to be created.

            You need to replace the path by a specific string, function or
            an invokable class (__invoke()).
            E.g.:
            'path'      => FlamePathFinder::class,
        */
        'flame' => [
            'namespace' => 'Your\Application\Features',
            'path'      => null,
        ],
        /*****/
    ],

    /* Shows the demo route. You can put it false sp the /flame route path is not loaded. */
    'demo' => [
        'route' => true,
    ],
];
