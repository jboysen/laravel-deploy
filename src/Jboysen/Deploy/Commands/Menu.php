<?php

namespace Jboysen\Deploy\Commands;

class Menu extends Base
{

    protected function getCommand()
    {
        return static::COMMAND_MENU;
    }

    /**
     * Execute the console command.
     *
     * @return  void
     */
    public function fire()
    {
        $this->listMenu();
    }
    
    protected function listMenu()
    {
        $this->comment('Available commands');
        
        $commands = static::commands();
        
        $commands['exit'] = '';
        unset($commands['deploy']);

        foreach ($commands as $command => $desc)
        {
            unset($commands[$command]);
            $command = str_replace('deploy:', '', $command);
            $commands[$command] = $desc;
            $this->comment(' ' . $command . "\t" . $desc);
        }

        $command = trim($this->ask('Enter command:'));

        if (array_key_exists($command, $commands))
        {
            if ($command != 'exit')
            {
                parent::fire();
                $this->comment('--------------------------------------------------------');
                $this->call('deploy:' . $command, array('server' => $this->argument('server')));
                $this->comment('--------------------------------------------------------');
                $this->listMenu();
            }
        }
        else
        {
            $this->error('Unrecognized command!');
            $this->listMenu();
        }
    }

}