<?php

return array(
    'version'   => array(
        'major' => 0,
        'minor' => 1,
    ),
    'targets'   => array(
        'production' => array(
            'host'     => '',
            'port'     => 22,
            'user'     => '',
            'password' => '',
            'root'     => '',
            'bin'      => '/usr/bin'
        ),
    ),
    'composer'  => 'composer.phar',
    'callbacks' => array(
        'before' => function(\Jboysen\Deploy\Deployment $deploy, \Jboysen\Deploy\Target $target)
        {
        },
        'after' => function(\Jboysen\Deploy\Deployment $deploy, \Jboysen\Deploy\Target $target)
        {
        }
    ),
);