# Upgrade from 1.x to 2.0

## Version requirement

Composer 1.0 is now the minimum requirement to get the plugin working.

## Code removal

`SLLH\ComposerVersionsCheck\VersionsCheck::versionCompare` method is removed.
This method was here for BC and should never be used externally.

## Default options changes

`show-links` option is now disabled by default.
