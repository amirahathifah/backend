<?php 
namespace Osky;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

class Command extends SymfonyCommand
{
    
    public function __construct()
    {
        parent::__construct();
    }
    protected function search(InputInterface $in, OutputInterface $out)
    {
        $io = new SymfonyStyle($in, $out);
        $outputStyle = new OutputFormatterStyle('red');
        $io->getFormatter()->setStyle('red', $outputStyle);

        $io->newLine(2);
        $io->writeln([
            '<red>Reddit Search v0.1.0</>',
            '<red>====================</>'
        ]);

        $subreddit = $io->ask('Please enter the name of the subreddit (default: webdev): ', 'webdev');
        $term = $io->ask('Please enter a search term (default: php): ', 'php');
        $term = strtolower($term);
        $reddit_url = 'https://www.reddit.com/r/' . $subreddit . '/new/.json?limit=100';

        $io->text('Searching for: ' . $term . ' at ' . $reddit_url . '...');
        $io->newLine();

        // Get data
        $data = $this->fetch($subreddit, $reddit_url);

        // Create table
        $table = new Table($out);
        $table->setHeaders(['Date', 'Title', 'URL', 'Excerpt']);
        $separator = new TableSeparator();

        // Reddit data
        $total_data = count($data->data->children);
        $first = 0;
        $counter = 0;
        foreach ($data->data->children as $item) {
            $title = $item->data->title;
            $text = $item->data->selftext;
            $postUrl = $item->data->url; 
            $postUrl = substr($postUrl, 0, 40) . '...';
            $date = date('Y-m-d H:i:s', $item->data->created_utc);
            $excerpt = '';

            // Skip if url not reddit.com
            if (stripos($postUrl, 'reddit.com') !== false) {}
            else { 
                $counter++;
                if ($counter == $total_data-1) {
                    $this->termNotFound();
                }
                continue;
            }

            // Search using $title and $text
            if (stripos($title, $term) !== false) {
                $excerpt = '';

                if (stripos($text, $term) !== false) {
                    $excerpt = $this->Excerpt($text, $term);
                }
            }
            else {

                if (stripos($text, $term) !== false) {
                    $excerpt = $this->Excerpt($text, $term);
                }
                else {
                    // If no post matches
                    $counter++;
                    if ($counter == $total_data-1) {
                        $this->termNotFound();
                    }

                    continue;
                }
            }

            //limit title length
            if (strlen($title) > 30)
                $title = substr($title,0,30).'...';

            //add separator
            $first++;
            if ($first === 1) {
                $table->addRow([$date, $title, $postUrl, $excerpt]);
            }
            else {
                $table->addRow($separator);
                $table->addRow([$date, $title, $postUrl, $excerpt]);
            }
        }

        $table->render();
    }

    /*
        Fetch raw JSON data from Reddit's API
    */
    private function fetch($subreddit, $reddit_url)
    {
        $client = new Client([
            'headers' => ['User-Agent' => 'redditsearch/1.0'],
            'verify' => false
        ]);

        $response = $client->request("GET", $reddit_url);

        $response = json_decode($response->getBody(true));

        if (empty($response->data->children))
            $this->subRedditNotFound();

        return $response;
    }

    private function Excerpt($text, $term) {
        $excerpt = '';
        $textLength = strlen($text);
        $termLength = strlen($term);
        $termPosition = stripos($text, $term);

        //text in between 
        if ( ($termPosition > 20) && ( ($textLength - $termPosition - $termLength) > 20) ) {
            $start = $termPosition - 20;
            $length = 40 + $termLength;
            $truncated = substr($text, $start, $length );
            $excerpt = '...' . $truncated . '...';
        }

        //text end with dot
        else if ( ($termPosition < 20) && ( ($textLength - $termPosition - $termLength) > 20) ) {
            $start = 0;
            $length = $termPosition + ($termLength + 20);
            $truncated = substr($text, $start, $length );
            $excerpt = $truncated . '...';
        }

        //text start with dot
        else if ( ($termPosition > 20) && ( ($textLength - $termPosition - $termLength) < 20 ) ) {
            $start = $termPosition - 20;
            $length = 40 + $termLength;
            $truncated = substr($text, $start, $length );
            $excerpt = '...' . $truncated;
        }

        //text no dot
        else if ( $textLength < (40 + $termLength) )
            $excerpt = $text;

        return $this->highlight($excerpt, $term);
    }

    /* 
        Find all instances of the search term, and surround them in color tag
    */
    private function highlight($excerpt, $term) 
    {
        $text = preg_filter('/' . preg_quote($term, '/') . '/i', '<red>$0</>', $excerpt);
        
        if (!empty($text)) {
            $excerpt = $text;
        }

        return $excerpt;
    }
    /* 
        subreddit not exist
    */
    private function subRedditNotFound()
    {   
        exit('Subreddit Not Found!');
    }
    /* 
        term not match all
    */
    private function termNotFound()
    {
        exit('No posts found with the search term supplied. :(');
    }
}