<?php
    
namespace Islandora\Crayfish\KeyCache;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;
use Moust\Silex\Cache\ArrayCache;
use Islandora\Chullo\Uuid\UuidGenerator;

class GetUuidCacheTest extends CrayfishWebTestCase
{

    private $cache;

    private $uuid_gen;

    public function setUp()
    {
        parent::setUp();
        $this->cache = new UuidCache(new ArrayCache());
        $this->uuid_gen = new UuidGenerator();
    }

    public function createApplication()
    {
        return parent::createApplication();
    }

    /**
     * @group UnitTest
     * @covers Islandora\Crayfish\KeyCache\UuidCache::getByUuid
     */
    public function testGetByUuid()
    {
        $txId = $this->uuid_gen->generateV4();
        $uuid = $this->uuid_gen->generateV4();
        $path = 'http://localhost:8080/fcrepo/rest/object1';

        $response = $this->cache->set($txId, $uuid, $path);
        $this->assertTrue($response, "Failed to set value in cache.");

        $response = $this->cache->getByUuid($txId, $uuid);
        $this->assertEquals($response, $path, "Failed to retrieve by UUID");
    }

    /**
     * @group UnitTest
     * @covers Islandora\Crayfish\KeyCache\UuidCache::getByUuid
     */
    public function testGetByUuidFailureNoTX()
    {
        $txId = $this->uuid_gen->generateV4();
        $uuid = $this->uuid_gen->generateV4();

        $response = $this->cache->getByUuid($txId, $uuid);
        $this->assertEquals($response, null, "Got a result when we shouldn't have");
    }

    /**
     * @group UnitTest
     * @covers Islandora\Crayfish\KeyCache\UuidCache::getByUuid
     */
    public function testGetByUuidFailureNoUuid()
    {
        $txId = $this->uuid_gen->generateV4();
        $uuid1 = $this->uuid_gen->generateV4();
        $uuid2 = $this->uuid_gen->generateV4();
        $path1 = 'http://localhost:8080/fcrepo/rest/object1';

        $response = $this->cache->set($txId, $uuid1, $path1);
        $this->assertTrue($response, "Failed to set value in cache.");

        $response = $this->cache->getByUuid($txId, $uuid2);
        $this->assertEquals($response, null, "Got a result when we shouldn't have");
    }

    /**
     * @group UnitTest
     * @covers Islandora\Crayfish\KeyCache\UuidCache::getByPath
     */
    public function testGetByPath()
    {
        $txId = $this->uuid_gen->generateV4();
        $uuid = $this->uuid_gen->generateV4();
        $path = 'http://localhost:8080/fcrepo/rest/object1';

        $response = $this->cache->set($txId, $uuid, $path);
        $this->assertTrue($response, "Failed to set value in cache.");

        $response = $this->cache->getByPath($txId, $path);
        $this->assertEquals($response, $uuid, "Failed to retrieve by path");
    }

    /**
     * @group UnitTest
     * @covers Islandora\Crayfish\KeyCache\UuidCache::getByPath
     */
    public function testGetByPathFailureNoTX()
    {
        $txId = $this->uuid_gen->generateV4();
        $path = 'http://localhost:8080/fcrepo/rest/object1';

        $response = $this->cache->getByPath($txId, $path);
        $this->assertEquals($response, null, "Got a result when we shouldn't have");
    }

    /**
     * @group UnitTest
     * @covers Islandora\Crayfish\KeyCache\UuidCache::getByPath
     */
    public function testGetByPathFailureNoPath()
    {
        $txId = $this->uuid_gen->generateV4();
        $uuid1 = $this->uuid_gen->generateV4();
        $path1 = 'http://localhost:8080/fcrepo/rest/object1';
        $path2 = 'http://localhost:8080/fcrepo/rest/object2';

        $response = $this->cache->set($txId, $uuid1, $path1);
        $this->assertTrue($response, "Failed to set value in cache.");

        $response = $this->cache->getByPath($txId, $path2);
        $this->assertEquals($response, null, "Got a result when we shouldn't have");
    }
}
