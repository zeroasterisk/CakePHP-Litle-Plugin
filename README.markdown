# Litle API

A CakePHP plugin to facilitate the easy implementation of Litle Bank's API.

## Install

    git submodule add app/Plugin/Litle __repo_path__
    cp app/Plugin/Litle/Config/litle.example.php app/Config/litle.php

    cd app/Plugin
    git clone __repo_path__ Litle
    cp Litle/Config/litle.example.php ../Config/litle.php

Finally, add the following to ../Config/database.php

    public $litle = array(
        'datasource' => 'Litle.LitleSource',
        /* the rest of the config is in config/litle.php */
    );

## Configuration

Edit: `app/Config/litle.php`

## API Logging

It's a good idea to log every API interaction, both what we sent and what we get back.

Edit the config file (above) and you can set logging to false, to disable API logging.

    $config['Litle']['logModel'] = false;

or edit the config file (above) and you can set logging to any Model you want
to setup in your application:

    $config['Litle']['logModel'] = 'MyCustomLoggingModel';

When an API transaction happens, it attempts to log in the following ways,
the first one of them that's available, wins:

    1) $MyCustomLoggingModel->logLitleRequest($LitleSale->lastRequest); # else
    2) $MyCustomLoggingModel->logRequest($LitleSale->lastRequest); # else
    3) $MyCustomLoggingModel->save($LitleSale->lastRequest);

The `lastRequest` contains the following values:

    $lastRequest = compact('type', 'status', 'response', 'message', 'transaction_id', 'litleToken', 'errors', 'data', 'request_raw', 'response_array', 'response_raw', 'url');

You can see a mockup of a Fixture and a Schema for your own API log tables

* https://github.com/zeroasterisk/CakePHP-Litle-Plugin/tree/master/Test/Fixture/MyCustomLoggingModelFixture.php
* https://github.com/zeroasterisk/CakePHP-Litle-Plugin/tree/master/Config/Schema/schema.php

## Features

* Credit Card Processing
** sales (charges)
** voids
** credits (refunds)
* Tokenization
** built into the sale
** stand alone API
* Recycler Advice / Updater
** built into the sale / implied on recycle attempts

## Unit Tests

    ./cake test Litle AllLitle

NOTE: you will need to have your IP address allowed into Litle's certification url/firewall.

## About

author Alan Blount <alan@zeroasterisk.com> https://github/com/zeroasterisk/
author Nick Baker <nick@webtechnick.com> https://github.com/webtechnick/

copyright (c) 2011 Alan Blount
license MIT License - http://www.opensource.org/licenses/mit-license.php
