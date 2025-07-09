# NzvBotLib
Lightweight library for Telegram bots

Usage
====
```
<?php
include (__DIR__ . '/vendor/autoload.php');
include (__DIR__ . '/NzvBotLib.php');
include (__DIR__ . '/ChatMessageWriter.php'); 

// Create database writer
$dbWriter = new Nazarov\ChatMessageWriter(
    'localhost', // host
    5432,        // port
    'change_to_your_username',    // username
    'change_to_your_password',    // password
    'mysql',   // database type
    'change_to_your_database' // database name
);

 
$telegram = new Telegram('CHANGE_TO_YOUR_BOT_TOKEN');

$mr = new Nazarov\MessagesRouting($telegram, $dbWriter);
$mr->addAction('/start', function(){
   return "response to start command";
});

$mr->process($mr->getMessageText());
```
# Quick commands using plugins

```
<?php
include (__DIR__ . '/vendor/autoload.php');
include (__DIR__ . '/NzvBotLib.php');
include (__DIR__ . '/NzvResponses.php');
include (__DIR__ . '/NzvPlugins.php');
$telegram = new Telegram('SET_TOKEN');

$plugins = \Nazarov\pluginCommands(__DIR__ . '/resp', ['help', 'start']);
...

foreach ($plugins as $command  => $resp){
    $mr->addAction("/$command", function() use ( $resp ) {
        return $resp;
    });
}
$mr->addAction('/help', function() use ( $plugins ) {
    $keys = array_keys($plugins);   
    $prefixed = array_map(fn($k) => '/' . $k, $keys);
    $list = implode(' , ', $prefixed);
    $also = "";
    if (count($keys)>0) { $also = "\n\n Also can use: $list"; };
   return "Help about bot. " . $also ;
});

```
Place files named as ```commandname.txt``` to ```/resp``` directory

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

# Testing

Create in the directory ```tests``` file ```AppTest.php```

with code by example 
```
<?php

include (__DIR__ . '/../vendor/autoload.php');
include (__DIR__ . '/../NzvBotLib.php');
...
use PHPUnit\Framework\TestCase; 
class AppTest extends TestCase
{
    private $telegram;
    private $mr;


    protected function setUp(): void {
        $this->telegram = new Telegram('SET_TOKEN');
        $this->mr = new Nazarov\MessagesRouting($this->telegram);
        
    }
    
      public function testStartResponse(): void
    {
            $this->mr->addAction('/start', function(){
                return "test response";
            });

        $this->assertEquals(
            "test response",
            $this->mr->process('/start')
        );
    }
...

}
```


In the terminal open project folder and run
```
 ./vendor/bin/phpunit --testdox tests
```
