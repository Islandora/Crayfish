# ![image](https://cloud.githubusercontent.com/assets/2371345/24111014/dbc65c56-0d73-11e7-91f0-06af315f78a8.png) Hypercube
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

## Introduction

[Tesseract][9] as a microservice for use with [API-X][10].

## Installation

- Install `tesseract`.  On Ubuntu, this can be done with `sudo apt-get install tesseract-ocr`.  If you want to install extra languages, they are available as separate packages in Ubuntu.  You can use apt's autocomplete to get a quick list of them.
- Install `pdftotext`.
- Clone this repository somewhere in your web root.
- Install `composer`.  [Install instructions here.][4]
- `$ cd /path/to/Hypercube` and run `$ composer install`
- For production, configure your web server appropriately (e.g. add a VirtualHost for Hypercube in Apache).

### Apache2

To use Hypercube with Apache you need to configure your Virtualhost with a few options:
- Redirect all requests to the Hypercube index.php file
- Make sure Hypercube has access to Authorization headers

Here is an example configuration for Apache 2.4:
```apache
  Alias "/hypercube" "/path/to/Crayfish/Hypercube/public"
  <Directory "/path/to/Crayfish/Hypercube/public">
    FallbackResource /hypercube/index.php
    Require all granted
    DirectoryIndex index.php
    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
  </Directory>
```

This will put the Hypercube at the /hypercube endpoint on the web server.

## Configuration

Symfony uses `.dotenv` to set environment variables. You can check the [.env](./.env) in the root of the Homarus directory.
To alter any settings, create a file called `.env.local` to store your specific changes. You can also set an actual environment
variable.

For production use make sure to set the add `APP_ENV=prod` environment variable.

If your `tesseract` installation is not on your path, then you can configure Hypercube to use a specific executable by editing
the `app.tesseract_executable` parameter in [`/path/to/Hypercube/config/services.yaml`](./config/services.yaml).

If your `pdftotext` installation is not on your path, then you can configure Hypercube to use a specific executable by editing
the `app.pdftotext_executable` parameter in [`/path/to/Hypercube/config/services.yaml`](./config/services.yaml).

You also need to set your Fedora Base Url to allow the Fedora Resource to be pulled in automatically. This is done in the
`/path/to/Hypercube/config/packages/crayfish_commons.yaml`. In the same file you can point to the location of your `syn-settings.xml`.
If you don't have a `syn-settings.xml` look at the [Syn](http://github.com/Islandora/Syn) documentation.

In order to work on larger images, be sure `post_max_size` is sufficiently large and `max_execution_time` is set to 0 in your PHP
installation's ini file.  You can determine which ini file is getting used by running the command `$ php --ini`.

### Logging

To change your log settings, edit the `/path/to/Hypercube/config/packages/monolog.yaml` file.

You can also copy the file into one of the `/path/to/Hypercube/config/packages/<environment>` directories.
Where `<environment>` is `dev`, `test`, or `prod` based on the `APP_ENV` variable (see above). The files in the specific
environment directory will take precedence over those in the `/path/to/Hypercube/config/packages` directory.

The location specified in the configuration file for the log must be writable by the web server.

### Disabling Syn

There are instructions in the `/path/to/Hypercube/config/packages/security.yaml` file describing what to change and what lines
to comment out to disable Syn.

## Usage

Hypercube is meant for use with API-X.  It accepts only accepts one request, a `GET` with the URI of a Fedora resource in the `Apix-Ldp-Resource` header..

For example, suppose if you have a TIFF in Fedora at `http://localhost:8080/fcrepo/rest/foo/bar`.  If running the PHP built-in server command described in the Installation section:
```
$ curl -H "Authorization: Bearer blabhlahblah" -H "Apix-Ldp-Resource: http://localhost:8080/fcrepo/rest/foo/bar" "http://localhost:8888"
```

This will return the OCR generated from the TIFF in Fedora.  Additional arguments to `tesseract` can be provided using the `X-Islandora-Args` header.  For example, to change the page layout:
```
$ curl -H "Authorization: Bearer blabhlahblah" -H "Apix-Ldp-Resource: http://localhost:8080/fcrepo/rest/foo/bar" -H "X-Islandora-Args: -psm 9" "http://localhost:8888"
```

But you're probably going to use Hypercube through API-X, which exposes it as `svc:ocr`.  Assuming your API-X proxy is on port 8081, you can access the service with
```
$ curl -H "Authorization: Bearer blabhlahblah" "http://localhost:8081/services/foo/bar/svc:ocr"
```

## Maintainers

Current maintainers:

* [Daniel Lamb](https://github.com/dannylamb)

## Development

If you would like to contribute, please get involved by attending our weekly [Tech Call](https://github.com/Islandora/docuentation/wiki). We love to hear from you!

If you would like to contribute code to the project, you need to be covered by an Islandora Foundation [Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_cla.pdf) or [Corporate Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_ccla.pdf). Please see the [Contributors](http://islandora.ca/resources/contributors) pages on Islandora.ca for more information.

## License

[MIT](https://opensource.org/licenses/MIT)

[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[4]: https://getcomposer.org/download/
[9]: https://github.com/tesseract-ocr
[10]: https://github.com/fcrepo4-labs/fcrepo-api-x
