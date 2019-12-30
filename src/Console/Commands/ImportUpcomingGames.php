<?php

namespace SAN\Console\Commands;

use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;


class ImportUpcomingGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:UpcomingGames';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import UpcomingGames from Data API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("\rStarting Upcoming Games Importer");

        $start = microtime(true);
        $url = config('service.cds-api.url');
        $path = config('service.cds-api.paths.upcoming-games');

        if(! $url )
            $this->error('Missing required configuration variable: service.cds-api.url');

        if(! $path )
            $this->error('Missing required configuration variable:service.cds-api.paths.upcoming-games');

        try {

            $client = new Guzzle();
            $upcoming = $client->get($url, ['connect_timeout' => 3000]);
            $upcomingJson = $upcoming->getBody()->getContents();

            if ($upcomingJson) {
                //  Store to Redis DB
                Redis::set('upcomingGamesSrc', $upcomingJson);
                $this->line("Successfully stored to Redis! ");
            }
            // Fixate process duration
            $preciseTime = microtime(true) - $start;
            $this->line("Process took " . $preciseTime . " seconds");

        } catch (RequestException | BadResponseException $e) {
            $this->error('ERROR');
            if (!$e->hasResponse()) {
                $this->line('No response found');
                $this->line( $url );
            } else {
                $this->line($e->getResponse()->getStatusCode() . ": " . $e->getResponse()->getReasonPhrase());
            }
        }
    }
}
