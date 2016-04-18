<?php

namespace SLLH\ComposerVersionsCheck;

use Composer\Package\AliasPackage;
use Composer\Package\LinkConstraint\VersionConstraint;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\ArrayRepository;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Semver\Comparator;
use Composer\Semver\Constraint\Constraint;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class VersionsCheck
{
    /**
     * @var OutdatedPackage[]
     */
    private $outdatedPackages = array();

    /**
     * @var VersionConstraint|null
     */
    private $oldComparator = null;

    /**
     * @param ArrayRepository             $distRepository
     * @param WritableRepositoryInterface $localRepository
     * @param RootPackageInterface        $rootPackage
     */
    public function checkPackages(ArrayRepository $distRepository, WritableRepositoryInterface $localRepository, RootPackageInterface $rootPackage)
    {
        $packages = $localRepository->getPackages();
        foreach ($packages as $package) {
            // Get source of alias packages to have real used version.
            if ($package instanceof AliasPackage) {
                $package = $package->getAliasOf();
            }

            // Old composer versions BC
            $versionConstraint = class_exists('Composer\Semver\Constraint\Constraint')
                ? new Constraint('>', $package->getVersion())
                : new VersionConstraint('>', $package->getVersion())
            ;

            $higherPackages = $distRepository->findPackages($package->getName(), $versionConstraint);
            // Remove not stable packages if unwanted
            if (true === $rootPackage->getPreferStable()) {
                $higherPackages = array_filter($higherPackages, function (PackageInterface $package) {
                    return 'stable' === $package->getStability();
                });
            }
            if (count($higherPackages) > 0) {
                // PHP 5.3 BC
                $that = $this;
                // Sort packages by highest version to lowest
                usort($higherPackages, function (PackageInterface $p1, PackageInterface $p2) use ($that) {
                    return $that->versionCompare($p1->getVersion(), '<', $p2->getVersion());
                });
                // Push actual and last package on outdated array
                array_push($this->outdatedPackages, new OutdatedPackage($package, $higherPackages[0]));
            }
        }
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        $output = array();

        if (0 === count($this->outdatedPackages)) {
            $output[] = '<info>All packages are up to date.</info>';
        } else {
            $this->createNotUpToDateOutput($output);
        }

        return implode(PHP_EOL, $output).PHP_EOL;
    }

    /**
     * Version comparator bridge to handle BC with old composer versions.
     *
     * This method is public only for PHP 5.3 BC and SHOULD NOT be used.
     * Deprecate it and remove it on next major when PHP 5.3 support will be droped.
     *
     * @param string $version1
     * @param string $operator
     * @param string $version2
     *
     * @return bool
     */
    public function versionCompare($version1, $operator, $version2)
    {
        if (!class_exists('Composer\Semver\Comparator')) {
            $this->oldComparator = $this->oldComparator ?: new VersionConstraint('==', '1.0');

            return $this->oldComparator->versionCompare($version1, $version2, $operator);
        }

        return Comparator::compare($version1, $operator, $version2);
    }

    private function createNotUpToDateOutput(array &$output)
    {
        $outdatedPackagesCount = count($this->outdatedPackages);
        $output[] = sprintf(
            '<warning>%d %s not up to date:</warning>',
            $outdatedPackagesCount,
            1 != $outdatedPackagesCount ? 'packages are' : 'package is'
        );
        $output[] = '';

        foreach ($this->outdatedPackages as $outdatedPackage) {
            $output[] = sprintf(
                '  - <info>%s</info> (<comment>%s</comment>) latest is <comment>%s</comment>',
                $outdatedPackage->getActual()->getPrettyName(),
                $outdatedPackage->getActual()->getPrettyVersion(),
                $outdatedPackage->getLast()->getPrettyVersion()
            );
        }

        $output[] = '';
    }
}
