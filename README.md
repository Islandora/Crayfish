# ![Crayfish](https://cloud.githubusercontent.com/assets/2371345/15409657/2dfb463a-1dec-11e6-9089-06df94ef3f37.png) Crayfish

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.4.1-8892BF.svg?style=flat-square)](https://php.net/)
[![Build Status](https://github.com/islandora/crayfish/actions/workflows/build-4.x.yml/badge.svg?branch=5.x)](https://github.com/Islandora/Crayfish/actions)
[![Contribution Guidelines](http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg)](./CONTRIBUTING.md)
[![LICENSE](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](./LICENSE)

## Introduction

Crayfish houses Milliner, the Islandora microservice that writes Drupal content to Fedora.

## Requirements

The supported Milliner application requires:

* PHP 8.4.1+
* Composer 2

## Services

Crayfish contains the following supported service:

* [Milliner](./Milliner): Microservice that converts Drupal entities into Fedora resources.

CrayFits, Homarus, Houdini, Hypercube, and Recast are deprecated. They are not installed or tested by the PHP 8.4 CI workflow.

## Security

Milliner can validate Islandora JWTs with LexikJWTAuthenticationBundle. Authentication is disabled by default; see
[`Milliner/config/packages/security.yaml`](./Milliner/config/packages/security.yaml) for the settings to enable it.

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
[10]: https://github.com/Islandora-Devops/islandora-playbook
