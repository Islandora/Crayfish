<?php
    
namespace Islandora\Crayfish\KeyCache;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Islandora\Crayfish\CrayfishWebTestCase;
use Moust\Silex\Cache\ArrayCache;

class DeleteUuidCacheTest extends CrayfishWebTestCase
{

    private $cache;

    public function setUp()
    {
        parent::setUp();
        $this->cache = new UuidCache(new ArrayCache());
    }

    public function createApplication()
    {
        return parent::createApplication();
    }

    /**
     * @group UnitTest
     * @covers Islandora\Crayfish\KeyCache\UuidCache::delete
     */
    public function testDelete()
    {
        $txId = $this->uuid_gen->generateV4();
        $uuid = $this->uuid_gen->generateV4();
        $path = 'http://localhost:8080/fcrepo/rest/object1';

        $response = $this->cache->set($txId, $uuid, $path);
        $this->assertTrue($response, "Failed to set value in cache.");
        
        $this->cache->delete($txId);

        $response = $this->cache->getByUuid($txId, $uuid);
        $this->assertEquals($response, null, "Failed to delete from cache");
    }
}
