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
        $docHook = @file_get_contents(getcwd() . '/src/hooks/pre-commit');

        $result = true;
        if ($gitHook !== $docHook) {
            $io->write('<error>Hook mismatch. Please update (rm -rf .git/hooks && ln -s ../src/hooks ./.git/hooks)</error>');
            $result = false;
        }

        return $result;
    }
}
