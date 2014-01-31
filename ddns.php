#!/usr/bin/env php
<?php

require 'Cloudflare.php';

if (!file_exists('config.php'))
{
  echo "Please copy config.php.skel to config.php and fill out the values therein.\n";
  return 1;
}

$config = require 'config.php';

foreach(array('cloudflare_email','cloudflare_api_key','domain','record_name','ttl','cloudflare_active') as $key)
{
  if (!isset($config[$key]) || $config[$key] === '')
  {
    echo "config.php is missing the '$key' config value\n";
    return 1;
  }
}

$api = new Cloudflare($config['cloudflare_email'], $config['cloudflare_api_key']);

$domain = $config['domain'];
$recordName = $config['record_name'];

$ip = getIP();

try 
{
  $record = getExistingRecord($api, $domain, $recordName);
  if (!$record)
  {
    echo "No existing record found. Creating a new one\n";
    $ret = $api->rec_new($domain, 'A', $recordName, $ip, $config['ttl'], $config['cloudflare_active']);
    throwExceptionIfError($ret);
  }
  elseif($record['type'] != 'A')
  {
    echo "Record exists but is not an A record. Fixing that.\n";
    $ret = $api->rec_delete($domain, $record['rec_id']);
    throwExceptionIfError($ret);
    $ret = $api->rec_new($domain, 'A', $recordName, $ip, $config['ttl'], $config['cloudflare_active']);
    throwExceptionIfError($ret);
  }
  elseif($record['content'] != $ip)
  {
    echo "Record exists. Updating IP.\n";
    $ret = $api->rec_edit('grin.io', 'A', $record['rec_id'], $ip, $config['ttl'], $config['cloudflare_active']);
    throwExceptionIfError($ret);
  }
  else
  {
    echo "Record OK. No need to update.\n";
  }
}
catch (Exception $e)
{
  echo "Error: " . $e->message . "\n";
  return 1;
}


// http://stackoverflow.com/questions/3097589/getting-my-public-ip-via-api
function getIP()
{
  return trim(file_get_contents('http://checkip.dyndns.org/'));
}


function getExistingRecord($api, $domain, $recordName)
{
  echo "Getting existing record.\n";
  $retry = false;
  $count = 0;
  do {
    $count++;
    $data = $api->rec_load_all($domain);
    $retry = isset($data['error']) && $data['error'] == 'Operation timed out after 5001 milliseconds with 0 bytes received';

    if ($retry && $count > 5)
    {
      throw new Exception('Could not get data from Cloudflare after 5 retries. Aborting.');
    }
  } while ($retry);

  foreach($data['response']['recs']['objs'] as $record)
  {
    if ($record['name'] == $recordName)
    {
      return $record;
    }
  }
  return null;
}


function throwExceptionIfError($data)
{
  if (isset($data['error']))
  {
    throw new Exception($data['error']);
  }
}
