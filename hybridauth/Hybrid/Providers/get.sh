#!/bin/bash

### go to the directory of the script
cd $(dirname $0)

### list of the providers that we use
provider_list="
    https://github.com/dashohoxha/hybridauth-drupaloauth2/raw/1.0/DrupalOAuth2.php
"

### get all the providers
for provider in $provider_list
do
    wget $provider
done