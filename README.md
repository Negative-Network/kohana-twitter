# Twitter module for Kohanaphp
## Modification from original

* Kohana 3.3
* Api from 1.0 to 1.1 
* Only json format as it is in 1.1
* Remove modification/writing capabilities, let only reading capabilities
* Some new useful functions for url replace in result

## Create Twitter app

     See https://dev.twitter.com to create you app 
    
     See https://twitter.com/settings/applications (to revoke access key and renegociate)

## Configure

    copy config file to config directory
    
    modules/koahana-twitter/config/twitter.php  >> application/config/twitter.php


## Use

    $twitter = new Twitter();

    $type = 'search'; // or 'home' or 'mentions' or 'user'
    $options = array('count' => 20,'q' => '@kohana #php'); 

    $load = $twitter->get($type,$options);

## Api Type & Options 

(see [https://dev.twitter.com/docs/api/1.1) for possible options. 
Only few options are for now supported as I don't need more right now. 
For new options update config/twitter-api.php and eventually send a pull request.


