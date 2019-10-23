![homarus](https://user-images.githubusercontent.com/2371345/48797524-c8c14300-ecd8-11e8-907d-9628fb6afacc.png) 
# Homarus                                          

## Introduction

[FFmpeg](https://www.ffmpeg.org/) as a microservice.

## Installation
- Install `ffmpeg`.  On Ubuntu, this can be done with `sudo apt-get install ffmpeg`. 
- Clone this repository somewhere in your web root (example: `/var/www/html/Crayfish/Homarus`).
- Copy `/var/www/html/Crayfish/Homarus/cfg/config.default.yml` to `/var/www/html/Crayfish/Homarus/cfg/config.yml`
- Copy `/var/www/html/Crayfish/Hypercube/syn-settings.xml` to `/var/www/html/Crayfish/Homarus/syn-settings.xml`
- Install `composer`.  [Install instructions here.][4]
- `$ cd /path/to/Homarus` and run `$ composer install`
- Then either
  - For production, configure your web server appropriately (e.g. add a VirtualHost for Homarus in Apache) OR
  - For development, run the PHP built-in web server `$ php -S localhost:8888 -t src` from Homarus root.
  

### Apache2

To use Homarus with Apache you need to configure your Virtualhost with a few options:
- Redirect all requests to the Homarus index.php file
- Make sure Hypercube has access to Authorization headers

Here is an example configuration for Apache 2.4:
```apache
Alias "/homarus" "/var/www/html/Crayfish/Homarus/src"
<Directory "/var/www/html/Crayfish/Homarus/src">
  FallbackResource /homarus/index.php
  Require all granted
  DirectoryIndex index.php
  SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
</Directory>
```

This will put the Homarus at the /homarus endpoint on the web server.

## Configuration

If your ffmpeg installation is not on your path, then you can configure homarus to use a specific executable by editing `executable` entry in [config.yaml](./cfg/config.example.yaml).

You also will need to set the `fedora base url` entry to point to your Fedora installation.

## Usage
This will return the an AVI file for the test video file in Fedora.  
```
curl -H "Authorization: Bearer islandora" -H "Accept: video/x-msvideo" -H "Apix-Ldp-Resource:http://localhost:8080/fcrepo/rest/testvideo" http://localhost:8000/homarus/convert --output output.avi
```

## Maintainers

Current maintainers:

* [Natkeeran](https://github.com/Natkeeran)

## Development

If you would like to contribute, please get involved by attending our weekly [Tech Call](https://github.com/Islandora-CLAW/CLAW/wiki). We love to hear from you!

If you would like to contribute code to the project, you need to be covered by an Islandora Foundation [Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_cla.pdf) or [Corporate Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_ccla.pdf). Please see the [Contributors](http://islandora.ca/resources/contributors) pages on Islandora.ca for more information.

We recommend using the [islandora-playbook](https://github.com/Islandora-Devops/islandora-playbook) to get started. 

## License

[MIT](https://opensource.org/licenses/MIT)
