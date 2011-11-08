Litle API

A CakePHP plugin to facilitate the easy implementation of Litle Bank's API. 

Implementation

    cd app/plugins
    git clone __repo_path__ litle
    cp litle/config/litle.example.php ../config/litle.php
    
Finally, add the following to ../config/database.php

    public $litle = array(
        'datasource' => 'Litle.LitleSource',
        /* the rest of the conifugration is in config/litle_config.php */
        );

Configuration

Edit: app/config/litle.php

Log Model

It's a good idea to log every API interaction, both what we sent and what we get back.

Features

* Credit Card Processing
** sales (charges)
** voids
** credits (refunds)
* Tokenization
** built into the sale
** stand alone API
* Recycler Advice / Updater
** built into the sale / implied on recycle attempts

Unit Tests

About

author Alan Blount <alan@zeroasterisk.com>
copyright (c) 2011 Alan Blount
license MIT License - http://www.opensource.org/licenses/mit-license.php
