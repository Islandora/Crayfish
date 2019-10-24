# Recast
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

## Introduction

Microservice that remaps Drupal URIs to add Fedora to Fedora links based on associated Drupal URIs in RDF.

## Installation

Recast requires a functioning LDP server to provide RDF (like a [Fedora](http://fedorarepository.org/) repository).
You will also need a working [Gemini](../Gemini) system to use for looking up the URI mappings.

- Clone this repository.
- Install `composer`.  [Install instructions here.][4]
- `$ cd /path/to/Recast` and run `$ composer install`
- Then either
  - For production, configure your web server appropriately (e.g. add a VirtualHost for Recast in Apache) OR
  - For development, run the PHP built-in web server `$ php -S localhost:8888 -t src` from Recast root.

## Configuration

Make a copy of the [config file](cfg/config.example.yaml) and name it `config.yaml` in the `cfg` directory.

You will need to set the `fedora_base_url` entry to point to your Fedora installation.

You will also need to set up the `drupal_base_url` entry to point to your Drupal 8 installation.

You will also need to set up the `gemini_base_url` entry to point to you Gemini instance.

You can also configure namespace prefixes, logging level and file and JWT security.

## Usage

Recast sets up a single endpoint, `/recast/{action}`, which accepts GET requests. The action can be one of:

* add - add Fedora URIs along with the provided Drupal URIs
* replace - replace Drupal URIs with the mapped Fedora URIs

By default the `add` action is assumed if no `action` is provided.

The Recast service looks to the `Apix-Ldp-Resource` header for the resource to map.

The Recast service will provide RDF in the format directed by the `Accept` header sent, or `text/turtle` if 
the `Accept` header is not provided or the system is unable to provide the requested format.

If requests are successful, they return the response from the Fedora server.  If the Drupal entity cannot be requested,
the error response from Drupal is returned.  If there is an exception thrown during execution, a 500 response is returned
with the exception's message.


### GET

This retrieves a representation of the specified Fedora entity with Drupal URIs remapped.

For example, a Drupal node that is a member of a collection would have a properties like:
```
<> <http://pcdm.org/models#memberOf> <some Drupal node>
```

Here is the RDF returned from the Fedora repository
```
> curl -H"Authorization: Bearer islandora" -H"Accept: text/turtle" http://localhost:8080/fcrepo/rest/82/67/81/b2/826781b2-327d-47b2-9de0-18f85ccfa29b

@prefix rdf:  <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
@prefix fedora:  <http://fedora.info/definitions/v4/repository#> .
@prefix ldp:  <http://www.w3.org/ns/ldp#> .
@prefix dcterms:  <http://purl.org/dc/terms/> .

<http://localhost:8080/fcrepo/rest/82/67/81/b2/826781b2-327d-47b2-9de0-18f85ccfa29b>
        rdf:type                    fedora:Container ;
        rdf:type                    fedora:Resource ;
        rdf:type                    <http://pcdm.org/models#Object> ;
        fedora:lastModifiedBy       "bypassAdmin" ;
        <http://schema.org/dateModified>  "2019-03-22T19:23:29+00:00"^^<http://www.w3.org/2001/XMLSchema#dateTime> ;
        <http://schema.org/author>  <http://localhost:8000/user/1?_format=jsonld> ;
        <http://schema.org/sameAs>  "http://localhost:8000/node/2?_format=jsonld" ;
        <http://schema.org/dateCreated>  "2019-03-22T19:23:11+00:00"^^<http://www.w3.org/2001/XMLSchema#dateTime> ;
        dcterms:extent              "1 item" ;
        fedora:createdBy            "bypassAdmin" ;
        fedora:lastModified         "2019-03-22T19:23:30.273Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> ;
        fedora:created              "2019-03-22T19:23:30.273Z"^^<http://www.w3.org/2001/XMLSchema#dateTime> ;
        <http://pcdm.org/models#memberOf>  <http://localhost:8000/node/1?_format=jsonld> ;
        dcterms:title               "A basic image"@en ;
        rdf:type                    ldp:RDFSource ;
        rdf:type                    ldp:Container .
```

In this case the `memberOf` triple is

```
<http://localhost:8080/fcrepo/rest/82/67/81/b2/826781b2-327d-47b2-9de0-18f85ccfa29b> <http://pcdm.org/models#memberOf>  <http://localhost:8000/node/1?_format=jsonld> ;
```

#### Add (default behaviour)

Passed through the Recast service (http://localhost:8000/recast in our example) we get:

```
> curl -H"Authorization: Bearer islandora" -H"Accept: text/turtle" -H"Apix-Ldp-Resource: http://localhost:8080/fcrepo/rest/82/67/81/b2/826781b2-327d-47b2-9de0-18f85ccfa29b" http://localhost:8000/recast/

@prefix fedora: <http://fedora.info/definitions/v4/repository#> .
@prefix pcdm: <http://pcdm.org/models#> .
@prefix ldp: <http://www.w3.org/ns/ldp#> .
@prefix schema: <http://schema.org/> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix dc: <http://purl.org/dc/terms/> .

<http://localhost:8080/fcrepo/rest/82/67/81/b2/826781b2-327d-47b2-9de0-18f85ccfa29b>
  a fedora:Container, fedora:Resource, pcdm:Object, ldp:RDFSource, ldp:Container ;
  fedora:lastModifiedBy "bypassAdmin" ;
  schema:dateModified "2019-03-22T19:23:29+00:00"^^xsd:dateTime ;
  schema:author <http://localhost:8000/user/1?_format=jsonld> ;
  schema:sameAs "http://localhost:8000/node/2?_format=jsonld" ;
  schema:dateCreated "2019-03-22T19:23:11+00:00"^^xsd:dateTime ;
  dc:extent "1 item" ;
  fedora:createdBy "bypassAdmin" ;
  fedora:lastModified "2019-03-22T19:23:30.273Z"^^xsd:dateTime ;
  fedora:created "2019-03-22T19:23:30.273Z"^^xsd:dateTime ;
  pcdm:memberOf <http://localhost:8000/node/1?_format=jsonld>, <http://localhost:8080/fcrepo/rest/c5/57/56/e8/c55756e8-8aac-4460-9699-e7c6efe0a89a> ;
  dc:title "A basic image"@en .
```

You'll notice that in this case the `pcdm:memberOf` has two objects. The original Drupal URI and the Fedora URI that maps to it.

```
<http://localhost:8080/fcrepo/rest/82/67/81/b2/826781b2-327d-47b2-9de0-18f85ccfa29b> pcdm:memberOf <http://localhost:8000/node/1?_format=jsonld>, <http://localhost:8080/fcrepo/rest/c5/57/56/e8/c55756e8-8aac-4460-9699-e7c6efe0a89a> ;
```

You can also explicitly specify the **add** action by using the Recast URI `http://localhost:8000/recast/add`

#### Replace

If you provide the **replace** action, the original Drupal URI will be removed for any URI that can be mapped. 

```
> curl -H"Authorization: Bearer islandora" -H"Accept: text/turtle" -H"Apix-Ldp-Resource: http://localhost:8080/fcrepo/rest/82/67/81/b2/826781b2-327d-47b2-9de0-18f85ccfa29b" http://localhost:8000/recast/replace

@prefix fedora: <http://fedora.info/definitions/v4/repository#> .
@prefix pcdm: <http://pcdm.org/models#> .
@prefix ldp: <http://www.w3.org/ns/ldp#> .
@prefix schema: <http://schema.org/> .
@prefix xsd: <http://www.w3.org/2001/XMLSchema#> .
@prefix dc: <http://purl.org/dc/terms/> .

<http://localhost:8080/fcrepo/rest/82/67/81/b2/826781b2-327d-47b2-9de0-18f85ccfa29b>
  a fedora:Container, fedora:Resource, pcdm:Object, ldp:RDFSource, ldp:Container ;
  fedora:lastModifiedBy "bypassAdmin" ;
  schema:dateModified "2019-03-22T19:23:29+00:00"^^xsd:dateTime ;
  schema:author <http://localhost:8000/user/1?_format=jsonld> ;
  schema:sameAs "http://localhost:8000/node/2?_format=jsonld" ;
  schema:dateCreated "2019-03-22T19:23:11+00:00"^^xsd:dateTime ;
  dc:extent "1 item" ;
  fedora:createdBy "bypassAdmin" ;
  fedora:lastModified "2019-03-22T19:23:30.273Z"^^xsd:dateTime ;
  fedora:created "2019-03-22T19:23:30.273Z"^^xsd:dateTime ;
  pcdm:memberOf <http://localhost:8080/fcrepo/rest/c5/57/56/e8/c55756e8-8aac-4460-9699-e7c6efe0a89a> ;
  dc:title "A basic image"@en .
```

In this example the `pcdm:memberOf` triple only contains the Fedora URI

```
<http://localhost:8080/fcrepo/rest/82/67/81/b2/826781b2-327d-47b2-9de0-18f85ccfa29b> pcdm:memberOf <http://localhost:8080/fcrepo/rest/c5/57/56/e8/c55756e8-8aac-4460-9699-e7c6efe0a89a>
```

However:

1. `schema:author <http://localhost:8000/user/1?_format=jsonld> ;` is not changed because we have no mapping for this URI.
1. `schema:sameAs "http://localhost:8000/node/2?_format=jsonld" ;` is not changed because it is a string and not a URI.

## Maintainers

Current maintainers:

* [Jared Whiklo](https://github.com/whikloj)

[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[4]: https://getcomposer.org/download/
