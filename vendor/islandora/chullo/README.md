# ![Chullo](https://cloud.githubusercontent.com/assets/2371345/15409650/21fd66a6-1dec-11e6-9fb3-4a1554a0fb3d.png) Chullo

Chullo is a PHP client for [Fedora](http://fedorarepository.org/) built using [Guzzle](http://guzzlephp.org) and [EasyRdf](http://www.easyrdf.org/).

[![Latest Stable Version](https://img.shields.io/packagist/v/Islandora/chullo.svg?style=flat-square)](https://packagist.org/packages/islandora/chullo)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.5-8892BF.svg?style=flat-square)](https://php.net/)
[![Downloads](https://img.shields.io/packagist/dt/islandora/chullo.svg?style=flat-square)](https://packagist.org/packages/islandora/chullo)
[![Build Status](https://travis-ci.org/Islandora-CLAW/chullo.svg?branch=master)](https://travis-ci.org/Islandora-CLAW/chullo)
[![Contribution Guidelines](http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg)](./CONTRIBUTING.md)
[![LICENSE](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](./LICENSE)
[![codecov](https://codecov.io/gh/Islandora-CLAW/chullo/branch/master/graph/badge.svg)](https://codecov.io/gh/Islandora-CLAW/chullo)

## Requirements

* PHP 5.6+
* [Composer](https://getcomposer.org/)

## Installation

1. `git clone git@github.com:Islandora-CLAW/chullo.git`
2. `cd chullo`
3. `php composer.phar install`

You can also install with composer by pointing to your local clone. Just add these relevant bits to your `composer.json`:

```
{
    "repositories": [
        {
            "type": "vcs",
            "url": "/path/to/chullo"
        }
    ],
    "require": {
        "islandora/chullo": "dev-master"
    }
}
```

Then just `php composer.phar install` as usual.

## Usage

### Fedora
```php
use Islandora\Chullo\Chullo;

// Instantiated with static factory
$chullo = Chullo::create('http://localhost:8080/fcrepo/rest');

// Create a new resource
$uri = $chullo->createResource(); // http://localhost:8080/fcrepo/rest/0b/0b/6c/68/0b0b6c68-30d8-410c-8a0e-154d0fd4ca20

// Parse resource as an EasyRdf Graph
$graph = $chullo->getGraph($uri);

// Set the resource's title
$graph->set($uri, 'dc:title', 'My Sweet Title');

// Save the graph to Fedora
$chullo->saveGraph($uri, $graph);

```

### Triplestore

```php
use Islandora\Chullo\TriplestoreClient;

$triplestore = TriplestoreClient::create('http://127.0.0.1:8080/bigdata/namespace/kb/sparql/');

$sparql = <<<EOD
    PREFIX fedora: <http://fedora.info/definitions/v4/repository#>

    SELECT ?s
    WHERE {
        ?s fedora:hasParent <http://localhost:8080/fcrepo/rest/> .
    }
    LIMIT 25
EOD;

$results = $triplestore->query($sparql);

foreach ($results as $triple) {
    echo $triple->s . "\n";
}
```

## Maintainers/Sponsors

Current maintainers:

* [Daniel Lamb](https://github.com/dannylamb)
* [Nick Ruest](https://github.com/ruebot)

## Development

If you would like to contribute to this module, please check out [CONTRIBUTING.md](CONTRIBUTING.md).
