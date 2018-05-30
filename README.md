# Apache Log Parser

A PHP library to parse Apache logs.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](http://opensource.org/licenses/MIT)

## Installation

This library is installable via [Composer](https://getcomposer.org/).
Just define the following requirement in your `composer.json` file:

```json
{
    "require": {
        "benmorel/apache-log-parser": "dev-master"
    }
}
```

## Requirements

This library requires PHP 7.1 or later.

## Project status & release process

This library is under development.

The current releases are numbered `0.x.y`. When a non-breaking change is introduced (adding new methods, optimizing
existing code, etc.), `y` is incremented.

**When a breaking change is introduced, a new `0.x` version cycle is always started.**

It is therefore safe to lock your project to a given release cycle, such as `0.1.*`.

If you need to upgrade to a newer release cycle, check the [release history](https://github.com/BenMorel/apache-log-parser/releases)
for a list of changes introduced by each further `0.x.0` version.

## Package contents

This library provides a single class, `Parser`.

## Example

First construct a `Parser` object with the `LogFormat` defined in the httpd.conf file of the server that generated the log file:

```php
use BenMorel\ApacheLogParser\Parser;

$logFormat = "%h %l %u %t \"%{Host}i\" \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"";
$parser = new Parser($logFormat);
```

The library converts every [format string](https://httpd.apache.org/docs/current/en/mod/mod_log_config.html#formats) of your log format to a field name;
the list of fields can be accessed through the `getFieldNames()` method:

```php
var_export(
    $parser->getFieldNames()
);
```

```php
array (
  0 => 'remoteHostname',
  1 => 'remoteLogname',
  2 => 'remoteUser',
  3 => 'time',
  4 => 'requestHeader:Host',
  5 => 'firstRequestLine',
  6 => 'status',
  7 => 'responseSize',
  8 => 'requestHeader:Referer',
  9 => 'requestHeader:User-Agent',
)
```

You're then ready to parse a single line of your log file: the `parse()` method accepts the log line,
and a boolean to indicate whether you want the results as a numeric array, whose keys match the ones of the field names array:

```php
$line = '1.2.3.4 - - [30/May/2018:15:00:23 +0200] "www.example.com" "GET / HTTP/1.0" 200 1234 "-" "Mozilla/5.0';

var_export(
    $parser->parse($line, false)
);
```

```php
array (
  0 => '1.2.3.4',
  1 => '-',
  2 => '-',
  3 => '30/May/2018:15:00:23 +0200',
  4 => 'www.example.com',
  5 => 'GET / HTTP/1.0',
  6 => '200',
  7 => '1234',
  8 => '-',
  9 => 'Mozilla/5.0',
)
```

Or as an associative array, with the field names as keys:

```php
var_export(
    $parser->parse($line, true)
);
```

```php
array (
  'remoteHostname' => '1.2.3.4',
  'remoteLogname' => '-',
  'remoteUser' => '-',
  'time' => '30/May/2018:15:00:23 +0200',
  'requestHeader:Host' => 'www.example.com',
  'firstRequestLine' => 'GET / HTTP/1.0',
  'status' => '200',
  'responseSize' => '1234',
  'requestHeader:Referer' => '-',
  'requestHeader:User-Agent' => 'Mozilla/5.0',
)
```
