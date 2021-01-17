#!/usr/bin/env php
<?php
/** @link https://odan.github.io/2017/08/16/create-a-php-phar-file.html */

// The php.ini setting phar.readonly must be set to 0
$pharFile = 'simple.phar';

// clean up
if (file_exists($pharFile)) {
    unlink($pharFile);
}

// create phar
$p = new Phar($pharFile);

// start buffering. Mandatory to modify stub.
$p->startBuffering();

// creating our library using whole directory
$p->buildFromDirectory(__DIR__);

// pointing main file which requires all classes
$stub = $p->createDefaultStub('simple');

// Modify stub to add shebang line
$stub = "#!/usr/bin/env php\n" . $stub;

$p->setStub($stub);

$p->stopBuffering();

echo "$pharFile successfully created\n";
