# ![image](https://cloud.githubusercontent.com/assets/2371345/24554336/902613ac-1603-11e7-9c4f-1c79204388e7.png) Gemini 
[![Contribution Guidelines][2]](./CONTRIBUTING.md)
[![LICENSE][3]](./LICENSE)

## Introduction

A path mapping service for Islandora-CLAW.  Gemini is what links content created in Drupal to data stored in Fedora.  It has a very simple API and is built on top of a relational database using Doctrine's [database abstraction layer][4].

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

Gemini accepts [configuration for Doctrine's DBAL](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html) as the `db.options` array in [its config file](./cfg/config.example.yaml) file.  Other settings such as the location of Gemini's log file and the base URL of your Fedora server are also in this configuration file. Reasonable defaults provided.  Do not commit the configuration file with your MySQL credentials into Git!

## Usage

Gemini associates URL paths between resources in Drupal and Fedora. To link the Drupal and Fedora URIs of a resource, a client must mint a new Fedora URI (using a POST) based on the UUID of the node or file in Drupal, and then persist the Gemini record linking the two URIs (using a PUT).

#### POST /

Mints a new Fedora URI:

`curl -v -H "Authorization: Bearer islandora" -H "Content-Type: application/json" -d 'ab70127a-8579-4c17-af07-b3b1eceebb17'  http://localhost:8000/gemini/`

Returns for example:

```
< HTTP/1.1 200 OK
< Date: Mon, 29 Oct 2018 19:03:36 GMT
< Server: Apache/2.4.18 (Ubuntu)
< X-Powered-By: PHP/7.0.32-0ubuntu0.16.04.1
< Cache-Control: no-cache, private
< Vary: Accept-Encoding
< Content-Length: 82
< Content-Type: text/html; charset=UTF-8
```

`http://localhost:8080/fcrepo/rest/ab/70/12/7a/ab70127a-8579-4c17-af07-b3b1eceebb17`

#### PUT /{UUID}

Updates the entry corresponding to the UUID with the Drupal URL:

`curl -v -H "Authorization: Bearer islandora" -X PUT -H "Content-Type: application/json" -d '{"drupal" : "http://localhost:8000/node/0001", "fedora" : "http://localhost:8080/fcrepo/rest/ab/70/12/7a/ab70127a-8579-4c17-af07-b3b1eceebb17"}' http://localhost:8000/gemini/ab70127a-8579-4c17-af07-b3b1eceebb17`


If successful, returns for example:

```
HTTP/1.1 201 Created
< Date: Mon, 29 Oct 2018 19:17:41 GMT
< Server: Apache/2.4.18 (Ubuntu)
< X-Powered-By: PHP/7.0.32-0ubuntu0.16.04.1
< Cache-Control: no-cache, private
< Location: http://localhost:8000/gemini/ab70127a-8579-4c17-af07-b3b1eceebb17
< Content-Length: 0
< Content-Type: text/html; charset=UTF-8
```

resulting in the creation of a new record in the Gemini database:

```
mysql> select * from Gemini where uuid = 'ab70127a-8579-4c17-af07-b3b1eceebb17'\G
*************************** 1. row ***************************
fedora_hash: 868afb07dbe25dc0539ba91ce4f0d9e5e2cebdc1124935590544abe14b54466ecf925113bcf057c3b1bbb9056e03e918dd60b50ad2047b9ecf44b60db8fb1a91
drupal_hash: 1cd9033dc7a45e4034bfba5b832f772b2b8a694ece2ac0c16bcc22a3563ee331a90adc843e3657e491ac550776eaff0ec2db521891da2a3a55609d817598b5da
       uuid: ab70127a-8579-4c17-af07-b3b1eceebb17
 drupal_uri: http://localhost:8000/node/0001
 fedora_uri: http://localhost:8080/fcrepo/rest/ab/70/12/7a/ab70127a-8579-4c17-af07-b3b1eceebb17
dateCreated: 2018-10-29 14:17:42
dateUpdated: 2018-10-29 14:17:42
1 row in set (0.00 sec)
```

#### GET /{UUID}

Fetches the Drupal/Fedora URIs corresponding to a UUID:

`curl -H "Authorization: Bearer islandora" http://localhost:8000/gemini/ab70127a-8579-4c17-af07-b3b1eceebb17`

This request returns, for example:

```
< HTTP/1.1 200 OK
< Date: Mon, 29 Oct 2018 20:31:25 GMT
< Server: Apache/2.4.18 (Ubuntu)
< X-Powered-By: PHP/7.0.32-0ubuntu0.16.04.1
< Cache-Control: no-cache, private
< Content-Length: 163
< Content-Type: application/json
```

```javascript
{
   "drupal":"http:\/\/localhost:8000\/node\/0001",
   "fedora":"http:\/\/localhost:8080\/fcrepo\/rest\/ab\/70\/12\/7a\/ab70127a-8579-4c17-af07-b3b1eceebb17"
}
```

#### DELETE /{UUID}

Purges the entry corresponding to the UUID from Gemini's database:

curl -v -X DELETE -H "Authorization: Bearer islandora" http://localhost:8000/gemini/ab70127a-8579-4c17-af07-b3b1eceebb17

If successful, this request returns, for example:

```
< HTTP/1.1 204 No Content
< Date: Mon, 29 Oct 2018 19:51:39 GMT
< Server: Apache/2.4.18 (Ubuntu)
< X-Powered-By: PHP/7.0.32-0ubuntu0.16.04.1
< Cache-Control: no-cache, private
< Content-Type: text/html; charset=UTF-8
```

## Maintainers

Current maintainers:

* [Daniel Lamb](https://github.com/dannylamb)

## License

[MIT](https://opensource.org/licenses/MIT)

[2]: http://img.shields.io/badge/CONTRIBUTING-Guidelines-blue.svg
[3]: https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square
[4]: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/
[5]: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/introduction.html
[6]: https://getcomposer.org/download/
