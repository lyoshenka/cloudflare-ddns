<?php

#https://github.com/mmerian/cloudflare

class CloudflareException extends Exception
{
}

class Cloudflare
{
  const GET    = 'GET';
  const POST   = 'POST';
  const PUT    = 'PUT';
  const PATCH  = 'PATCH';
  const DELETE = 'DELETE';

  const ENDPOINT = 'https://api.cloudflare.com/client/v4/';

  private $email;
  private $apiKey;
  private $resultInfo;

  /**
   * @param string $email
   * @param string $apiKey
   *
   * @return CloudFlare
   */
  public function __construct($email, $apiKey)
  {
    $this->email  = $email;
    $this->apiKey = $apiKey;
  }

  /**
   * Issues an HTTPS request and returns the result
   *
   * @param string $method
   * @param string $endpoint
   * @param array  $params
   *
   * @throws Exception
   *
   * @return mixed
   */
  public function request($method, $endpoint, $params = [])
  {
    $curl = curl_init();

    $headers = [
      'X-Auth-Email: ' . $this->email,
      'X-Auth-Key: ' . $this->apiKey
    ];

    $url = self::ENDPOINT . ltrim($endpoint, '/');
    switch ($method)
    {
      case self::POST :
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        break;
      case self::PUT :
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        $headers[] = 'Content-type: application/json';
        break;
      case self::PATCH :
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        $headers[] = 'Content-type: application/json';
        break;
      case self::DELETE :
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        $headers[] = 'Content-type: application/json';
        break;
      default:
        if ($params)
        {
          $url .= '?' . http_build_query($params);
        }
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($curl), true);
    if (!$response)
    {
      throw new CloudflareException(curl_error($curl));
    }
    elseif (false == $response['success'])
    {
      throw new CloudflareException($response['errors'][0]['message']);
    }
    curl_close($curl);

    $this->resultInfo = isset($response['result_info']) ? $response['result_info'] : null;

    return $response['result'];
  }

  /**
   * Issues an HTTP GET request
   *
   * @param string $endpoint
   * @param array  $params
   */
  public function get($endpoint, $params = [])
  {
    return $this->request(self::GET, $endpoint, $params);
  }

  /**
   * Issues an HTTP POST request
   *
   * @param string $endpoint
   * @param array  $params
   */
  public function post($endpoint, $params = [])
  {
    return $this->request(self::POST, $endpoint, $params);
  }

  /**
   * Issues an HTTP PUT request
   *
   * @param string $endpoint
   * @param array  $params
   */
  public function put($endpoint, $params = [])
  {
    return $this->request(self::PUT, $endpoint, $params);
  }

  /**
   * Issues an HTTP PATCH request
   *
   * @param string $endpoint
   * @param array  $params
   */
  public function patch($endpoint, $params = [])
  {
    return $this->request(self::PATCH, $endpoint, $params);
  }

  /**
   * Issues an HTTP DELETE request
   *
   * @param string $endpoint
   * @param array  $params
   */
  public function delete($endpoint, $params = [])
  {
    return $this->request(self::DELETE, $endpoint, $params);
  }

  public function getZones(array $params = [])
  {
    return $this->get('/zones', $params);
  }

  public function getResultInfo()
  {
    return $this->resultInfo;
  }

  public function getZone($name)
  {
    $zones = $this->getZones([
      'name' => $name
    ]);
    foreach ($zones as $zone)
    {
      if ($zone['name'] == $name)
      {
        return $zone;
      }
    }
    return null;
  }

  public function getZoneDnsRecords($zoneId, array $params = [])
  {
    return $this->get('/zones/' . $zoneId . '/dns_records', $params);
  }

  public function createDnsZone($name, array $params = [])
  {
    $params['name'] = $name;
    return $this->post('/zones', $params);
  }

  /**
   * Creates a zone if it doesn't already exist.
   *
   * Returns information about the zone
   */
  public function registerDnsZone($name, $params = [])
  {
    if ($res = $this->getZone($name))
    {
      return $res;
    }
    return $this->createDnsZone($name, $params);
  }

  public function setDnsZoneSsl($zoneId, $type)
  {
    $allowedTypes = [
      'off',
      'flexible',
      'full',
      'full_strict'
    ];

    if (!in_array($type, $allowedTypes))
    {
      throw new Exception('SSL type not allowed. valid types are ' . join(', ', $allowedTypes));
    }

    return $this->patch('/zones/' . $zoneId . '/settings/ssl', ['value' => $type]);
  }

  public function setDnsZoneCache($zoneId, $type)
  {
    $allowedTypes = [
      'aggressive',
      'basic',
      'simplified',
    ];

    if (!in_array($type, $allowedTypes))
    {
      throw new Exception('Cache type not allowed. valid types are ' . join(', ', $allowedTypes));
    }

    return $this->patch('/zones/' . $zoneId . '/settings/cache_level', ['value' => $type]);
  }

  public function clearZoneCache($zoneId)
  {
    return $this->delete('/zones/' . $zoneId . '/purge_cache', ['purge_everything' => true]);
  }

  public function setDnsZoneMinify($zoneId, $settings)
  {
    return $this->patch('/zones/' . $zoneId . '/settings/minify', ['value' => $settings]);
  }

  public function createDnsRecord($zoneId, $type, $name, $content, array $params = [])
  {
    $params = array_merge($params, [
      'type'    => $type,
      'name'    => $name,
      'content' => $content,
    ]);
    return $this->post('/zones/' . $zoneId . '/dns_records', $params);
  }

  public function updateDnsRecord($zoneId, $recordId, array $params = [])
  {
    return $this->put('/zones/' . $zoneId . '/dns_records/' . $recordId, $params);
  }

  public function deleteDnsRecord($zoneId, $recordId)
  {
    return $this->delete('/zones/' . $zoneId . '/dns_records/' . $recordId);
  }
}