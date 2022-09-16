This document guides you through the process of upgrading Recast. First, check if a section named "Upgrade to x.x.x" exists, with x.x.x being the version you are planning to upgrade to.

## Upgrade to 3.0.0

Recast (and all of Crayfish) adheres to [semantic versioning](https://semver.org), which makes a distinction between "major", "minor", and "patch" versions. The upgrade path will be different depending on which previous version from which you are migrating.

### Upgrade from version 2.x.x

Recast has switched from a Silex application to a Symfony application. This does not require much in code changes, but does use a different file layout.

Previously your configuration file would be located in the `/path/to/Recast/config` directory and be called `config.yaml`.

The configuration from this file will now be located several locations documented below.

#### Base Urls
Old location `/path/to/Recast/config/config.yaml`

```
---

fedora_resource:
  base_url: http://localhost:8080/fcrepo/rest

gemini_base_url: http://localhost:8000/gemini

drupal_base_url: http://localhost:8000
```

Two of these variables are now located in `/path/to/Recast/config/services.yaml` and appears in the `parameters`, the third `gemini_base_url` variable is no longer needed.

```
parameters:
    app.drupal_base_url: "http://localhost:8000"
    app.fedora_base_url: "http://localhost:8080/fcrepo/rest"
```

**NOTE**: The `app.fedora_base_url` is needed for Recast as well as Crayfish-Commons, hence in `/path/to/Recast/config/packages/crayfish_commons.yaml` you will see:

```
crayfish_commons:
  # Because we define a Fedora parameter in the services.yaml we can re-use it here.
  fedora_base_uri: '%app.fedora_base_url%'
```

#### Namespaces
Old location `/path/to/Recast/config/config.yaml`

```
# Add namespace prefixes used by Fedora for recast service
# Must be inside an array to maintain the internal associative array.
namespaces:
-
  acl: "http://www.w3.org/ns/auth/acl#"
  fedora: "http://fedora.info/definitions/v4/repository#"
  ldp: "http://www.w3.org/ns/ldp#"
  memento: "http://mementoweb.org/ns#"
  pcdm: "http://pcdm.org/models#"
  pcdmuse: "http://pcdm.org/use#"
  webac: "http://fedora.info/definitions/v4/webac#"
  vcard: "http://www.w3.org/2006/vcard/ns#"
```

This variables are now located in `/path/to/Recast/config/services.yaml` and appears in the `parameters`

```
parameters:
    ...
    app.namespaces:
        acl: "http://www.w3.org/ns/auth/acl#"
        fedora: "http://fedora.info/definitions/v4/repository#"
        ldp: "http://www.w3.org/ns/ldp#"
        memento: "http://mementoweb.org/ns#"
        pcdm: "http://pcdm.org/models#"
        pcdmuse: "http://pcdm.org/use#"
        webac: "http://fedora.info/definitions/v4/webac#"
        vcard: "http://www.w3.org/2006/vcard/ns#"
```

#### Log settings
Old location `/path/to/Recast/config/config.yaml`

```
...
log:
  # Valid log levels are:
  # DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY, NONE
  # log level none won't open logfile
  level: NONE
  file: ../recast.log
```

This setting is in `/path/to/Recast/config/packages/monolog.yaml`. This file contains commented out defaults from Symfony and a new handler for Recast.

```
monolog:
    handlers:
    ...
        recast:
            type: rotating_file
            path: /tmp/Recast.log
            level: DEBUG
            max_files: 1
            channels: ["!event", "!console"]
```

#### Syn settings
Old location `/path/to/Recast/config/config.yaml`

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

The `syn.config` variable is in `/path/to/Recast/config/crayfish_commons.yaml`.

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
To enable/disable Syn look in the `/path/to/Recast/config/packages/security.yaml`. By default Syn is disabled, to enable look the below lines and follow the included instructions

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

