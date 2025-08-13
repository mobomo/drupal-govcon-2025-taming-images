<?php

/**
 * @file
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

use Robo\Collection\CollectionBuilder;
use Robo\Task\Base\ExecStack;
use Robo\Tasks;

/**
 * RoboEnvironment constants.
 */
class RoboEnvironment {
    const CUSTOM_MODULES = __DIR__ . '/web/modules/custom';
    const CUSTOM_THEMES = __DIR__ . '/web/themes/custom';
    const SYSTEM_SITE_UUID = '9e3853c9-b2ab-4c2e-9a75-18a56b5af1d2';

}

/**
 * Robo Tasks.
 */
class RoboFile extends Tasks {
  /**
   * Local site update.
   */
  public function localUpdate(): CollectionBuilder {
    $this->say("Local site update starting -");
    $collection = $this->collectionBuilder();
    $collection->taskComposerInstall();

    $collection->taskExec("vendor/bin/drush state:set system.maintenance_mode 1 -y")
      ->taskExec("vendor/bin/drush updatedb --no-cache-clear -y")
      ->taskExec("vendor/bin/drush cr")
      ->taskExec("vendor/bin/drush cim -y || drush cim -y")
      ->taskExec("vendor/bin/drush cim -y")
      ->taskExec("vendor/bin/drush php-eval \"node_access_rebuild();\" -y")
      ->addTask($this->buildTheme())
      ->taskExec("vendor/bin/drush state:set system.maintenance_mode 0 -y")
      ->taskExec("vendor/bin/drush cr");
    $this->say("Local site update completed.");
    return $collection;
  }

  /**
   * Builds theme.
   */
  public function buildTheme() {
    $defaultTheme = $this->_getDefaultTheme();
    $themeDir = RoboEnvironment::CUSTOM_THEMES . "/{$defaultTheme}";

    $collection = $this->collectionBuilder();
    $collection->progressMessage('Building the theme...')
      ->taskNpmInstall()->dir($themeDir)
      ->taskExec('cd ' . $themeDir . ' && npm run develop');

    return $collection;
  }

  /**
   * Runs PHP Code Sniffer.
   */
  public function phpcs() {
    $this->say("PHP Code Sniffer (drupalStandards) started -");
    $task = $this->taskExec('vendor/bin/phpcs -s');
    if (file_exists(__DIR__ . '/.phpcs.xml')) {
      $task->arg('--standard=' . __DIR__ . '/.phpcs.xml');
    }
    elseif (file_exists(__DIR__ . '/phpcs.xml')) {
      $task->arg('--standard=' . __DIR__ . '/phpcs.xml');
    }
    elseif (file_exists(__DIR__ . '/.phpcs.xml.dist')) {
      $task->arg('--standard=' . __DIR__ . '/.phpcs.xml.dist');
    }
    elseif (file_exists(__DIR__ . '/phpcs.xml.dist')) {
      $task->arg('--standard=' . __DIR__ . '/phpcs.xml.dist');
    }
    else {
      // Default settings if no project or developer settings are found.
      $task->arg('--standard=Drupal,DrupalPractice')
        ->arg('--extensions=php,module,inc,install,test,profile,theme,info')
        ->arg('--ignore=*/node_modules/*,*/vendor/*');
    }
    $result = $task->arg(RoboEnvironment::CUSTOM_MODULES)
      ->arg(RoboEnvironment::CUSTOM_THEMES)
      ->printOutput(TRUE)
      ->run();
    $message = $result->wasSuccessful() ? 'No Drupal standards violations found :)' : 'Drupal standards violations found :( Please review the code.';
    $this->say("PHP Code Sniffer finished: " . $message);
  }

  /**
   * Runs Static Code Analysis (phpstan).
   */
  public function analyze(): int {
    $this->say("Static Code Analysis started -");
    $result = $this->taskExec('vendor/bin/phpstan')
      ->arg('analyze')
      ->arg(RoboEnvironment::CUSTOM_THEMES)
      ->arg(RoboEnvironment::CUSTOM_MODULES)
      ->printOutput(TRUE)
      ->run();
    $this->say("Static Code Analysis complete.");
    return $result->getExitCode();
  }

  /**
   * Runs PHP Code Beautifier.
   */
  public function codefix() {
    $this->say("PHP Code Beautifier (drupalStandards) started -");
    $task = $this->taskExec('vendor/bin/phpcbf -n');
    // Default settings if no project or developer settings are found.
    $task->arg('--standard=Drupal,DrupalPractice')
      ->arg('--extensions=php,module,inc,install,test,profile,theme,css,info,txt,md,yml,yaml,feature,js,json,ts,tsx,feature,twig')
      ->arg('--ignore=*/node_modules/*,*/vendor/*,*.tpl.php')
      ->arg(RoboEnvironment::CUSTOM_MODULES)
      ->arg(RoboEnvironment::CUSTOM_THEMES)
      ->printOutput(TRUE)
      ->run();
    $this->say("PHP Code Beautifier finished.");
  }

  /**
   * Fixes files permissions.
   *
   * @return \Robo\Collection\CollectionBuilder|\Robo\Task\Base\ExecStack
   *   Exec chown and chmod.
   */
  public function fixPerms(): CollectionBuilder|ExecStack {
    return $this->taskExecStack()
      ->stopOnFail()
      ->exec('chown $(id -u) ./')
      ->exec('chmod u=rwx,g=rwxs,o=rx ./')
      ->exec('find ./ -not -path "./web/sites/default/files*" -exec chown $(id -u) {} \;')
      ->exec('find ./ -not -path "./web/sites/default/files*" -exec chmod u=rwX,g=rwX,o=rX {} \;')
      ->exec('find ./ -type d -not -path "./web/sites/default/files*" -exec chmod g+s {} \;')
      ->exec('chmod -R u=rwx,g=rwxs,o=rwx ./web/sites/default/files');
  }

  /**
   * Project init.
   */
  public function projectInit(): CollectionBuilder {

    $defaultTheme = $this->_getDefaultTheme();
    $adminTheme = $this->_getAdminTheme();
    $systemSiteUuid = RoboEnvironment::SYSTEM_SITE_UUID;

    $collection = $this->collectionBuilder();
    $collection->taskComposerInstall()
      ->ignorePlatformRequirements()
      ->noInteraction()
      ->taskExec("drush sin --account-name=admin --account-pass=admin --existing-config minimal -y")
      ->taskExec("drush cset system.site uuid " . $systemSiteUuid . " -y")
      ->taskExec("drush theme:enable $defaultTheme $adminTheme -y")
      ->taskExec("drush config:set system.theme default $defaultTheme -y")
      ->taskExec("drush config:set system.theme admin $adminTheme -y")
      ->taskExec("drush config:set node.settings use_admin_theme true -y")
      ->taskExec('drush cr')
      ->addTask($this->localUpdate());
    $this->say("Project initialized.");
  
    return $collection;
  }

  /**
   * Get saved front end theme (not stark, default olivero).
   */
  public function _getDefaultTheme(): string {
    $result = $this
      ->taskExec('drush config:get system.theme default')
      ->silent(TRUE)
      ->run();

    if ($result->wasSuccessful()) {
      $theme = str_replace("'system.theme:default': ", '', $result->getMessage());
      if ($theme != 'stark') {
        return $theme;
      }
    }
    return 'olivero';
  }

  /**
   * Get saved admin (not stark, default claro).
   */
  public function _getAdminTheme(): string {
    $result = $this
      ->taskExec('drush config:get system.theme admin')
      ->silent(TRUE)
      ->run();

    if ($result->wasSuccessful()) {
      $theme = str_replace("'system.theme:admin': ", '', $result->getMessage());

      if ($theme != 'stark') {
        return $theme;
      }
    }
    return 'claro';
  }

}
