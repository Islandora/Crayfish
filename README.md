# ![Crayfish](https://cloud.githubusercontent.com/assets/2371345/15409657/2dfb463a-1dec-11e6-9089-06df94ef3f37.png) Crayfish

[![Latest Stable Version](https://img.shields.io/packagist/v/Islandora/crayfish.svg?style=flat-square)](https://packagist.org/packages/islandora/crayfish)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.5-8892BF.svg?style=flat-square)](https://php.net/)
[![Downloads](https://img.shields.io/packagist/dt/islandora/crayfish.svg?style=flat-square)](https://packagist.org/packages/islandora/crayfish)
[![Build Status](https://travis-ci.org/Islandora-CLAW/Crayfish.svg?branch=master)](https://travis-ci.org/Islandora-CLAW/Crayfish)

This is a top level container for the various Islandora CLAW microservices, lovingly known as Crayfish. It allows you to mount the various endpoints at one port on one machine and makes a development vagrant/docker configuration easier to produce.

PCDM specific services are available with [PDX](https://github.com/Islandora-CLAW/PDX).

## Requirements

* PHP 5.5+
* [Composer](https://getcomposer.org/)
* [Chullo](https://github.com/Islandora-CLAW/chullo)
* [Fedora 4](https://github.com/fcrepo4/fcrepo4)
* A triplestore (i.e. [BlazeGraph](https://www.blazegraph.com/download/), [Fuseki](https://jena.apache.org/documentation/fuseki2/), etc)

## Installation

You will need to copy the configuration file [_example.settings.yml_](config/example.settings.yml) to either **settings.yml** or **settings.dev.yml** (if $app['debug'] = TRUE) and change any required settings.

You can run just this service using PHP by executing 

```
php -S localhost:<some port> -t src/ src/index.php
```
from this directory to start it running.

## Services

This mounts all the various individual microservices under the `/islandora` URL, so you currently have access to 

* ResourceService at `/islandora/resource`
* TransactionService at `/islandora/transaction`

See the individual services for more information on their endpoints.

### ResourceService

This an Islandora PHP Microservice to perform some middleware functions such as

1. UUID -> Fedora4 path translation
2. UUID validation
3. Host header normalization

and pass the request to Chullo.

#### Services

The ResourceService provides the following endpoints for HTTP requests. 

**Note**: The UUID is of the form `18c67794-366c-a6d9-af13-b3464a1fb9b5`

1. GET from `/resource/{uuid}/{child}`

    for getting the Fedora Resource from either {uuid} (if {child} is left off), or {child} if both are provided.
    

1. POST to `/resource`

    for creating a new Resource at the root level

2. POST to `/resource/{uuid}`

    for creating a new Resource as a child of resource {uuid}

3. PUT to `/resource/{uuid}/{child}`

    for creating a new Resource with a predefined name {child} under the parent {uuid}, to PUT at root leave the {uuid} blank (ie. //).

1. PATCH to `/resource/{uuid}/{child}`

    for patching a resource at either {uuid} (if {child} is left off), or {child} if both are provided.
    
2. DELETE to `/resource/{uuid}/{child}`

    for deleting a resource at either {uuid} (if {child} is left off), or {child} if both are provided.

### TransactionService

This an Islandora PHP Microservice to create/extend/commit or rollback Fedora 4 transactions

#### Services

The TransactionService provides the following endpoints for HTTP requests. 

**Note**: The transaction ID (or txID) is of the form `tx:83e34464-144e-43d9-af13-b3464a1fb9b5`

1. POST to `/transaction`

    for creating a new transaction. It returns the transaction ID in the Location: header. It can be retrieved by passing the Response to the `getId()` function.
    
2. POST to `/transaction/{txID}/extend`

    for extending a transaction. Normally a transaction will expire once it has sat for approximately 3 minutes without any interactions. This allows you to extend the transaction without performing any other interaction.
    
3. POST to `/transaction/{txID}/commit`

    to commit the transaction.
    
4. POST to `/transaction/{txID}/rollback`

    to rollback a transaction

## Sponsors

* UPEI
* discoverygarden inc.
* LYRASIS
* McMaster University
* University of Limerick
* York University
* University of Manitoba
* Simon Fraser University
* PALS
* American Philosophical Society
* common media inc.

## Maintainers

* [Jared Whiklo](https://github.com/whikloj)
* [Diego Pino](https://github.com/diegopino)
* [Nick Ruest](https://github.com/ruebot)

## License

[MIT](https://opensource.org/licenses/MIT)
