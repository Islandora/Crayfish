# ![Crayfish](https://cloud.githubusercontent.com/assets/2371345/15409657/2dfb463a-1dec-11e6-9089-06df94ef3f37.png) Crayfish

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3-8892BF.svg?style=flat-square)](https://php.net/)
[![Build Status](https://github.com/islandora/crayfish/actions/workflows/build-dev.yml/badge.svg)](https://github.com/Islandora/Crayfish/actions)
[![Contribution Guidelines](http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg)](./CONTRIBUTING.md)
[![LICENSE](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](./LICENSE)
[![codecov](https://codecov.io/gh/Islandora/Crayfish/branch/dev/graphs/badge.svg?branch=dev)](https://codecov.io/gh/Islandora/Crayfish)

## Introduction

A collection of Islandora 8 microservices, lovingly known as Crayfish.  Some of the microservices are built specifically for use with a Fedora Repository and API-X, while others are just for general use within Islandora 8.

## Requirements

The minimum requirements for any microservice are

* PHP 7.3+
* [Composer](https://getcomposer.org/)

Many microservices have extra installation requirements.  Please see the README of each microservice for additional details.

## Services

Crayfish contains the following services

* [Gemini](./Gemini): A path mapping micro service to align resources in Drupal and Fedora.
* [Homarus](./Homarus): FFmpeg as a microservice.
* [Houdini](./Houdini): ImageMagick as a microservice.
* [Hypercube](./Hypercube): Tesseract as a microservice.
* [Milliner](./Milliner): Microservice that converts Drupal entities into Fedora resources.
* [Recast](./Recast): Microservice that remaps Drupal URIs to add Fedora to Fedora links based on associated Drupal URIs in RDF.

See the individual services for more information on their endpoints.

## Security

Crayfish microservices use JWTs to handle authentication like the rest of the Islandora 8.
It is disabled by default. To enable, set `security enabled` to `true` in `cfg/cfg.php` for any microservice.
You can also set the path to an xml configuration file for security a la [Syn][9] with the `security config` parameter.

## Development

If you would like to contribute, please get involved by attending our weekly 
[Tech Call][5]. We love to hear from you!

If you would like to contribute code to the project, you need to be covered by 
an Islandora Foundation [Contributor License Agreement][6] or 
[Corporate Contributor License Agreement][7]. Please see the 
[Contributors][8] pages on Islandora.ca for more information.

We recommend using the [islandora-playbook][10] to get started. If you want to pull down the submodules for development, don't forget to run `git submodule update --init --recursive` after cloning.


## Maintainers

* [Jonathan Green](https://github.com/jonathangreen)

This project has been sponsored by:

* American Philosophical Society
* Born-Digital
* discoverygarden inc.
* LYRASIS
* McMaster University
* PALS
* University of Limerick
* University of Manitoba
* UPEI
* Simon Fraser University
* York University


## License

[MIT](https://opensource.org/licenses/MIT)

[5]: https://github.com/Islandora/documentation/wiki
[6]: http://islandora.ca/sites/default/files/islandora_cla.pdf
[7]: http://islandora.ca/sites/default/files/islandora_ccla.pdf
[8]: http://islandora.ca/resources/contributors
[9]: https://github.com/Islandora/Syn/blob/main/conf/syn-settings.example.xml
[10]: https://github.com/Islandora-Devops/islandora-playbook
