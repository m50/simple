# (Not so) Simple

The documentation verification tool to make sure that nothing is considered easy or simple.

Run *Simple* in your CI process on your documentation to make sure you don't put out any documentation that is condescending or unhelpful to learners. Everywhere that *Simple* finds any of the problematic words, it may be a perfect case to more detailed documentation.

## Usage

To use simple, run it from the command line providing the directory the documentation files reside in. Example:

```sh
simple -d ./docs/
```

To get a full list of options and flags, run the help command:

```sh
simple help
```

## Installation

Simple can either be installed as an executable PHAR, or as a composer dependency.

### Phar:

```sh
wget -O simple.phar $SIMPLE_DOWNLOAD_PATH
chmod 755 simple.phar
mv simple.phar /usr/local/bin/simple
```

### Composer

```sh
composer global require m50/simple
```
