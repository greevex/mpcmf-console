<?php

namespace mpcmf\apps\mpcmf\commands;

use mpcmf\system\application\consoleCommandBase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SDS Alert telegram bot
 *
 * @author Gregory Ostrovsky <greevex@gmail.com>
 */
class example
    extends consoleCommandBase
{

    /**
     * Define arguments
     *
     * @return mixed
     */
    protected function defineArguments()
    {
        $this->addArgument('query', InputArgument::OPTIONAL, 'Some query, used for example');
    }

    /**
     * Executes the current command.
     *
     * This method is not because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws \LogicException When this method is not implemented
     *
     * @see setCode()
     */
    protected function handle(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>This is example command</comment>');

        $query = $input->getArgument('query');

        if ($query !== null) {
            $output->writeln('<info>Your input:</info>');
            $output->writeln($query);
        } else {
            $output->writeln('<info>Try to add argument for command call</info>');
        }
    }
}