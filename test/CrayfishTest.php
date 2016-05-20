<?php

namespace Islandora\Crayfish\Test;

use Islandora\Chullo\FedoraApi;
use Islandora\Chullo\TriplestoreClient;
use Symfony\Component\HttpFoundation\Response;
use Silex\WebTestCase;

class CrayfishTest extends WebTestCase
{
 
    protected $api;
    
    protected $triplestore;
    
    protected static $theDate;
    
    protected $rootRdf = <<<EOF
@prefix premis: <http://www.loc.gov/premis/rdf/v1#> .
@prefix image: <http://www.modeshape.org/images/1.0> .
@prefix ore: <http://www.openarchives.org/ore/terms/> .
@prefix sv: <http://www.jcp.org/jcr/sv/1.0> .
@prefix isl: <http://www.islandora.ca/ontologies/2016/02/28/isl/v1.0/> .
@prefix test: <info:fedora/test/> .
@prefix nt: <http://www.jcp.org/jcr/nt/1.0> .
@prefix pcdm: <http://pcdm.org/models#> .
@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
@prefix xsi: <http://www.w3.org/2001/XMLSchema-instance> .
@prefix mode: <http://www.modeshape.org/1.0> .
@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix fedora: <http://fedora.info/definitions/v4/repository#> .
@prefix nfo: <http://www.semanticdesktop.org/ontologies/2007/03/22/nfo/v1.2/> .
@prefix xml: <http://www.w3.org/XML/1998/namespace> .
@prefix ebucore: <http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#> .
@prefix ldp: <http://www.w3.org/ns/ldp#> .
@prefix xs: <http://www.w3.org/2001/XMLSchema> .
@prefix fedoraconfig: <http://fedora.info/definitions/v4/config#> .
@prefix mix: <http://www.jcp.org/jcr/mix/1.0> .
@prefix foaf: <http://xmlns.com/foaf/0.1/> .
@prefix dc: <http://purl.org/dc/elements/1.1/> .


<http://localhost:8080/fcrepo/rest/> a ldp:RDFSource , ldp:Container , ldp:BasicContainer , <http://www.modeshape.org/1.0root> , <http://www.jcp.org/jcr/nt/1.0base> , <http://www.jcp.org/jcr/mix/1.0referenceable> ;
	fedora:primaryType "mode:root"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:writable "true"^^<http://www.w3.org/2001/XMLSchema#boolean> ;
	fedora:repositoryJcrRepositoryName "ModeShape"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionVersioningSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionQuerySqlSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementMultivaluedPropertiesSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementOverridesSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryLevel1Supported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryJcrSpecificationVersion "2.0"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementUpdateInUseSuported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryQueryFullTextSearchSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionLifecycleSupported "false"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionNodeAndPropertyWithSameNameSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionRetentionSupported "false"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionNodeTypeManagementSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryIdentifierStability "identifier.stability.indefinite.duration"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionSimpleVersioningSupported "false"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryQueryStoredQueriesSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementOrderableChildNodesSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryJcrSpecificationName "Content Repository for Java Technology API"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionUnfiledContentSupported "false"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionBaselinesSupported "false"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementPrimaryItemNameSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryLevel2Supported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionActivitiesSupported "false"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementMultipleBinaryPropertiesSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionTransactionsSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionLockingSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryQueryXpathPosIndex "false"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionUpdateMixinNodeTypesSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryQueryJoins "query.joins.inner.outer"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionUpdatePrimaryNodeTypeSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryJcrRepositoryVersion "4.2.0.Final"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionJournaledObservationSupported "false"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionAccessControlSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementValueConstraintsSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionShareableNodesSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryCustomRepName "repo"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionWorkspaceManagementSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryWriteSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryQueryXpathDocOrder "false"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementAutocreatedDefinitionsSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionObservationSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementResidualDefinitionsSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryJcrRepositoryVendor "JBoss, a division of Red Hat"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionXmlExportSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementPropertyTypes "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementInheritance "node.type.management.inheritance.multiple"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryOptionXmlImportSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryNodeTypeManagementSameNameSiblingsSupported "true"^^<http://www.w3.org/2001/XMLSchema#string> ;
	fedora:repositoryJcrRepositoryVendorUrl "http://www.modeshape.org"^^<http://www.w3.org/2001/XMLSchema#string> ;
	ldp:contains <http://localhost:8080/fcrepo/rest/fedora:system> , <http://localhost:8080/fcrepo/rest/1e/65/62/6e/1e65626e-9790-425e-b89d-56a520cefa9c> , <http://localhost:8080/fcrepo/rest/object1> , <http://localhost:8080/fcrepo/rest/52/b9/72/33/52b97233-c80e-4b47-ad74-9d3d56a9fc15> .

<http://localhost:8080/fcrepo/rest/fcr:export?format=jcr/xml> dc:format <http://fedora.info/definitions/v4/repository#jcr/xml> .

<http://fedora.info/definitions/v4/repository#jcr/xml> rdfs:label "jcr/xml"^^<http://www.w3.org/2001/XMLSchema#string> .

<http://localhost:8080/fcrepo/rest/> fedora:exportsAs <http://localhost:8080/fcrepo/rest/fcr:export?format=jcr/xml> ;
	fedora:hasTransactionProvider <http://localhost:8080/fcrepo/rest/fcr:tx> .
EOF;

    protected $rootHeaders = array(
        'Server' => 'Apache-Coyote/1.1',
        'Link' => '<http://www.w3.org/ns/ldp#Resource>;rel="type"',
        'Link' => '<http://www.w3.org/ns/ldp#Container>;rel="type"',
        'Link' => '<http://www.w3.org/ns/ldp#BasicContainer>;rel="type"',
        'Accept-Patch' => 'application/sparql-update',
        'Accept-Post' => 'text/turtle,text/rdf+n3,text/n3,application/rdf+xml,application/n-triples,multipart/form-data,application/sparql-update',
        'Allow' => 'MOVE,COPY,DELETE,POST,HEAD,GET,PUT,PATCH,OPTIONS',
        'Preference-Applied' => 'return=representation',
        'Vary' => 'Prefer',
        'Vary' => 'Accept, Range, Accept-Encoding, Accept-Language',
        'Content-Type' => 'text/turtle',
    );
    
    protected $commonHeaders = array(
        'Server' => 'Apache-Coyote/1.1',
        'ETag' => "4e98ff87ecceab2aa535f606fef7b7cde38ab8b9",
        'Content-Type' => 'text/plain',
        'Content-Length' => 'changed in setUp',
        'Date' => 'changed in setUp',
        'Last-Modified' => 'changed in setUp',
    );
    
    public function setUp()
    {
        parent::setUp();
        $date = new \DateTime("now", new \DateTimeZone('UTC'));
        CrayfishTest::$theDate = $date->format('r');
        $this->rootHeaders['Content-Length'] = strlen($this->rootRdf);
        $this->rootHeaders['Date'] = CrayfishTest::$theDate;
        
        $this->commonHeaders['Last-Modified'] = CrayfishTest::$theDate;
        $this->commonHeaders['Date'] = CrayfishTest::$theDate;
        
        $this->api = $this->getMockBuilder('\Islandora\Chullo\FedoraApi')
        ->disableOriginalConstructor()
        ->setMethods(array("getResource", "saveResource", "createResource", "modifyResource", "deleteResource"))
        ->getMock();
        
        $this->triplestore = $this->getMockBuilder('\Islandora\Chullo\TriplestoreClient')
        ->disableOriginalConstructor()
        ->setMethods(array('query'))
        ->getMock();
        
        $this->app['api'] = $this->api;
        $this->app['triplestore'] = $this->triplestore;
    }
    
    public function createApplication()
    {
        // must return an Application instance
        return require __DIR__.'/../src/app.php';
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceServiceController::get
     */
    public function testGetRootResource()
    {
        $getResponse = Response::create($this->rootRdf, 200, $this->rootHeaders);

        $this->api->expects($this->once())->method('getResource')->will($this->returnValue($getResponse));
        // Symfony BrowserKit Client
        // @link http://api.symfony.com/2.3/Symfony/Component/BrowserKit/Client.html
        $client = $this->createClient();
        // Symfony DomCrawler Crawler
        // @link http://api.symfony.com/3.0/Symfony/Component/DomCrawler/Crawler.html
        $crawler = $client->request('GET', '/islandora/resource');
        $this->assertEquals($client->getResponse()->getStatusCode(), 200, "Did not get root resource");
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceServiceController::get
     */
    public function testGetResource()
    {
        $headers = $this->commonHeaders;
        $headers['Location'] = 'http://localhost:8080/fcrepo/rest/bobs/burgers';
        $headers['Content-Length'] = strlen($headers['Location']);
        
        $getResponse = Response::create($headers["Location"], 200, $headers);

        $this->api->expects($this->once())->method('getResource')->will($this->returnValue($getResponse));

        $query_result = '{
  "head" : {
    "vars" : [ "s" ]
  },
  "results" : {
    "bindings" : [ {
      "s" : {
        "type" : "uri",
        "value" : "http://localhost:8080/fcrepo/rest/bobs/burgers"
      }
    } ]
  }
}';
        
        $result = new \EasyRdf_Sparql_Result($query_result, 'application/sparql-results+json');
        
        $this->triplestore->expects($this->once())->method('query')->will($this->returnValue($result));
        $client = $this->createClient();
        $crawler = $client->request('GET', '/islandora/resource/f218d271-98ee-4a90-a06a-03420a96d5af');
        $this->assertEquals($client->getResponse()->getStatusCode(), 200, "Did not get resource");
        $this->assertEquals($client->getResponse()->headers->get('location'), $headers['Location'], "Did not get correct resource location");
        
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceServiceController::post
     */
    public function testPostResourceToRoot()
    {
        $headers = $this->commonHeaders;
        $headers['Location'] = 'http://localhost:8080/fcrepo/rest/b5/84/bd/d6/b584bdd6-f98a-4a6a-be36-5702e8e97a79';
        $headers['Content-Length'] = strlen($headers['Location']);
        unset($headers['Last-Modified']);
        
        $postResponse = Response::create($headers['Location'], 201, $headers);
        
        $this->api->expects($this->once())->method('createResource')->will($this->returnValue($postResponse));
       
        $client = $this->createClient();
        $crawler = $client->request("POST", "/islandora/resource");
        $this->assertEquals($client->getResponse()->getStatusCode(), 201, "Did not create new node");     
        $this->assertEquals($client->getResponse()->getContent(), $headers['Location'], "Created URL does not match");
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceServiceController::post
     */
    public function testPostResource()
    {
        $headers = $this->commonHeaders;
        $headers['Location'] = 'http://localhost:8080/fcrepo/rest/test/b5/84/bd/d6/b584bdd6-f98a-4a6a-be36-5702e8e97a79';
        $headers['Content-Length'] = strlen($headers['Location']);
        unset($headers['Last-Modified']);
        
        $postResponse = Response::create($headers['Location'], 201, $headers);
        
        $this->api->expects($this->once())->method('createResource')->will($this->returnValue($postResponse));
        
        $query_result = '{
  "head" : {
    "vars" : [ "s" ]
  },
  "results" : {
    "bindings" : [ {
      "s" : {
        "type" : "uri",
        "value" : "http://localhost:8080/fcrepo/rest/test"
      }
    } ]
  }
}';
        
        $result = new \EasyRdf_Sparql_Result($query_result, 'application/sparql-results+json');
        
        $this->triplestore->expects($this->once())->method('query')->will($this->returnValue($result));
       
        $client = $this->createClient();
        $crawler = $client->request("POST", "/islandora/resource/f218d271-98ee-4a90-a06a-03420a96d5af");
        $this->assertEquals($client->getResponse()->getStatusCode(), 201, "Did not create new node");     
        $this->assertEquals($client->getResponse()->getContent(), $headers['Location'], "Created URL does not match");
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceServiceController::put
     */
    public function testPutResource() {
        
        $headers = $this->commonHeaders;
        $headers['Location'] = 'http://localhost:8080/fcrepo/rest/new/test/object';
        $headers['Content-Length'] = strlen($headers['Location']);
        unset($headers['Last-Modified']);
        
        $putResponse = Response::create($headers['Location'], 201, $headers);
        
        $this->api->expects($this->once())->method('saveResource')->will($this->returnValue($putResponse));
        
        $client = $this->createClient();
        $crawler = $client->request('PUT', '/islandora/resource/');
        $this->assertEquals($client->getResponse()->getStatusCode(), 201, "Did not create a new resource");
        $this->assertEquals($client->getResponse()->headers->get('Location'), $headers['Location'], 'Did not get the correct Location');
    }

    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceServiceController::patch
     */
    public function testPatchResource()
    {
        $patch_content = "prefix dc: <http://purl.org/dc/elements/1.1/> INSERT { <> dc:title 'The title' . }  WHERE {}";
        
        $patch_headers = array(
            'Content-Type' => 'application/sparql-update',
        );
        
        $headers = $this->commonHeaders;
        unset($headers['Content-Length']);
        unset($headers['Content-Type']);
        
        $patchResponse = Response::create('', 204, $headers);
        
        $this->api->expects($this->once())->method('modifyResource')->will($this->returnValue($patchResponse));
        
        $query_result = '{
  "head" : {
    "vars" : [ "s" ]
  },
  "results" : {
    "bindings" : [ {
      "s" : {
        "type" : "uri",
        "value" : "http://localhost:8080/fcrepo/rest/test"
      }
    } ]
  }
}';
        
        $result = new \EasyRdf_Sparql_Result($query_result, 'application/sparql-results+json');
        
        $this->triplestore->expects($this->once())->method('query')->will($this->returnValue($result));
        
        $client = $this->createClient();
        $crawler = $client->request('PATCH', '/islandora/resource/f218d271-98ee-4a90-a06a-03420a96d5af', array(), array(), $patch_headers);
        $this->assertEquals($client->getResponse()->getStatusCode(), 204, "Did not patch resource");
    }
    
    /**
     * @group UnitTest
     * @covers \Islandora\Crayfish\ResourceService\Controller\ResourceServiceController::delete
     */
    public function testDeleteResource()
    {
        $headers = array(
            'Server' => 'Apache-Coyote/1.1',
            'Date' => CrayfishTest::$theDate,
        );

        $deleteResponse = Response::create('', 204, $headers);
        
        $this->api->expects($this->once())->method('deleteResource')->will($this->returnValue($deleteResponse));
        
        $client = $this->createClient();
        $crawler = $client->request('DELETE', '/islandora/resource/');
        $this->assertEquals($client->getResponse()->getStatusCode(), 204, "Did not delete resource");
    }
    
}
