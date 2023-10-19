# CrayFits

## Introduction

[FITS][1] as a microservice.

## Requirements

* Tomcat
* [Composer][2]

## Installation

### Install FITS webservice

* Download the latest `fits.zip` and `fits.war` from
[https://projects.iq.harvard.edu/fits/downloads](https://projects.iq.harvard.edu/fits/downloads).
You may need to install a zip library to unzip the file.
* Copy the `.war` file to your Tomcat webapps directory and test.
* Edit the Tomcat `conf/catalina.properties` file by adding the
following two lines to the bottom of the file:
```properties
fits.home=/\<path-to-fits>/fits
shared.loader=/\<path-to-fits>/fits/lib/*.jar
```
* Restart Tomcat.
* Test the webservice with:
```bash
curl -k -F datafile="@/path/to/myfile.jpg" http://[tomcat_domain]:[tomcat_port]/fits/examine
```
(note: the ‘@’ is required.)

### Install CrayFITS microservice

* Clone this repository somewhere in your web root.
* `$ cd /path/to/CrayFits` and run `$ composer install`
* For production, configure your web server appropriately (e.g. add a VirtualHost for CrayFits in Apache)

To run the microservice on the Symfony Console, enter:
```bash
php bin/console server:start *:8050
```
in the microservice root folder.

The server is stopped with:
```bash
php bin/console server:stop
```
On a production machine you'd probably want to configure an additional
port in Apache.

Note: The location of the FITS webserver is stored in the `.env` file in the
root dir of the Symfony app.  This will have to be reconfigured if the FITS
server is anywhere other than `localhost:8080/fits`


#### Optional: Configure Alpaca to accept derivative requests from Islandora.

To use Alpaca as an interface to this microservice, configure
an [`islandora-connector-derivative`](https://github.com/Islandora/Alpaca#islandora-connector-derivative)
appropriate to your installation of CrayFits.

[1]: https://projects.iq.harvard.edu/fits
[2]: https://getcomposer.org/download/


