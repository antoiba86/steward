<?php

namespace Lmc\Steward\Console\Command;

use Lmc\Steward\Console\CommandEvents;
use Lmc\Steward\Console\Event\BasicConsoleEvent;
use Lmc\Steward\Selenium\Downloader;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\Question;

/**
 * Install (download) Selenium standalone server.
 */
class InstallCommand extends Command
{
    /** @var Downloader */
    protected $downloader;

    /**
     * Target directory to store the selenium server (relatively to STEWARD_BASE_DIR)
     * @var string
     */
    protected $targetDir = '/vendor/bin';

    /**
     * @param Downloader $downloader
     */
    public function setDownloader(Downloader $downloader)
    {
        $this->downloader = $downloader;
    }

    /**
     * @return Downloader
     * @codeCoverageIgnore
     */
    public function getDownloader()
    {
        if (!$this->downloader) {
            $this->downloader = new Downloader(STEWARD_BASE_DIR . $this->targetDir);
        }

        return $this->downloader;
    }

    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setName('install')
            ->setDescription('Download latest Selenium standalone server jar file')
            ->addArgument(
                'version',
                InputArgument::OPTIONAL,
                'Specific Selenium version to install'
            );

        $this->getDispatcher()->dispatch(CommandEvents::CONFIGURE, new BasicConsoleEvent($this));
    }

    /**
     * In interactive or very verbose (-vv) mode provide more output, otherwise only output full path to selenium
     * server jar file (so it could be parsed and run).
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $verboseOutput = false;
        if ($input->isInteractive() || $output->isVeryVerbose()) {
            $verboseOutput = true;
        }

        $version = $input->getArgument('version'); // exact version could be specified as argument

        if (!$version) {
            $latestVersion = Downloader::getLatestVersion();

            /** @var QuestionHelper $questionHelper */
            $questionHelper = $this->getHelper('question');

            $question = new Question(
                '<question>Enter Selenium server version to install:</question> '
                . ($latestVersion ? "[$latestVersion] " : ''),
                $latestVersion
            );
            $version = $questionHelper->ask($input, $output, $question);
        }

        if ($verboseOutput) {
            $output->writeln(
                'Steward is now downloading Selenium standalone server...'
                . (!getenv('JOB_NAME') ? ' Just for you <3!' : '') // in jenkins it is not just for you, sorry
            );
        }

        $downloader = $this->getDownloader();
        $downloader->setVersion($version);

        if ($verboseOutput) {
            $output->writeln(sprintf('Version: %s', $version));
            $output->writeln(sprintf('File URL: %s', $downloader->getFileUrl()));
            $output->writeln(sprintf('Target file path: %s', $downloader->getFilePath()));
        }

        if ($downloader->isAlreadyDownloaded()) {
            $targetPath = realpath($downloader->getFilePath());
            if ($verboseOutput) {
                $output->writeln(
                    sprintf(
                        'File "%s" already exists in directory "%s" - won\'t be downloaded again.',
                        basename($targetPath),
                        dirname($targetPath)
                    )
                );
            } else {
                $output->writeln($targetPath); // In non-verbose mode only output path to the file
            }
            return 0;
        }

        if ($verboseOutput) {
            $output->writeln('Downloading (may take a while - its over 30 MB)...');
        }

        $downloadedSize = $downloader->download();

        if (!$downloadedSize) {
            $output->writeln('Error downloading file :-(');
            return 1;
        }

        if ($verboseOutput) {
            $output->writeln('Downloaded ' . $downloadedSize . ' bytes, file saved successfully.');
        } else {
            $targetPath = realpath($downloader->getFilePath());
            $output->writeln($targetPath); // In non-verbose mode only output path to the file
        }

        return 0;
    }
}
