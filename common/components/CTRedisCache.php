<?php
/**
 * CTRedisCache.php class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.thankyoumenu.com/
 * @copyright Copyright &copy; 2013
 * @license http://www.thankyoumenu.com/license/
 *
 * <pre>
 * array(
 *     'components'=>array(
 *         'cache'=>array(
 *             'class'=>'CTRedisCache',
 *             'servers'=>array(
 *                 'master'=>array(
 *                     'host'=>'server1',
 *                     'port'=>6379,
 *                     'db' => 5,
 *                 ),
 *                 'slave'=>array(
 *                     'host'=>'server1',
 *                     'port'=>6379,
 *                     'db' => 5,
 *                 ),
 *             ),
 *         ),
 *     ),
 * )
 * </pre>
 */
class CTRedisCache extends CCache
{

	const DEFAULT_PORT=6379;
	
	protected $_slaveCache = null;
    protected $_masterCache = null;


    private $connectedSlave = true;
    private $connectedMaster = true;

    /**
     * @var string list of servers
     */
    public $servers = array(
        'master' => array('host' => 'redismaster', 'port' => 6379, 'db' => 0),
        'slave' => array('host' => 'redis', 'port' => 6379, 'db' => 0),
    );

    /**
     * Initializes this application component.
     * This method is required by the {@link IApplicationComponent} interface.
     * It creates the redis instance and adds redis servers.
     * @throws CException if redis extension is not loaded
     */
    public function init()
    {
        parent::init();
        self::getRedisSlave();
        Yii::log('Loading "CredisCache" application component', CLogger::LEVEL_TRACE, 'extension.CRedisCache');
    }

    /**
     * @return mixed the redis instance (or redisd if {@link useRedisd} is true) used by this component.
     */
    protected function getRedisSlave()
    {
        if ($this->_slaveCache === null) {
            Yii::log('Opening Redis Slave connection', CLogger::LEVEL_TRACE, 'CTRedisCache');

            $this->_slaveCache = new Redis();
            $connected = $this->_slaveCache->pconnect($this->servers['slave']['host'], (isset($this->servers['slave']['port']) ? $this->servers['slave']['port'] : self::DEFAULT_PORT));
            if (!$connected) {
                $this->connectedSlave = false;
            } else {
                $this->_slaveCache->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
                if (isset($this->servers['slave']['db'])) {
                    $this->_slaveCache->select($this->servers['slave']['db']);
                }
            }
        }
        return $this->_slaveCache;
    }

    /**
     * @return mixed the redis instance (or redisd if {@link useRedisd} is true) used by this component.
     */
    protected function getRedisMaster()
    {
        if ($this->_masterCache === null) {
            Yii::log('Opening Redis Master connection', CLogger::LEVEL_TRACE, 'extension.CRedisCache');

            $this->_masterCache = new Redis();
            $connected = $this->_masterCache->pconnect($this->servers['master']['host'], (isset($this->servers['master']['port']) ? $this->servers['master']['port'] : self::DEFAULT_PORT));
            if (!$connected) {
                $this->connectedMaster = false;
            } else {
                $this->_masterCache->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
                if (isset($this->servers['master']['db'])) {
                    $this->_masterCache->select($this->servers['master']['db']);
                }
            }
        }
        return $this->_masterCache;
    }

    /**
     * Retrieves a value from cache with a specified key.
     * This is the implementation of the method declared in the parent class.
     * @param string $key a unique key identifying the cached value
     * @return string the value stored in cache, false if the value is not in the cache or expired.
     */
    public function getValue($key)
    {
        return ($this->connectedSlave) ? $this->_slaveCache->get($key) : false;
    }

    /**
     * Retrieves multiple values from cache with the specified keys.
     * @param array $keys a list of keys identifying the cached values
     * @return array a list of cached values indexed by the keys
     * @since 1.0.8
     */
    public function getValues($keys)
    {
        return ($this->connectedSlave) ? $this->_slaveCache->mget($keys) : false;
    }

    /**
     * Stores a value identified by a key in cache.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    public function setValue($key, $value, $expire = 0)
    {
        if ($this->_masterCache === null) {
            self::getRedisMaster();
        }
        if ($this->connectedMaster) {
            if ($expire > 0)
                return $this->_masterCache->setex($key, $expire, $value);
            else
                return $this->_masterCache->set($key, $value);
        }
        return false;
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached
     * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
     * @return boolean true if the value is successfully stored into cache, false otherwise
     */
    public function addValue($key, $value, $expire = 0)
    {
        if ($this->_masterCache === null) {
            self::getRedisMaster();
        }
        if ($this->connectedMaster) {
            if ($expire > 0) {
                return $this->_masterCache->setex($key, $expire, $value);
            } else
                return $this->_masterCache->setnx($key, $value);
        }
        return false;
    }

    /**
     * Deletes a value with the specified key from cache
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key of the value to be deleted
     * @return boolean if no error happens during deletion
     */
    public function deleteValue($key)
    {
        if ($this->_masterCache === null) {
            self::getRedisMaster();
        }
        if ($this->connectedMaster)
            return $this->_masterCache->del($key);
        return false;
    }

    /**
     * Deletes all values from cache.
     * This is the implementation of the method declared in the parent class.
     * @return boolean whether the flush operation was successful.
     * @since 1.1.5
     */
    public function flushValues()
    {
        if ($this->_masterCache === null) {
            self::getRedisMaster();
        }
        if ($this->connectedMaster)
            return $this->_masterCache->flushAll();
        return false;
    }

    /**
     * call unusual method
     * */
    public function __call($method, $args)
    {
        //return call_user_func_array(array($this->_cache,$method),$args);
        Yii::log("__call function was fired to do ${method} with following args:" . print_r($args, true), CLogger::LEVEL_ERROR, 'extension.CRedisCache');
        return false;
    }

    /**
     * Returns whether there is a cache entry with a specified key.
     * This method is required by the interface ArrayAccess.
     * @param string $id a key identifying the cached value
     * @return boolean
     */
    public function offsetExists($id)
    {
        if ($this->connectedSlave)
            return $this->_slaveCache->exists($id);
    }
}