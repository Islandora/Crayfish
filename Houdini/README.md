# ![Houdini](https://cloud.githubusercontent.com/assets/2371345/24676060/e247a284-1957-11e7-95a3-f4c419b3ef20.png) Houdini

## Introduction

[ImageMagick][9] as a microservice.

## Installation

- Install `imagemagick`.  On Ubuntu, this can be done with `sudo apt-get install imagemagick`. If you want extra image delegates (for example JPEG2000) you may have to compile from source.
- Clone this repository somewhere in your web root.
- Install `composer`.  [Install instructions here.][4]
- `$ cd /path/to/Houdini` and run `$ composer install`
- Then either
  - For production, configure your web server appropriately (e.g. add a VirtualHost for Houdini in Apache) OR
  - For development, run the PHP built-in web server `$ php -S localhost:8888 -t src` from Houdini root.

## Configuration

If your imagemagick installation is not on your path, then you can configure Houdini to use a specific executable by editing `executable` entry in [config.yaml](./cfg/config.example.yaml).

You also will need to set the `fedora base url` entry to point to your Fedora installation.

In order to work on larger images, be sure `post_max_size` is sufficiently large and `max_execution_time` is set to 0 in your PHP installation's ini file.  You can determine which ini file is getting used by running the command `$ php --ini`.

The location specified in the Houdini configuration file for the log must be writable by the web server.

## Usage

Houdini sets up two endpoints:
 - /identify/
 - /convert/

Houdini is meant for use with API-X, and accepts `GET` and `OPTIONS` requests to those endpoints.  The `OPTIONS` requests are for use with the API-X service loading mechanism, and return RDF describing the
service for API-X.  The `GET` requests are used to execute the services, and must contain the URI to an image in Fedora in the `ApixLdpResource` header.

### Identify

This runs the imagemagick identify command on the specified resource and returns the results as JSON with the MIME type application/json.

For example, suppose if you have a TIFF in Fedora at `http://localhost:8080/fcrepo/rest/foo/bar`.  If running the PHP built-in server command described in the Installation section:
```
$ curl -H "Authorization: Bearer blabhlahblah" -H "Apix-Ldp-Resource: http://localhost:8080/fcrepo/rest/foo/bar" "localhost:8888/identify"
```

But you're probably going to use Houdini through API-X, which exposes this service as `svc:identify`.  Assuming your API-X proxy is on port 8081, you can access the service with
```
$ curl -H "Authorization: Bearer blabhlahblah" "http://localhost:8081/services/foo/bar/svc:identify"
```

### Convert

This runs the imagemagick convert command on the specified resource. The output format is decided by the Accept header sent along with the request. A default output format can also be selected in the configuration.

For example, suppose if you have an image in Fedora at `http://localhost:8080/fcrepo/rest/foo/bar`.  If running the PHP built-in server command described in the Installation section:
```
$ curl -H "Authorization: Bearer blabhlahblah" -H "Apix-Ldp-Resource: http://localhost:8080/fcrepo/rest/foo/bar" -H "Accept: image/png" "localhost:8888/convert/foo/bar"
```

This will return the TIFF converted into a PNG file.

Additional arguments can be specified using the X-Islandora-Args header. For example to resize to 10% the size use:
```
$ curl -H "Authorization: Bearer blabhlahblah" -H "Apix-Ldp-Resource: http://localhost:8080/fcrepo/rest/foo/bar" -H "X-Islandora-Args: -resize 10%" "localhost:8888/convert/foo/bar"
```

But you're probably going to use Houdini through API-X, which exposes this service as `svc:convert`.  Assuming your API-X proxy is on port 8081, you can access the service with
```
$ curl -H "Authorization: Bearer blabhlahblah" "http://localhost:8081/services/foo/bar/svc:convert"
```

## Maintainers

Current maintainers:

* [Jonathan Green](https://github.com/jonathangreen)

[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[4]: https://getcomposer.org/download/
[9]: https://www.imagemagick.org/script/index.php
