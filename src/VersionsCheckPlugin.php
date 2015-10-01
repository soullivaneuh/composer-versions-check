<?php

namespace SLLH\ComposerVersionsCheck;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class VersionsCheckPlugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        die('version-check-plugin');
    }
}
