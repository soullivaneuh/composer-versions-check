<?php

namespace SLLH\ComposerVersionsCheck;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Repository\RepositoryManager;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class VersionsCheckPlugin implements PluginInterface, EventSubscriberInterface
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
     * @var VersionsCheck
     */
    private $versionsCheck;

    /**
     * @var bool
     */
    private $preferLowest;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->versionsCheck = new VersionsCheck();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            PluginEvents::COMMAND => array(
                array('command'),
            ),
            ScriptEvents::POST_UPDATE_CMD => array(
                array('postUpdate', -100),
            ),
        );
    }

    /**
     * @param CommandEvent $event
     */
    public function command(CommandEvent $event)
    {
        $input = $event->getInput();
        $this->preferLowest = $input->hasOption('prefer-lowest') && true === $input->getOption('prefer-lowest');
    }

    /**
     * @param Event $event
     */
    public function postUpdate(Event $event)
    {
        if (true === $this->preferLowest) {
            return;
        }

        $this->checkVersions($this->composer->getRepositoryManager(), $this->composer->getPackage());
    }

    /**
     * @param RepositoryManager    $repositoryManager
     * @param RootPackageInterface $rootPackage
     */
    private function checkVersions(RepositoryManager $repositoryManager, RootPackageInterface $rootPackage)
    {
        foreach ($repositoryManager->getRepositories() as $repository) {
            $this->versionsCheck->checkPackages(
                $repository,
                $repositoryManager->getLocalRepository(),
                $rootPackage
            );
        }

        $this->io->write($this->versionsCheck->getOutput(), false);
    }
}
