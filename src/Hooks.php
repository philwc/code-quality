<?php
namespace philwc;

use Composer\Script\Event;

/**
 * @author Philip Wright- Christie <pwrightchristie.sfp@gmail.com>
 * Date: 22/07/14
 */
class Hooks
{
    public static function checkHooks(Event $event)
    {
        $io      = $event->getIO();
        $gitHook = @file_get_contents(getcwd() . '/.git/hooks/pre-commit');

        if (file_exists(getcwd() . '/src/hooks/pre-commit')) {
            $hookLocation = getcwd() . '/src/hooks/pre-commit';
        } else {
            $hookLocation = getcwd() . '/vendor/philwc/code-quality/src/hooks/pre-commit';
        }

        $docHook = @file_get_contents($hookLocation);

        $result = true;
        if ($gitHook !== $docHook) {
            $error = 'Hook mismatch. Please update (rm -rf .git/hooks && ln -s ../src/hooks ./.git/hooks)';
            $io->write('<error>' . $error . '</error>');
            $result = false;
        }

        return $result;
    }
}
