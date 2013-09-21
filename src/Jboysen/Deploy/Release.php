<?php namespace Jboysen\Deploy;

class Release
{
    const STATE_OLD_RELEASE = 0;
    const STATE_CURRENT = 1;
    const STATE_NEW = 2;
    
    /**
     *
     * @var string
     */
    private $name;
    
    /**
     *
     * @var Version
     */
    private $version;
    
    /**
     *
     * @var int 
     */
    private $state;
    
    /**
     *
     * @var Target
     */
    private $target;
    
    public function __construct($name = null, $state = null, Target $target = null)
    {
        $this->name = $name;
        $this->state = $state;
        $this->target = $target;
    }
    
    public function __toString()
    {
        return "" . $this->name;
    }
    
    public function getName()
    {
        if ($this->name === null)
        {
            $this->name = date('YmdHis');
        }
        return $this->name;
    }
    
    public function getVersion()
    {
        if ($this->version === null)
        {
            switch ($this->state)
            {
                case static::STATE_OLD_RELEASE:
                case static::STATE_CURRENT:
                    $version = Version::parse($this->target->getRawVersion($this));
                    break;
                case static::STATE_NEW:
                default:
                    $version = Version::fromConfig();
                    break;
            }
            $this->version = $version;
        }
        
        return $this->version;
    }
    
    public function updateVersion(Version $current)
    {
        if ($current->major != $this->getVersion()->major || $current->minor != $this->getVersion()->minor)
        {
            $this->getVersion()->patch = 0;
        }
        else
        {
            $this->getVersion()->patch = $current->patch + 1;
        }
    }
    
    public function getDate()
    {
        $date = \DateTime::createFromFormat('YmdHis', str_replace('release_', '', $this->name));
        return $date->format('Y-m-d H:i:s');
    }
    
    public function setState($state)
    {
        $this->state = $state;
    }
    
    public function setTarget(Target $target)
    {
        $this->target = $target;
    }
    
    /**
     * 
     * @return boolean
     */
    public function remove()
    {
        return $this->target->removeRelease($this->name);
    }
    
}

?>
