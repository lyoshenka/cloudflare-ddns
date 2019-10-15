# cloudflare-ddns

A dynamic DNS script (written in PHP) that uses CloudFlare's free DNS and their API to set up a dynamic DNS record pointing to your server.

## How To Use

```
git clone https://github.com/lyoshenka/cloudflare-ddns.git
cd cloudflare-ddns
cp config.php.skel config.php
### Edit config.php - enter your CloudFlare credentials and domain details
./ddns.php
```

If everything works, put it in your crontab.

```
0 * * * * /path/to/cloudflare-ddns/ddns.php -s
``` 

### Extra feature: API mode

If you want, you can put this script on a web server and use the "dynamic DNS" option of your router to trigger the IP update.

To do this, first enable API mode by setting an `auth_token` value in your config. 

Then, configure your router to call the script like this: `https://example.com/ddns.php?auth_token=YOUR_TOKEN&ip=IP_ADDR`

With Mikrotik devices you can use this script https://github.com/Arcanum417/cloudflare-ddns-mikrotik

## License

Uncopyrighted. Do whatever you want. I hope this code makes you rich. Spiritually.

## No PHP?

For an even simpler version written in Bash, see [this gist](https://gist.github.com/lyoshenka/6257440).

## Development

Use [php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) like `php-cs-fixer fix ddns.php  --rules=@PSR2` to enforce code style.
