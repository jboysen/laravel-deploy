<?php

namespace Jboysen\Deploy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DBVersion extends Command
{

    /**
     * The console command name
     *
     * @var string
     */
    protected $name = 'deploy:dbversion';

    /**
     * The console command description
     *
     * @var string
     */
    protected $description = 'Get current DB version';

    /**
     * Create a new command instance
     *
     * @return  void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return  void
     */
    public function fire()
    {
        $lastMigration = 0;
        foreach (DB::table('migrations')->get() as $migration)
            if ($migration->batch > $lastMigration)
                $lastMigration = $migration->batch;
        $this->line($lastMigration);
    }

    /**
     * Get the console command arguments
     *
     * @return  array
     */
    protected function getArguments()
    {
        return array(
            
        );
    }

    /**
     * Get the console command options
     *
     * @return  array
     */
    protected function getOptions()
    {
        return array(
        );
    }

}