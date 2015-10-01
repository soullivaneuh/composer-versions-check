<?php

namespace SLLH\ComposerVersionsCheck;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\PackageEvent;
use Composer\Script\ScriptEvents;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class VersionsCheckPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_PACKAGE_UPDATE => array(
                array('postPackageUpdate'),
            ),
            ScriptEvents::POST_UPDATE_CMD => array(
                array('postUpdate'),
            ),
        );
    }

    /**
     * @param PackageEvent $event
     */
    public function postPackageUpdate(PackageEvent $event)
    {
        die('postPackageUpdate');
    }

    /**
     * @param Event $event
     */
    public function postUpdate(Event $event)
    {
        die('postUpdate');
    }
}
