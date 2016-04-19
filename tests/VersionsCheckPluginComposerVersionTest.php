<?php

namespace SLLH\ComposerVersionsCheck\Tests;

use Composer\Command\UpdateCommand;
use Composer\Composer;
use Composer\Config;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\BufferIO;
use Composer\Package\RootPackage;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginManager;
use Composer\Repository\ArrayRepository;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableArrayRepository;
use Composer\Script\ScriptEvents;
use SLLH\ComposerVersionsCheck\VersionsCheckPlugin;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * The only goal of this class is to test composer version check.
 *
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
class VersionsCheckPluginComposerVersionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BufferIO
     */
    private $io;

    /**
     * @var Composer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $composer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->io = new BufferIO();
        $this->composer = $this->getMock('Composer\Composer');

        $this->composer->expects($this->any())->method('getConfig')
            ->willReturn(new Config(false));
        $this->composer->expects($this->any())->method('getPackage')
            ->willReturn(new RootPackage('my/project', '1.0.0', '1.0.0'));
        $this->composer->expects($this->any())->method('getPluginManager')
            ->willReturn(new PluginManager($this->io, $this->composer));
        $this->composer->expects($this->any())->method('getEventDispatcher')
            ->willReturn(new EventDispatcher($this->composer, $this->io));
        $this->composer->expects($this->any())->method('getRepositoryManager')
            ->willReturn(new RepositoryManager($this->io, new Config()));
    }

    public function testComposerVersionMessage()
    {
        $this->addComposerPlugin(new VersionsCheckPlugin());

        $this->composer->getRepositoryManager()->setLocalRepository(new WritableArrayRepository());
        $this->composer->getRepositoryManager()->addRepository(new ArrayRepository());

        $updateCommand = new UpdateCommand();
        $input = new ArrayInput(array('update'), $updateCommand->getDefinition());
        $commandEvent = new CommandEvent(PluginEvents::COMMAND, 'update', $input, new NullOutput());
        $this->composer->getEventDispatcher()->dispatch($commandEvent->getName(), $commandEvent);
        $this->composer->getEventDispatcher()->dispatchScript(ScriptEvents::POST_UPDATE_CMD);

        if (VersionsCheckPlugin::satisfiesComposerVersion()) {
            $this->assertSame('', $this->io->getOutput());
        } else {
            $this->assertSame(
                'Composer v'.Composer::VERSION.' is not supported by sllh/composer-versions-check plugin, please upgrade to v'.VersionsCheckPlugin::COMPOSER_MIN_VERSION." or higher.\n",
                $this->io->getOutput()
            );
        }
    }

    private function addComposerPlugin(PluginInterface $plugin)
    {
        $pluginManagerReflection = new \ReflectionClass($this->composer->getPluginManager());
        $addPluginReflection = $pluginManagerReflection->getMethod('addPlugin');
        $addPluginReflection->setAccessible(true);
        $addPluginReflection->invoke($this->composer->getPluginManager(), $plugin);
    }
}
