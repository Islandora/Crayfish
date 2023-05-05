![homarus](https://user-images.githubusercontent.com/2371345/48797524-c8c14300-ecd8-11e8-907d-9628fb6afacc.png)
# Homarus

## Introduction

[FFmpeg](https://www.ffmpeg.org/) as a microservice.

## Installation
- Install `ffmpeg`.  On Ubuntu, this can be done with `sudo apt-get install ffmpeg`.
- Clone this repository somewhere in your web root (example: `/var/www/html/Crayfish/Homarus`).
- Install `composer`.  [Install instructions here.][4]
- `$ cd /path/to/Homarus` and run `$ composer install`
- Configure your web server appropriately (e.g. add a VirtualHost for Homarus in Apache)

### Apache2

To use Homarus with Apache you need to configure your Virtualhost with a few options:
- Redirect all requests to the Homarus index.php file
- Make sure Homarus has access to Authorization headers

Here is an example configuration for Apache 2.4:
```apache
Alias "/homarus" "/var/www/html/Crayfish/Homarus/public"
<Directory "/var/www/html/Crayfish/Homarus/public">
  FallbackResource /homarus/index.php
  Require all granted
  DirectoryIndex index.php
  SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
</Directory>
```

This will put the Homarus at the /homarus endpoint on the web server.

## Upgrading

Steps for upgrading Homarus can be found in [UPGRADE.md](UPGRADE.md)

## Configuration

Symfony uses `.dotenv` to set environment variables. You can check the [.env](./.env) in the root of the Homarus directory.
To alter any settings, create a file called `.env.local` to store your specific changes. You can also set an actual environment
variable.

For production use make sure to set the add `APP_ENV=prod` environment variable.

If your `ffmpeg` installation is not on your path, then you can configure homarus to use a specific executable by editing
the `app.executable` parameter in [`/path/to/Homarus/config/services.yaml`](./config/services.yaml).

You also need to set your Fedora Base Url to allow the Fedora Resource to be pulled in automatically.
This is done in the `/path/to/Homarus/config/packages/crayfish_commons.yaml`. 

### Logging

To change your log settings, edit the `/path/to/Homarus/config/packages/monolog.yaml` file.

You can also copy the file into one of the `/path/to/Homarus/config/packages/<environment>` directories.
Where `<environment>` is `dev`, `test`, or `prod` based on the `APP_ENV` variable (see above). The files in the specific
environment directory will take precedence over those in the `/path/to/Homarus/config/packages` directory.

The location specified in the configuration file for the log must be writable by the web server.

### Enabling JWT authentication

There are instructions in the `/path/to/Homarus/config/packages/security.yaml` file describing what to change and what lines
to comment out to enable authentication.

We use the Lexik JWT Authentication Bundle for Symfony, more information here
https://github.com/lexik/LexikJWTAuthenticationBundle

## Usage
This will return the an AVI file for the test video file in Fedora.
```
curl -H "Authorization: Bearer islandora" -H "Accept: video/x-msvideo" -H "Apix-Ldp-Resource:http://localhost:8080/fcrepo/rest/testvideo" http://localhost:8000/homarus/convert --output output.avi
```

## Maintainers

Current maintainers:

* [Natkeeran](https://github.com/Natkeeran)

## Development

If you would like to contribute, please get involved by attending our weekly [Tech Call](https://github.com/Islandora/docuentation/wiki). We love to hear from you!

If you would like to contribute code to the project, you need to be covered by an Islandora Foundation [Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_cla.pdf) or [Corporate Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_ccla.pdf). Please see the [Contributors](http://islandora.ca/resources/contributors) pages on Islandora.ca for more information.

## License

[MIT](https://opensource.org/licenses/MIT)
