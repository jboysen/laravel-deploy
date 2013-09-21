<?php namespace Jboysen\Deploy\Commands;

class Test extends Base
{
    protected function getCommand()
    {
        return static::COMMAND_TEST;
    }

    /**
     * Execute the console command.
     *
     * @return  void
     */
    public function fire()
    {
        $this->comment('Testing configuration...');
        parent::fire();
        $this->setup();
        $this->comment("Success!");
    }

}