<?php

namespace Jboysen\Deploy;

use Jboysen\Deploy\Release;

class Version
{

    public $major;
    public $minor;
    public $patch;
    public $db;

    /**
     * 
     * @param string $input with the format d.d.d-d
     * @return \Jboysen\Deploy\Version
     */
    public static function parse($input)
    {
        $major = $minor = $patch = $db = 0;
        
        if (preg_match_all("/(\\d+)(\\.)(\\d+)(\\.)(\\d+)(-)(\\d+)/is", $input, $matches))
        {
            $major = $matches[1][0];
            $minor = $matches[3][0];
            $patch = $matches[5][0];
            $db = $matches[7][0];
            
        }

        return new Version($major, $minor, $patch, $db);
    }
    
    /**
     * 
     * @return \Jboysen\Deploy\Version
     */
    public static function fromConfig()
    {
        $major = \Config::get('deploy::version.major', 0);
        $minor = \Config::get('deploy::version.minor', 0);
        
        return new Version($major, $minor);
    }
    
    public function __construct($major, $minor, $patch = 0, $db = 0)
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
        $this->db = $db;
    }
    
    public function __toString()
    {
        return $this->toString();
    }
    
    public function toString()
    {
        return sprintf('%d.%d.%d-%d', $this->major, $this->minor, $this->patch, $this->db);
    }
}

?>
