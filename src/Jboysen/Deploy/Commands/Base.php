<?php

namespace Jboysen\Deploy\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use \Net_SFTP;
use Jboysen\Deploy\Target;

class Base extends Command
{
    const COMMAND_MENU = 'deploy';
    const COMMAND_TEST = 'deploy:test';
    const COMMAND_SETUP = 'deploy:setup';
    const COMMAND_CLEANUP = 'deploy:cleanup';
    const COMMAND_DEPLOY = 'deploy:deploy';
    const COMMAND_DBVERSION = 'deploy:dbversion';
    
    /**
     *
     * @var string
     */
    protected $name = 'deploy';
    
    /**
     *
     * @var Target
     */
    protected $target;
    
    /**
     * Create a new command instance
     *
     * @return  void
     */
    public function __construct()
    {
        parent::__construct();
        
        $command = $this->getCommand();
        $this->setName($command);
        $this->setDescription($this->commands()[$command]);
    }

    /**
     * 
     * @return array of commands with descriptions
     */
    public static function commands()
    {
        return array(
            static::COMMAND_MENU => 'List all deploy commands',
            static::COMMAND_TEST => 'Test the current configuration',
            static::COMMAND_SETUP => 'Setup the target if it isn\'t',
            static::COMMAND_CLEANUP => 'Cleanup old releases',
            static::COMMAND_DEPLOY => 'Deploy the current git commit',
            static::COMMAND_DBVERSION => 'Get current DB version',
        );
    }
    
    /**
     * Must be implemented by sub classes
     * @return const COMMAND_* the current command
     */
    protected function getCommand()
    {
        return null;
    }

    /**
     * Execute the console command.
     *
     * @return  void
     */
    public function fire()
    {
        if (Target::getCurrent() === null)
        {
            $target = $this->_getTarget();
            $connection = $this->_getConnection($target->getConfig());
            $target->setConnection($connection);
            Target::setCurrent($target);
        }
        $this->target = Target::getCurrent();
    }
    
    /**
     * 
     */
    protected function setup()
    {
        if (!$this->target->isSetup())
        {
            $setup = $this->confirm('Server not setup. Setup now? [yes(default)|no]');
            
            if ($setup)
            {
                $this->call('deploy:setup', array('server'=>$this->argument('server')));
            }
            else
            {
                $this->error('Exiting because server is not setup');
                exit;
            }
        }
    }

    /**
     * Verify that the specified server exists in the config
     *
     * @return Target
     */
    private function _getTarget()
    {
        $server = $this->argument('server');

        $config = \Config::get("deploy::targets.{$server}");

        $this->comment("Finding environment: \"{$server}\" (change by specifying the environment as first argument)");

        if (is_null($config))
        {
            $this->error("Environment \"{$server}\" does not exist");
            exit;
        }
        
        $target = new Target($server, $config);
        $config = $target->getConfig();
        
        if ($config['password'] == '')
        {
            $config['password'] = $this->secret('Enter SSH password:', '');
        }
        
        $target->setConfig($config);
        
        $this->printTarget($config);

        return $target;
    }
    
    /**
     * 
     * @param array $config
     */
    protected function printTarget(array $config = null)
    {
        if ($config === null)
        {
            $config = Target::getCurrent()->getConfig();
        }
        $this->comment("    Host:   " . $config['host'] . ":" . $config['port']);
        $this->comment("    User:   " . $config['user']);
        $this->comment("    Root:   " . $config['root']);
    }

    /**
     * 
     * @param array $config
     * @return \Net_SFTP
     */
    private function _getConnection(array $config)
    {
        $this->comment("Connecting to the server...");

        $connection = new Net_SFTP($config['host'], $config['port']);
        
        if (!$connection->login($config['user'], $config['password']))
        {
            $this->error('Could not connect to server');
            exit;
        }
        
        $connection->chdir($config['root']);
        $this->comment('Current directory:');
        $this->info('> ' . $connection->pwd());

        return $connection;
    }

    /**
     * Get the console command arguments
     *
     * @return  array
     */
    protected function getArguments()
    {
        return array(
            array('server', InputArgument::OPTIONAL, 'The deployment server', 'production')
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