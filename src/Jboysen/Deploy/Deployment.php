<?php

namespace Jboysen\Deploy;

use Jboysen\Deploy\Commands\Base;
use Jboysen\Deploy\Target;
use Jboysen\Deploy\Release;

class Deployment
{

    const PRINT_MODE_COMMENT = 'comment';
    const PRINT_MODE_ERROR = 'error';
    const PRINT_MODE_INFO = 'info';
    const PRINT_MODE_COMMAND = 'command';
    const CALLBACK_BEFORE = 'before';
    const CALLBACK_UNPACK = 'unpack';
    const CALLBACK_AFTER = 'after';

    /**
     *
     * @var Base
     */
    private $cli = null;

    /**
     *
     * @var Target
     */
    private $target;

    /**
     *
     * @var Release
     */
    private $release;

    public function __construct(Target $target, Base $cli = null)
    {
        $this->cli = $cli;
        $this->target = $target;
        $this->release = new Release(date('YmdHis'), Release::STATE_NEW, $this->target);
    }

    /**
     * 
     * @return \Jboysen\Deploy\Deployment
     */
    public function buildArchive()
    {
        $this->cli('Building archive...');

        exec(sprintf('git archive HEAD --format=tar | gzip > %s.tar.gz', $this->release->getName()));
        $size = round(filesize($this->release->getName() . '.tar.gz') / 1024 / 1024, 2);

        $this->cli('Archive size: ' . $size . 'MB');
        
        $this->_callback('build_archive');

        return $this;
    }

    /**
     * 
     * @return \Jboysen\Deploy\Deployment
     */
    public function moveToServer()
    {
        $this->cli('Moving archive to server...');

        $this->target->getConnection()->chdir($this->target->getRoot() . '/' . Target::DIR_RELEASES);
        $this->target->getConnection()->mkdir($this->getReleaseName());
        $this->target->getConnection()->chdir($this->getReleaseName());
        $this->target->getConnection()->put($this->getReleaseName() . '.tar.gz', $this->getReleaseName() . '.tar.gz', NET_SFTP_LOCAL_FILE);

        $this->_callback('move_to_server');
        
        return $this;
    }

    /**
     * 
     * @return \Jboysen\Deploy\Deployment
     */
    public function shutdown()
    {
        $this->target->shutdown();

        $this->_callback('shutdown');
        
        return $this;
    }

    /**
     * 
     * @return \Jboysen\Deploy\Deployment
     */
    public function startup()
    {
        $this->target->startup();
        
        $this->_callback('startup');

        return $this;
    }

    /**
     * 
     * @return \Jboysen\Deploy\Deployment
     */
    public function linkStorageAndVendor()
    {
        $this->cli('Linking /storage to /releases/' . $this->getReleaseName() . '/app/storage...');

        $this->target->getConnection()->rename($this->target->getRoot() . '/' . Target::DIR_RELEASES . '/' . $this->getReleaseName() . '/app/storage', $this->target->getRoot() . '/' . Target::DIR_RELEASES . '/' . $this->getReleaseName() . '/app/storage-org');
        $this->target->symlink(Target::DIR_STORAGE, Target::DIR_RELEASES . '/' . $this->getReleaseName() . '/app/storage');

        $this->cli('Linking /vendor to /releases/' . $this->getReleaseName() . '/vendor...');

        $this->target->symlink(Target::DIR_VENDOR, Target::DIR_RELEASES . '/' . $this->getReleaseName() . '/' . Target::DIR_VENDOR);

        $this->_callback('link_storage_and_vendor');
        
        return $this;
    }
    
    /**
     * 
     * @return \Jboysen\Deploy\Deployment
     */
    public function linkCurrent()
    {
        $this->cli('Linking /releases/' . $this->getReleaseName() . ' to /current...');

        $this->target->symlink(Target::DIR_RELEASES . '/' . $this->getReleaseName(), Target::DIR_CURRENT);

        $this->_callback('link_current');
        
        return $this;
    }

    /**
     * 
     * @return \Jboysen\Deploy\Deployment
     */
    public function migrateDatabase()
    {
        $this->cli('Migrating database...');

        $commands = array();
        $commands[] = sprintf('cd %s/%s/%s', $this->target->getRoot(), Target::DIR_RELEASES, $this->getReleaseName());
        $commands[] = $this->target->getConfig()['bin'] . 'php artisan migrate --env=production';

        if ($this->target->isFirstRelease())
        {
            $commands[] = $this->target->getConfig()['bin'] . 'php artisan db:seed --env=production';
        }

        $this->target->runCommands($commands, $this);
        
        $this->_callback('migrate_database');

        return $this;
    }

    /**
     * 
     * @return \Jboysen\Deploy\Deployment
     */
    public function runComposer()
    {
        $this->cli('Running composer...');

        $commands = array();
        $commands[] = sprintf('cd %s/%s/%s', $this->target->getRoot(), Target::DIR_RELEASES, $this->getReleaseName());
        $commands[] = 'curl -sS https://getcomposer.org/installer | php';
        $commands[] = './composer.phar install --no-dev -o';

        $this->target->runCommands($commands, $this);
        
        $this->_callback('run_composer');
        
        return $this;
    }

    /**
     * 
     * @return \Jboysen\Deploy\Deployment
     */
    public function updateVersion()
    {
        $this->cli('Saving release name...');
        
        $this->target->getConnection()->chdir($this->target->getRoot() . '/' . Target::DIR_RELEASES . '/' . $this->getReleaseName());
        $this->target->getConnection()->put(Target::FILE_RELEASE, $this->getReleaseName());
        
        $currentVersion = $this->target->getCurrentRelease()->getVersion();
        
        $this->cli('Updating version number...');
        $this->cli('Current version: ' . $currentVersion);
        
        $this->release->updateVersion($currentVersion);
        
        $this->cli('New version: ' . $this->release->getVersion());
        
        $this->target->getConnection()->chdir($this->target->getRoot() . '/' . Target::DIR_RELEASES . '/' . $this->getReleaseName());
        $this->target->getConnection()->put(Target::FILE_VERSION, $this->release->getVersion()->toString());
        
        $this->_callback('update_version');
        
        /* 

          $commands = array();
          $commands[] = sprintf('cd %s/%s/%s', $this->config['path'], static::DIR_RELEASES, $this->config['releaseName']);
          $commands[] = $this->config['bin'] . 'php artisan deploy:dbversion --env=production';

          $new[static::VERSION_DB] = $this->runCommands($commands, true);
          $this->comment('New version:     ' . $this->formatVersion($new));

          $this->writeVersion($new, $this->config['releaseName']);
         */
        return $this;
    }

    /**
     * 
     * @return \Jboysen\Deploy\Deployment
     */
    public function unpackArchive()
    {
        $this->cli('Unpacking archive and removing on server...');

        $commands = array();
        $commands[] = sprintf('cd %s/%s/%s', $this->target->getRoot(), Target::DIR_RELEASES, $this->getReleaseName());
        $commands[] = sprintf('tar -xzf %s.tar.gz', $this->getReleaseName());
        $commands[] = sprintf('rm %s.tar.gz', $this->getReleaseName());

        $this->target->runCommands($commands, $this);

        $this->_callback('unpack');

        return $this;
    }

    public function cleanup()
    {
        $this->cli('Cleaning up...');
        exec(sprintf('rm %s.tar.gz', $this->getReleaseName()));
        
        $this->_callback('after');

        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getReleaseName()
    {
        return $this->release->getName();
    }

    private function _callback($key)
    {
        $callback = \Config::get('deploy::callbacks.' . $key, null);
        if ($callback)
            $callback($this, $this->target);
    }

    public function cli($msg, $mode = null)
    {
        if ($mode === null)
            $mode = static::PRINT_MODE_COMMENT;

        if ($this->cli)
        {
            switch ($mode)
            {
                case static::PRINT_MODE_COMMENT:
                    $this->cli->comment($msg);
                    break;
                case static::PRINT_MODE_ERROR:
                    $this->cli->error($msg);
                    break;
                case static::PRINT_MODE_COMMAND:
                    $this->cli->info('> ' . $msg);
                    break;
                case static::PRINT_MODE_INFO:
                default:
                    $this->cli->info($msg);
                    break;
            }
        }
    }

}

?>
