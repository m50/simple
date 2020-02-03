<?php
/** @link https://odan.github.io/2017/08/16/create-a-php-phar-file.html */

ini_set('phar.readonly', 0);
// The php.ini setting phar.readonly must be set to 0
$pharFile = 'simple.phar';

// clean up
if (file_exists($pharFile)) {
    unlink($pharFile);
}
if (file_exists($pharFile . '.gz')) {
    unlink($pharFile . '.gz');
}

// create phar
$p = new Phar($pharFile);

// creating our library using whole directory
$p->buildFromDirectory('src/');

// pointing main file which requires all classes
$p->setDefaultStub('index.php', '/index.php');

// plus - compressing it into gzip
$p->compress(Phar::GZ);

echo "$pharFile successfully created";
