<?php

/**
 * @package ActiveRecord
 */

namespace ActiveRecord;

use Closure;
use ReflectionException;
use ActiveRecord\DateTime;
use ActiveRecord\Exceptions\ConfigException;

/**
 * Manages configuration options for ActiveRecord.
 *
 * <code>
 * ActiveRecord::initialize(function($cfg) {
 *   $cfg->set_model_home('models');
 *   $cfg->set_connections(array(
 *     'development' => 'mysql://user:pass@development.com/awesome_development',
 *     'production' => 'mysql://user:pass@production.com/awesome_production'));
 * });
 * </code>
 *
 * @package ActiveRecord
 */
class Config extends Singleton
{
    /**
     * Name of the connection to use by default.
     *
     * <code>
     * ActiveRecord\Config::initialize(function($cfg) {
     *   $cfg->set_connections(array(
     *     'development' => 'mysql://user:pass@development.com/awesome_development',
     *     'production' => 'mysql://user:pass@production.com/awesome_production'));
     * });
     * </code>
     *
     * This is a singleton class so you can retrieve the {@link Singleton} instance by doing:
     *
     * <code>
     * $config = ActiveRecord\Config::instance();
     * </code>
     *
     * @var string
     */
    private $default_connection = 'development';

    /**
     * Contains the list of database connection strings.
     *
     * @var array
     */
    private $connections = array();

    /**
     * Switch for logging.
     *
     * @var bool
     */
    private $logging = false;

    /**
     * Contains a Logger object that must impelement a log() method.
     *
     * @var object
     */
    private $logger;

    /**
     * Contains the class name for the Date class to use. Must have a public format() method and a
     * public static createFromFormat($format, $time) method
     *
     * @var string
     */
    private $date_class = DateTime::class;

    /**
     * The format to serialize DateTime values into.
     *
     * @var string
     */
    private $date_format = \DateTime::ISO8601;

    /**
     * Allows config initialization using a closure.
     *
     * This method is just syntatic sugar.
     *
     * <code>
     * ActiveRecord\Config::initialize(function($cfg) {
     *   $cfg->set_connections(array(
     *     'development' => 'mysql://username:password@127.0.0.1/database_name'));
     * });
     * </code>
     *
     * You can also initialize by grabbing the singleton object:
     *
     * <code>
     * $cfg = ActiveRecord\Config::instance();
     * $cfg->set_model_directory('/path/to/your/model_directory');
     * $cfg->set_connections(array('development' =>
     *   'mysql://username:password@localhost/database_name'));
     * </code>
     *
     * @param Closure $initializer A closure
     * @return void
     */
    public static function initialize(Closure $initializer)
    {
        $initializer(parent::instance());
    }

    /**
     * Sets the list of database connection strings.
     *
     * <code>
     * $config->set_connections(array(
     *     'development' => 'mysql://username:password@127.0.0.1/database_name'));
     * </code>
     *
     * @param array $connections Array of connections
     * @param string $default_connection Optionally specify the default_connection
     * @return void
     * @throws ActiveRecord\ConfigException
     */
    public function set_connections($connections, $default_connection = null)
    {
        if (!is_array($connections)) {
            throw new ConfigException("Connections must be an array");
        }

        if ($default_connection) {
            $this->set_default_connection($default_connection);
        }

        $this->connections = $connections;
    }

    /**
     * Returns the connection strings array.
     *
     * @return array
     */
    public function get_connections()
    {
        return $this->connections;
    }

    /**
     * Returns a connection string if found otherwise null.
     *
     * @param string $name Name of connection to retrieve
     * @return string connection info for specified connection name
     */
    public function get_connection($name)
    {
        if (array_key_exists($name, $this->connections)) {
            return $this->connections[$name];
        }

        return null;
    }

    /**
     * Returns the default connection string or null if there is none.
     *
     * @return string
     */
    public function get_default_connection_string()
    {
        return array_key_exists($this->default_connection, $this->connections) ?
            $this->connections[$this->default_connection] : null;
    }

    /**
     * Returns the name of the default connection.
     *
     * @return string
     */
    public function get_default_connection()
    {
        return $this->default_connection;
    }

    /**
     * Set the name of the default connection.
     *
     * @param string $name Name of a connection in the connections array
     * @return void
     */
    public function set_default_connection($name)
    {
        $this->default_connection = $name;
    }

    /**
     * Turn on/off logging
     *
     * @param boolean $bool
     * @return void
     */
    public function set_logging($bool)
    {
        $this->logging = (bool)$bool;
    }

    /**
     * Sets the logger object for future SQL logging
     *
     * @param object $logger
     * @return void
     * @throws ConfigException if Logger objecct does not implement public log()
     */
    public function set_logger($logger)
    {
        $klass = Reflections::instance()->add($logger)->get($logger);

        if (!$klass->getMethod('log') || !$klass->getMethod('log')->isPublic()) {
            throw new ConfigException("Logger object must implement a public log method");
        }

        $this->logger = $logger;
    }

    /**
     * Return whether or not logging is on
     *
     * @return boolean
     */
    public function get_logging()
    {
        return $this->logging;
    }

    /**
     * Returns the logger
     *
     * @return object
     */
    public function get_logger()
    {
        return $this->logger;
    }

    public function set_date_class($date_class)
    {
        try {
            $klass = Reflections::instance()->add($date_class)->get($date_class);
        } catch (ReflectionException $e) {
            throw new ConfigException("Cannot find date class");
        }

        if (!$klass->hasMethod('format') || !$klass->getMethod('format')->isPublic()) {
            throw new ConfigException('Given date class must have a "public format($format = null)" method');
        }

        if (!$klass->hasMethod('createFromFormat') || !$klass->getMethod('createFromFormat')->isPublic()) {
            throw new ConfigException('Given date class must have a "public static createFromFormat($format, $time)" method');
        }

        $this->date_class = $date_class;
    }

    public function get_date_class()
    {
        return $this->date_class;
    }

    /**
     * @deprecated
     */
    public function get_date_format()
    {
        trigger_error('Use ActiveRecord\Serialization::$DATETIME_FORMAT. Config::get_date_format() has been deprecated.', E_USER_DEPRECATED);
        return Serialization::$DATETIME_FORMAT;
    }

    /**
     * @deprecated
     */
    public function set_date_format($format)
    {
        trigger_error('Use ActiveRecord\Serialization::$DATETIME_FORMAT. Config::set_date_format() has been deprecated.', E_USER_DEPRECATED);
        Serialization::$DATETIME_FORMAT = $format;
    }

    /**
     * Sets the url for the cache server to enable query caching.
     *
     * Only table schema queries are cached at the moment. A general query cache
     * will follow.
     *
     * Example:
     *
     * <code>
     * $config->set_cache("memcached://localhost");
     * $config->set_cache("memcached://localhost",array("expire" => 60));
     * </code>
     *
     * @param string $url Url to your cache server.
     * @param array $options Array of options
     */
    public function set_cache($url, $options = array())
    {
        Cache::initialize($url, $options);
    }
}
