This document guides you through the process of upgrading Hypercube. First, check if a section named "Upgrade to x.x.x" exists, with x.x.x being the version you are planning to upgrade to.

## Upgrade to 4.0.0

4.0.0 uses Symfony 5.4, which has differences from 3.x.x which used Symfony 4.4.
This makes it non-backwards compatible and requires testing of any custom changes you
may have made.

Hypercube relies on Crayfish-Commons `^4.0` which no longer includes our own JWT authentication,
to perform JWT authentication we use the [Lexik JWT Bundle](https://github.com/lexik/LexikJWTAuthenticationBundle).

### Upgrade from version 3.x.x

You can remove the `syn_config:` line from the `./config/packages/crayfish_commons.yaml` file.

You will need to make a file in `./config/packages` called `lexik_jwt_authentication.yaml`.

The file needs to contain (at a minimum) or you can copy the file from the Github repository:
```yaml
lexik_jwt_authentication:
    # This is the public key from the pair generated by Drupal and is required to validate the JWTs
    public_key: '%env(resolve:JWT_PUBLIC_KEY)%'
    # By default lexik_jwt looks for the username key in the payload, we use sub
    user_identity_field: sub
```

You can either:
* define an [environment variables](https://symfony.com/doc/5.4/configuration.html#configuration-based-on-environment-variables) for
  the `JWT_PUBLIC_KEY` variable defined above and pointed to the Drupal public key file

_or_
* explicitly overwrite the `'%env(resolve:JWT_PUBLIC_KEY)%'` in
  the above file and specify the path to the Drupal public key


## Upgrade to 3.0.0

Hypercube (and all of Crayfish) adheres to [semantic versioning](https://semver.org), which makes a distinction between "major", "minor", and "patch" versions. The upgrade path will be different depending on which previous version from which you are migrating.

### Upgrade from version 2.x.x

Hypercube has switched from a Silex application to a Symfony application. This does not require much in code changes, but does use a different file layout.

Previously your configuration file would be located in the `/path/to/Hypercube/config` directory and be called `config.yaml`.

The configuration from this file will now be located several locations documented below.

#### Executable location
Old location `/path/to/Hypercube/config/config.yaml`

```
---
hypercube:
  # path to the convert executable
  tesseract_executable: tesseract
  pdftotext_executable: pdftotext
```

The two "executable" variables are now located in `/path/to/Hypercube/config/services.yaml` and appears in the `parameters`

```
parameters:
  app.tesseract_executable: tesseract
  app.pdftotext_executable: pdftotext

```

#### Fedora Resource
Old location `/path/to/Hypercube/config/config.yaml`

```
...
fedora_resource:
  base_url: http://localhost:8080/fcrepo/rest
```

This variable is necessary for the Crayfish-Commons setup, it has been moved to `/path/to/Hypercube/config/packages/crayfish_commons.yaml`

```
crayfish_commons:
  fedora_base_uri: 'http://localhost:8080/fcrepo/rest'
```

#### Log settings
Old location `/path/to/Hypercube/config/config.yaml`

```
...
log:
  # Valid log levels are:
  # DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY, NONE
  # log level none won't open logfile
  level: NONE
  file: ../hypercube.log
```

This setting is in `/path/to/Hypercube/config/packages/monolog.yaml`. This file contains commented out defaults from Symfony and a new handler for Hypercube.

```
monolog:
    handlers:
    ...
        hypercube:
            type: rotating_file
            path: /tmp/Hypercube.log
            level: DEBUG
            max_files: 1
            channels: ["!event", "!console"]
```

#### Syn settings
Old location `/path/to/Hypercube/config/config.yaml`

```
syn:
  # toggles JWT security for service
  enable: True
  # Path to the syn config file for authentication.
  # example can be found here:
  # https://github.com/Islandora/Syn/blob/main/conf/syn-settings.example$
  config: ../syn-settings.xml
```

The `syn.enable` variable is no longer used as Syn is part of the security for Symfony, see [below](#enable-disable-syn) for steps to see where to enable/disable Syn.

The `syn.config` variable is in `/path/to/Hypercube/config/crayfish_commons.yaml`.

```
crayfish_commons:
  ...
  #syn_config: '/path/to/syn-settings.xml'
```

`crayfish_commons.syn_config` needs to point to a file or be left commented out to use a default syn config of

```
<?xml version="1.0" encoding="UTF-8"?>
<!-- Default Config  -->
<config version='1'>
</config>
```

##### Enable/Disable Syn
To enable/disable Syn look in the `/path/to/Hypercube/config/packages/security.yaml`. By default Syn is disabled, to enable look the below lines and follow the included instructions

```
security:
    ...
    firewall:
        ...
        main:
            ...
            # To enable Syn, change anonymous to false and uncomment the lines further below
            anonymous: true
            ...
            # To enable Syn, uncomment the below 4 lines and change anonymous to false above.
            #provider: jwt_user_provider
            #guard:
            #    authenticators:
            #        - Islandora\Crayfish\Commons\Syn\JwtAuthenticator
```
