<?php

use Brunocfalcao\Flame\Exceptions\FlameException;
use Brunocfalcao\Flame\Renderers\Panel;

function flame(...$args)
{
    return (new Panel($args))->makeView();
}

function group_absolute_path($namespaceGroup)
{
    // Config path exists?
    if (is_null(array_get(config('flame'), "groups.{$namespaceGroup}.path"))) {
        throw FlameException::configurationNamespacePathNotFound("flame.groups.{$namespaceGroup}.path");
    }

    $value = config("flame.groups.{$namespaceGroup}.path");

    if (class_exists($value)) {
        $value = app($value)();
    } else {
        throw FlameException::configurationPathFinderNotFound($value);
    }

    return $value;
}
