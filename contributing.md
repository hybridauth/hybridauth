# Contributing to HybridAuth


HybridAuth is a community driven project and accepts contributions of code and documentation from the community. These contributions are made in the form of Issues or [Pull Requests](http://help.github.com/send-pull-requests/) on the [HybridAuth repository](https://github.com/hybridauth/hybridauth/) on GitHub.

Issues are a quick way to point out a bug, not to ask general question (for that there is [StackOverflow](https://stackoverflow.com/questions/tagged/hybridauth)). If you find a bug in HybridAuth then please check a few things first:

1. There is not already an open Issue.
2. The issue has already been fixed (check the master branch, or look for closed Issues).
3. That you have everything setup properly with your providers.
4. Is it something really obvious that you can fix yourself?

Reporting issues is helpful but an even better approach is to send a Pull Request, which is done by "Forking" the main repository and committing to your own copy. This will require you to use the version control system called Git.

## Guidelines

Before we look into how, here are the guidelines. If your Pull Requests fail
to pass these guidelines it will be declined and you will need to re-submit
when you’ve made the changes. This might sound a bit tough, but it is required
for us to maintain quality of the code-base.

### PHP Style


### Documentation

If you change anything that requires a change to documentation then you will need to add it. New providers, methods, parameters, changing default values, etc are all things that will require a change to documentation. The changelog must also be updated for every change.

### Compatibility

HybridAuth is compatible with PHP 5.3 so all code supplied must stick to
this requirement. If PHP 5.4+ functions or features are used then there
must be a fallback for PHP 5.3.

## License
Except where otherwise noted, HybridAuth is released under dual licence MIT and GPL.
