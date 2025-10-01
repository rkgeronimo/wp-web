# Database Migration Tool

A simple, WordPress-integrated database migration tool for the Geronimo project.

## Features

- ✅ Run SQL and PHP-based migrations
- ✅ Track migration history with batch numbers
- ✅ Rollback migrations to any point
- ✅ View migration status
- ✅ Create new migration files from CLI
- ✅ Initial schema from `geronimo_basic.sql`

## Installation

The migration system is already set up. The tool creates a `wp_schema_migrations` table automatically to track which migrations have been executed.

## Usage

All commands are run from the project root:

### Run Migrations

Execute all pending migrations:

```bash
php database/migrate.php migrate
```

### Rollback Migrations

Rollback the last batch of migrations:

```bash
php database/migrate.php rollback
```

Rollback multiple batches:

```bash
php database/migrate.php rollback --steps=2
```

### Check Migration Status

View which migrations have been executed:

```bash
php database/migrate.php status
```

### Create New Migration

Create a new migration file:

```bash
php database/migrate.php create add_user_preferences_table
```

This creates a timestamped file in `database/migrations/` with the format:
`YYYY_MM_DD_HHMMSS_add_user_preferences_table.php`

## Migration Files

Migrations are stored in [`database/migrations/`](database/migrations/) and must follow the naming pattern:

```
YYYY_MM_DD_HHMMSS_description.(sql|php)
```

### SQL Migrations

SQL files contain raw SQL statements:

```sql
-- 2024_01_01_000000_initial_schema.sql
CREATE TABLE wp_example (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255)
);
```

**Note:** SQL migrations cannot be rolled back automatically.

### PHP Migrations

PHP migrations provide more flexibility and support rollback:

```php
<?php
// 2024_10_01_120000_add_courses_table.php

return [
    'up' => function($wpdb) {
        $charset = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . 'courses';

        $sql = "CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) {$charset}";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    },

    'down' => function($wpdb) {
        $tableName = $wpdb->prefix . 'courses';
        $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
    }
];
```

## Initial Schema

The initial database schema from [`geronimo_basic.sql`](../geronimo_basic.sql) has been imported as:

```
database/migrations/2024_01_01_000000_initial_schema.sql
```

Run `php database/migrate.php migrate` to apply it.

## Example Workflow

1. **Check current status:**
   ```bash
   php database/migrate.php status
   ```

2. **Create a new migration:**
   ```bash
   php database/migrate.php create add_student_grades
   ```

3. **Edit the migration file** in `database/migrations/`

4. **Run the migration:**
   ```bash
   php database/migrate.php migrate
   ```

5. **If something goes wrong, rollback:**
   ```bash
   php database/migrate.php rollback
   ```

## Best Practices

1. **Always create migrations for schema changes** - Never modify the database directly
2. **Test rollbacks** - Ensure your `down` method properly reverses the `up` method
3. **Keep migrations small** - One logical change per migration
4. **Use descriptive names** - Make it clear what each migration does
5. **Version control** - Commit migration files to git
6. **Run in order** - Migrations are executed in chronological order based on timestamp

## File Structure

```
database/
├── MigrationManager.php          # Core migration logic
├── migrate.php                   # CLI tool
├── migrations/                   # Migration files directory
│   ├── 2024_01_01_000000_initial_schema.sql
│   └── 2024_10_01_120000_example_migration.php
└── README.md                     # This file
```

## Troubleshooting

**Migration fails with SQL error:**
- Check the MySQL syntax in your migration
- Ensure table names use `$wpdb->prefix`
- Look at the error message for details

**Cannot rollback SQL migration:**
- SQL-only migrations don't support rollback
- Convert to PHP migration with proper `down` method

**WordPress not found error:**
- Ensure you're running from the correct directory
- Check that WordPress is installed at `web/wp/`
