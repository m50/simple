<?php
/** @link https://odan.github.io/2017/08/16/create-a-php-phar-file.html */

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
$p->buildFromDirectory('./');

// pointing main file which requires all classes
$p->setDefaultStub('simple', '/simple');

// plus - compressing it into gzip
$p->compress(Phar::GZ);

echo "$pharFile successfully created\n";
