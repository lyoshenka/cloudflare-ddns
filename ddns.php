#!/usr/bin/env php
<?php

require __DIR__ . '/Cloudflare.php';

$confFile = __DIR__ . '/config.php';
if (!file_exists($confFile)) {
    echo "Missing config file. Please copy config.php.skel to config.php and fill out the values therein.\n";
    return 1;
}

$config = require $confFile;

foreach ([
             'cloudflare_email',
             'cloudflare_api_key',
             'domain',
             'record_name',
             'ttl',
             'proxied',
             'protocol',
         ] as $key) {
    if (!isset($config[$key]) || $config[$key] === '') {
        echo "config.php is missing the '$key' config value\n";
        return 1;
    }
}

$api = new Cloudflare($config['cloudflare_email'], $config['cloudflare_api_key']);

// default to first value of config array
$domain = $config['domain'][0];
$recordName = $config['record_name'][0];
// set domain and record from request if value exists in config
if ($_GET['domain'] || $_GET['record']) {
    if (
        $_GET['domain'] &&
        $_GET['record'] &&
        in_array($_GET['domain'], $config['domain']) &&
        in_array($_GET['record'], $config['record_name'])
    ) {
        $domain = $_GET['domain'];
        $recordName = $_GET['record'];
    } else {
        echo "Missing or invalid 'domain' or/and 'record_name' param\n";
        return 1;
    }
}

if (isset($config['auth_token']) && $config['auth_token']) {
    // API mode. Use IP from request params.
    if (
        empty($_GET['auth_token']) ||
        empty($_GET['ip']) ||
        $_GET['auth_token'] != $config['auth_token']
    ) {
        echo "Missing or invalid 'auth_token' param, or missing 'ip' param\n";
        return 1;
    }
    $ip = $_GET['ip'];
} else {
    // Local mode. Get IP from service.
    $ip = getIP($config['protocol']);
}

$verbose = !isset($argv[1]) || $argv[1] != '-s';

try {
    $zone = $api->getZone($domain);
    if (!$zone) {
        echo "Domain $domain not found\n";
        return 1;
    }

    $records = $api->getZoneDnsRecords($zone['id'], ['name' => $recordName]);
    $record = $records && $records[0]['name'] == $recordName ? $records[0] : null;

    if (!$record) {
        if ($verbose) {
            echo "No existing record found. Creating a new one\n";
        }
        $ret = $api->createDnsRecord($zone['id'], 'A', $recordName, $ip, [
            'ttl' => $config['ttl'],
            'proxied' => $config['proxied'],
        ]);
    } elseif (
        $record['type'] != 'A' ||
        $record['content'] != $ip ||
        $record['ttl'] != $config['ttl'] ||
        $record['proxied'] != $config['proxied']
    ) {
        if ($verbose) {
            echo "Updating record.\n";
        }
        $ret = $api->updateDnsRecord($zone['id'], $record['id'], [
            'type' => 'A',
            'name' => $recordName,
            'content' => $ip,
            'ttl' => $config['ttl'],
            'proxied' => $config['proxied'],
        ]);
    } else {
        if ($verbose) {
            echo "Record appears OK. No need to update.\n";
        }
    }
    return 0;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    return 1;
}

// http://stackoverflow.com/questions/3097589/getting-my-public-ip-via-api
// http://major.io/icanhazip-com-faq/
function getIP($protocol)
{
    $prefixes = [
        'ipv4' => 'ipv4.',
        'ipv6' => 'ipv6.',
        'auto' => '',
    ];
    if (!isset($prefixes[$protocol])) {
        throw new Exception('Invalid "protocol" config value.');
    }
    return trim(file_get_contents('http://' . $prefixes[$protocol] . 'icanhazip.com'));
}
