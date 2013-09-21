<?php namespace Jboysen\Deploy\Commands;

use Jboysen\Deploy\Target;
use Jboysen\Deploy\Release;

class Cleanup extends Base
{

    protected function getCommand()
    {
        return static::COMMAND_CLEANUP;
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
        $this->listReleases();
    }
    
    private $releases = array();
    
    protected function listReleases()
    {
        $this->comment("Listing releases in /" . Target::DIR_RELEASES);
        
        $current = $this->target->getCurrentRelease();
        
        $i = 0;
        
        $this->releases = array();
        $releases = $this->target->getDirectories(Target::DIR_RELEASES);
        
        $this->comment("#\tName:\t\tVersion:\tDate:");
        
        foreach ($releases as $name => $details)
        {
            $release = new Release($name, Release::STATE_OLD_RELEASE, $this->target);
            $str = "\t" . $release . "\t" . $release->getVersion() . "\t\t" . $release->getDate();
            
            if ($name != $current->getName())
            {
                $this->releases[$i] = $release;
                $str = $i . $str;                
            }
            else
            {
                $str .= " [current]";
            }
            $this->info($str);
            $i++;            
        }
        
        $this->removeReleases();
    }
    
    protected function removeReleases()
    {
        $this->comment('Choose a release to remove');
        $this->comment('Enter \'all\' to remove all releases except the current');
        
        $release = trim($this->ask('Choice [or \'exit\']:', null));
        
        if ($release != 'exit')
        {
            $this->removeRelease($release);
        }
    }
    
    protected function removeRelease($release)
    {
        if ($release == 'all')
        {
            foreach ($this->releases as $r)
            {
                $this->removeDir($r);
            }
        }
        else
        {
            if (array_key_exists($release, $this->releases))
                $this->removeDir($this->releases[$release]);
            else
                $this->error('The choice was not valid');
        }
        
        $this->listReleases();
    }
    
    protected function removeDir(Release $release)
    {
        $this->comment('Removing release \'' . $release .'\'');
        $status = $release->remove();
        
        if ($status === false)
        {
            $this->error('Something went wrong when removing \'' . $release .'\'');
            exit;
        }
    }

}