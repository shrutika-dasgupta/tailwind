<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Class ExportUserData
 *
 * @use php artisan export:user
 */
class ExportUserData extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'export:user';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description =
        'Allows you to SSH into the devbox and export the user data for a given user';

    /**
     * Create a new command instance.
     *
     * @return \ExportUserData
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
    public function fire()
    {
        $cust_id = $this->ask('Please enter a cust_id: ');
        $user    = 'will';


        $this->line('SSHing into Devbox and running export from Master DB');

        SSH::into('devbox')->run(array(
                                      'php laravel/engines/internal/export_user_data.php ' . $cust_id,
                                 ), function ($line) {
                $this->comment($line);
            }
        );
        $this->line('Downloading customer export');

        $payload = json_decode(SSH::into('devbox')->getString('/home/' . $user . '/laravel/engines/internal/' . $cust_id . '.json'), true);

        $this->info('Download complete');

        foreach ($payload as $name => $insert) {

            try {

                $PDO = DB::getPdo();

                if (empty($insert['query'])) {
                    $this->comment("No query: $name");
                    continue;
                }

                if (empty($insert['data'])) {
                    $this->comment("No data: $name");
                    continue;
                }

                $STH = $PDO->prepare($insert['query']);

                $xx = 0;
                foreach ($insert['data'] as $key => $array) {
                    foreach ($array as $column => $value) {
                        $xx++;
                        $STH->bindParam($xx, array_get($insert['data'][$key], $column, null));
                    }
                }
                $STH->execute();
                $this->info("Complete: $name succesfully imported.");
            }
            catch (Exception $e) {
                $this->error('Failed: ' . $name . ' - ' . $e->getMessage());
            }
        }

        //SSH::into('devbox')->run(['rm laravel/engines/internal/'.$cust_id.'.json -f']);
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('user', 'will', InputOption::VALUE_OPTIONAL, 'Set the user. This is important as it sets the path where the export script is run.', null),
        );
    }

}
