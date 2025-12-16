<?php

namespace Elasticquent;
use Phalcon\Di;

trait ElasticquentConfigTrait
{
    /**
     * Get Index Name
     *
     * @return string
     */
    public function getIndexName()
    {
        // The first thing we check is if there is an elasticquent
        // config file and if there is a default index.
        $index_name = $this->getElasticConfig('default_index');

        if (!empty($index_name)) {
            return $index_name;
        }

        // Otherwise we will just go with 'default'
        return 'default';
    }

    /**
     * Get the Elasticquent config
     *
     * @param string $key the configuration key
     * @param string $prefix filename of configuration file
     * @return array configuration
     */
    public function getElasticConfig($key = 'config', $prefix = 'elasticquent')
    {
		$config = Di::getDefault()->getShared('config')->elasticsearch;
		return $config;
    }

    /**
     * Inject given config file into an instance of Laravel's config
     *
     * @throws \Exception when the configuration file is not found
     * @return \Illuminate\Config\Repository configuration repository
     */
    protected function getConfigHelper()
    {
        $config_file = $this->getConfigFile();

        if (!file_exists($config_file)) {
            throw new \Exception('Config file not found.');
        }

        return new \Illuminate\Config\Repository(array('elasticquent' => require($config_file)));
    }

    /**
     * Get the config path and file name to use when Laravel framework isn't present
     * e.g. using Eloquent stand-alone or running unit tests
     *
     * @return string config file path 
     */
    protected function getConfigFile()
    {
        return __DIR__ . '/config/elasticquent.php';
    }
}
