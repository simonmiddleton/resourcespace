<?php

namespace Muglug\PackageVersions;

/**
 * This is a stub class: it is in place only for scenarios where PackageVersions
 * is installed with a `--no-scripts` flag, in which scenarios the Versions class
 * is not being replaced.
 *
 * If you are reading this docBlock inside your `vendor/` dir, then this means
 * that PackageVersions didn't correctly install, and is in "fallback" mode.
 */
final class Versions
{
    public static $VERSIONS = array();

    private function __construct()
    {
    }

    /**
     * @throws \OutOfBoundsException if a version cannot be located
     * @throws \UnexpectedValueException if the composer.lock file could not be located
     * @param string $packageName
     * @return string
     */
    public static function getVersion($packageName)
    {
        return FallbackVersions::getVersion($packageName);
    }

    /**
     * @throws \OutOfBoundsException if a version cannot be located
     */
    public static function getShortVersion($packageName)
    {
        return explode('@', static::getVersion($packageName))[0];
    }

    /**
     * @throws \OutOfBoundsException if a version cannot be located
     */
    public static function getMajorVersion($packageName)
    {
        return explode('.', static::getShortVersion($packageName))[0];
    }
}
