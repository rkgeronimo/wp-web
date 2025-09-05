# RK Geronimo WordPress (Bedrock) — Local Docker setup

This guide explains how to run the RK Geronimo WordPress app locally using **Docker**.

---

## Prerequisites

- Docker
- Git
- `geronimo_basic.sql` database dump available in the project root (needed for initial data).

---

## Quick start (Docker)

# 1. Clone the repository
```bash
git clone https://github.com/rkgeronimo/wp-web.git
cd wp-web
```

# 2. Create environment file for Docker
```bash
cp .env.docker-example.local .env
```

# 3. Start the Docker stack
```bash
docker compose up -d --build
```

# 4. Install PHP dependencies
```bash
docker compose run --rm php composer install
```

# 5. Install JS dependencies & build assets
```bash
docker compose run --rm node sh -lc "yarn install && ./node_modules/.bin/gulp"
```

# 6. Update URLs in the database
```bash
docker compose run --rm wpcli wp search-replace 'http://local.rkgeronimo' 'http://localhost:8080' --all-tables --skip-columns=guid
```

# 7. (Optional) Install SMTP plugin
```bash
docker compose run --rm wpcli wp plugin install wp-mail-smtp --activate
```

---

## Access

- **WordPress:** [http://localhost:8080](http://localhost:8080)  
- **MailHog (emails):** [http://localhost:8025](http://localhost:8025)

---

## `.env.docker-example.local`

This file contains the default environment variables for running the app via Docker.  
It is separate from `.env.example` to avoid conflicts with the non-Docker setup.

Example contents:

```env
WP_ENV=development
WP_HOME=http://localhost:8080
WP_SITEURL=${WP_HOME}/wp

DB_NAME=bedrock
DB_USER=bedrock
DB_PASSWORD=bedrock
DB_HOST=db

AUTH_KEY='dev'
SECURE_AUTH_KEY='dev'
LOGGED_IN_KEY='dev'
NONCE_KEY='dev'
AUTH_SALT='dev'
SECURE_AUTH_SALT='dev'
LOGGED_IN_SALT='dev'
NONCE_SALT='dev'
```

---

## Troubleshooting

- **CSS/JS not loading (404)**  
  Run the asset build command again:
  ```bash
  docker compose run --rm node sh -lc "./node_modules/.bin/gulp"
  ```

- **Missing uploads**  
  Copy the `web/app/uploads` folder from staging/production into your local environment.

- **Database still points to old domain**  
  Re-run the search/replace command:
  ```bash
  docker compose run --rm wpcli wp search-replace 'http://local.rkgeronimo' 'http://localhost:8080' --all-tables --skip-columns=guid
  ```

---

## Demo credentials

- **Admin:**  
  Username: `demo`  
  Password: `demo123`

- **Instructor:**  
  Username: `instruktor`  
  Password: `instruktor123`

---

© RK Geronimo, Zagreb
