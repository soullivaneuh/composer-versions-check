<?php

namespace SLLH\ComposerVersionsCheck;

use Composer\Package\PackageInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class OutdatedPackage
{
    /**
     * @var PackageInterface
     */
    private $actual;

    /**
     * @var PackageInterface
     */
    private $last;

    /**
     * @param PackageInterface $actual
     * @param PackageInterface $last
     */
    public function __construct(PackageInterface $actual, PackageInterface $last)
    {
        $this->actual = $actual;
        $this->last = $last;
    }

    /**
     * @return PackageInterface
     */
    public function getActual()
    {
        return $this->actual;
    }

    /**
     * @return PackageInterface
     */
    public function getLast()
    {
        return $this->last;
    }
}
