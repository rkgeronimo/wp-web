"""
Configuration management for backup script
"""

import os
from typing import Dict, List, Optional
from dotenv import load_dotenv
from pathlib import Path


class BackupConfig:
    """Centralized configuration management"""

    def __init__(self, env_path: Optional[str] = None):
        """
        Initialize configuration from .env file

        Args:
            env_path: Optional path to .env file. If not provided, looks in current directory
        """
        if env_path:
            load_dotenv(env_path)
        else:
            load_dotenv()

        # Database configuration
        self.db_name = os.getenv('DB_NAME')
        self.db_user = os.getenv('DB_USER')
        self.db_password = os.getenv('DB_PASSWORD')
        self.db_host = os.getenv('DB_HOST', 'localhost')

        # Backup configuration
        self.backup_provider = os.getenv('BACKUP_PROVIDER', 'dropbox')
        self.backup_retention_days = int(os.getenv('BACKUP_RETENTION_DAYS', '7'))
        self.backup_temp_dir = os.getenv('BACKUP_TEMP_DIR', '/tmp/backups')
        self.uploads_path = os.getenv('BACKUP_UPLOADS_PATH',
                                      '/private/var/www/geronimo/wp-web/web/app/uploads')

        # Dropbox configuration
        self.dropbox_access_token = os.getenv('DROPBOX_ACCESS_TOKEN')
        self.dropbox_backup_folder = os.getenv('DROPBOX_BACKUP_FOLDER', '/backups')

        # mysqldump path
        self.mysqldump_path = os.getenv('MYSQLDUMP_PATH', '/usr/local/bin/mysqldump')

        # Dry run mode
        self.dry_run = os.getenv('BACKUP_DRY_RUN', 'false').lower() == 'true'

    def validate(self) -> List[str]:
        """
        Validate configuration and return list of errors

        Returns:
            List of error messages (empty list if valid)
        """
        errors = []

        # Required database fields
        if not self.db_name:
            errors.append("DB_NAME is required")
        if not self.db_user:
            errors.append("DB_USER is required")
        if not self.db_password:
            errors.append("DB_PASSWORD is required")

        # Provider-specific validation
        if self.backup_provider == 'dropbox':
            if not self.dropbox_access_token or self.dropbox_access_token == 'your_dropbox_access_token_here':
                errors.append("DROPBOX_ACCESS_TOKEN is required when using Dropbox provider")

        # Check mysqldump exists
        if not Path(self.mysqldump_path).exists():
            errors.append(f"mysqldump not found at {self.mysqldump_path}")

        # Check uploads path exists
        if not Path(self.uploads_path).exists():
            errors.append(f"Uploads path not found: {self.uploads_path}")

        return errors

    def to_dict(self) -> Dict:
        """
        Convert config to dictionary for provider initialization

        Returns:
            Dictionary with configuration values
        """
        return {
            'dropbox_access_token': self.dropbox_access_token,
            'dropbox_backup_folder': self.dropbox_backup_folder,
            'dry_run': self.dry_run,
            'backup_temp_dir': self.backup_temp_dir,
            # Add other provider configs as needed
        }

    def __repr__(self) -> str:
        """String representation (with sensitive data masked)"""
        return (
            f"BackupConfig("
            f"provider={self.backup_provider}, "
            f"db_name={self.db_name}, "
            f"retention_days={self.backup_retention_days}, "
            f"dry_run={self.dry_run})"
        )
