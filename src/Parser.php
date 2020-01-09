<?php

declare(strict_types=1);

namespace BenMorel\ApacheLogParser;

class Parser
{
    /**
     * Maps Apache log format strings (following the % char) to human readable names.
     * The field names will be composed from these names.
     */
    private const FORMAT_STRING_NAMES = [
        'a' => 'clientIp',
        'A' => 'localIp',
        'B' => 'responseSize',
        'b' => 'responseSize',
        'C' => 'cookie',
        'D' => 'responseTime',
        'e' => 'env',
        'f' => 'filename',
        'h' => 'remoteHostname',
        'H' => 'requestProtocol',
        'i' => 'requestHeader',
        'k' => 'keepaliveRequests',
        'l' => 'remoteLogname',
        'L' => 'requestLogId',
        'm' => 'requestMethod',
        'n' => 'note',
        'o' => 'responseHeader',
        'p' => 'canonicalPort',
        'P' => 'processId',
        'q' => 'queryString',
        'r' => 'firstRequestLine',
        'R' => 'handler',
        's' => 'status',
        't' => 'time',
        'T' => 'timeToServe',
        'u' => 'remoteUser',
        'U' => 'urlPath',
        'v' => 'serverName',
        'V' => 'serverName',
        'X' => 'connectionStatus',
        'I' => 'bytesReceived',
        'O' => 'bytesSent',
        'S' => 'bytesTransferred',
        '^ti' => 'requestTrailerLine',
        '^to' => 'responseTrailerLine',
    ];

    /**
     * The regex pattern used to parse the Apache log format.
     *
     * This pattern parses 3 possible strings:
     *  - A % char followed by a potentially valid format string; this will be replaced with a regular expression
     *  - A % char followed by any other char; this fail with an exception, unless the second char is also a % (escape)
     *  - Any other string not containing a % char; this will be properly quoted to be regex safe
     */
    private const LOG_FORMAT_PATTERN = '/%(?:\{([a-zA-Z\-_]+)\})?[\<\>]?([a-zA-Z]|\^t[io])|%.|[^%]+/';

    /**
     * The regex pattern used to parse a single log line.
     *
     * @var string
     */
    private $pattern;

    /**
     * The field names.
     *
     * @var string[]
     */
    private $names = [];

    /**
     * Parser constructor.
     *
     * @param string $logFormat
     *
     * @throws \InvalidArgumentException If the log format is not valid.
     */
    public function __construct(string $logFormat)
    {
        $pattern = preg_replace_callback(self::LOG_FORMAT_PATTERN, [$this, 'replaceCallback'], $logFormat);

        $this->pattern = '/^' . $pattern . '\r?\n?$/';
    }

    /**
     * @param array $matches
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    private function replaceCallback(array $matches) : string
    {
        if ($matches[0] === '%%') {
            return '%';
        }

        // Escape non- format string chars
        if ($matches[0][0] !== '%') {
            return preg_quote($matches[0], '/');
        }

        if (! isset($matches[1])) {
            throw new \InvalidArgumentException('Unknown format string: ' . $matches[0]);
        }

        [, $bracketName, $formatString] = $matches;

        if (! isset(self::FORMAT_STRING_NAMES[$formatString])) {
            throw new \InvalidArgumentException('Unknown format string: %' . $formatString);
        }

        $name = self::FORMAT_STRING_NAMES[$formatString];

        if ($bracketName !== '') {
            $name .= ':' . $bracketName;
        }

        $this->names[] = $this->getUniqueName($name);

        return $this->getFormatStringRegexp($formatString, $bracketName !== '');
    }

    /**
     * @param string $formatString The log format string.
     * @param bool   $hasBrackets  Whether the format string has a curly brackets part.
     *
     * @return string A regexp with a single capturing group.
     */
    private function getFormatStringRegexp(string $formatString, bool $hasBrackets) : string
    {
        switch ($formatString) {
            case 'a': // Client IP address of the request
            case 'A': // Local IP address
                return '([0-9\.\:]+)';

            case 'b': // Size of response in bytes in CLF format, i.e. a '-' rather than a 0 when no bytes are sent
                return '([0-9]+|\-)';

            case 'B': // Size of response in bytes
            case 'D': // Time taken to serve the request, in microseconds
            case 'I': // Bytes received, including request and headers
            case 'k': // Number of keepalive requests handled on this connection
            case 'p': // Canonical port of the server serving the request
            case 'O': // Bytes sent, including headers
            case 'P': // Process ID of the child that serviced the request
            case 's': // Status
            case 'S': // Bytes transferred (received and sent), including request and headers
            case 'T': // Time taken to serve the request
                return '([0-9]+)';

            case 'H': // Request protocol
                return '(HTTP\/[0-9\.]+)';

            case 'm': // Request method
                return '([A-Za-z]+)';

            case 'q': // Query string, prepended with a ? if a query string exists, otherwise an empty string
                return '((?:\?.*?)?)';

            case 'r': // First line of request; can be '-' for an empty (bogus) request
                return '([A-Za-z]+ \S+ HTTP\/[0-9\.]+|\-)';

            case 't': // Time the request was received, in the format [18/Sep/2011:19:18:28 -0400]
                return $hasBrackets ? '([0-9]+)' : '\[([^\]]+)\]';

            case 'U': // URL path requested, not including any query string
            case 'v': // Canonical ServerName of the server serving the request
            case 'V': // Server name according to the UseCanonicalName setting
                return '(\S*)';

            case 'X': // Connection status when response is completed: X, + or -
                return '([X\+\-])';

            default:
                return '(.+?)';
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function getUniqueName(string $name) : string
    {
        for ($i = 1;; $i++) {
            $result = $name;

            if ($i !== 1) {
                $result .= ':' . $i;
            }

            if (in_array($result, $this->names, true)) {
                continue;
            }

            return $result;
        }
    }

    /**
     * Returns the field names.
     *
     * @return string[] A numeric array of field names.
     */
    public function getFieldNames() : array
    {
        return $this->names;
    }

    /**
     * Parses a log line.
     *
     * If the `$assoc` parameter is `false`, the result is returned as a numeric (0-based) array containing one entry
     * for each field. If this parameter is `true`, the result is returned as an associative array containing one entry
     * for each field, indexed by field name.
     *
     * @param string $line  The log line to parse.
     * @param bool   $assoc Whether to return the line as an associative (true) or numeric (false) array.
     *
     * @return string[] A numeric array of values, one for each field.
     *
     * @throws \InvalidArgumentException If the line cannot be parsed according to the log format.
     */
    public function parse(string $line, bool $assoc) : array
    {
        if (preg_match($this->pattern, $line, $matches) !== 1) {
            throw new \InvalidArgumentException('Cannot parse log line: ' . $line);
        }

        $result = array_slice($matches, 1);

        if ($assoc) {
            return array_combine($this->names, $result);
        }

        return $result;
    }
}
