<?php

namespace SLLH\ComposerVersionsCheck;

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
     * @param ArrayRepository             $distRepository
     * @param WritableRepositoryInterface $localRepository
     * @param RootPackageInterface        $rootPackage
     */
    public function checkPackages(ArrayRepository $distRepository, WritableRepositoryInterface $localRepository, RootPackageInterface $rootPackage)
    {
        // Var comment to be removed if following PR is merged: https://github.com/composer/composer/pull/4469
        /** @var PackageInterface[] $packages */
        $packages = $localRepository->getPackages();
        foreach ($packages as $package) {
            /** @var PackageInterface[] $higherPackages */
            $higherPackages = $distRepository->findPackages($package->getName(), new Constraint('>', $package->getVersion()));
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

        return implode("\n", $output)."\n";
    }

    private function createNotUpToDateOutput(array &$output)
    {
        $output[] = '<warning>Some packages are not up to date:</warning>';
        $output[] = '';

        foreach ($this->outdatedPackages as $outdatedPackage) {
            $output[] = sprintf(
                ' - <info>%s</info> (<comment>%s</comment>) last version is <comment>%s</comment>',
                $outdatedPackage->getActual()->getPrettyName(),
                $outdatedPackage->getActual()->getPrettyVersion(),
                $outdatedPackage->getLast()->getPrettyVersion()
            );
        }

        $output[] = '';
    }
}
