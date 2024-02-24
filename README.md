# radiostat

## Requirements

* PHP >=8.3 with `curl`, `sqlite3`, `hash` and `json` extensions

## Usage

Simple usage. Reads stats and writes to database:
```shell
php -f ./src/stat.php <PATH_TO_DATABASE>
```

Everything at once:

```shell
# Parses stats, creates html-page and pushes it to Neocities
USER='<NEOCITIES_LOGIN>' PASS='<NEOCITIES_PASSWORD>' ./build.sh
```