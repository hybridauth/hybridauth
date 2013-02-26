# HybridAuth 3.0.0-dev branch

This branch contains work in progress toward the next HybridAuth 3 release and subject to change at any time.

Roadmap
-------
This roadmap outlines the planned strategy for HybridAuth 3.x, and it take into account a legit request by @EvanDotPro and many other (https://github.com/hybridauth/hybridauth/issues/34) 

* <del>Try to keep HybridAuth PHP 5.2 compatible if possible</del>. => Now requires 5.3 
* Upgrade to PSR-[01<del>2</del>]? ([cf. accepted ones so far](https://github.com/php-fig/fig-standards/tree/master/accepted)). => HybridAuth is now (more or less) in compliance with PSR-0 and PSR-1.
* Make <del>Logger</del> and Storage injectable. => Storage injectable. Logger has been removed entirely.
* Provide custom certificates for SSL communication (https://github.com/hybridauth/hybridauth/issues/39). => SSL certs could be now set in HybridAuth config.
* <del>Move all requirements checks to install.php</del>. => HybridAuth install.php is removed along unnecessary checks. 
* Allow the use of third-parties http clients libs. => Http clients could be now set in HybridAuth config.
* Make curl options configurable. => HybridAuth default Http clients curl options could be now set in HybridAuth config.
* ...
* Make the migration process from HybridAuth 2.x easy and manageable
* add hooks to getUserProfile, getUserContacts to chage the default behavior/result
* Rewrite the code documentation and user guide (God help us all)
* Implement some coding standards (Comments and constructive criticism on this are welcome and appreciated) 
* Hybridauth 3.x will come bundled only 6 "major" providers + OpenID by default -- and only them will be kept maintained on upstream. Additionals providers will be maintained independently.

Bundled Providers
-----------------
* Google
* Facebook
* Twitter
* Yahoo
* Windows
* LinkedIn
* OpenID

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
