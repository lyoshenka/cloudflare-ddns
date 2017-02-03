# cloudflare-ddns

A dynamic DNS script (written in PHP) that uses CloudFlare's free DNS and their API to set up a dynamic DNS record pointing to your server.

## Setup

```
git clone https://github.com/lyoshenka/cloudflare-ddns.git
cd cloudflare-ddns
cp config.php.skel config.php
### Edit config.php - enter your CloudFlare credentials and domain details
```

### Local mode

Uses the public IP of the machine you're running this on to update your CF record.

```
./ddns.php
```

If everything works, put it in your crontab.

```
0 * * * * /path/to/cloudflare-ddns/ddns.php -s
``` 

### API mode

You can put this script on a server and use the DynDns option of your router to trigger the IP update.

To do this set ``auth_token`` in your config which enables API mode.

Then you can call the script like this: ``yourdomain/ddns.php?auth_token=yourtoken&ip=127.0.0.1``

## License

Uncopyrighted. Do whatever you want. I hope this code makes you rich. Spiritually.

## No PHP?

For an even simpler version written in Bash, see [this gist](https://gist.github.com/lyoshenka/6257440).
