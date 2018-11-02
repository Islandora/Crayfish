# Homarus

### Apache Config
```
# managed by Ansible

Alias "/homarus" "/var/www/html/Crayfish/Homarus/src"
<Directory "/var/www/html/Crayfish/Homarus/src">
  FallbackResource /homarus/index.php
  Require all granted
  DirectoryIndex index.php
  SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
</Directory>
```

### Testing
* Clone this microservice into `/var/www/html/Crayfish`
* Install the depencies using composer `composer install`
* Place the above confir in `/etc/apache2/conf-available` as `Homarus.conf`.  
* Enable the conf: `sudo a2enconf Homarus`.  Restart Apache: `sudo systemctl restart apache2`
* Create a test video by going here: `http://localhost:8080/fcrepo/rest/` and uploading a binary
* Test via curl as below or using Postman

```
curl -H "Authorization: Bearer islandora" -H "Accept: video/x-msvideo" -H "Apix-Ldp-Resource:http://localhost:8080/fcrepo/rest/testvideo" http://localhost:8000/homarus/convert --output output.avi
```
