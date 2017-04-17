# ![image](https://cloud.githubusercontent.com/assets/2371345/24554336/902613ac-1603-11e7-9c4f-1c79204388e7.png) Gemini 
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

## Introduction

A path mapping service for Islandora-CLAW.  Gemini is what links content created in Drupal to data stored in Fedora.  It has a very simple API and is built on top of a relational database using Doctrine's [database abstreaction layer][4].

## Installation

- Install the database of your choice that is [compatible with Doctrine's DBAL][5]. 
- Clone this repository.
- Install `composer`.  [Install instructions here.][6]
- `$ cd /path/to/Gemini` and run `$ composer install`
- Then either
  - For production, configure your web server appropriately (e.g. add a VirtualHost for Gemini in Apache) OR
  - For development, run the PHP built-in webserver `$ php -S localhost:8888 -t src` from Gemini root.

Gemini runs on its own database, and requires one table.  You'll need to set that up manually.  For example, using MySQL:
```mysql
create database gemini;
CREATE TABLE gemini.Gemini (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    drupal VARCHAR(2048) NOT NULL UNIQUE,
    fedora VARCHAR(2048) NOT NULL UNIQUE
) ENGINE=InnoDB;
```

## Configuration

Gemini accepts [configuration for Doctrine's DBAL](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html) as the `db.options` array in [its config file](./cfg/config.yaml) file.  Reasonable defaults provided are for a MySQL installation.  Do not commit the configuration file with your credentials into Git!

## Usage

Gemini's links url paths (excluding base urls) between Drupal and Fedora.  Assuming you have a FedoraResource entity in Drupal at `http://localhost:8000/fedora_resource/1` and an RdfSource in Fedora at `http://localhost:8080/fcrepo/rest/foo/bar`:

#### POST /
To link two resources, one must POST a JSON message that conforms to this format:
```
{
  "drupal" : "path/in/drupal",
  "fedora" : "path/in/fedora"
}
```
For example, with the resources described above:
```
$ curl -X POST -H "Content-Type: application/json" -d '{"drupal" : "fedora_resource/1", "fedora" : "foo/bar"}' "localhost:8888/"
```
will return 201 on success.

Once linked, the following operations are available to retrieve and delete information in Gemini.

#### GET drupal/path/to/resource 
For example, with the resources described above:
```
curl "http://localhost:8888/drupal/fedora_resource/1"
```
returns `foo/bar`.

#### GET fedora/path/to/resource 
For example, with the resources described above:
```
curl "http://localhost:8888/fedora/foo/bar"
```
returns `fedora_resource/1`.

#### DELETE drupal/path/to/resource
For example, with the resources described above:
```
curl -X DELETE "http://localhost:8888/drupal/fedora_resource/1"
```
will unlink the two entities.

#### DELETE fedora/path/to/resource
For example, with the resources described above:
```
curl -X DELETE "http://localhost:8888/fedora/foo/bar"
```
will unlink the two entities.

## Maintainers

Current maintainers:

* [Daniel Lamb](https://github.com/dannylamb)

## License

[MIT](http://www.gnu.org/licenses/gpl-2.0.txt)

[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[4]: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/
[5]: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/introduction.html
[6]: https://getcomposer.org/download/
