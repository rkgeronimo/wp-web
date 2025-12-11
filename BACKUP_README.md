# WordPress Backup Script

A production-ready Python backup script that creates compressed MySQL database dumps and uploads folder backups, uploads them to Dropbox (with pluggable provider architecture for future extensibility), and automatically deletes backups older than 7 days.

## Features

- ✅ **Database Backup**: MySQL dump with gzip compression
- ✅ **Uploads Backup**: Compressed tar archive of WordPress uploads folder
- ✅ **Dropbox Integration**: Automatic upload to Dropbox with chunked upload support for large files
- ✅ **Smart Cleanup**: Deletes backups older than 7 days when uploads succeed
- ✅ **Safety Net**: Keeps at least 1 backup of each type when uploads fail (prevents data loss)
- ✅ **Dry-Run Mode**: Test without actual uploads or deletions
- ✅ **Provider Abstraction**: Easy to add AWS S3, Google Drive, or other storage providers
- ✅ **Comprehensive Logging**: Detailed logs with file sizes and progress

**Quick start:**
```bash
# Test backup script (dry-run mode)
python3 backup_script.py --dry-run --verbose

# Run actual backup (after configuration)
python3 backup_script.py
```

## Requirements

- Python 3.7+
- MySQL/MariaDB
- mysqldump binary
- Dropbox account (for Dropbox provider)

## Installation

### 1. Install Python Dependencies

```bash
cd /path/to/wp-web
/usr/local/bin/python3 -m pip install -r requirements.txt --break-system-packages
```

### 2. Configure Dropbox OAuth

To use Dropbox with OAuth 2.0 refresh tokens:

1. Follow the instructions at: https://www.codemzy.com/blog/dropbox-long-lived-access-refresh-token
2. You will obtain:
   - `DROPBOX_APP_KEY` (your app's key)
   - `DROPBOX_APP_SECRET` (your app's secret)
   - `DROPBOX_REFRESH_TOKEN` (long-lived refresh token)
3. Add these to your `.env` file

The Dropbox SDK automatically handles access token refresh using the refresh token.

### 3. Configure Environment Variables

Edit `.env` file and update these values:

```bash
# Dropbox OAuth Configuration
DROPBOX_APP_KEY=your_app_key_here
DROPBOX_APP_SECRET=your_app_secret_here
DROPBOX_REFRESH_TOKEN=your_refresh_token_here
```

### 4. Test the Backup Script

Test without uploading to Dropbox:

```bash
/usr/local/bin/python3 backup_script.py --dry-run --verbose
```

Test actual backup (will upload to Dropbox):

```bash
/usr/local/bin/python3 backup_script.py --verbose
```

## Usage

### Command Line Options

```bash
python3 backup_script.py [OPTIONS]

Options:
  --config PATH      Path to .env configuration file (default: .env)
  --dry-run          Run in dry-run mode (no uploads or deletions)
  --verbose, -v      Enable verbose (DEBUG) logging
  --log-file PATH    Path to log file (logs to stdout if not specified)
  --help, -h         Show help message
```

### Examples

```bash
# Run backup with default config
python3 backup_script.py

# Test without uploading
python3 backup_script.py --dry-run --verbose

# Specify custom config file
python3 backup_script.py --config /path/to/.env

# Write logs to file
python3 backup_script.py --log-file /var/log/backup.log
```

## Scheduling with Cron

### Setup Daily Backups at 2 AM

1. Edit crontab:
```bash
crontab -e
```

2. Add this line (adjust the path to your wp-web directory):
```cron
0 2 * * * cd /path/to/wp-web && /usr/local/bin/python3 backup_script.py >> /var/log/wordpress_backup.log 2>&1
```

3. Verify crontab:
```bash
crontab -l
```

### Setup Log Rotation

Create `/etc/logrotate.d/wordpress_backup`:

```
/var/log/wordpress_backup.log {
    daily
    rotate 30
    compress
    missingok
    notifempty
    create 0644 root root
}
```

## Configuration Reference

### Backup Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `BACKUP_PROVIDER` | `dropbox` | Storage provider (dropbox, s3, local) |
| `BACKUP_RETENTION_DAYS` | `7` | Number of days to retain backups |
| `BACKUP_TEMP_DIR` | `/tmp/backups` | Temporary directory for backup files |
| `BACKUP_UPLOADS_PATH` | `web/app/uploads` | Path to uploads folder (relative to wp-web) |
| `MYSQLDUMP_PATH` | `/usr/local/bin/mysqldump` | Path to mysqldump binary |
| `BACKUP_DRY_RUN` | `false` | Enable dry-run mode |

### Dropbox Configuration

| Variable | Default | Description |
|----------|---------|-------------|
| `DROPBOX_APP_KEY` | *(required)* | Dropbox application key |
| `DROPBOX_APP_SECRET` | *(required)* | Dropbox application secret |
| `DROPBOX_REFRESH_TOKEN` | *(required)* | OAuth 2.0 refresh token |
| `DROPBOX_BACKUP_FOLDER` | `/backups` | Remote folder path in Dropbox |


## Backup File Naming

Backups are named with timestamps for easy identification:

- **Database**: `geronimo_database_20251209_143022.sql.gz`
- **Uploads**: `uploads_20251209_143022.tar.gz`

Format: `{name}_{YYYYMMDD}_{HHMMSS}.{ext}`

## Backup Retention Policy

The script automatically manages backup retention with intelligent safety rules:

### When Current Backup Succeeds
- **Clean up aggressively**: All backups older than `BACKUP_RETENTION_DAYS` (default: 7 days) are deleted
- **No minimum retention needed**: Since fresh backups exist, old ones can be safely removed

### When Current Backup Fails
- **Safety net activated**: At least **1 database backup** and **1 uploads backup** are kept, regardless of age
- **Prevents data loss**: Even if backups fail for weeks, you'll always have the most recent successful backup

**Example scenarios:**

| Scenario | Behavior |
|----------|----------|
| Fresh backup uploaded successfully | All backups >7 days deleted (no minimum retention) |
| Backup upload fails for 10 days | Oldest successful backup kept despite being >7 days old |
| Upload succeeds after failure period | Old backup (10 days) deleted, new backup kept |
| Multiple old backups, upload fails | Deletes oldest backups but keeps newest 1 of each type |

This ensures you never lose all backups due to temporary issues, while keeping storage clean when backups work normally.

## Restoration

### Restore Database

```bash
# Download backup from Dropbox
# Extract and restore

# Decompress
gunzip geronimo_database_20251209_143022.sql.gz

# Restore to MySQL
mysql -u $DB_USER -p $DB_NAME < geronimo_database_20251209_143022.sql
```

### Restore Uploads

```bash
# Download backup from Dropbox
# Extract uploads

# Backup existing uploads (optional)
mv web/app/uploads /tmp/uploads_old

# Extract archive (run from wp-web directory)
tar -xzf uploads_20251209_143022.tar.gz -C web/app/

# Set proper permissions
chown -R www-data:www-data web/app/uploads
chmod -R 755 web/app/uploads
```

## Adding New Storage Providers

The script uses a provider abstraction pattern that makes it easy to add new storage backends.

### Example: Adding AWS S3

1. Create `backup/providers/s3_provider.py`:

```python
import boto3
from .base import StorageProvider

class S3Provider(StorageProvider):
    def __init__(self, config: dict):
        super().__init__(config)
        self.bucket_name = config.get('s3_bucket_name')
        self.s3_client = boto3.client('s3')

    def upload(self, local_path: str, remote_path: str) -> bool:
        # S3-specific implementation
        pass

    # Implement other abstract methods...
```

2. Add S3 config to `backup/config.py`:

```python
self.s3_bucket_name = os.getenv('S3_BUCKET_NAME')
self.s3_access_key = os.getenv('S3_ACCESS_KEY')
self.s3_secret_key = os.getenv('S3_SECRET_KEY')
```

3. Update factory method in `backup_script.py`:

```python
def _get_storage_provider(self):
    if provider_name == 's3':
        from backup.providers.s3_provider import S3Provider
        return S3Provider(self.config.to_dict())
```

4. Add credentials to `.env`:

```bash
BACKUP_PROVIDER=s3
S3_BUCKET_NAME=my-wordpress-backups
S3_ACCESS_KEY=your_access_key
S3_SECRET_KEY=your_secret_key
```

## Troubleshooting

### Database Backup Fails

**Error**: `Access denied; you need (at least one of) the PROCESS privilege(s)`

**Solution**: The script uses `--no-tablespaces` flag to avoid this issue. If you still encounter errors, ensure your database user has sufficient privileges:

```sql
GRANT SELECT, LOCK TABLES, SHOW VIEW ON geronimo.* TO 'geronimo'@'localhost';
FLUSH PRIVILEGES;
```

### Dropbox Upload Fails

**Error**: `Dropbox authentication failed`

**Solution**:
- Verify your `DROPBOX_ACCESS_TOKEN` is correct
- Check if the token has expired
- Ensure your Dropbox app has the required permissions


### Disk Space Issues

**Error**: `No space left on device`

**Solution**:
- Change `BACKUP_TEMP_DIR` to a location with more space
- Reduce retention period to keep fewer backups
- Clean up old backups manually from Dropbox

## Monitoring

### Check Backup Logs

```bash
# View recent backup logs
tail -100 /var/log/wordpress_backup.log

# Search for errors
grep -i error /var/log/wordpress_backup.log

# Check cron execution
grep CRON /var/log/syslog | grep backup
```

### Verify Backups in Dropbox

1. Log in to Dropbox web interface
2. Navigate to `/backups` folder
3. Verify recent backups are present
4. Check file sizes are reasonable
5. Verify old backups are being deleted

## Security Considerations

- ⚠️ **Never** commit `.env` file to git (already in `.gitignore`)
- ⚠️ Set restrictive permissions: `chmod 600 .env`
- ⚠️ Ensure Dropbox folder is NOT publicly shared
- ⚠️ Rotate Dropbox access token periodically
- ⚠️ Run script as WordPress user (not root)
- ⚠️ Consider encrypting backups before upload (future enhancement)

## Architecture

### Module Structure

```
wp-web/
├── backup_script.py              # Main entry point with CLI
├── backup/                       # Python package
│   ├── __init__.py
│   ├── config.py                 # Configuration management
│   ├── database.py               # MySQL backup with mysqldump + gzip
│   ├── filesystem.py             # Uploads folder tar.gz compression
│   └── providers/                # Storage provider implementations
│       ├── __init__.py
│       ├── base.py               # Abstract StorageProvider base class
│       ├── dropbox_provider.py   # Dropbox API implementation
│       └── local_provider.py     # Local storage for testing
├── requirements.txt              # Python dependencies
└── .env                          # Configuration (relative paths)
```

### Design Patterns

- **Abstract Base Class**: `StorageProvider` defines interface for all storage backends
- **Factory Pattern**: Main script selects provider based on `BACKUP_PROVIDER` env variable
- **Dependency Injection**: Pass config to all modules for testability

## Future Enhancements

- [ ] Additional storage providers (AWS S3, Google Drive, Backblaze B2)
- [ ] GPG encryption for backups
- [ ] Multi-provider uploads (upload to multiple services simultaneously)
- [ ] Incremental backups (only backup changed files)
- [ ] Failure/Success notifications (optional email on successful or failed backups)
- [ ] Backup verification (test restore process automatically)
- [ ] Web dashboard for monitoring backup status

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review logs: `/var/log/wordpress_backup.log`
3. Test with `--dry-run --verbose` mode
4. Check Dropbox API status: https://status.dropbox.com/

## License

This script is provided as-is for use with your WordPress installation.
