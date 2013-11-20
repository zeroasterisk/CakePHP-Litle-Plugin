# Litle API

## NOT TESTED YET OR SAFE TO RUN IN CAKEPHP2.X

A CakePHP plugin to facilitate the easy implementation of Litle Bank's API. 

## Implementation

    cd app/Plugin
    git clone __repo_path__ Litle
    cp Litle/Config/litle.example.php ../Config/litle.php
    
Finally, add the following to ../Config/database.php

    public $litle = array(
        'datasource' => 'Litle.LitleSource',
        /* the rest of the conifugration is in config/litle.php */
        );

## Configuration

Edit: app/Config/Litle.php

It's a good idea to log every API interaction, both what we sent and what we get back.

    $config['Litle']['logModel'] = 'MyCustomLoggingModel';
    $MyCustomLoggingModel->logLitleRequest($LitleSale->lastRequest); # else
    //$MyCustomLoggingModel->logRequest($LitleSale->lastRequest); # else
    //$MyCustomLoggingModel->save($LitleSale->lastRequest);
    
    # FYI: $lastRequest = compact('type', 'status', 'response', 'message', 'transaction_id', 'litleToken', 'errors', 'data', 'request_raw', 'response_array', 'response_raw', 'url');

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

## About

author Alan Blount <alan@zeroasterisk.com>
copyright (c) 2011 Alan Blount
license MIT License - http://www.opensource.org/licenses/mit-license.php
