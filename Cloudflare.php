<?php
/**
 * CloudFlare API
 *
 * @author AzzA <azza@broadcasthe.net>
 * @copyright omgwtfhax inc. 2011
 * @version 1.0
 */
class Cloudflare {

    //The URL of the API
    private $url = array(
      'user' => 'https://www.cloudflare.com/api_json.html',
      'host' => 'https://api.cloudflare.com/host-gw.html'
    );

    //Timeout for the API requests in seconds
    const TIMEOUT = 5;

    //Interval values for Stats
    const INTERVAL_365_DAYS = 10;
    const INTERVAL_30_DAYS = 20;
    const INTERVAL_7_DAYS = 30;
    const INTERVAL_DAY = 40;
    const INTERVAL_24_HOURS = 100;
    const INTERVAL_12_HOURS = 110;
    const INTERVAL_6_HOURS = 120;

    //Stores the api key
    private $token_key;
    private $host_key;

    //Stores the email login
    private $email;

    /**
     * Make a new instance of the API client
     */
    public function __construct() {
        $parameters = func_get_args();
        switch (func_num_args()) {
            case 1:
                //a host API
                $this->host_key  = $parameters[0];
                break;
            case 2:
                //a user request
                $this->email     = $parameters[0];
                $this->token_key = $parameters[1];
                break;
        }
    }

    public function setEmail($email) {
        $this->email = $email;
    }

    public function setToken($token_key) {
        $this->token_key = $token_key;
    }

    /**
     * CLIENT API
     * Section 3
     * Access
     */

    /**
     * 3.1 - Retrieve Domain Statistics For A Given Time Frame
     * This function retrieves the current stats and settings for a particular website.
     * It can also be used to get currently settings of values such as the security level.
     */
    public function stats($domain, $interval = 20) {
        $data['a']        = 'stats';
        $data['z']        = $domain;
        $data['interval'] = $interval;
        return $this->http_post($data);
    }

    /**
     * 3.2 - Retrieve A List Of The Domains
     * This lists all domains in a CloudFlare account along with other data.
     */
    public function zone_load_multi() {
        $data['a']        = 'zone_load_multi';
        return $this->http_post($data);
    }

    /**
     * 3.3 - Retrieve DNS Records Of A Given Domain
     * This function retrieves the current stats and settings for a particular website.
     * It can also be used to get currently settings of values such as the security level.
     */
    public function rec_load_all($domain) {
        $data['a']        = 'rec_load_all';
        $data['z']        = $domain;
        return $this->http_post($data);
    }

    /**
     * 3.4 - Checks For Active Zones And Returns Their Corresponding Zids
     * This function retrieves domain statistics for a given time frame.
     */
    public function zone_check($zones) {
        if (is_array($zones)) {
            $zones = implode(',', $zones);
        }
        $data['a']     = 'zone_check';
        $data['zones'] = $zones;
        return $this->http_post($data);
    }

    /**
     * 3.5 - Pull Recent IPs Visiting Your Site
     * This function returns a list of IP address which hit your site classified by type.
     * $zoneid = ID of the zone you would like to check.
     * $hours = Number of hours to go back. Default is 24, max is 48.
     * $class = Restrict the result set to a given class. Currently r|s|t, for regular, crawler, threat resp.
     * $geo = Optional token. Add to add longitude and latitude information to the response. 0,0 means no data.
     */
    public function zone_ips($domain, $hours, $class, $geo = '0,0') {
        $data['a']     = 'zone_ips';
        $data['z']     = $domain;
        $data['hours'] = $hours;
        $data['class'] = $class;
        $data['geo']   = $geo;
        return $this->http_post($data);
    }

    /**
     * 3.6 - Check The Threat Score For A Given IP
     * This function retrieves the current threat score for a given IP.
     * Note that scores are on a logarithmic scale, where a higher score indicates a higher threat.
     */
    public function threat_score($ip) {
        $data['a']  = 'ip_lkup';
        $data['ip'] = $ip;
        return $this->http_post($data);
    }

    /**
     * 3.7 - List All The Current Settings
     * This function retrieves all the current settings for a given domain.
     */
    public function zone_settings($domain) {
        $data['a'] = 'zone_settings';
        $data['z'] = $domain;
        return $this->http_post($data);
    }

    /**
     * CLIENT API
     * Section 4
     * Modify
     */

    /**
     * 4.1 - Set The Security Level
     * This function sets the Basic Security Level to I'M UNDER ATTACK! / HIGH / MEDIUM / LOW / ESSENTIALLY OFF.
     * The switches are: (high|med|low|eoff).
     */
    public function sec_lvl($domain, $mode) {
        $data['a'] = 'sec_lvl';
        $data['z'] = $domain;
        $data['v'] = $mode;
        return $this->http_post($data);
    }

    /**
     * 4.2 - Set The Cache Level
     * This function sets the Caching Level to Aggressive or Basic.
     * The switches are: (agg|basic).
     */
    public function cache_lvl($domain, $mode) {
        $data['a'] = 'cache_lvl';
        $data['z'] = $domain;
        $data['v'] = ($mode == 'agg') ? 'agg' : 'basic';
        return $this->http_post($data);
    }

    /**
     * 4.3 - Toggling Development Mode
     * This function allows you to toggle Development Mode on or off for a particular domain.
     * When Development Mode is on the cache is bypassed.
     * Development mode remains on for 3 hours or until when it is toggled back off.
     */
    public function devmode($domain, $mode) {
        $data['a'] = 'devmode';
        $data['z'] = $domain;
        $data['v'] = ($mode == true) ? 1 : 0;
        return $this->http_post($data);
    }

    /**
     * 4.4 - Clear CloudFlare's Cache
     * This function will purge CloudFlare of any cached files.
     * It may take up to 48 hours for the cache to rebuild and optimum performance to be achieved.
     * This function should be used sparingly.
     */
    public function fpurge_ts($domain) {
        $data['a'] = 'fpurge_ts';
        $data['z'] = $domain;
        $data['v'] = 1;
        return $this->http_post($data);
    }

    /**
     * 4.5 - Purge A Single File In CloudFlare's Cache
     * This function will purge a single file from CloudFlare's cache.
     */
    public function zone_file_purge($domain, $url) {
        $data['a'] = 'zone_file_purge';
        $data['z'] = $domain;
        $data['url'] = $url;
        return $this->http_post($data);
    }

    /**
     * 4.6 - Update The Snapshot Of Your Site
     * This snapshot is used on CloudFlare's challenge page
     * This function tells CloudFlare to take a new image of your site.
     * Note that this call is rate limited to once per zone per day.
     * Also the new image may take up to 1 hour to appear.
     */
    public function update_image($zoneid) {
        $data['a']   = 'zone_grab';
        $data['zid'] = $zoneid;
        return $this->http_post($data);
    }

    /**
     * 4.7a - Whitelist IPs
     * You can add an IP address to your whitelist.
     */
    public function wl($ip) {
        $data['a']   = 'wl';
        $data['key'] = $ip;
        return $this->http_post($data);
    }

    /**
     * 4.7b - Blacklist IPs
     * You can add an IP address to your blacklist.
     */
    public function ban($ip) {
        $data['a']   = 'ban';
        $data['key'] = $ip;
        return $this->http_post($data);
    }

    /**
     * 4.7c - Unlist IPs
     * You can remove an IP address from the whitelist and the blacklist.
     */
    public function nul($ip) {
        $data['a']   = 'nul';
        $data['key'] = $ip;
        return $this->http_post($data);
    }

    /**
     * 4.8 - Toggle IPv6 Support
     * This function toggles IPv6 support.
     */
    public function ipv46($domain, $mode) {
        $data['a'] = 'ipv46';
        $data['z'] = $domain;
        $data['v'] = ($mode == true) ? 1 : 0;
        return $this->http_post($data);
    }

    /**
     * 4.9 - Set Rocket Loader
     * This function changes Rocket Loader setting.
     */
    public function async($domain, $mode) {
        $data['a'] = 'async';
        $data['z'] = $domain;
        $data['v'] = $mode;
        return $this->http_post($data);
    }

    /**
     * 4.10 - Set Minification
     * This function changes minification settings.
     */
    public function minify($domain, $mode) {
        $data['a'] = 'minify';
        $data['z'] = $domain;
        $data['v'] = $mode;
        return $this->http_post($data);
    }

    /**
     * CLIENT API
     * Section 5
     * DNS Record Management
     */

    /**
     * 5.1 - Add A New DNS Record
     * This function creates a new DNS record for a zone.
     *
     * @link http://www.cloudflare.com/docs/client-api.html#s5.1 for documentation.
     *
     * @param string $domain   The target domain
     * @param string $type     Type of DNS record. Values include: [A/CNAME/MX/TXT/SPF/AAAA/NS/SRV/LOC]
     * @param string $name     Name of the DNS record.
     * @param string $content  The content of the DNS record, will depend on the the type of record being added
     * @param int    $ttl      TTL of record in seconds. 1 = Automatic, otherwise, value must in between 120 and 4,294,967,295 seconds.
     * @param int    $mode
     * @param int    $prio     MX record priority.
     * @param int    $service  Service for SRV record
     * @param int    $srvname  Service Name for SRV record
     * @param int    $protocol Protocol for SRV record. Values include: [_tcp/_udp/_tls].
     * @param int    $weight   Weight for SRV record.
     * @param int    $port     Port for SRV record
     * @param int    $target   Target for SRV record
     * @return array|mixed
     */
    public function rec_new($domain, $type, $name, $content, $ttl = 1, $mode = 1, $prio = 1, $service = 1, $srvname = 1, $protocol = 1, $weight = 1, $port = 1, $target = 1) {
        $data['a']            = 'rec_new';
        $data['z']            = $domain;
        $data['type']         = $type;
        $data['name']         = $name;
        $data['content']      = $content;
        $data['ttl']          = $ttl;

        if ($type == 'A' OR $type == 'AAAA' OR $type == 'CNAME') {
            $data['service_mode'] = ($mode == true) ? 1 : 0;
        }

        if ($type == 'MX' OR $type == 'SRV') {
            $data['prio'] = $prio;
        }

        if ($type == 'SRV') {
            $data['service'] = $service;
            $data['srvname'] = $srvname;
            $data['protocol'] = $protocol;
            $data['weight'] = $weight;
            $data['port'] = $port;
            $data['target'] = $target;
        }

        return $this->http_post($data);
    }

    /**
     * 5.2 - Edit A DNS Record
     * This function edits a DNS record for a zone.
     * See http://www.cloudflare.com/docs/client-api.html#s5.1 for documentation.
     * @param string $domain   The target domain
     * @param string $type     Type of DNS record. Values include: [A/CNAME/MX/TXT/SPF/AAAA/NS/SRV/LOC]
     * @param int $id          DNS Record ID. Available by using the rec_load_all call.
     * @param string $name     Name of the DNS record.
     * @param string $content  The content of the DNS record, will depend on the the type of record being added
     * @param int $ttl      TTL of record in seconds. 1 = Automatic, otherwise, value must in between 120 and 4,294,967,295 seconds.
     * @param int $mode
     * @param int $prio     MX record priority.
     * @param int $service  Service for SRV record
     * @param int $srvname  Service Name for SRV record
     * @param int $protocol Protocol for SRV record. Values include: [_tcp/_udp/_tls].
     * @param int $weight   Weight for SRV record.
     * @param int $port     Port for SRV record
     * @param int $target   Target for SRV record
     * @return array|mixed
     */
    public function rec_edit($domain, $type, $id, $name, $content, $ttl = 1, $mode = 1, $prio = 1, $service = 1, $srvname = 1, $protocol = 1, $weight = 1, $port = 1, $target = 1) {
        $data['a']            = 'rec_edit';
        $data['z']            = $domain;
        $data['type']         = $type;
        $data['id']         = $id;
        $data['name']         = $name;
        $data['content']      = $content;
        $data['ttl']          = $ttl;

        if ($type == 'A' OR $type == 'AAAA' OR $type == 'CNAME') {
            $data['service_mode'] = ($mode == true) ? 1 : 0;
        }
        if ($type == 'MX' OR $type == 'SRV') {
            $data['prio'] = $prio;
        }
        if ($type == 'SRV') {
            $data['service'] = $service;
            $data['srvname'] = $srvname;
            $data['protocol'] = $protocol;
            $data['weight'] = $weight;
            $data['port'] = $port;
            $data['target'] = $target;
        }
        return $this->http_post($data);
    }

    /**
     * 5.3 - Delete A DNS Record
     * This function deletes a DNS record for a zone.
     * $zone = zone
     * $id = The DNS Record ID (Available by using the rec_load_all call)
     * $type = A|CNAME
     */
    public function rec_delete($domain, $id) {
        $data['a']            = 'rec_delete';
        $data['z']            = $domain;
        $data['id']           = $id;
        return $this->http_post($data);
    }

    /**
     * HOST API
     * Section 3
     * Specific Host Provider Operations
     */

    public function user_create($email, $password, $username = '', $id = '') {
        $data['act']                 = 'user_create';
        $data['cloudflare_email']    = $email;
        $data['cloudflare_pass']     = $password;
        $data['cloudflare_username'] = $username;
        $data['unique_id']           = $id;
        return $this->http_post($data, 'host');
    }

    public function zone_set($key, $zone, $resolve_to, $subdomains) {
        if (is_array($subdomains)) {
            $subdomains = implode(',', $subdomains);
        }
        $data['act']        = 'zone_set';
        $data['user_key']   = $key;
        $data['zone_name']  = $zone;
        $data['resolve_to'] = $resolve_to;
        $data['subdomains'] = $subdomains;
        return $this->http_post($data, 'host');
    }

    public function user_lookup($email, $isID = false) {
        $data['act'] = 'user_lookup';
        if ($isID) {
            $data['unique_id'] = $email;
        } else {
            $data['cloudflare_email'] = $email;
        }
        return $this->http_post($data, 'host');
    }

    public function user_auth($email, $password, $id = '') {
        $data['act']              = 'user_auth';
        $data['cloudflare_email'] = $email;
        $data['cloudflare_pass']  = $password;
        $data['unique_id']        = $id;
        return $this->http_post($data, 'host');
    }

    public function zone_lookup($zone, $user_key) {
        $data['act']       = 'zone_lookup';
        $data['user_key']  = $user_key;
        $data['zone_name'] = $zone;
        return $this->http_post($data, 'host');
    }

    public function zone_delete($zone, $user_key) {
        $data['act']       = 'zone_delete';
        $data['user_key']  = $user_key;
        $data['zone_name'] = $zone;
        return $this->http_post($data, 'host');
    }

    /**
     * API Call
     * HTTP POST a specific task with the supplied data
     */
    private function http_post($data, $type = 'user') {
        switch ($type) {
            case 'user':
                $data['u']   = $this->email;
                $data['tkn'] = $this->token_key;
                break;
            case 'host':
                $data['host_key'] = $this->host_key;
                break;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_URL, $this->url[$type]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $http_result = curl_exec($ch);
        $error       = curl_error($ch);
        $http_code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($http_code != 200) {
            return array(
                'error' => $error
            );
        } else {
            return json_decode($http_result, true);
        }
    }
}
