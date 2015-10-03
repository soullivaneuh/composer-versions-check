<?php

namespace SLLH\ComposerVersionsCheck\Tests;

use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Repository\ArrayRepository;
use Composer\Repository\WritableArrayRepository;
use SLLH\ComposerVersionsCheck\VersionsCheck;

class VersionsCheckTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getOutdatedDetectionTestData
     *
     * @param string $actualVersion
     * @param string $higherVersion
     * @param bool   $shouldBeUpdated
     * @param bool   $preferStable
     */
    public function testOutdatedDetection($actualVersion, $higherVersion, $shouldBeUpdated, $preferStable = false)
    {
        $distRepository = new ArrayRepository();
        $localRepository = new WritableArrayRepository();

        $distRepository->addPackage(new Package('foo/bar', $higherVersion, $higherVersion));
        $localRepository->addPackage(new Package('foo/bar', $actualVersion, $actualVersion));

        $rootPackage = new RootPackage('my/project', '1.0.0', '1.0.0');
        $rootPackage->setPreferStable($preferStable);
        $versionsCheck = new VersionsCheck();
        $versionsCheck->checkPackages($distRepository, $localRepository, $rootPackage);

        // Must have one outdatedPackage if this should be updated
        if (true === $shouldBeUpdated) {
            $this->assertAttributeCount(1, 'outdatedPackages', $versionsCheck);
            $this->assertSame(sprintf(<<<EOF
<warning>Some packages are not up to date:</warning>

 - <info>foo/bar</info> (<comment>%s</comment>) last version is <comment>%s</comment>


EOF
                , $actualVersion, $higherVersion), $versionsCheck->getOutput());
        } else {
            $this->assertAttributeCount(0, 'outdatedPackages', $versionsCheck);
            $this->assertSame("<info>All packages are up to date.</info>\n", $versionsCheck->getOutput());
        }
    }

    public function getOutdatedDetectionTestData()
    {
        return array(
            array('1.0.0', '1.0.0', false),
            array('1.0.0', '1.0.0', false),
            array('1.0.0', '2.0.0', true),
            array('2.0.0', '1.0.0', false),
            array('2.0.0', '2.0.0', false),
            array('2.0.0', '1.9.9', false),
            array('1.9.9', '2.0.0', true),
            array('2.0.0', '2.1.0-alpha1', true),        // Should be true because no stable package available
            array('2.0.0', '2.1.0-alpha1', false, true), // Should be false because of minimum stability
            array('2.0.0-alpha1', '2.1.0-alpha2', true),
        );
    }

    /**
     * @dataProvider getMultiplePackagesComparisonTestsData
     *
     * @param array $packagesData
     * @param bool  $preferStable
     */
    public function testMultiplePackagesComparison(array $packagesData, $preferStable = false)
    {
        $rootPackage = new RootPackage('my/project', '1.0.0', '1.0.0');
        $rootPackage->setMinimumStability('dev');
        $rootPackage->setPreferStable($preferStable);

        $distRepository = new ArrayRepository();
        $localRepository = new WritableArrayRepository();
        $shouldBeUpdatedOutput = array();

        foreach ($packagesData as $name => $packageData) {
            list($actualVersion, $availableVersions, $expectedVersion) = $packageData;
            $actualPackage = new Package($name, $actualVersion, $actualVersion);
            $localRepository->addPackage($actualPackage);
            foreach ($availableVersions as $availableVersion) {
                $distRepository->addPackage(new Package($name, $availableVersion, $availableVersion));
            }

            if (false !== $expectedVersion) {
                $shouldBeUpdatedOutput[] = sprintf(
                    ' - <info>%s</info> (<comment>%s</comment>) last version is <comment>%s</comment>',
                    $name, $actualVersion, $expectedVersion
                );
            }
        }

        $versionsCheck = new VersionsCheck();
        $versionsCheck->checkPackages($distRepository, $localRepository, $rootPackage);

        $this->assertSame(sprintf(<<<EOF
<warning>Some packages are not up to date:</warning>

%s


EOF
        , implode("\n", $shouldBeUpdatedOutput)), $versionsCheck->getOutput());
    }

    public function getMultiplePackagesComparisonTestsData()
    {
        return array(
            array(array(
                'foo/bar'              => array('1.0.0', array('1.0.0', '1.0.1', '1.0.2', '1.0.4', '2.0.0'), '2.0.0'),
                'some/package'         => array('1.0.4', array('1.0.0', '1.0.1', '1.0.2', '1.0.4', '2.0.0'), '2.0.0'),
                'vendor/package-1'     => array('2.0.0', array('1.0.0', '1.0.1', '1.0.2', '1.0.4', '2.0.0'), false),
                'vendor/up-to-date'    => array('9.9.0', array('8.0.0', '9.9.0', '1.0-dev'), false),
                'vendor/dev-master'    => array('9.9.0', array('8.0.0', '9.9.0', '10.0-dev'), '10.0-dev'),
                'vendor/package-2'     => array('1.0.0', array('1.0.0', '1.0.1', '2.0.0', '2.0.0-alpha1'), '2.0.0'),
                'vendor/package-3'     => array('1.0.1', array('1.0.0', '1.0.1', '2.0.0-alpha1'), '2.0.0-alpha1'),
                'vendor/prefer-stable' => array('1.0.1', array('1.0.0', '1.0.1', '2.0.0-alpha1'), '2.0.0-alpha1'),
            ), false),
            array(array(
                'foo/bar'              => array('1.0.0', array('1.0.0', '1.0.1', '1.0.2', '1.0.4', '2.0.0'), '2.0.0'),
                'some/package'         => array('1.0.4', array('1.0.0', '1.0.1', '1.0.2', '1.0.4', '2.0.0'), '2.0.0'),
                'vendor/package-1'     => array('2.0.0', array('1.0.0', '1.0.1', '1.0.2', '1.0.4', '2.0.0'), false),
                'vendor/up-to-date'    => array('9.9.0', array('8.0.0', '9.9.0', 'dev-master'), false),
                'vendor/package-2'     => array('1.0.0', array('1.0.0', '1.0.1', '2.0.0', '2.0.0-alpha1'), '2.0.0'),
                'vendor/package-3'     => array('1.0.1', array('1.0.0', '1.0.1', '2.0.0-alpha1'), false),
                'vendor/prefer-stable' => array('1.0.1', array('1.0.0', '1.0.1', '2.0.0-alpha1'), false),
            ), true),
        );
    }
}
