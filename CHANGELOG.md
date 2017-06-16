# CHANGELOG

* 2.0.3 (2017-06-16)

  * Fix wrong outdated output on aliased packages.

* 2.0.2 (2016-04-29)

  * Remove composer compatibility check.

* 2.0.1 (2016-04-26)

  * Fix `UnexpectedValueException` with `Semver::satisfies` issue
 try to update with `--no-plugins` to get this new version.

* 2.0.0 (2016-04-19)

  * Composer version compatibility check.
  * Remove composer BC version compare method.
  * Disable `show-links` option by default.

* 1.1.0 (2016-04-19)

  * More tiny and sexy outdated output words.
  * Outdated packages number on output.
  * Show outdated packages links if exist.

* 1.0.2 (2015-10-12)

  * Align output indent to Composer.
  * Plugin disabled with --prefer-lowest option.

* 1.0.1 (2015-10-06)

  * Cross-platform newline character support.
  * Fix composer versions without `composer/semver` enabled.
