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

use Mandango\Repository as BaseRepository;
use Mandango\Connection;
use Mandango\Mandango;
use Mandango\Query;

class Repository extends BaseRepository
{
    protected $documentClass = 'MyDocument';
    protected $connectionName = 'foo';
    protected $collectionName = 'bar';

    public function idToMongo($id)
    {
        return $id;
    }
}

class RepositoryMock extends Repository
{
    private $collectionNameMock;
    private $collection;
    private $connection;

    public function setCollectionName($collectionName)
    {
        $this->collectionNameMock = $collectionName;

        return $this;
    }

    public function getCollectionName()
    {
        return $this->collectionNameMock;
    }

    public function setCollection($collection)
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    public function getConnection()
    {
        return $this->connection;
    }
}

class RepositoryTest extends TestCase
{
    public function testConstructorGetMandango()
    {
        $repository = new Repository($this->mandango);
        $this->assertSame($this->mandango, $repository->getMandango());
    }

    public function testGetIdentityMap()
    {
        $repository = new Repository($this->mandango);
        $identityMap = $repository->getIdentityMap();
        $this->assertInstanceOf('Mandango\IdentityMap', $identityMap);
        $this->assertSame($identityMap, $repository->getIdentityMap());
    }

    public function testGetDocumentClass()
    {
        $repository = new Repository($this->mandango);
        $this->assertSame('MyDocument', $repository->getDocumentClass());
    }

    public function testGetConnectionName()
    {
        $repository = new Repository($this->mandango);
        $this->assertSame('foo', $repository->getConnectionName());
    }

    public function testGetCollectionName()
    {
        $repository = new Repository($this->mandango);
        $this->assertSame('bar', $repository->getCollectionName());
    }

    public function testGetConnection()
    {
        $connections = array(
            'local'  => new Connection($this->uri, $this->dbName.'_local'),
            'global' => new Connection($this->uri, $this->dbName.'_global'),
        );

        $mandango = new Mandango($this->metadataFactory, $this->cache);
        $mandango->setConnections($connections);
        $mandango->setDefaultConnectionName('local');

        $this->assertSame($connections['local'], $mandango->getRepository('Model\Article')->getConnection());
        $this->assertSame($connections['global'], $mandango->getRepository('Model\ConnectionGlobal')->getConnection());
    }

    public function testCollection()
    {
        $mandango = new Mandango($this->metadataFactory, $this->cache);
        $connection = new Connection($this->uri, $this->dbName.'_collection');
        $mandango->setConnection('default', $connection);
        $mandango->setDefaultConnectionName('default');

        $collection = $mandango->getRepository('Model\Article')->getCollection();
        $this->assertEquals($connection->getDatabase()->selectCollection('articles'), $collection);
        $this->assertSame($collection, $mandango->getRepository('Model\Article')->getCollection());
    }

    public function testQuery()
    {
        $query = $this->mandango->getRepository('Model\Article')->createQuery();
        $this->assertInstanceOf('Model\ArticleQuery', $query);

        $query = $this->mandango->getRepository('Model\Author')->createQuery();
        $this->assertInstanceOf('Model\AuthorQuery', $query);

        $criteria = array('is_active' => true);
        $query = $this->mandango->getRepository('Model\Article')->createQuery($criteria);
        $this->assertInstanceOf('Model\ArticleQuery', $query);
        $this->assertSame($criteria, $query->getCriteria());
    }

    public function testIdsToMongo()
    {
        $ids = $this->mandango->getRepository('Model\Article')->idsToMongo(array(
            $this->generateObjectId(),
            $id1 = new \MongoDB\BSON\ObjectID($this->generateObjectId()),
            $this->generateObjectId(),
        ));
        $this->assertSame(3, count($ids));
        $this->assertInstanceOf('\\MongoDB\\BSON\\ObjectID', $ids[0]);
        $this->assertSame($id1, $ids[1]);
        $this->assertInstanceOf('\\MongoDB\\BSON\\ObjectID', $ids[2]);
    }

    public function testFindByIdAndFindOneById()
    {
        $articles = array();
        $articlesById = array();
        for ($i = 0; $i <= 10; $i++) {
            $articleSaved = $this->mandango->create('Model\Article')->setTitle('Article'.$i)->save();
            $articles[] = $article = $this->mandango->create('Model\Article')->setId($articleSaved->getId())->setIsNew(false);
            $articlesById[$article->getId()->__toString()] = $article;
        }

        $repository = $this->mandango->getRepository('Model\Article');
        $identityMap = $repository->getIdentityMap();

        $identityMap->clear();
        $this->assertEquals($articles[1]->toArray(), $article1 = $repository->findOneById($articles[1]->getId())->toArray());
        $this->assertEquals($articles[3]->toArray(), $article3 = $repository->findOneById($articles[3]->getId())->toArray());
        $this->assertEquals($article1, $repository->findOneById($articles[1]->getId())->toArray());
        $this->assertEquals($article3, $repository->findOneById($articles[3]->getId())->toArray());

        $articles1 = [];
        foreach($repository->findById($ids1 = array($articles[1]->getId(), $articles[3]->getId(), $articles[4]->getId())) as $document) {
            $articles1[$document->getId()->__toString()] = $document->toArray();
        }

        $identityMap->clear();
        $this->assertEquals(array(
            $articles[1]->getId()->__toString() => $articles[1]->toArray(),
            $articles[3]->getId()->__toString() => $articles[3]->toArray(),
            $articles[4]->getId()->__toString() => $articles[4]->toArray(),
        ), $articles1);

        $articles2 = [];
        foreach($repository->findById($ids2 = array($articles[1]->getId(), $articles[4]->getId(), $articles[7]->getId())) as $document) {
            $articles2[$document->getId()->__toString()] = $document->toArray();
        }

        $this->assertEquals(array(
            $articles[1]->getId()->__toString() => $articles[1]->toArray(),
            $articles[4]->getId()->__toString() => $articles[4]->toArray(),
            $articles[7]->getId()->__toString() => $articles[7]->toArray(),
        ), $articles2);
        $this->assertSame($articles1[$articles[1]->getId()->__toString()], $articles2[$articles[1]->getId()->__toString()]);
        $this->assertSame($articles1[$articles[4]->getId()->__toString()], $articles2[$articles[4]->getId()->__toString()]);
    }

    public function testFindByIdShouldWorkWithMixedAlreadyQueriedAndNot()
    {
        $articlesWithIds = $this->createArticles(5);
        $articles = array_values($articlesWithIds);

        $repository = $this->getRepository('Model\Article');
        $repository->findById(array(
            $articles[1]->getId(),
            $articles[3]->getId(),
        ));

        $results = $repository->findById(array(
            $articles[0]->getId(),
            $articles[1]->getId(),
            $articles[2]->getId(),
            $articles[3]->getId(),
            $articles[4]->getId(),
        ));

        $array1 = array();
        foreach ($articlesWithIds as $document) {
            $array1[$document->getId()->__toString()] = $document->toArray();
        }
        $array2 = array();
        foreach ($results as $document) {
            $array2[$document->getId()->__toString()] = $document->toArray();
        }

        $this->assertEquals($array1, $array2);
    }

    public function testFindByIdWithIdAsArray()
    {
        $repository = $this->getRepository('Model\IdAsArray');
        $documents = [
            ['_id' => ['a' => 1, 'b' => 2], 'name' => 'first'],
            ['_id' => ['a' => 1, 'b' => 3], 'name' => 'second'],
        ];
        $repository->getCollection()->insertMany($documents);

        $results = $repository->findById([['a' => 1, 'b' => 2]]);

        $this->assertCount(1, $results);
        $this->assertEquals($documents[0]['name'], $results[0]->getName());
    }

    public function testCount()
    {
        $criteria = array('is_active' => false);
        $count = 20;

        $collection = $this->getMockBuilder('\\MongoDB\\Collection')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $collection
            ->expects($this->any())
            ->method('count')
            ->with($criteria)
            ->will($this->returnValue($count))
        ;

        $repository = new RepositoryMock($this->mandango);
        $repository->setConnection($this->mandango->getDefaultConnection());
        $repository->setCollection($collection);
        $this->assertSame($count, $repository->count($criteria));
    }

    public function testUpdate()
    {
        $criteria = array('is_active' => false);
        $newObject = array('$set' => array('title' => 'ups'));

        $collection = $this->getMockBuilder('\\MongoDB\\Collection')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $collection
            ->expects($this->any())
            ->method('updateMany')
            ->with($criteria, $newObject)
        ;

        $repository = new RepositoryMock($this->mandango);
        $repository->setCollection($collection);
        $repository->setConnection($this->mandango->getDefaultConnection());
        $repository->update($criteria, $newObject);
        $this->addToAssertionCount(1);
    }

    public function testRemove()
    {
        $criteria = array('is_active' => false);

        $collection = $this->getMockBuilder('\\MongoDB\\Collection')
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $collection
            ->expects($this->any())
            ->method('deleteMany')
            ->with($criteria)
        ;

        $repository = new RepositoryMock($this->mandango);
        $repository->setCollection($collection);
        $repository->setConnection($this->mandango->getDefaultConnection());
        $repository->remove($criteria);
        $this->addToAssertionCount(1);
    }

    public function testAggregate()
    {
        $pipeline = [
            ['$match' => ['status' => 'A']],
            ['$group' => ['_id' => '$cust_id', 'total' => ['$sum' => 'amount']]]
        ];

        $result = [
            ['_id' => 'a1', 'total' => 321],
            ['_id' => 'a2', 'total' => 87],
        ];

        $collection = $this->getMockBuilder('\\MongoDB\\Collection')
            ->disableOriginalConstructor()
            ->getMock();

        $collection
            ->expects($this->once())
            ->method('aggregate')
            ->with($pipeline)
            ->will($this->returnValue($result));

        $repository = new RepositoryMock($this->mandango);
        $repository->setCollection($collection);
        $repository->setConnection($this->mandango->getDefaultConnection());

        $repository->aggregate($pipeline);
    }

    public function testDistinct()
    {
        $field = 'fieldName';
        $query = array('foo' => 'bar');

        $return = new \ArrayObject();

        $collection = $this->createCollectionMock();
        $collection
            ->expects($this->once())
            ->method('distinct')
            ->with($field, $query)
            ->will($this->returnValue($return));

        $repository = $this->createRepositoryMock()
            ->setCollection($collection);
        $repository->setConnection($this->mandango->getDefaultConnection());

        $this->assertSame($return, $repository->distinct($field, $query));
    }

    public function testMapReduce()
    {
        $collectionName = 'myCollectionName';

        $map = new \MongoDB\BSON\Javascript('map');
        $reduce = new \MongoDB\BSON\Javascript('reduce');
        $out = array('replace' => 'replaceCollectionName');
        $query = array('foo' => 'bar');

        $expectedCommand = array(
            'mapreduce' => $collectionName,
            'map'       => $map,
            'reduce'    => $reduce,
            'out'       => $out,
            'query'     => $query,
        );

        $resultCollectionName = 'myResultCollectionName';
        $result = array('ok' => true, 'result' => $resultCollectionName);

        $cursor = new \DateTime();

        $resultCollection = $this->createCollectionMock();
        $resultCollection
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($cursor));

        $database = $this->createMongoDatabaseMock();
        $database
            ->expects($this->once())
            ->method('command')
            ->with($expectedCommand)
            ->will($this->returnValue($result));
        $database
            ->expects($this->once())
            ->method('selectCollection')
            ->with($resultCollectionName)
            ->will($this->returnValue($resultCollection));

        $connection = $this->createConnectionMockWithMongoDatabase($database);
        $repository = $this->createRepositoryMock()
            ->setCollectionName($collectionName)
            ->setConnection($connection);

        $this->assertSame($cursor, $repository->mapReduce($map, $reduce, $out, $query));
    }

    public function testMapReduceWithOptions()
    {
        $collectionName = 'myCollectionName';

        $map = new \MongoDB\BSON\Javascript('map');
        $reduce = new \MongoDB\BSON\Javascript('reduce');
        $out = array('replace' => 'replaceCollectionName');
        $query = array('foo' => 'bar');
        $options = array('ups' => 2, 'session' => null);

        $expectedCommand = array(
            'mapreduce' => $collectionName,
            'map'       => $map,
            'reduce'    => $reduce,
            'out'       => $out,
            'query'     => $query,
        );

        $resultCollectionName = 'myResultCollectionName';
        $result = array('ok' => true, 'result' => $resultCollectionName);

        $cursor = new \DateTime();

        $resultCollection = $this->createCollectionMock();
        $resultCollection
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($cursor));

        $database = $this->createMongoDatabaseMock();
        $database
            ->expects($this->once())
            ->method('command')
            ->with($expectedCommand, $options)
            ->will($this->returnValue($result));
        $database
            ->expects($this->once())
            ->method('selectCollection')
            ->with($resultCollectionName)
            ->will($this->returnValue($resultCollection));

        $connection = $this->createConnectionMockWithMongoDatabase($database);
        $repository = $this->createRepositoryMock()
            ->setCollectionName($collectionName)
            ->setConnection($connection);

        $command = array();
        $this->assertSame($cursor, $repository->mapReduce($map, $reduce, $out, $query, $command, $options));
    }

    public function testMapReduceInline()
    {
        $collectionName = 'myCollectionName';

        $map = 'map';
        $reduce = 'reduce';
        $out = array('inline' => 1);
        $query = array();

        $expectedCommand = array(
            'mapreduce' => $collectionName,
            'map'       => new \MongoDB\BSON\Javascript($map),
            'reduce'    => new \MongoDB\BSON\Javascript($reduce),
            'out'       => $out,
            'query'     => $query,
        );

        $results = array(new \DateTime());
        $result = array('ok' => true, 'results' => $results);

        $database = $this->createMongoDatabaseMock();
        $database
            ->expects($this->once())
            ->method('command')
            ->with($expectedCommand)
            ->will($this->returnValue($result));

        $connection = $this->createConnectionMockWithMongoDatabase($database);
        $repository = $this->createRepositoryMock()
            ->setCollectionName($collectionName)
            ->setConnection($connection);

        $this->assertSame($results, $repository->mapReduce($map, $reduce, $out, $query));
    }

    public function testMapReduceRuntimeExceptionOnError()
    {
        $this->expectException(\RuntimeException::class);
        $collectionName = 'myCollectionName';

        $result = array('ok' => false, 'errmsg' => $errmsg = 'foobarbarfooups');

        $database = $this->createMongoDatabaseMock();
        $database
            ->expects($this->once())
            ->method('command')
            ->will($this->returnValue($result));

        $connection = $this->getMockBuilder('\\Mandango\\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection
            ->expects($this->any())
            ->method('getDatabase')
            ->will($this->returnValue($database));

        $connection = $this->createConnectionMockWithMongoDatabase($database);
        $repository = $this->createRepositoryMock()
            ->setCollectionName($collectionName)
            ->setConnection($connection);

        $repository->mapReduce('foo', 'bar', array('inline' => 1));
    }

    private function createMongoDatabaseMock()
    {
        return $this->getMockBuilder('\\MongoDB\\Database')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createCollectionMock()
    {
        return $this->getMockBuilder('\\MongoDB\\Collection')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createConnectionMockWithMongoDatabase($database)
    {
        $connection = $this->getMockBuilder('\\Mandango\\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection
            ->expects($this->any())
            ->method('getDatabase')
            ->will($this->returnValue($database));

        return $connection;
    }

    private function createRepositoryMock()
    {
        return new RepositoryMock($this->mandango);
    }
}
