# ![Crayfish](https://cloud.githubusercontent.com/assets/2371345/15409657/2dfb463a-1dec-11e6-9089-06df94ef3f37.png) Crayfish

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg?style=flat-square)](https://php.net/)
[![Build Status](https://travis-ci.com/Islandora-CLAW/Crayfish.svg?branch=master)](https://travis-ci.com/Islandora-CLAW/Crayfish)
[![Contribution Guidelines](http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg)](./CONTRIBUTING.md)
[![LICENSE](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](./LICENSE)
[![codecov](https://codecov.io/gh/Islandora-CLAW/Crayfish/branch/master/graph/badge.svg)](https://codecov.io/gh/Islandora-CLAW/Crayfish)

A collection of Islandora CLAW microservices, lovingly known as Crayfish.  Some of the microservices are built specifically for use with a Fedora Repository and API-X, while others are just for general use within CLAW.

## Requirements

The minimum requirements for any microservice are

* PHP 5.6+
* [Composer](https://getcomposer.org/)

Many microservices have extra installation requirements.  Please see the README of each microservice for additional details.

## Services

Crayfish contains the following services

* [Hypercube](./Hypercube): Tesseract as a microservice
* [Gemini](./Gemini): A path mapping micro service to align resources in Drupal and Fedora.
* [Houdini](./Houdini): Imagemagick as a microservice

See the individual services for more information on their endpoints.

## Security

Crayfish microservices use JWTs to handle authentication like the rest of the Islandora CLAW.
It is disabled by default. To enable, set `security enabled` to `true` in `cfg/cfg.php` for any microservice.
You can also set the path to an xml configuration file for security a la [Syn][9] with the `security config` parameter.

## Development

If you would like to contribute, please get involved by attending our weekly 
[Tech Call][5]. We love to hear from you!

If you would like to contribute code to the project, you need to be covered by 
an Islandora Foundation [Contributor License Agreement][6] or 
[Corporate Contributor License Agreement][7]. Please see the 
[Contributors][8] pages on Islandora.ca for more information.

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

[5]: https://github.com/Islandora-CLAW/CLAW/wiki
[6]: http://islandora.ca/sites/default/files/islandora_cla.pdf
[7]: http://islandora.ca/sites/default/files/islandora_ccla.pdf
[8]: http://islandora.ca/resources/contributors
[9]: https://github.com/Islandora-CLAW/Syn/blob/master/conf/syn-settings.example.xml
