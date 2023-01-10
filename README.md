# RK Geronimo Wordpress App

This repository contains Wordpress app, initially created using [https://github.com/roots/bedrock/](Bedrock) stack.
It depends on multiple other repositories like plugins and theme that are added as submodules (no need to download them separately).

## Requirements

- PHP >= 7.4
- MySQL
- Composer - [Install](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
- Yarn - [Install](https://classic.yarnpkg.com/lang/en/docs/install/)

## Setting Up Development Environment

Before running the commands make sure to install all the requirements mentioned above.

To install and setup everything, run the following commands:

1. `yarn` - Installs NPM requirements from the `package.json` file
2. `composer install` - Installs PHP requirements from  the`composer.json` file
3. `./node_modules/.bin/gulp` - Builds static files (js, css)
4. Build additional custom plugins - `cd web/app/plugins/rkg-gallery && yarn install && yarn build` *(this part should be automated in the future)*
5. `cp .env.example .env` - Create environment file and define variables (below explained)
6. Make sure all Wordpress permissions are allright, there could be an issue with `uploads` directory. If so, run `chmod -R 755 web/app/uploads` and make sure it's owned by the right group e.g. `chown -R www-data:www-data web/app/uploads`
7. Import app database [geronimo_basic.sql](eronimo_basic.sql) to your newly created MySQL database
8. Install plugin `wp-mail-smtp`, manually or using WP CLI `wp plugin install wp-mail-smtp --activate`
9. Setup Apache virtual host `http://local.rkgeronimo` the document root on the webserver to the `web` folder: `/path/to/site/web/`
10. Map the hostname by editing your local `/etc/hosts` file and adding entry for the domain (http://local.rkgeronimo) next to localhost.


Note: If you are using other virual host and other domain, then domain replacements in the database should be made. This can be easily done through [WP-CLI](https://wp-cli.org): `wp search-replace 'http://local.rkgeronimo' 'new'`. WP-CLI plugin can be used for similar data updates e.g. user password changing.

### Demo credentials

Super Administrator:
Username: demo
Password: demo123

Instructor:
Username: instruktor
Password: instruktor123


### Environment variables

Environment variables in the `.env` file. Wrap values that may contain non-alphanumeric characters with quotes, or they may be incorrectly parsed.

- Database variables
  - `DB_NAME` - Database name
  - `DB_USER` - Database user
  - `DB_PASSWORD` - Database password
  - `DB_HOST` - Database host
  - Optionally, you can define `DATABASE_URL` for using a DSN instead of using the variables above (e.g. `mysql://user:password@127.0.0.1:3306/db_name`)
- `WP_ENV` - Set to environment (`development`, `staging`, `production`)
- `WP_HOME` - Full URL to WordPress home (https://example.com)
- `WP_SITEURL` - Full URL to WordPress including subdirectory (https://example.com/wp)
- `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`, `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT`
  - Generate with [wp-cli-dotenv-command](https://github.com/aaemnnosttv/wp-cli-dotenv-command)
  - Generate with the [WordPress salts generator](https://roots.io/salts.html)


## Development Docs

- JavaScript / CSS changes (watcher and build)
- Plugin building

TBD