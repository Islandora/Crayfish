# ![Milliner](https://cloud.githubusercontent.com/assets/2371345/25281066/8eca01c8-2682-11e7-987f-c77df38cd733.png) Milliner

## Introduction

Microservice that converts Drupal entities into Fedora resources.

## Installation

Milliner requires a functioning [Drupal 8](https://www.drupal.org/docs/8/install) installation with the [jsonld](http://github.com/Islandora/jsonld) module enabled.
It also requires a functioning [Fedora](http://fedorarepository.org/) repository.
You will also need an SQL database as needed for [Gemini](../Gemini)

- Clone this repository somewhere in your web root.
- Install `composer`.  [Install instructions here.][4]
- `$ cd /path/to/Milliner` and run `$ composer install`
- Then either
  - For production, configure your web server appropriately (e.g. add a VirtualHost for Milliner in Apache) OR
  - For development, run the PHP built-in web server `$ php -S localhost:8888 -t src` from Milliner root.

## Configuration

Make a copy of the [config file](cfg/config.example.yaml) and name it `config.yaml` in the `cfg` directory.
You will need to set the `fedora_base_url` entry to point to your Fedora installation.
You will also need to set up the `drupal_base_url` entry to point to your Drupal 8 installation.
The SQL db can be configured by following [Gemini's instructions](../Gemini).

## Usage

Milliner sets up a single endpoint, `/metadata/{drupal_path}`, which accepts POST, PUT, and DELETE requests.
If requests are successful, they return the response from the Fedora server.  If the Drupal entity cannot be requested,
the error response from Drupal is returned.  If there is an exception thrown during execution, a 500 response is returned
with the exception's message.


### POST

This retrieves a jsonld representation of the specified Drupal entity and inserts it in Fedora.

For example, suppose you create an entity at `http://localhost:8000/fedora_resource/1`.  If running the PHP built-in server command described in the Installation section:
```
$ curl -X "POST" "localhost:8888/metadata/fedora_resource/1"
```
will return the URI to the created Fedora resource if successfully created.

### PUT

This retrieves a jsonld representation of the specified Drupal entity and updates it in Fedora.

For example, suppose you update an entity at `http://localhost:8000/fedora_resource/1`.  If running the PHP built-in server command described in the Installation section:
```
$ curl -X "PUT" "localhost:8888/metadata/fedora_resource/1"
```
will return `204 No Content` if the resource is successfully updated.

### DELETE

This deletes the corresponding Fedora resource for the specified Drupal entity.

For example, suppose if you have an entity at `http://localhost:8000/fedora_resource/`.  If running the PHP built-in server command described in the Installation section:
```
$ curl -X "DELETE" "localhost:8888/metadata/fedora_resource/1"
```
will return `204 No Content` if the resource is successfully updated.

## Maintainers

Current maintainers:

* [Daniel Lamb](https://github.com/dannylamb)

## Development

If you would like to contribute, please get involved by attending our weekly [Tech Call](https://github.com/Islandora-CLAW/CLAW/wiki). We love to hear from you!

If you would like to contribute code to the project, you need to be covered by an Islandora Foundation [Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_cla.pdf) or [Corporate Contributor License Agreement](http://islandora.ca/sites/default/files/islandora_ccla.pdf). Please see the [Contributors](http://islandora.ca/resources/contributors) pages on Islandora.ca for more information.

We recommend using the [islandora-playbook](https://github.com/Islandora-Devops/islandora-playbook) to get started. 

## License

[MIT](https://opensource.org/licenses/MIT)

[3]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[4]: https://getcomposer.org/download/
