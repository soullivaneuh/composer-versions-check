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
use Composer\Semver\Semver;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class VersionsCheckPlugin implements PluginInterface, EventSubscriberInterface
{
    const COMPOSER_MIN_VERSION = '1.0.0-stable';

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
     * @var array
     */
    private $options = array();

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        try {
            if (!static::satisfiesComposerVersion(true)) {
                $io->writeError(sprintf(
                    '<error>Composer v%s is not supported by sllh/composer-versions-check plugin,'
                    .' please upgrade to v%s or higher.</error>',
                    Composer::VERSION,
                    self::COMPOSER_MIN_VERSION
                ));
            }
        } catch (\UnexpectedValueException $e) {
            $io->write('<warning>You are running an unstable version of composer.'
                .' The sllh/composer-versions-check plugin might not works as expected.</warning>');
        }

        $this->composer = $composer;
        $this->io = $io;
        $this->versionsCheck = new VersionsCheck();
        $this->options = $this->resolveOptions();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        // Do not subscribe the plugin if not compatible.
        if (!static::satisfiesComposerVersion()) {
            return array();
        }

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
     * @param bool $allowException
     *
     * @return bool
     */
    public static function satisfiesComposerVersion($allowException = false)
    {
        try {
            return Semver::satisfies(Composer::VERSION, sprintf('>=%s', static::COMPOSER_MIN_VERSION));
        } catch (\UnexpectedValueException $e) {
            if (true === $allowException) {
                throw $e;
            }
        }

        // Can't determine version. Assuming it satisfies.
        return true;
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
     * Tries to get plugin options and resolves them.
     *
     * @return array
     */
    private function resolveOptions()
    {
        $pluginConfig = $this->composer->getConfig()
            ? $this->composer->getConfig()->get('sllh-composer-versions-check')
            : null
        ;

        $options = array(
            'show-links' => false,
        );

        if (null === $pluginConfig) {
            return $options;
        }

        $options['show-links'] = isset($pluginConfig['show-links']) ? (bool) $pluginConfig['show-links'] : $options['show-links'];

        return $options;
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

        $this->io->write($this->versionsCheck->getOutput($this->options['show-links']), false);
    }
}
