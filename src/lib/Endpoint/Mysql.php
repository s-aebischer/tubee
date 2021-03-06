<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Endpoint;

use Generator;
use Psr\Log\LoggerInterface;
use Tubee\AttributeMap\AttributeMapInterface;
use Tubee\DataType\DataTypeInterface;
use Tubee\Endpoint\Mysql\Wrapper as MysqlWrapper;

class Mysql extends AbstractSqlDatabase
{
    /**
     * Init endpoint.
     *
     * @param string            $name
     * @param string            $type
     * @param string            $table
     * @param DataTypeInterface $datatype
     * @param Logger            $logger
     * @param iterable          $config
     */
    public function __construct(string $name, string $type, string $table, MysqlWrapper $mysqli, DataTypeInterface $datatype, LoggerInterface $logger, ?Iterable $config = null)
    {
        $this->resource = $mysqli;
        $this->table = $table;
        parent::__construct($name, $type, $datatype, $logger, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function setup(bool $simulate = false): EndpointInterface
    {
        $this->resource->connect();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function shutdown(bool $simulate = false): EndpointInterface
    {
        $this->resource->close();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAll($filter): Generator
    {
        $filter = $this->buildFilterAll($filter);

        if ($filter === null) {
            $sql = 'SELECT * FROM '.$this->table;
        } else {
            $sql = 'SELECT * FROM '.$this->table.' WHERE '.$filter;
        }

        $result = $this->resource->select($sql);

        while ($row = $result->fetch_assoc()) {
            yield $row;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOne(Iterable $object, Iterable $attributes = []): Iterable
    {
        $filter = $this->getFilterOne($object);
        $sql = 'SELECT * FROM '.$this->table.' WHERE '.$filter;
        $result = $this->resource->select($sql);

        if ($result->num_rows > 1) {
            throw new Exception\ObjectMultipleFound('found more than one object with filter '.$filter);
        }
        if ($result->num_rows === 0) {
            throw new Exception\ObjectNotFound('no object found with filter '.$filter);
        }

        return $result->fetch_assoc();
    }

    /**
     * {@inheritdoc}
     */
    public function create(AttributeMapInterface $map, Iterable $object, bool $simulate = false): ?string
    {
        $result = $this->prepareCreate($object, $simulate);

        if ($simulate === true) {
            return null;
        }

        return (string) $result->insert_id;
    }
}
