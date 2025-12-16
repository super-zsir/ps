<?php

namespace Imee\Comp\Common\Fixed;

use Phalcon\Loader as PhalconLoader;

class Loader extends PhalconLoader
{
    protected $_prefixes = array();

    public function __construct()
    {
        //parent::__construct();
    }

    /**
     * Autoloads the registered classes
     */
    public function autoLoad($className)
    {
        if ($this->_eventsManager) {
            $this->_eventsManager->fire("loader:beforeCheckClass", $this, $className);
        }

        /**
         * First we check for static paths for classes
         */
        if (is_array($this->_classes) && isset($this->_classes[$className])) {
            $filePath = $this->_classes[$className];
            if ($this->_eventsManager) {
                $this->_foundPath = $filePath;
                $this->_eventsManager->fire("loader:pathFound", $this, $filePath);
            }
            require $filePath;
            return true;
        }

        $ds = DIRECTORY_SEPARATOR;
        $namespaceSeparator = "\\";

        /**
         * Checking in namespaces
         */
        if (is_array($this->_namespaces)) {
            foreach ($this->_namespaces as $nsPrefix => $directoryArray) {
                if (strpos($className, $nsPrefix) === 0) {
                    $fileName = substr($className, strlen($nsPrefix . $namespaceSeparator));
                    $fileName = str_replace($namespaceSeparator, $ds, $fileName);
                    if ($fileName) {
                        foreach ($directoryArray as $directory) {
                            $fixedDirectory = rtrim($directory, $ds) . $ds;
                            $lastIndex = strrpos($fileName, '/');
                            if ($lastIndex !== false) {
                                $fileNameList = array_map(function ($v) {
                                    return lcfirst($v);
                                }, explode('/', substr($fileName, 0, $lastIndex)));

                                $fixedDirectory = $fixedDirectory . implode('/', $fileNameList) . $ds;
                                $fileName = substr($fileName, $lastIndex + 1);
                            }

                            foreach ($this->_extensions as $extension) {
                                $filePath = $fixedDirectory . $fileName . "." . $extension;
                                if ($this->_eventsManager) {
                                    $this->_checkedPath = $filePath;
                                    $this->_eventsManager->fire("loader:beforeCheckPath", $this, $filePath);
                                }
                                if (is_file($filePath)) {
                                    if ($this->_eventsManager) {
                                        $this->_foundPath = $filePath;
                                        $this->_eventsManager->fire("loader:pathFound", $this, $filePath);
                                    }
                                    require $filePath;
                                    return true;
                                } else {
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Checking in prefixes
         */
        if (is_array($this->_prefixes)) {
            foreach ($this->_prefixes as $prefix => $directory) {
                if (strpos($className, $nsPrefix) === 0) {
                    $fileName = str_replace($prefix . $namespaceSeparator, "", $className);
                    $fileName = str_replace($prefix . "_", "", $fileName);
                    $fileName = str_replace("_", $ds, $fileName);
                    if ($fileName) {
                        $fixedDirectory = rtrim($directory, $ds) . $ds;
                        foreach ($this->_extensions as $extension) {
                            $filePath = $fixedDirectory . $fileName . "." . $extension;
                            if ($this->_eventsManager) {
                                $this->_checkedPath = $filePath;
                                $this->_eventsManager->fire("loader:beforeCheckPath", $this, $filePath);
                            }
                            if (is_file($filePath)) {
                                if ($this->_eventsManager) {
                                    $this->_foundPath = $filePath;
                                    $this->_eventsManager->fire("loader:pathFound", $this, $filePath);
                                }
                                require $filePath;
                                return true;
                            }
                        }
                    }
                }
            }
        }


        $dsClassName = str_replace("_", $ds, $className);
        $nsClassName = str_replace("\\", $ds, $dsClassName);

        if (is_array($this->_directories)) {
            foreach ($this->_directories as $directory) {
                $fixedDirectory = rtrim($directory, $ds) . $ds;
                foreach ($this->_extensions as $extension) {
                    $filePath = $fixedDirectory . $nsClassName . "." . $extension;
                    if ($this->_eventsManager) {
                        $this->_checkedPath = $filePath;
                        $this->_eventsManager->fire("loader:beforeCheckPath", $this, $filePath);
                    }
                    if (is_file($filePath)) {
                        if ($this->_eventsManager) {
                            $this->_foundPath = $filePath;
                            $this->_eventsManager->fire("loader:pathFound", $this, $filePath);
                        }
                        require $filePath;
                        return true;
                    }
                }
            }
        }

        if ($this->_eventsManager) {
            $this->_eventsManager->fire("loader:afterCheckClass", $this, $className);
        }

        return false;
    }
}
