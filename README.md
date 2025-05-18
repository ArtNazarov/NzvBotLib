# NzvBotLib
Lightweight library for Telegram bots

Usage
====
```
<?php
include (__DIR__ . '/vendor/autoload.php');
include (__DIR__ . '/NzvBotLib.php');
 
$telegram = new Telegram('CHANGE_TO_YOUR_BOT_TOKEN');

$mr = new Nazarov\MessagesRouting($telegram);

$mr->addAction('/start', function(){
   return "response to start command";
});

$mr->process($mr->getMessageText());
```

# Installation

Install composer

```
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
```

Install dependencies

In project folder:

```
composer install
```

If need other libraries run

```
composer require vendor/package
```
