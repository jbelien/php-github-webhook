[![Latest Stable Version](https://poser.pugx.org/jbelien/github-webhook/v/stable)](https://packagist.org/packages/jbelien/github-webhook)
[![Total Downloads](https://poser.pugx.org/jbelien/github-webhook/downloads)](https://packagist.org/packages/jbelien/github-webhook)
[![Monthly Downloads](https://poser.pugx.org/jbelien/github-webhook/d/monthly.png)](https://packagist.org/packages/jbelien/github-webhook)

# PHP GitHub Webhook

GitHub Webhook using [Zend Expressive](https://docs.zendframework.com/zend-expressive/) (PHP)

# Install

    composer create-project jbelien/github-webhook

# Configuration

Create a `config.php` file in `config/application` directory :

```php
<?php

return [
    'token' => 'your_webhook_token',
    'endpoints' => [
        [
            'repository' => 'jbelien/myrepo',
            'branch' => 'master', // required for PUSH event
            'run' => '',
        ],
    ],
];
```

- Replace `your_webhook_token` by the token you provided in your webhook settings (see hereunder) ;
- Replace `jbelien/myrepo` by your repository ;
- Change the branch name if needed ;
- The `run` option can be one (string) or a list (array) of command to execute ;

You can provide as many endpoints as needed ! For instance, if you need to use this "PHP GitHub Webhook" with more than one repository.

## GitHub

1. Go in the "Settings" tab of your repository ;
2. Go in "Webhooks"
3. Create a new webhook
4. Put the link to the webhook in "Payload URL" : something like `http://YOUR_IP_ADDRESS/webhook` ; don't forget to add the `/webhook` after your IP address or domain name !
5. Choose `application/json` as "Content type"
6. I suggest to add a token in "Secret" (don't forget to define it in your `config.php` file)
7. You only need to send the `push` (or `release`) events.
