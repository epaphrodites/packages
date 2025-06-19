<?php


namespace Epaphrodites\Packages;

trait Constant{
   
    private const YAML_CONFIG_FILE = 'synchrone-config.yaml';
    private const VENDOR_INIT_PATH = '/vendor/epaphrodites/packages/src/epaphrodites/init-ressources';
    private const VENDOR_NEW_PATH = '/vendor/epaphrodites/packages/src/epaphrodites/new-ressources';
    private const VENDOR_BACKUP_PATH = '/vendor/epaphrodites/packages/src/epaphrodites/old-ressources';
    
    private const STANDARD_DIRECTORIES = ['bin', 'public/layouts', 'config'];
    private const NEW_COMPONENT_DIRECTORIES = ['bin', 'public/layouts'];
    
    private const PERMISSIONS = 0775;    
}