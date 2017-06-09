# ![image](https://cloud.githubusercontent.com/assets/2371345/24111014/dbc65c56-0d73-11e7-91f0-06af315f78a8.png) Hypercube
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

## Introduction

[Tesseract][9] as a microservice for use with [Api-X][10].

## Installation

- Install `tesseract`.  On Ubuntu, this can be done with `sudo apt-get install tesseract-ocr`.  If you want to install extra languages, they are available as separate packages in Ubuntu.  You can use apt's autocomplete to get a quick list of them.
- Clone this repository somewhere in your web root.
- Install `composer`.  [Install instructions here.][4]
- `$ cd /path/to/Hypercube` and run `$ composer install`
- Then either
  - For production, configure your web server appropriately (e.g. add a VirtualHost for Hypercube in Apache) some documentation (here)[http://silex.sensiolabs.org/doc/2.0/web_servers.html].
  - For development, run the PHP built-in webserver `$ php -S localhost:8888 -t src` from Hypercube root.

### Apache2

To use Hypercube with Apache you need to configure your Virtualhost with a few options:
- Redirect all requests to the Hypercube index.php file
- Make sure Hypercube has access to Authorization headers

Here is an example configuration for Apache 2.4:
```apache
  Alias "/hypercube" "/path/to/Crayfish/Hypercube/src"
  <Directory "/path/to/Crayfish/Hypercube/src">
    FallbackResource /hypercube/index.php
    Require all granted
    DirectoryIndex index.php
    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
  </Directory>
```

This will put the Hypercube at the /hypercube endpoint on the webserver.

## Configuration

If your tesseract installation is not on your path, then you can configure Hypercube to use a specific executable by editing `executable` entry in [config.yaml](./cfg/config.example.yaml).

You also will need to set the `fedora base url` entry to point to your Fedora installation.

In order to work on larger images, be sure `post_max_size` is sufficiently large and `max_execution_time` is set to 0 in your PHP installation's ini file.  You can determine which ini file is getting used by running the command `$ php --ini`.

## Usage

Hypercube is meant for use with Api-X.  It accepts only accepts one request, a `GET` with the URI of a Fedora resource in the `ApixLdpResource` header..

For example, suppose if you have a TIFF in Fedora at `http://localhost:8080/fcrepo/rest/foo/bar`.  If running the PHP built-in server command described in the Installation section:
```
$ curl -H "Authorization: Bearer blabhlahblah" -H "ApixLdpResource: http://localhost:8080/fcrepo/rest/foo/bar" "http://localhost:8888"
```

This will return the OCR generated from the TIFF in Fedora.  Additional arguments to `tesseract` can be provided using the `X-Islandora-Args` header.  For example, to change the page layout:
```
$ curl -H "Authorization: Bearer blabhlahblah" -H "ApixLdpResource: http://localhost:8080/fcrepo/rest/foo/bar" -H "X-Islandora-Args: -psm 9" "http://localhost:8888"
```

But you're probably going to use Hypercube through Api-X, which exposes it as `svc:ocr`.  Assuming your Api-X proxy is on port 8081, you can access the service with
```
$ curl -H "Authorization: Bearer blabhlahblah" "http://localhost:8081/services/foo/bar/svc:ocr"
```

## Maintainers

Current maintainers:

* [Daniel Lamb](https://github.com/dannylamb)

## License

[MIT](https://opensource.org/licenses/MIT)

[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[4]: https://getcomposer.org/download/
[9]: https://github.com/tesseract-ocr
[10]: https://github.com/fcrepo4-labs/fcrepo-api-x 
