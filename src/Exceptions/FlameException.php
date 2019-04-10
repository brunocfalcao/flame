<?php

namespace Brunocfalcao\Flame\Exceptions;

use Exception;

class FlameException extends Exception
{
    public static function PanelNotFound($feature)
    {
        return new static(sprintf("Your Feature %s don't have a possible Panel view path", $feature));
    }

    public static function namespaceNotFound($feature)
    {
        return new static(sprintf('No namespace found for feature %s', $feature));
    }

    public static function twinkleNotFound($twinkle)
    {
        return new static(sprintf('Twinkle %s not found', $twinkle));
    }

    public static function configurationNamespacePathNotFound($path)
    {
        return new static(sprintf('Configuration key %s invalid, null or inexistent. Please check your flame.php configuration file', $path));
    }
}
