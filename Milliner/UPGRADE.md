This document guides you through the process of upgrading Milliner. First, check if a section named "Upgrade to x.x.x" exists, with x.x.x being the version you are planning to upgrade to.

## Upgrade to 3.0.0

Milliner (and all of Crayfish) adheres to [semantic versioning](https://semver.org), which makes a distinction between "major", "minor", and "patch" versions. The upgrade path will be different depending on which previous version from which you are migrating.

### Upgrade from version 2.x.x

Milliner has switched from a Silex application to a Symfony application. This does not require much in code changes, but does use a different file layout.

Previously your configuration file would be located in the `/path/to/Milliner/config` directory and be called `config.yaml`.

The configuration from this file will now be located several locations documented below.

#### Base Urls
Old location `/path/to/Milliner/config/config.yaml`

```
---

fedora_base_url: http://localhost:8080/fcrepo/rest
# if drupal_base_url contains a path, be sure to include trailing slash
# or relative paths will not resolve correctly.
drupal_base_url: http://localhost:8000
```

The `fedora_base_url` variable is now located in `/path/to/Milliner/config/services.yaml` and appears in the `parameters`

```
parameters:
    app.fedora_base_url: "http://localhost:8080/fcrepo/rest"
```

**Note**: the `drupal_base_url` variable is no longer needed and has been removed.

#### Modified date predicate
Old location `/path/to/Milliner/config/config.yaml`

```
...
modified_date_predicate: http://schema.org/dateModified
```

This variable is now located in `/path/to/Milliner/config/services.yaml` and appears in the `parameters`

```
parameters:
    ...
    app.modified_date_predicate: "http://schema.org/dateModified"
```

#### Strip format JsonLd
Old location `/path/to/Milliner/config/config.yaml`

```
...
strip_format_jsonld: true 
```

This variable is now located in `/path/to/Milliner/config/services.yaml` and appears in the `parameters`

```
parameters:
    ...
    app.strip_format_jsonld: true
```

#### Is Fedora 6
Old location `/path/to/Milliner/config/config.yaml`

```
...
fedora6: true
```

**Note**: This variable may only exist in your configuration if you are running a Fedora 6.x.x repository.

This variable is now located in `/path/to/Milliner/config/services.yaml` and appears in the `parameters`
```
parameters:
    ...
    app.isFedora6: true
```

#### Log settings
Old location `/path/to/Milliner/config/config.yaml`

```
...
log:
  # Valid log levels are:
  # DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY, NONE
  # log level none won't open logfile
  level: NONE
  file: ../milliner.log
```

This setting is in `/path/to/Milliner/config/packages/monolog.yaml`. This file contains commented out defaults from Symfony and a new handler for Milliner.

```
monolog:
    handlers:
    ...
        milliner:
            type: rotating_file
            path: /tmp/Milliner.log
            level: DEBUG
            max_files: 1
            channels: ["!event", "!console"]
```

#### Syn settings
Old location `/path/to/Milliner/config/config.yaml`

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

The `syn.config` variable is in `/path/to/Milliner/config/crayfish_commons.yaml`.

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
To enable/disable Syn look in the `/path/to/Milliner/config/packages/security.yaml`. By default Syn is disabled, to enable look the below lines and follow the included instructions

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

