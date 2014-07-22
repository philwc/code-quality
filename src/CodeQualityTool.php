<?php
/**
 * @author Philip Wright- Christie <pwrightchristie.sfp@gmail.com>
 * Date: 22/07/14
 */

namespace philwc;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Console\Application;

class CodeQualityTool extends Application
{
    private $output;
    private $input;
    private $binPath;

    const PHP_FILES_IN_SRC     = '/.*\/src\/(.*)(\.php)$/';
    const PHP_FILES_IN_CLASSES = '/^classes\/(.*)(\.php)$/';

    public function __construct()
    {
        parent::__construct('Code Quality Tool', '1.0.0');

        $composer = json_decode(file_get_contents(getcwd() . '/composer.json'), true);
        if (isset($composer['config']['bin-dir'])) {
            $this->binPath = $composer['config']['bin-dir'];
        } else {
            if (file_exists(getcwd() . '/vendor/bin')) {
                $this->binPath = getcwd() . '/vendor/bin';
            } else {
                $this->binPath = getcwd() . '/bin';
            }
        }
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        $output->writeln('<fg=white;options=bold;bg=red>Code Quality Tool</fg=white;options=bold;bg=red>');
        $output->writeln('<info>Fetching files</info>');
        $files = $this->extractCommitedFiles();

        if (empty($files)) {
            $finder = new Finder();

            $finderFiles = $finder->files()
                                  ->in(getcwd() . '/src')
                                  ->name('*.php');

            foreach ($finderFiles as $file) {
                $files[] = $file->getPathname();
            }
        }

        if (!empty($files)) {
            $output->writeln('<info>Running PHPLint</info>');
            if (!$this->phpLint($files)) {
                throw new \Exception('There are some PHP syntax errors!');
            }

            $output->writeln('<info>Checking code style with PHPCS</info>');
            if (!$this->codeStylePsr($files)) {
                throw new \Exception(sprintf('There are PHPCS coding standards violations!'));
            }

            $output->writeln('<info>Checking code mess with PHPMD</info>');
            if (!$this->phpMd($files)) {
                throw new \Exception(sprintf('There are PHPMD violations!'));
            }

            $output->writeln('<info>Running unit tests</info>');
            if (!$this->unitTests()) {
                throw new \Exception('Fix the unit tests!');
            }
        }
        $output->writeln('<info>Good job dude!</info>');
    }

    private function extractCommitedFiles()
    {
        $output = array();

        exec('git diff --cached --name-only --diff-filter=ACM', $output);

        return $output;
    }

    private function phpLint($files)
    {
        $needle  = '/(\.php)|(\.inc)$/';
        $succeed = true;

        foreach ($files as $file) {
            if (!preg_match($needle, $file)) {
                continue;
            }

            $processBuilder = new ProcessBuilder(array('php', '-l', $file));
            $process        = $processBuilder->getProcess();
            $process->run();

            if (!$process->isSuccessful()) {
                $this->output->writeln($file);
                $this->output->writeln(sprintf('<error>%s</error>', trim($process->getErrorOutput())));

                if ($succeed) {
                    $succeed = false;
                }
            }
        }

        return $succeed;
    }

    private function phpMd($files)
    {
        $needle   = self::PHP_FILES_IN_SRC;
        $succeed  = true;
        $rootPath = realpath(getcwd());

        foreach ($files as $file) {
            if (!preg_match($needle, $file)) {
                continue;
            }

            $bin            = $this->binPath . '/phpmd';
            $processBuilder = new ProcessBuilder([$bin, $file, 'text', getcwd() . '/phpmd.xml']);
            $processBuilder->setWorkingDirectory($rootPath);
            $process = $processBuilder->getProcess();
            $process->run();

            if (!$process->isSuccessful()) {
                $this->output->writeln($file);
                $this->output->writeln(sprintf('<error>%s</error>', trim($process->getErrorOutput())));
                $this->output->writeln(sprintf('<info>%s</info>', trim($process->getOutput())));
                if ($succeed) {
                    $succeed = false;
                }
            } else {
                $this->output->writeln('OK');

            }
        }

        return $succeed;
    }

    private function unitTests()
    {
        if (file_exists(getcwd() . '/app/phpunit.xml.dist')) {
            $processBuilder = new ProcessBuilder(array($this->binPath . '/phpunit', '-c', 'app'));
        } else {
            return true;
        }

        $processBuilder->setWorkingDirectory(getcwd());
        $processBuilder->setTimeout(3600);
        $phpunit = $processBuilder->getProcess();

        $func = function (
            $type,
            $buffer
        ) {
            $this->output->write($buffer);
        };

        $phpunit->run($func);

        return $phpunit->isSuccessful();
    }

    private function codeStylePsr(array $files)
    {
        $succeed = true;
        $needle  = self::PHP_FILES_IN_SRC;

        foreach ($files as $file) {
            if (!preg_match($needle, $file)) {
                continue;
            }

            $processBuilder = new ProcessBuilder(array($this->binPath . '/phpcs', '--standard=PSR2', $file));
            $phpCsFixer     = $processBuilder->getProcess();
            $phpCsFixer->run();

            if (!$phpCsFixer->isSuccessful()) {
                $this->output->writeln(sprintf('<error>%s</error>', trim($phpCsFixer->getOutput())));

                if ($succeed) {
                    $succeed = false;
                }
            }
        }

        return $succeed;
    }
}
