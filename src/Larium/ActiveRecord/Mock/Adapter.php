<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Mock;

use Larium\Database\AdapterInterface;
use Larium\Database\QueryInterface;
use Larium\ActiveRecord\Mysql\Query;

/**
 * Mock a MySQL database connection.
 *
 */
class Adapter implements AdapterInterface
{

    const FETCH_ASSOC = 2;

    const FETCH_OBJ = 5;

    /**
     * An array with configuration data for this adapter
     *
     * Possible values are
     *
     * host         the host name or an IP address of MySQL server
     * port         Specifies the port number to attempt to connect to the MySQL
     *              server.
     * database     the database name to connect.
     * username     the user that has access to this database
     * password     password for this user
     * charset      the default client character set
     * fetch        the default fetch style for the result set row.
     *              Possible values are:
     *              AdapterInterface::FETCH_ASSOC || AdapterInterface::FETCH_OBJ
     *
     * @var array
     */
    protected $config;

    /**
     * The mysqli connection
     *
     * @var \stdClass
     */
    protected $connection;

    /**
     * An instance of logger to be used fro logging queries.
     *
     * @var mixed
     */
    protected $logger;

    /**
     * The default fetch style for the result set row.
     *
     * @var int
     */
    protected $fetch_style;

    /**
     * An array with all executed queries
     *
     * @var array
     */
    protected $query_array = array();

    private $real_query;

    /**
     * Creates an Adapter instance using an array of options.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the current connection between PHP and MySQL database.
     *
     * @return \stdClass
     */
    public function getConnection()
    {
        if (null === $this->connection) {
            $this->connect();
        }

        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if ( null === $this->connection ) {

            $this->connection = new \stdClass;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->connection = null;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|ResultIterator
     */
    public function execute(QueryInterface $query, $action='Load', $hydration = null)
    {

        $this->real_query = $query->toRealSql();

        if ($this->logger) {
            $this->getLogger()->logQuery(
                $this->real_query,
                $query->getObject(),
                (microtime(true) - $start),
                $action
            );
        }

        $this->query_array[] = $this->real_query;

        switch ($action) {
            case 'Create':
                //INSERT statement

                return $this->getInsertId();
                break;
            case 'Load':
                // SELECT statement

                if (Query::HYDRATE_OBJ == $hydration) {
                    $this->fetch_style = self::FETCH_OBJ;
                }

                $iterator = new ResultIterator(
                    array(),
                    $hydration ?: $this->fetch_style,
                    $query->getObject()
                );

                return $iterator;
                break;
            default:
                // UPDATE, DELETE statement

                return 1;
                break;
        }

        //return $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function createQuery($object = null)
    {
        return new Query($object, $this);
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function getInsertId($stmt = null)
    {
        return (int) mt_rand();
    }

    public function sanitize(&$value)
    {
        if (null === $value) {
            $value = 'NULL';

            return;
        }

        $value = addslashes($value);
        $value = is_numeric($value) ? $value : $this->quote($value);
    }

    public function quote($string)
    {
        //if (substr_count($string, "'") == 2) return $string;

        return "'" . $string . "'";
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Gets an array with queries executed by this adapter.
     *
     * @return array An array with queries
     */
    public function getQueries()
    {
        return $this->query_array;
    }
}
