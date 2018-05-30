# Apache Log Parser

A PHP library to parse Apache logs.

[![Build Status](https://secure.travis-ci.org/BenMorel/apache-log-parser.svg?branch=master)](http://travis-ci.org/BenMorel/apache-log-parser)
[![Coverage Status](https://coveralls.io/repos/github/BenMorel/apache-log-parser/badge.svg?branch=master)](https://coveralls.io/github/BenMorel/apache-log-parser?branch=master)
[![Latest Stable Version](https://poser.pugx.org/benmorel/apache-log-parser/v/stable)](https://packagist.org/packages/benmorel/apache-log-parser)
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

## Quick start

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
  'remoteHostname'           => '1.2.3.4',
  'remoteLogname'            => '-',
  'remoteUser'               => '-',
  'time'                     => '30/May/2018:15:00:23 +0200',
  'requestHeader:Host'       => 'www.example.com',
  'firstRequestLine'         => 'GET / HTTP/1.0',
  'status'                   => '200',
  'responseSize'             => '1234',
  'requestHeader:Referer'    => '-',
  'requestHeader:User-Agent' => 'Mozilla/5.0',
)
```

If a line cannot be parsed, an `InvalidArgumentException` is thrown. Be sure to wrap your `parse()` calls in a try-catch block:

```php
try {
    $parser->parse($line, true)
} catch (\InvalidArgumentException $e) {
    // ...
}
```

## Field names returned by the library

This table shows how [format strings](https://httpd.apache.org/docs/current/en/mod/mod_log_config.html#formats) are mapped to field names by the library:

| Format string   | Field name                  |
|-----------------|-----------------------------|
| `%a`            | clientIp                    |
| `%{c}a`         | clientIp:c                  |
| `%A`            | localIp                     |
| `%B`            | responseSize                |
| `%b`            | responseSize                |
| `%{VARNAME}C`   | cookie:VARNAME              |
| `%D`            | responseTime                |
| `%{VARNAME}e`   | env:VARNAME                 |
| `%f`            | filename                    |
| `%h`            | remoteHostname              |
| `%H`            | requestProtocol             |
| `%{VARNAME}i`   | requestHeader:VARNAME       |
| `%k`            | keepaliveRequests           |
| `%l`            | remoteLogname               |
| `%L`            | requestLogId                |
| `%m`            | requestMethod               |
| `%{VARNAME}n`   | note:VARNAME                |
| `%{VARNAME}o`   | responseHeader:VARNAME      |
| `%p`            | canonicalPort               |
| `%{FORMAT}p`    | canonicalPort:FORMAT        |
| `%P`            | processId                   |
| `%{FORMAT}P`    | processId:FORMAT            |
| `%q`            | queryString                 |
| `%r`            | firstRequestLine            |
| `%R`            | handler                     |
| `%s`            | status                      |
| `%t`            | time                        |
| `%{FORMAT}t`    | time:FORMAT                 |
| `%T`            | timeToServe                 |
| `%{UNIT}T`      | timeToServe:UNIT            |
| `%u`            | remoteUser                  |
| `%U`            | urlPath                     |
| `%v`            | serverName                  |
| `%V`            | serverName                  |
| `%X`            | connectionStatus            |
| `%I`            | bytesReceived               |
| `%O`            | bytesSent                   |
| `%S`            | bytesTransferred            |
| `%{VARNAME}^ti` | requestTrailerLine:VARNAME  |
| `%{VARNAME}^to` | responseTrailerLine:VARNAME |

If two or more format strings yield the same field name, the second one will get a `:2` suffix, the third one a `:3` suffix, etc.

## Performance notes

You can expect to parse more than 250,000 records per second when reading logs from a file on a modern server with an SSD drive.

Returning records as an associative array comes with a small performance penalty of about 6%.
