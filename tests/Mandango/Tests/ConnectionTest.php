<?php

/*
 * This file is part of Mandango.
 *
 * (c) Pablo Díez <pablodip@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Mandango\Tests;

use Mandango\Connection;

class ConnectionTest extends TestCase
{
    public function testConnection()
    {
        $connection = new Connection($this->uri, $this->dbName);

        $client   = $connection->getClient();
        $database = $connection->getDatabase();

        $this->assertInstanceOf('\MongoDB\Client', $client);
        $this->assertInstanceOf('\MongoDB\Database', $database);
        $this->assertSame($this->dbName, $database->__toString());

        $this->assertSame($client, $connection->getClient());
        $this->assertSame($database, $connection->getDatabase());
    }

    public function testGetters()
    {
        $connection = new Connection('mongodb://127.0.0.1:27017', 'databaseName', array('connect' => true));

        $this->assertSame('mongodb://127.0.0.1:27017', $connection->getUri());
        $this->assertSame('databaseName', $connection->getDbName());
        $this->assertSame(array('connect' => true), $connection->getOptions());
    }

    public function testSession()
    {
        $connection = new Connection($this->uri, $this->dbName);
        $this->assertNull($connection->getSession());

        $session = $connection->startSession();

        $this->assertInstanceOf('MongoDB\\Driver\\Session', $session);
        $this->assertSame($session, $connection->getSession());
    }

    public function testSetUri()
    {
        $connection = new Connection($this->uri, $this->dbName);
        $connection->setUri($uri = 'mongodb://localhost:27017');
        $this->assertSame($uri, $connection->getUri());

        $connection->getClient();
        try {
            $connection->setUri($this->uri);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf('LogicException', $e);
        }
    }

    public function testSetDbName()
    {
        $connection = new Connection($this->uri, $this->dbName);
        $connection->setDbName($dbName = 'mandango_testing');
        $this->assertSame($dbName, $connection->getDbName());

        $connection->getDatabase();
        try {
            $connection->setDbName($this->dbName);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf('LogicException', $e);
        }
    }

    public function testSetOptions()
    {
        $connection = new Connection($this->uri, $this->dbName);
        $connection->setOptions($options = array('connect' => true));
        $this->assertSame($options, $connection->getOptions());

        $connection->getClient();
        try {
            $connection->setOptions(array());
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf('LogicException', $e);
        }
    }

    public function testSetDriverOptions()
    {
        $connection = new Connection($this->uri, $this->dbName);
        $driverOptions = [
            'typeMap' => [
                'array' => 'array',
                'document' => 'array',
                'root' => 'array',
            ]
        ];
        $connection->setDriverOptions($driverOptions);
        $this->assertSame($driverOptions, $connection->getDriverOptions());

        $connection->getClient();
        try {
            $connection->setDriverOptions([]);
            $this->fail();
        } catch (\Exception $e) {
            $this->assertInstanceOf('LogicException', $e);
        }
    }
}
