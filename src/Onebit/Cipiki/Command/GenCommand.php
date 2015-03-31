<?php

namespace Onebit\Cipiki\Command;

use Onebit\Cipiki\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class GenCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('gen')
            ->setDescription('Generate HTML from specified directory')
            ->addArgument(
                'config_file',
                InputArgument::REQUIRED,
                'YAML configuration file'
            )
            ->addArgument(
                'source_dir',
                InputArgument::OPTIONAL,
                'Override configuration\'s source_dir'
            )
            ->addArgument(
                'target_dir',
                InputArgument::OPTIONAL,
                'Override configuration\'s target_dir'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config_file = $input->getArgument('config_file');
        $config_dir = realpath(dirname($config_file)) . '/';
        $config = Yaml::parse(file_get_contents($config_file));

        if ($input->getArgument('source_dir'))
        {
            $config['source_dir'] = $input->getArgument('source_dir');
        } 
        else
        {
            $config['source_dir'] = realpath($config_dir . $config['source_dir']);
        }

        if ($input->getArgument('target_dir'))
        {
            $config['target_dir'] = $input->getArgument('target_dir');
        } 
        else
        {
            $target_dir = $config_dir . $config['target_dir'];
            if (!file_exists($target_dir))
                mkdir($target_dir, 0755, true);

            $config['target_dir'] = realpath($target_dir);
        }

        // verify
        $required = ['source_dir', 'target_dir'];
        foreach ($required as $key)
        {
            if (!key_exists($key, $config))
                throw new \Exception("`$key` is required. Please check `$config_file`", 1);
        }

        $generator = new Generator($config, $output);
        $generator->generate();
    }
}