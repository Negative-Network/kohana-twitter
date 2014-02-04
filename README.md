# Twitter module for Kohanaphp
## Modification from original

* Kohana 3.3
* Api from 1.0 to 1.1
* Remove modification/writing capabilities, let only reading capabilities

## Create Twitter app

     See https://dev.twitter.com to create you app 
    
     See https://twitter.com/settings/applications (to revoke access key and renegociate)

## Configure

    copy config file to config directory
    
    modules/koahana-twitter/config/twitter.php  >> application/config/twitter.php


## Use

    $twitter = new Twitter();
    
### Load

(see [https://dev.twitter.com/docs/api/1.1)

    $load = $twitter->load(Twitter::MENTIONS,optional count);
or
    $load = $twitter->load(Twitter::USER,'optional user',optional count);
or
    $load = $twitter->load(Twitter::HOME,optional count);

### Search

    $load = twitter->search('@myserch #mysearch');


