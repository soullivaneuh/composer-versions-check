<?php

namespace SLLH\ComposerVersionsCheck\Tests;

use Composer\Command\UpdateCommand;
use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\BufferIO;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginManager;
use Composer\Repository\ArrayRepository;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableArrayRepository;
use Composer\Script\ScriptEvents;
use SLLH\ComposerVersionsCheck\VersionsCheck;
use SLLH\ComposerVersionsCheck\VersionsCheckPlugin;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class VersionsCheckPluginTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BufferIO
     */
    private $io;

    /**
     * @var Composer
     */
    private $composer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->io = new BufferIO();
        $this->composer = new Composer();
        $this->composer->setPackage(new RootPackage('my/project', '1.0.0', '1.0.0'));
        $this->composer->setPluginManager(new PluginManager($this->io, $this->composer));
        $this->composer->setEventDispatcher(new EventDispatcher($this->composer, $this->io));
        $this->composer->setRepositoryManager(new RepositoryManager($this->io, new Config()));
    }

    public function testPluginRegister()
    {
        $plugin = new VersionsCheckPlugin();
        $this->composer->getPluginManager()->addPlugin($plugin);

        $this->assertSame(array($plugin), $this->composer->getPluginManager()->getPlugins());
        $this->assertAttributeInstanceOf('Composer\Composer', 'composer', $plugin);
        $this->assertAttributeInstanceOf('Composer\IO\IOInterface', 'io', $plugin);
        $this->assertAttributeInstanceOf('SLLH\ComposerVersionsCheck\VersionsCheck', 'versionsCheck', $plugin);
    }

    public function testUpdateCommand()
    {
        $this->composer->getPluginManager()->addPlugin(new VersionsCheckPlugin());

        $localRepository = new WritableArrayRepository();
        $localRepository->addPackage(new Package('foo/bar', '1.0.0', '1.0.0'));
        $this->composer->getRepositoryManager()->setLocalRepository($localRepository);

        $distRepository = new ArrayRepository();
        $distRepository->addPackage(new Package('foo/bar', '1.0.0', '1.0.0'));
        $distRepository->addPackage(new Package('foo/bar', '1.0.1', '1.0.1'));
        $distRepository->addPackage(new Package('foo/bar', '2.0.0', '2.0.0'));
        $this->composer->getRepositoryManager()->addRepository($distRepository);

        $this->composer->getEventDispatcher()->dispatchScript(ScriptEvents::POST_UPDATE_CMD);

        $this->assertSame(<<<EOF
<warning>Some packages are not up to date:</warning>

  - foo/bar (1.0.0) last version is 2.0.0


EOF
            , $this->io->getOutput());
    }

    public function testPreferLowest()
    {
        $this->composer->getPluginManager()->addPlugin(new VersionsCheckPlugin());

        $localRepository = new WritableArrayRepository();
        $localRepository->addPackage(new Package('foo/bar', '1.0.0', '1.0.0'));
        $this->composer->getRepositoryManager()->setLocalRepository($localRepository);

        $distRepository = new ArrayRepository();
        $distRepository->addPackage(new Package('foo/bar', '1.0.0', '1.0.0'));
        $distRepository->addPackage(new Package('foo/bar', '2.0.0', '2.0.0'));
        $this->composer->getRepositoryManager()->addRepository($distRepository);

        $updateCommand = new UpdateCommand();
        $input = new ArrayInput(array('update'), $updateCommand->getDefinition());
        $input->setOption('prefer-lowest', true);
        $commandEvent = new CommandEvent(PluginEvents::COMMAND, 'update', $input, new NullOutput());
        $this->composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);
        $this->composer->getEventDispatcher()->dispatchScript(ScriptEvents::POST_UPDATE_CMD);

        $this->assertSame('', $this->io->getOutput(), 'Plugin should not be runned.');
    }

    public function testPreferLowestNotExists()
    {
        $this->composer->getPluginManager()->addPlugin(new VersionsCheckPlugin());

        $localRepository = new WritableArrayRepository();
        $localRepository->addPackage(new Package('foo/bar', '1.0.0', '1.0.0'));
        $this->composer->getRepositoryManager()->setLocalRepository($localRepository);

        $distRepository = new ArrayRepository();
        $distRepository->addPackage(new Package('foo/bar', '1.0.0', '1.0.0'));
        $distRepository->addPackage(new Package('foo/bar', '2.0.0', '2.0.0'));
        $this->composer->getRepositoryManager()->addRepository($distRepository);

        $commandEvent = new CommandEvent(PluginEvents::COMMAND, 'update', new ArrayInput(array()), new NullOutput());
        $this->composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);
        $this->composer->getEventDispatcher()->dispatchScript(ScriptEvents::POST_UPDATE_CMD);

        $this->assertSame(<<<EOF
<warning>Some packages are not up to date:</warning>

  - foo/bar (1.0.0) last version is 2.0.0


EOF
            , $this->io->getOutput());
    }
}
