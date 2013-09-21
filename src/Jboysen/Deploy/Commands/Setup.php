<?php namespace Jboysen\Deploy\Commands;

class Setup extends Base
{

    protected function getCommand()
    {
        return static::COMMAND_SETUP;
    }
    
    /**
     * Execute the console command.
     *
     * @return  void
     */
    public function fire()
    {
        parent::fire();
        $this->setup();
    }
    
    protected function setup()
    {
        if($this->target->isSetup())
        {
            $this->error("Server already setup");
        }
        else
        {
            $this->comment('Directories not setup on server...');
            $this->comment("Creating directories on server...");
            
            $this->target->setup();
            
            $this->comment("Directories created!");
        }
    }

}