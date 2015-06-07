Installation
============

**Important** : Please, avoid using Hybridauth  master branch, as we usually keep it for development.

### Using Composer

When using Composer, you'll have to add Hybridauth to your project dependencies in `composer.json`:

```json
"require": {
    "hybridauth/hybridauth": "3.0.*"
}
```

Next step would be to install Composer and dependencies. Once this step is done, the installation process is completed.

```
$ curl -s http://getcomposer.org/installer | php
$ php composer.phar install
```

Composer comes bundled with and autoloader which can be used to autoload the required class by hybridauth library. A typical
usage example of the autoloader would be something like the following:

```php
<?php
    // include Composer autoloader
    require_once 'vendor/autoload.php'; 

    //Hybridauth namespace should be now accessible
    echo Hybridauth\Hybridauth::$version;
```


### For the conservatives

In case you don't wish to use Composer, or if you never heard of it, or for whatever other reason you can't use it to auto-install
hybridauth, then you still can include hybridauth the traditional way by downloading the library archive and unzipping it into
your project.

The required steps are typically the following:

1. Download the latest available release at https://github.com/hybridauth/hybridauth/releases
2. Unzip the archive file to your project directory.
3. If your project is already using a PSR-4 autoloader, then simply map the `Hybridauth\\` namespace to hybridauth `./src` 
folder on your configuration files. Otherwise, you may use the included autoloader that can be found on the examples folder:

```php
<?php
    // include Hybridauth autoloader
    require_once 'hybridauth/examples/hybridauth_autoload.php'; 

    //Hybridauth namespace should be now accessible
    echo Hybridauth\Hybridauth::$version;
```
