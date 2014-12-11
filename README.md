# cloudflare-ddns

A dynamic DNS script that uses CloudFlare's free DNS and their API

## Setup

```
git clone https://github.com/lyoshenka/cloudflare-ddns.git
cd cloudflare-ddns
cp config.php.skel config.php
```
Edit config.php - enter your CloudFlare credentials and domain details.

```
./ddns.php
```

If everythings works, put it in your crontab.

```
0 * * * * /path/to/cloudflare-ddns/ddns.php -s
``` 

## License

Uncopyrighted. Do whatever you want. I hope this code makes you rich. Spiritually.
