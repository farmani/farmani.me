<?php
/**
 * CTDynamoDBHttpSession class
 *
 * @author Ramin Farmani <ramin.farmani@gmail.com>
 * @link http://www.farmani.ir/
 * @copyright 2013 Picontest
 * @license http://www.picontest.ir/license/
 */


/**
 * CCacheHttpSession implements a session component using cache as storage medium.
 *
 * The cache being used can be any cache application component implementing {@link ICache} interface.
 * The ID of the cache application component is specified via {@link cacheID}, which defaults to 'cache'.
 *
 * Beware, by definition cache storage are volatile, which means the data stored on them
 * may be swapped out and get lost. Therefore, you must make sure the cache used by this component
 * is NOT volatile. If you want to use {@link CDbCache} as storage medium, use {@link CDbHttpSession}
 * is a better choice.
 *
 * @property boolean $useCustomStorage Whether to use custom storage.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.web
 * @since 1.0
 */
class CTDynamoDBHttpSession
{
    /**
     * @var string The name of the DynamoDB table in which to store the sessions.
     * This defaults to sessions.
     * @since 1.0.0
     */
    public $sessionTableName;
    /**
     * @var string The name of the hash key in the DynamoDB sessions table. This defaults to id.
     * @since 1.0.0
     */
    public $hashKey;
    /**
     * @var integer The lifetime of an inactive session before it should be garbage collected.
     * If it is not provided, then the actual lifetime value that will be used is
     * ini_get('session.gc_maxlifetime').
     * @since 1.0.0
     */
    public $sessionLifetime;
    /**
     * @var boolean Whether or not the session handler should use consistent reads
     * for the GetItem operation. This defaults to true.
     * @since 1.0.0
     */
    public $consistentRead;
    /**
     * @var mixed The strategy used for doing session locking.
     * By default the handler uses the NullLockingStrategy, which means that session locking
     * is not enabled (see the Session Locking section for more information).
     * Valid values for this option include null, 'null', 'pessemistic', or an instance
     * of NullLockingStrategy or PessimisticLockingStrategy.
     * @since 1.0.0
     */
    public $lockingStrategy;
    /**
     * @var boolean Whether or not to use PHP’s session auto garbage collection.
     * This defaults to the value of (bool) ini_get('session.gc_probability'),
     * but the recommended value is false.
     * @since 1.0.0
     */
    public $automaticGc;
    /**
     * @var integer The batch size used for removing expired sessions during garbage collection.
     * This defaults to 25, which is the maximum size of a single BatchWriteItem operation.
     * This value should also take your provisioned throughput into account as well as the
     * timing of your garbage collection.
     * @since 1.0.0
     */
    public $gcBatchSize;
    /**
     * @var integer The delay (in seconds) between service operations performed during
     * garbage collection. This defaults to 0. Increasing this value allows you to
     * throttle your own requests in an attempt to stay within your provisioned
     * throughput capacity during garbage collection.
     * @since 1.0.0
     */
    public $gcOperationDelay = 0;
    /**
     * @var integer Maximum time (in seconds) that the session handler should wait to
     * acquire a lock before giving up. This defaults to 10 and is only used with the
     * PessimisticLockingStrategy.
     * @since 1.0.0
     */
    public $maxLockWaitTime = 10;
    /**
     * @var integer Minimum time (in microseconds) that the session handler should
     * wait between attempts to acquire a lock. This defaults to 10000 and is
     * only used with the PessimisticLockingStrategy.
     * @since 1.0.0
     */
    public $minLockRetryMicrotime = 10000;
    /**
     * @var integer Maximum time (in microseconds) that the session handler should
     * wait between attempts to acquire a lock. This defaults to 50000 and is only
     * used with the PessimisticLockingStrategy.
     * @since 1.0.0
     */
    public $maxLockRetryMicrotime = 50000;
    /**
     * @var \Aws\DynamoDb\DynamoDbClient The DynamoDbClient object that should be used
     * for performing DynamoDB operations. If you register the session handler from
     * a client object using the registerSessionHandler() method,
     * this will default to the client you are registering it from.
     * If using the SessionHandler::factory() method, you are required to provide
     * an instance of DynamoDbClient.
     * @since 1.0.0
     */
    public $dynamoDBClient;
	/**
	 * Initializes the application component.
	 * This method overrides the parent implementation by checking if cache is available.
	 */
	public function init()
	{
        $dynamoDb = new A2DynamoDb();
        $dynamoDb->getClient()->registerSessionHandler(array(
            'table_name'               => $this->sessionTableName,
            'hash_key'                 => 'id',
            'session_lifetime'         => $this->sessionLifetime,
            'consistent_read'          => $this->consistentRead,
            'locking_strategy'         => $this->lockingStrategy,
            'automatic_gc'             => $this->automaticGc,
            'gc_batch_size'            => $this->gcBatchSize,
            'max_lock_wait_time'       => $this->maxLockWaitTime,
            'min_lock_retry_microtime' => $this->minLockRetryMicrotime,
            'max_lock_retry_microtime' => $this->maxLockRetryMicrotime,
        ));
	}
}
