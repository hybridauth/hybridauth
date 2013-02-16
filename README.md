# HybridAuth 3.0.0-dev branch

This branch contains work in progress toward the next HybridAuth 3 release and subject to change at any time.

Roadmap
-------
This roadmap outlines the planned strategy for HybridAuth 3.x, and it take into account a legit request by @EvanDotPro and many other (https://github.com/hybridauth/hybridauth/issues/34) 

* Try to keep HybridAuth PHP 5.2 compatible if possible
* Make the migration process from HybridAuth 2.x easy and manageable
* Upgrade to PSR-[123]? ([cf. accepted ones so far](https://github.com/php-fig/fig-standards/tree/master/accepted))
* Implement some coding standards (Comments and constructive criticism on this are welcome and appreciated)
* Remove all the static usage in favor of real OOP practices
* Make Logger and Session injectable
* Add official composer/packagist support for the core, default and extra providers
* Make it overall more friendly for integration with projects and frameworks (hopefully)
* Rewrite the code documentation and user guide (God help us all)
* ...
* add hooks to getUserProfile, getUserContacts to chage the default behavior/result

Requirements
------------
* PHP 5.2
* PHP/libcurl enabled

Resources
---------
* Documentation: http://hybridauth.sourceforge.net/userguide.html
* Support: http://hybridauth.sourceforge.net/support.html
* Issues: https://github.com/hybridauth/hybridauth/issues
* Email: hybridauth@gmail.com

License
-------
HybridAuth is released under dual licence MIT and GPLv3
http://hybridauth.sourceforge.net/licenses.html
