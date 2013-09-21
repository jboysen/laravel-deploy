<?php

namespace Jboysen\Deploy\Commands;

use Symfony\Component\Process\Process;
use Jboysen\Deploy\Deployment;

class Deploy extends Base
{

    protected function getCommand()
    {
        return static::COMMAND_DEPLOY;
    }

    /**
     * Execute the console command.
     *
     * @return  void
     */
    public function fire()
    {
        $this->runComposerLocally();
        $this->runTests();

        parent::fire();
        $this->setup();

        $deployment = new Deployment($this->target, $this);
        $deployment->buildArchive()
                ->moveToServer()
                ->unpackArchive()
                ->shutdown()
                ->linkStorageAndVendor()
                ->runComposer()
                ->migrateDatabase()
                ->updateVersion()
                ->linkCurrent()
                ->startup()
                ->cleanup();

        $this->comment('Finished!');
    }

    protected function runComposerLocally()
    {
        $composerPath = \Config::get('deploy::composer', 'composer.phar');
        
        $this->comment('Updating composer and commit if any changes were found...');
        
        $composer = new Process($composerPath . ' self-update');
        $composer->setTimeout(120);
        $composer->run(function ($type, $buffer)
                {
                    echo $buffer;
                });
                
        $git = new Process('git commit -a -m "composer updated"');
        $git->run(function ($type, $buffer)
                {
                    echo $buffer;
                });
    }

    protected function runTests()
    {
        $this->line('##############################################################');
        
        $this->comment('Running tests...');
        
        $phpunit = new Process('phpunit');
        $phpunit->run(function ($type, $buffer)
                {
                    echo $buffer;
                    if (str_contains($buffer, 'FAILURES!'))
                    {
                        echo "Found errors. Deployment cancelled!\n";
                        exit;
                    }
                });
                
        $this->line('##############################################################');
    }

}