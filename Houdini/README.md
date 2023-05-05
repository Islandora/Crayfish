# ![Houdini](https://cloud.githubusercontent.com/assets/2371345/24676060/e247a284-1957-11e7-95a3-f4c419b3ef20.png) Houdini

## Introduction

[ImageMagick][9] as a microservice.

## Installation

- Install `imagemagick`.  On Ubuntu, this can be done with `sudo apt-get install imagemagick`. If you want extra image delegates (for example JPEG2000) you may have to compile from source.
- Clone this repository somewhere in your web root.
- Install `composer`.  [Install instructions here.][4]
- `$ cd /path/to/Houdini` and run `$ composer install`
- For production, configure your web server appropriately (e.g. add a VirtualHost for Houdini in Apache)

## Upgrading

Steps for upgrading Houdini can be found in [UPGRADE.md](UPGRADE.md)

## Configuration

Symfony uses `.dotenv` to set environment variables. You can check the [.env](./.env) in the root of the Houdini directory.
To alter any settings, create a file called `.env.local` to store your specific changes. You can also set an actual environment
variable.

For production use make sure to set the add `APP_ENV=prod` environment variable.

If your `imagemagick` installation is not on your path, then you can configure Houdini to use a specific executable by editing
the `app.executable` parameter in [`/path/to/Houdini/config/services.yaml`](./config/services.yaml).

You also need to set your Fedora Base Url to allow the Fedora Resource to be pulled in automatically. This is done in the
`/path/to/Houdini/config/packages/crayfish_commons.yaml`.

### Logging

To change your log settings, edit the `/path/to/Houdini/config/packages/monolog.yaml` file.

You can also copy the file into one of the `/path/to/Houdini/config/packages/<environment>` directories.
Where `<environment>` is `dev`, `test`, or `prod` based on the `APP_ENV` variable (see above). The files in the specific
environment directory will take precedence over those in the `/path/to/Houdini/config/packages` directory.

The location specified in the configuration file for the log must be writable by the web server.

### Enabling JWT authentication

There are instructions in the `/path/to/Houdini/config/packages/security.yaml` file describing what to change and what lines
to comment out to enable authentication.

We use the Lexik JWT Authentication Bundle for Symfony, more information here
https://github.com/lexik/LexikJWTAuthenticationBundle

## Usage

Houdini sets up two endpoints:
 - /identify/
 - /convert/

Houdini is meant for use with API-X, and accepts `GET` and `OPTIONS` requests to those endpoints.  The `OPTIONS` requests are for use with the API-X service loading mechanism, and return RDF describing the
service for API-X.  The `GET` requests are used to execute the services, and must contain the URI to an image in Fedora in the `Apix-Ldp-Resource` header.

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

## Development

If you would like to contribute, please get involved by attending our weekly [Tech Call](https://github.com/Islandora/docuentation/wiki). We love to hear from you!

If you would like to contribute code to the project, you need to be covered by an Islandora Foundation [Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_cla.pdf) or [Corporate Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_ccla.pdf). Please see the [Contributors](http://islandora.ca/resources/contributors) pages on Islandora.ca for more information.

## License

[MIT](https://opensource.org/licenses/MIT)

[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[4]: https://getcomposer.org/download/
[9]: https://www.imagemagick.org/script/index.php
