<?php namespace Jboysen\Deploy;

use \Net_SFTP;

class SSH
{

    /**
     *
     * @var Net_SFTP
     */
    private static $connection = null;

    /**
     *
     * @var array
     */
    private static $config = null;

    public static function isInit()
    {
        return static::$connection !== null && static::$config !== null;
    }
    
    public static function init(Net_SFTP $connection, array $config)
    {
        static::$connection = $connection;
        static::$config = $config;
    }

    /**
     * 
     * @return Net_SFTP
     */
    public static function getConnection()
    {
        return static::$connection;
    }
    /**
     * 
     * @return array
     */
    public static function getConfig()
    {
        return static::$config;
    }

}

?>
