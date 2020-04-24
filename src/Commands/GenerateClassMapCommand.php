<?php
/**
 * Created by PhpStorm.
 * User: Gregor Becker <gregor.becker@getinbyte.com>
 * Date: 23.04.2020
 * Time: 14:10
 */

namespace CodeDredd\Soap\Commands;


use Illuminate\Console\Command;
use TitasGailius\Terminal\Terminal;

class GenerateClassMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'soap:classmap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command to check our php code';

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
     *
     * @return mixed
     */
    public function handle()
    {
        $clients = config()->get('soap.clients');
        $clientNames = [];
        if (!empty($possibleClients)) {
            $clientNames = array_keys($clients);
        }
        $wsdl = $this->anticipate('Please type the wsdl or the name of your client configuration if u have defined in the config "soap.php"', $clientNames);

    }

}
