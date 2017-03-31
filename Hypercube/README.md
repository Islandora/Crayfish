# ![image](https://cloud.githubusercontent.com/assets/2371345/24111014/dbc65c56-0d73-11e7-91f0-06af315f78a8.png) Hypercube
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

## Introduction

[Tesseract][9] as a microservice.

## Installation

- Install `tesseract`.  On Ubuntu, this can be done with `sudo apt-get install tesseract-ocr`.  If you want to install extra languages, they are available as separate packages in Ubuntu.  You can use apt's autocomplete to get a quick list of them.
- Clone this repository somewhere in your web root.
- Install `composer`.  [Install instructions here.][4]
- `$ cd /path/to/Hypercube` and run `$ composer install`
- Then either
  - For production, configure your web server appropriately (e.g. add a VirtualHost for Hypercube in Apache) OR
  - For development, run the PHP built-in webserver `$ php -S localhost:8888 -t src` from Hypercube root.

## Configuration

If your tesseract installation is on your PATH, no configuration of the application is required.  If it is not, then you can configure Hypercube to use a specific executable by editing `executable` entry in [cfg.php](./cfg/cfg.php).

In order to work on larger images, be sure `post_max_size` is sufficiently large and `max_execution_time` is set to 0 in your PHP installation's ini file.  You can determine which ini file is getting used by running the command `$ php --ini`.

## Usage

Hypercube only accepts one request, a `POST` containing a TIFF image.

For example, if running the PHP built-in server command described in the Installation section:
```
$ curl -X POST -H "Content-Type: image/tiff" --data-binary @ocr-sample.tiff "localhost:8888/"
```

Additional arguments to `tesseract` can be provided using the `X-Islandora-Args` header.  For example, to change the page layout:
```
$ curl -X POST -H "Content-Type: image/tiff" -H "X-Islandora-Args: -psm 9" --data-binary @ocr-sample.tiff "localhost:8888/"
```

## Maintainers

Current maintainers:

* [Daniel Lamb](https://github.com/dannylamb)

## License

[MIT](http://www.gnu.org/licenses/gpl-2.0.txt)

[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[4]: https://getcomposer.org/download/
[9]: https://github.com/tesseract-ocr
