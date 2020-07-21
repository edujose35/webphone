<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use PAMI\Client\Exception\ClientException;
use PAMI\Client\Impl\ClientImpl as PamiClient;
use PAMI\Message\Event\HangupEvent;
use PAMI\Message\Event\NewstateEvent;
use PAMI\Message\Event\UnknownEvent;

class AsteriskAdminJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public $pami;
    public $client;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->pami = new PamiClient(
            array(
                'host' => env('ASTERISK_HOST'),
                'scheme' => env('ASTERISK_SCJHEME'),
                'port' => env('ASTERISK_PORT'),
                'username' => env('ASTERISK_USERNAME'),
                'secret' => env('ASTERISK_SECRET'),
                'connect_timeout' => env('ASTERISK_TIMEOUT_CONNECT'),
                'read_timeout' => env('ASTERISK_TIMEOUT_READ')
            )
        );

        $this->client = $this->pami;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws ClientException
     */
    public function handle()
    {
        $this->client->open();

        Log::info("Asterisk PAMI iniciado");

        $this->client->registerEventListener(function ($event){
            if($event instanceof HangupEvent){
                Log::info("Ligação encerrada", $event->getKeys());
            }

            if ($event instanceof NewstateEvent){
                Log::alert("Nova ligação", $event->getKeys());
            }
        });

        $running = true;

        while($running) {
            $this->client->process();
            usleep(1000);
        }

        $this->client->close();
    }
}
