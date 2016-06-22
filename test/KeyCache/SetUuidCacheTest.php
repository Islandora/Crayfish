<?php
    
namespace Islandora\Crayfish\KeyCache;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;
use Moust\Silex\Cache\ArrayCache;
use Islandora\Chullo\Uuid\UuidGenerator;

class SetUuidCacheTest extends CrayfishWebTestCase
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
     * @covers Islandora\Crayfish\KeyCache\UuidCache::set
     */
    public function testSetCache()
    {
        $txId = $this->uuid_gen->generateV4();
        $uuid = $this->uuid_gen->generateV4();
        $path = 'http://localhost:8080/fcrepo/rest/object1';
        
        $response = $this->cache->set($txId, $uuid, $path);
        $this->assertTrue($response, "Failed to set value in cache.");
    }

    /**
     * @group UnitTest
     * @covers Islandora\Crayfish\KeyCache\UuidCache::set
     */
    public function testAddToTransaction()
    {
        $txId = $this->uuid_gen->generateV4();

        $uuid1 = $this->uuid_gen->generateV4();
        $path1 = 'http://localhost:8080/fcrepo/rest/object1';
        
        $response = $this->cache->set($txId, $uuid1, $path1);
        $this->assertTrue($response, "Failed to set value in cache.");

        $uuid2 = $this->uuid_gen->generateV4();
        $path2 = 'http://localhost:8080/fcrepo/rest/object2';

        $response = $this->cache->set($txId, $uuid2, $path2);
        $this->assertTrue($response, "Failed to set value in cache.");
    }
}
