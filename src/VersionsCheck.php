<?php

namespace SLLH\ComposerVersionsCheck;

use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Repository\ArrayRepository;
use Composer\Repository\ComposerRepository;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Semver\Comparator;
use Composer\Semver\Constraint\Constraint;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 */
final class VersionsCheck
{
    /**
     * @var array an array of array(PackageInterface, PackageInterface). Actual and last package
     */
    private $outdatedPackages = array();

    /**
     * @param ArrayRepository             $composerRepository
     * @param WritableRepositoryInterface $localRepository
     * @param RootPackageInterface        $rootPackage
     */
    public function checkPackages(ArrayRepository $composerRepository, WritableRepositoryInterface $localRepository, RootPackageInterface $rootPackage)
    {
        // Var comment to be removed if following PR is merged: https://github.com/composer/composer/pull/4469
        /** @var PackageInterface[] $packages */
        $packages = $localRepository->getPackages();
        foreach ($packages as $package) {
            /** @var PackageInterface[] $higherPackages */
            $higherPackages = $composerRepository->findPackages($package->getName(), new Constraint('>', $package->getVersion()));
            // Remove not stable packages if unwanted
            if (true === $rootPackage->getPreferStable()) {
                $higherPackages = array_filter($higherPackages, function (PackageInterface $package) {
                    return 'stable' === $package->getStability();
                });
            }
            if (count($higherPackages) > 0) {
                // Sort packages by highest version to lowest
                usort($higherPackages, function (PackageInterface $p1, PackageInterface $p2) {
                    return Comparator::lessThan($p1->getVersion(), $p2->getVersion());
                });
                // Push actual and last package on outdated array
                array_push($this->outdatedPackages, array($package, $higherPackages[0]));
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

        return implode("\n", $output)."\n";
    }

    private function createNotUpToDateOutput(array &$output)
    {
        $output[] = '<warning>Some packages are not up to date:</warning>';
        $output[] = '';

        /** @var PackageInterface[] $packages */
        foreach ($this->outdatedPackages as $packages) {
            /** @var PackageInterface $actual */
            /** @var PackageInterface $last */
            list($actual, $last) = $packages;
            $output[] = sprintf(
                ' - <info>%s</info> (<comment>%s</comment>) last version is <comment>%s</comment>',
                $actual->getPrettyName(),
                $actual->getPrettyVersion(),
                $last->getPrettyVersion()
            );
        }

        $output[] = '';
    }
}
