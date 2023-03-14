# ![Milliner](https://cloud.githubusercontent.com/assets/2371345/25281066/8eca01c8-2682-11e7-987f-c77df38cd733.png) Milliner

## Introduction

Microservice that converts Drupal entities into Fedora resources.

## Installation

Milliner requires a functioning [Drupal](https://www.drupal.org/) 8 or 9
installation with the [jsonld](http://github.com/Islandora/jsonld) module enabled.

It also requires a functioning [Fedora](http://fedorarepository.org/) repository.

- Clone this repository somewhere in your web root.
- Install `composer`.  [Install instructions here.][4]
- `$ cd /path/to/Milliner` and run `$ composer install`
- For production, configure your web server appropriately (e.g. add a VirtualHost for Milliner in Apache)

## Upgrading

Steps for upgrading Milliner can be found in [UPGRADE.md](UPGRADE.md)

## Configuration

Symfony uses `.dotenv` to set environment variables. You can check the [.env](./.env) in the root of the Milliner directory.
To alter any settings, create a file called `.env.local` to store your specific changes. You can also set an actual environment
variable.

For production use make sure to set the add `APP_ENV=prod` environment variable.

There are various configurable parameters located in the [`/path/to/Milliner/config/services.yaml`](./config/services.yaml).
```
parameters:
    app.fedora_base_url: "http://localhost:8080/fcrepo/rest"
    app.modified_date_predicate: "http://schema.org/dateModified"
    app.strip_format_jsonld: true
    app.isFedora6: true
```

`app.fedora_base_url` defines the base URL of your Fedora repository.

`app.modified_date_predicate` defines the predicate which will be compared to determine which of the Fedora or Drupal
resource has been updated more recently.

`app.strip_format_jsonld` determines whether to remove the `?_format=jsonld` from subject URIs before pushing the RDF to Fedora.

`app.isFedora6` determines whether the Fedora instance is version 5.*.* or 6.*.*.

You do NOT need to edit the `fedora_base_url` inside `/path/to/Milliner/config/packages/crayfish_commons.yaml` as this
re-uses the above setting.

### Logging

To change your log settings, edit the `/path/to/Milliner/config/packages/monolog.yaml` file.

You can also copy the file into one of the `/path/to/Milliner/config/packages/<environment>` directories.
Where `<environment>` is `dev`, `test`, or `prod` based on the `APP_ENV` variable (see above). The files in the specific
environment directory will take precedence over those in the `/path/to/Milliner/config/packages` directory.

The location specified in the configuration file for the log must be writable by the web server.

### Enabling JWT authentication

There are instructions in the `/path/to/Milliner/config/packages/security.yaml` file describing what to change and what lines
to comment out to enable authentication.

We use the Lexik JWT Authentication Bundle for Symfony, more information here
https://github.com/lexik/LexikJWTAuthenticationBundle

## Usage

Milliner sets up a multiple endpoints,
* `/node/{uuid}` which accepts POST and DELETE requests.
  * POST is to save a Drupal resource to Fedora.
  * DELETE is to delete a Fedora resource.
* `/node/{uuid}/version` which accepts POST requests.
  * POST creates a new version in Fedora for the resource
* `/media/{source_field}` which accepts POST requests.
  * POST creates a new media in Fedora for the resource identifed in the `Content-Location` header.
* `/media/{source_field}/version` which accepts POST requests.
  * POST creates a new version of the media in Fedora for the resource identifed in the `Content-Location` header.
* `/external/{uuid}` which accepts POST requests.
  * POST creates a new external content resource for the Drupal resource

UUID is transformed into a Fedora URI using the [Crayfish-Commons](https://github.com/Islandora/Crayfish-Commons) EntityMapper.

## Maintainers

Current maintainers:

* [Daniel Lamb](https://github.com/dannylamb)

## Development

If you would like to contribute, please get involved by attending our weekly [Tech Call](https://github.com/Islandora/docuentation/wiki). We love to hear from you!

If you would like to contribute code to the project, you need to be covered by an Islandora Foundation [Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_cla.pdf) or [Corporate Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_ccla.pdf). Please see the [Contributors](http://islandora.ca/resources/contributors) pages on Islandora.ca for more information.

## License

[MIT](https://opensource.org/licenses/MIT)

[3]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[4]: https://getcomposer.org/download/
