<?php declare(strict_types=1);

namespace NotSoSimple\DataObjects;

use NotSoSimple\Exceptions\AccessDeniedException;

/**
 * @psalm-immutable
 */
class Cwd
{
    public static function get(): string
    {
        $cwd = getcwd();
        if ($cwd === false) {
            throw new AccessDeniedException('Unable to access current working directory.', 21);
        }
        
        return $cwd;
    }
}
