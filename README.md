# Playpal

A PHP and MySQL gaming marketplace prototype for listing game-related products and rental options, collecting purchase or rental requests, running giveaways, receiving customer suggestions, and managing catalogue content through an administrative interface.

## Features

### Public experience

- Browse products by game
- View product images, prices, sale pricing, and rental options
- Submit purchase or rental requests
- Enter active giveaways
- Submit game and product suggestions
- Responsive interface built with Tailwind CSS
- Animated content using AOS

### Administration

- Add and edit products
- Upload multiple product images
- Manage games, giveaways, and prizes
- Review transactions and customer suggestions
- View basic visitor statistics
- Admin and moderator roles

## Technology

- PHP 7.4+
- MySQL or MariaDB
- MySQLi prepared statements
- HTML, JavaScript, and Tailwind CSS
- Font Awesome and AOS

## Repository structure

```text
playpal-shop/
├── playpal/          # PHP application
├── playpal_db.sql    # Sanitized development schema
└── .env.example      # Configuration reference
```

## Local setup

1. Clone the repository:

   ```bash
   git clone https://github.com/ICARUSTUDIO/playpal-shop.git
   cd playpal-shop
   ```

2. Create a database named `playpal_db` and import the sanitized schema:

   ```bash
   mysql -u root -p playpal_db < playpal_db.sql
   ```

3. Configure the environment variables listed in `.env.example` through your shell, local server, hosting dashboard, or container configuration.

4. Start a local PHP server from the application directory:

   ```bash
   cd playpal
   php -S localhost:8000
   ```

5. Open `http://localhost:8000`.

> Plain PHP does not automatically load `.env` files. The example file documents the required values; set them in the actual server environment or add an environment loader.

## Configuration

| Variable | Purpose | Local default |
| --- | --- | --- |
| `PLAYPAL_DB_HOST` | MySQL host | `localhost` |
| `PLAYPAL_DB_USER` | MySQL user | `root` |
| `PLAYPAL_DB_PASS` | MySQL password | empty |
| `PLAYPAL_DB_NAME` | Database name | `playpal_db` |
| `PLAYPAL_TIMEZONE` | PHP timezone | `UTC` |

## Data and security

The repository now contains a schema-only database file. Production visitor records, session identifiers, IP addresses, submissions, transactions, and administrative credentials must never be committed.

The current administrative authentication is legacy code and should not be exposed publicly as-is. Before deployment, replace plaintext password comparison with `password_hash()` and `password_verify()`, regenerate the session ID after login, disable browser error display, add CSRF protection, rate-limit login attempts, and validate uploaded files using server-side MIME inspection.

No default administrative user is included. This is intentional: create accounts only after the authentication flow has been upgraded.

## Project status

Playpal is presented as a portfolio prototype demonstrating PHP/MySQL CRUD workflows, relational data modelling, prepared statements, file uploads, responsive UI, form handling, and administrative tooling. It is not currently production-ready.
