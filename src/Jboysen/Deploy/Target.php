<?php

namespace Jboysen\Deploy;

use \Net_SFTP;

class Target
{

    const DIR_RELEASES = 'releases';
    const DIR_CURRENT = 'current';
    const DIR_STORAGE = 'storage';
    const DIR_VENDOR = 'vendor';
    
    const FILE_RELEASE = 'app/RELEASE';
    const FILE_VERSION = 'app/VERSION';

    /**
     *
     * @var Target
     */
    private static $current = null;

    /**
     * 
     * @return Target
     */
    public static function getCurrent()
    {
        return static::$current;
    }

    /**
     * 
     * @return Target
     */
    public static function setCurrent(Target $target)
    {
        static::$current = $target;
        return static::$current;
    }

    private $name;

    /**
     *
     * @var array
     */
    private $config = array();

    /**
     *
     * @var Net_SFTP
     */
    private $connection;

    public function __construct($name, array $config)
    {
        $this->name = $name;

        $config = array_add($config, 'host', 'localhost');
        $config = array_add($config, 'port', 22);
        $config = array_add($config, 'user', 'root');
        $config = array_add($config, 'password', '');
        $config = array_add($config, 'root', '/home/root');
        $config = array_add($config, 'bin', '/usr/bin/');

        if (!\Str::endsWith($config['bin'], '/'))
        {
            $config['bin'] .= '/';
        }

        $this->config = $config;

        return $this;
    }

    public function setConnection(Net_SFTP $connection)
    {
        $this->connection = $connection;
        return $this;
    }
    
    /**
     * 
     * @return Net_SFTP
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * 
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
        return $this;
    }

    public function getRoot()
    {
        return $this->config['root'];
    }
    
    public function getReleaseDir($releaseName)
    {
        return sprintf('cd %s/%s/%s', $this->getRoot(), Target::DIR_RELEASES, $releaseName);
    }

    /**
     * 
     * @return array
     */
    public function getDirectories($path = '')
    {
        $this->chdir($path);
        $dirs = $this->connection->rawlist();
        ksort($dirs);
        return array_except($dirs, array('.', '..'));
    }

    /**
     * 
     * @return boolean
     */
    public function isSetup()
    {
        $this->chdir();
        $dirs = $this->getDirectories();
        return $this->connection->pwd() == $this->getRoot() && array_key_exists(static::DIR_RELEASES, $dirs) && array_key_exists(static::DIR_STORAGE, $dirs) && array_key_exists(static::DIR_VENDOR, $dirs);
    }
    
    public function setup()
    {
        $this->chdir();
        if ($this->connection->pwd() != $this->getRoot())
        {
            $this->connection->mkdir($this->getRoot(), -1, true);
            $this->chdir();
        }
        
        $this->connection->mkdir(static::DIR_RELEASES);
        $this->connection->mkdir(static::DIR_VENDOR);
        $this->connection->mkdir(static::DIR_STORAGE);
        $this->connection->mkdir(static::DIR_STORAGE . '/cache');
        $this->connection->mkdir(static::DIR_STORAGE . '/logs');
        $this->connection->mkdir(static::DIR_STORAGE . '/meta');
        $this->connection->mkdir(static::DIR_STORAGE . '/sessions');
        $this->connection->mkdir(static::DIR_STORAGE . '/views');
        $this->connection->chmod(0757, static::DIR_STORAGE, true);
    }
    
    /**
     * 
     * @param string $path
     * @return \Jboysen\Deploy\Target
     */
    public function chdir($path = '')
    {
        $this->connection->chdir($this->getRoot() . '/' . $path);
        return $this;
    }
    
    /**
     *
     * @var Release
     */
    private $currentRelease;
    
    /**
     * 
     * @return Release
     */
    public function getCurrentRelease()
    {
        if ($this->currentRelease === null)
        {
            $this->chdir(static::DIR_CURRENT);
            $this->currentRelease = new Release(trim($this->connection->get(static::FILE_RELEASE)));
            $this->currentRelease->setState(Release::STATE_CURRENT);
            $this->currentRelease->setTarget($this);
        }        
        return $this->currentRelease;
    }
    
    /**
     * 
     * @param string $release
     * @return boolean
     */
    public function removeRelease($release)
    {
        return $this->connection->delete($this->getRoot() . '/' . static::DIR_RELEASES . '/' . $release, true);
    }
    
    public function symlink($from, $to)
    {
        $this->connection->delete($this->getRoot() . '/' . $to);
        return $this->runCommands('ln -s ' . $this->getRoot() . '/' . $from . ' ' . $this->getRoot() . '/' . $to);
    }

    /**
     * 
     * @param mixed $commands
     * @param \Jboysen\Deploy\Deployment $deploy
     * @return mixed
     */
    public function runCommands($commands, Deployment $deploy = null)
    {
        if (!is_array($commands))
            $commands = array($commands);
        
        if ($deploy)
        {
            foreach ($commands as $command)
            {
                $deploy->cli($command, Deployment::PRINT_MODE_COMMAND);
            }
        }
        
        $commands = implode('; ', $commands);
        
        if ($deploy)
        {
            $this->connection->exec($commands, function($str) use ($deploy)
                    {
                        $deploy->cli($str, Deployment::PRINT_MODE_COMMAND);
                    });
        }
        else
        {
            return $this->connection->exec($commands);
        }
    }
    
    public function getRawVersion(Release $release)
    {
        $this->chdir(static::DIR_RELEASES . '/' . $release->getName());
        return trim($this->connection->get(static::FILE_VERSION));
    }
    
    public function shutdown()
    {
        $this->_toggleApplication(true);
    }

    public function startup()
    {
        $this->_toggleApplication(false);
    }
    
    private function _toggleApplication($shutdown = true)
    {
        if ($this->_hasCurrentDir())
        {
            $mode = $shutdown ? 'down' : 'up';
            
            $commands = array();
            $commands[] = sprintf('cd %s/%s', $this->getRoot(), static::DIR_CURRENT);
            $commands[] = $this->config['bin'] . 'php artisan ' . $mode . ' --env=production';
            $this->runCommands($commands);
        }
    }
    
    private function _hasCurrentDir()
    {
        $this->chdir();
        $dirs = $this->connection->rawlist();
        return array_key_exists(static::DIR_CURRENT, $dirs);
    }
    
    public function isFirstRelease()
    {
        return !$this->_hasCurrentDir();
    }

}

?>
