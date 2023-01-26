<?php 
namespace Osky;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Osky\Command;

class App extends Command
{
    
    public function configure()
    {
        $this -> setName('reddit:search')
            -> setDescription('Search a given subreddit')
            -> setHelp('Search a given subreddit');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->search($input, $output);
    }
}