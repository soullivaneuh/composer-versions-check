<?php

namespace SLLH\ComposerVersionsCheck;

use Composer\Package\Link;
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
     * @var Link[]
     */
    private $links = array();

    /**
     * @param Link[] $links
     */
    public function __construct(PackageInterface $actual, PackageInterface $last, array $links = null)
    {
        $this->actual = $actual;
        $this->last = $last;
        if (null !== $links) {
            $this->links = $links;
        }
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

    /**
     * @return Link[]
     */
    public function getLinks()
    {
        return $this->links;
    }
}
