#!/usr/bin/env python3
"""
WordPress Backup Script with Dropbox Integration

This script creates backups of:
1. MySQL database (compressed with gzip)
2. Uploads folder (compressed tar archive)

And uploads them to Dropbox with automatic cleanup of old backups.

Usage:
    python3 backup_script.py [--config PATH] [--dry-run] [--verbose] [--log-file PATH]

Examples:
    # Run backup with default config
    python3 backup_script.py

    # Test without uploading
    python3 backup_script.py --dry-run --verbose

    # Specify custom config file
    python3 backup_script.py --config /path/to/.env
"""

import sys
import logging
import argparse
from pathlib import Path
from typing import List

# Import backup modules
from backup.config import BackupConfig
from backup.database import DatabaseBackup
from backup.filesystem import FilesystemBackup
from backup.providers.dropbox_provider import DropboxProvider
from backup.providers.local_provider import LocalProvider


def setup_logging(log_file: str = None, verbose: bool = False):
    """
    Setup logging configuration

    Args:
        log_file: Optional path to log file
        verbose: Enable verbose (DEBUG) logging
    """
    log_level = logging.DEBUG if verbose else logging.INFO
    log_format = '%(asctime)s - %(name)s - %(levelname)s - %(message)s'

    handlers = [logging.StreamHandler(sys.stdout)]

    if log_file:
        handlers.append(logging.FileHandler(log_file))

    logging.basicConfig(
        level=log_level,
        format=log_format,
        handlers=handlers
    )


class BackupOrchestrator:
    """Main orchestrator for backup operations"""

    def __init__(self, config: BackupConfig):
        """
        Initialize backup orchestrator

        Args:
            config: BackupConfig instance
        """
        self.config = config
        self.logger = logging.getLogger(__name__)
        self.backup_files: List[str] = []
        self.errors: List[str] = []

        # Initialize components
        self.db_backup = DatabaseBackup(config)
        self.fs_backup = FilesystemBackup(config)
        self.storage_provider = self._get_storage_provider()

    def _get_storage_provider(self):
        """
        Factory method to get appropriate storage provider

        Returns:
            StorageProvider instance
        """
        provider_name = self.config.backup_provider.lower()

        if provider_name == 'dropbox':
            return DropboxProvider(self.config.to_dict())
        elif provider_name == 'local':
            return LocalProvider(self.config.to_dict())
        else:
            raise ValueError(f"Unknown storage provider: {provider_name}")

    def run(self) -> bool:
        """
        Execute complete backup workflow

        Returns:
            True if backup successful, False otherwise
        """
        self.logger.info("=" * 70)
        self.logger.info("WordPress Backup Script")
        self.logger.info("=" * 70)
        self.logger.info(f"Provider: {self.config.backup_provider}")
        self.logger.info(f"Database: {self.config.db_name}")
        self.logger.info(f"Retention: {self.config.backup_retention_days} days")
        self.logger.info(f"Dry run: {self.config.dry_run}")
        self.logger.info("=" * 70)

        try:
            # Test storage connection first
            if not self._test_connection():
                return False

            # Create backups
            if not self._create_backups():
                return False

            # Upload backups
            if not self._upload_backups():
                return False

            # Cleanup old backups
            self._cleanup_old_backups()

            # Cleanup local temporary files
            self._cleanup_local_files()

            self.logger.info("=" * 70)
            self.logger.info("Backup process completed successfully!")
            self.logger.info("=" * 70)
            return True

        except Exception as e:
            self.logger.error(f"Backup process failed with exception: {str(e)}", exc_info=True)
            self.errors.append(f"Fatal error: {str(e)}")
            return False

    def _test_connection(self) -> bool:
        """
        Test connection to storage provider

        Returns:
            True if connection successful, False otherwise
        """
        self.logger.info("Testing storage provider connection...")

        if self.config.dry_run:
            self.logger.info("Dry run mode: Skipping connection test")
            return True

        try:
            if self.storage_provider.test_connection():
                self.logger.info("Storage provider connection successful")
                return True
            else:
                self.logger.error("Storage provider connection failed")
                self.errors.append("Storage provider connection test failed")
                return False
        except Exception as e:
            self.logger.error(f"Connection test error: {str(e)}", exc_info=True)
            self.errors.append(f"Connection test error: {str(e)}")
            return False

    def _create_backups(self) -> bool:
        """
        Create database and filesystem backups

        Returns:
            True if all backups created successfully, False otherwise
        """
        self.logger.info("")
        self.logger.info("Creating backups...")
        self.logger.info("-" * 70)

        # Create database backup
        self.logger.info("Step 1/2: Database backup")
        db_backup_path = self.db_backup.create_backup(self.config.backup_temp_dir)

        if db_backup_path:
            self.backup_files.append(db_backup_path)
            self.logger.info(" Database backup created successfully")
        else:
            self.errors.append("Database backup failed")
            return False

        # Create uploads backup
        self.logger.info("")
        self.logger.info("Step 2/2: Uploads folder backup")
        fs_backup_path = self.fs_backup.create_backup(self.config.backup_temp_dir)

        if fs_backup_path:
            self.backup_files.append(fs_backup_path)
            self.logger.info(" Uploads backup created successfully")
        else:
            self.errors.append("Uploads backup failed")
            return False

        self.logger.info("-" * 70)
        return True

    def _upload_backups(self) -> bool:
        """
        Upload backups to storage provider

        Returns:
            True if all uploads successful, False otherwise
        """
        self.logger.info("")
        self.logger.info("Uploading backups to storage provider...")
        self.logger.info("-" * 70)

        success = True

        for i, backup_file in enumerate(self.backup_files, 1):
            filename = Path(backup_file).name
            remote_path = f"{self.config.dropbox_backup_folder}/{filename}"

            # Get and display file size before uploading
            file_size = Path(backup_file).stat().st_size
            size_str = self._format_size(file_size)

            self.logger.info(f"Uploading {i}/{len(self.backup_files)}: {filename} ({size_str})")

            if self.config.dry_run:
                self.logger.info(f"Dry run: Would upload {backup_file} to {remote_path}")
            else:
                if self.storage_provider.upload(backup_file, remote_path):
                    self.logger.info(f" Successfully uploaded {filename}")
                else:
                    self.logger.error(f" Failed to upload {filename}")
                    self.errors.append(f"Upload failed: {filename}")
                    success = False

        self.logger.info("-" * 70)

        return success

    def _cleanup_old_backups(self):
        """Delete backups older than retention period"""
        self.logger.info("")
        self.logger.info(f"Cleaning up backups older than {self.config.backup_retention_days} days...")
        self.logger.info("-" * 70)

        try:
            deleted_files = self.storage_provider.cleanup_old_backups(
                self.config.backup_retention_days
            )

            if deleted_files:
                self.logger.info(f" Deleted {len(deleted_files)} old backup(s)")
                for file in deleted_files:
                    self.logger.info(f"  - {file}")
            else:
                self.logger.info("No old backups to delete")

        except Exception as e:
            self.logger.error(f"Cleanup failed: {str(e)}")
            # Don't fail the entire backup if cleanup fails

        self.logger.info("-" * 70)

    def _cleanup_local_files(self):
        """Remove local temporary backup files"""
        self.logger.info("")
        self.logger.info("Cleaning up local temporary files...")
        self.logger.info("-" * 70)

        for backup_file in self.backup_files:
            try:
                Path(backup_file).unlink()
                self.logger.info(f" Deleted local file: {Path(backup_file).name}")
            except Exception as e:
                self.logger.warning(f"Failed to delete {backup_file}: {str(e)}")

        self.logger.info("-" * 70)

    def _format_size(self, size_bytes: int) -> str:
        """
        Format bytes to human readable size

        Args:
            size_bytes: Size in bytes

        Returns:
            Human readable size string (e.g., "54.41 KB")
        """
        for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
            if size_bytes < 1024.0:
                return f"{size_bytes:.2f} {unit}"
            size_bytes /= 1024.0
        return f"{size_bytes:.2f} PB"


def main():
    """Main entry point"""
    parser = argparse.ArgumentParser(
        description='WordPress Backup Script with Dropbox Integration',
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog="""
Examples:
  %(prog)s                           Run backup with default config
  %(prog)s --dry-run --verbose       Test without uploading
  %(prog)s --config /path/to/.env    Specify custom config file
  %(prog)s --log-file backup.log     Write logs to file
        """
    )

    parser.add_argument(
        '--config',
        default='/private/var/www/geronimo/wp-web/.env',
        help='Path to .env configuration file (default: %(default)s)'
    )
    parser.add_argument(
        '--dry-run',
        action='store_true',
        help='Run in dry-run mode (no uploads or deletions)'
    )
    parser.add_argument(
        '--verbose',
        '-v',
        action='store_true',
        help='Enable verbose (DEBUG) logging'
    )
    parser.add_argument(
        '--log-file',
        help='Path to log file (logs to stdout if not specified)'
    )

    args = parser.parse_args()

    # Setup logging
    setup_logging(args.log_file, args.verbose)

    logger = logging.getLogger(__name__)

    try:
        # Load configuration
        logger.info(f"Loading configuration from: {args.config}")
        config = BackupConfig(args.config)

        # Override dry_run if specified via command line
        if args.dry_run:
            config.dry_run = True
            logger.info("Dry-run mode enabled via command line")

        # Validate configuration
        validation_errors = config.validate()
        if validation_errors:
            logger.error("Configuration validation failed:")
            for error in validation_errors:
                logger.error(f"   {error}")
            logger.error("")
            logger.error("Please fix the configuration errors and try again.")
            sys.exit(1)

        logger.info("Configuration validated successfully")
        logger.info("")

        # Run backup
        orchestrator = BackupOrchestrator(config)
        success = orchestrator.run()

        sys.exit(0 if success else 1)

    except FileNotFoundError as e:
        logger.error(f"Configuration file not found: {args.config}")
        sys.exit(1)
    except Exception as e:
        logger.error(f"Fatal error: {str(e)}", exc_info=True)
        sys.exit(1)


if __name__ == '__main__':
    main()
