<?php

namespace SLLH\ComposerVersionsCheck;

use Composer\Composer;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Repository\ComposerRepository;
use Composer\Repository\RepositoryManager;
use Composer\Script\Event;
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
     * @var VersionsCheck
     */
    private $versionsCheck;

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
            ScriptEvents::POST_UPDATE_CMD => array(
                array('postUpdate', -100),
            ),
        );
    }

    /**
     * @param PackageEvent $event
     */
    public function prePackageUpdate(PackageEvent $event)
    {
        $operation = $event->getOperation();

        if (!$operation instanceof UpdateOperation) {
            return;
        }

        $this->versionsCheck->check($operation->getTargetPackage());
    }

    /**
     * @param Event $event
     */
    public function postUpdate(Event $event)
    {
        $this->checkVersions($event->getComposer()->getRepositoryManager());
    }

    /**
     * @param RepositoryManager $repositoryManager
     */
    private function checkVersions(RepositoryManager $repositoryManager)
    {
        $composerRepository = null;
        foreach ($repositoryManager->getRepositories() as $repository) {
            if ($repository instanceof ComposerRepository) {
                $composerRepository = $repository;
            }
            break;
        }

        if (null === $composerRepository) {
            return;
        }

        $this->versionsCheck->checkPackages($composerRepository, $repositoryManager->getLocalRepository());
        $this->io->write($this->versionsCheck->getOutput(), false);
    }
}
