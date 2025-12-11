"""
Abstract base class for storage providers
"""

from abc import ABC, abstractmethod
from typing import List, Optional
from dataclasses import dataclass
from datetime import datetime
import logging

logger = logging.getLogger(__name__)


@dataclass
class BackupFile:
    """Represents a backup file with metadata"""
    local_path: str
    remote_path: str
    size_bytes: int
    created_at: datetime

    def size_human_readable(self) -> str:
        """Convert bytes to human readable format (KB, MB, GB)"""
        size = self.size_bytes
        for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
            if size < 1024.0:
                return f"{size:.2f} {unit}"
            size /= 1024.0
        return f"{size:.2f} PB"


class StorageProvider(ABC):
    """Abstract base class for all storage providers"""

    def __init__(self, config: dict):
        self.config = config
        self.dry_run = config.get('dry_run', False)

    @abstractmethod
    def upload(self, local_path: str, remote_path: str) -> bool:
        """
        Upload a file to remote storage

        Args:
            local_path: Path to the local file
            remote_path: Destination path in remote storage

        Returns:
            True if upload successful, False otherwise
        """
        pass

    @abstractmethod
    def list_backups(self, prefix: str = '') -> List[BackupFile]:
        """
        List all backups in remote storage

        Args:
            prefix: Optional prefix to filter backups

        Returns:
            List of BackupFile objects
        """
        pass

    @abstractmethod
    def delete(self, remote_path: str) -> bool:
        """
        Delete a file from remote storage

        Args:
            remote_path: Path to the file in remote storage

        Returns:
            True if deletion successful, False otherwise
        """
        pass

    @abstractmethod
    def test_connection(self) -> bool:
        """
        Test if connection to storage provider is working

        Returns:
            True if connection successful, False otherwise
        """
        pass

    def cleanup_old_backups(self, retention_days: int = 7, current_backup_succeeded: bool = True) -> List[str]:
        """
        Delete backups older than retention_days

        If current backup succeeded: Delete all backups older than retention_days
        If current backup failed: Keep at least one backup of each type regardless of age

        Args:
            retention_days: Number of days to retain backups
            current_backup_succeeded: Whether the current backup run was successful

        Returns:
            List of deleted file paths
        """
        deleted_files = []

        try:
            if current_backup_succeeded:
                logger.info(f"Current backup succeeded - cleaning up backups older than {retention_days} days...")
            else:
                logger.info(f"Current backup failed - keeping at least 1 backup of each type (retention: {retention_days} days)...")

            backups = self.list_backups()

            if not backups:
                logger.info("No backups found")
                return deleted_files

            # Sort backups by age (newest first)
            backups_sorted = sorted(backups, key=lambda b: b.created_at, reverse=True)

            # Group backups by type (database vs uploads)
            database_backups = [b for b in backups_sorted if 'database' in b.remote_path.lower()]
            uploads_backups = [b for b in backups_sorted if 'uploads' in b.remote_path.lower()]

            # Determine minimum backups to keep based on current backup status
            # If current backup failed, keep at least 1 of each type as safety net
            # If current backup succeeded, no minimum - clean up everything old
            min_to_keep_db = 0 if current_backup_succeeded else 1
            min_to_keep_uploads = 0 if current_backup_succeeded else 1

            now = datetime.now()

            for backup in backups_sorted:
                age_days = (now - backup.created_at).days
                is_database = 'database' in backup.remote_path.lower()
                is_uploads = 'uploads' in backup.remote_path.lower()

                # Count how many backups of this type would remain if we delete this one
                if is_database:
                    remaining_db = sum(1 for b in database_backups if b.remote_path != backup.remote_path)
                    should_keep_minimum = remaining_db < min_to_keep_db
                elif is_uploads:
                    remaining_uploads = sum(1 for b in uploads_backups if b.remote_path != backup.remote_path)
                    should_keep_minimum = remaining_uploads < min_to_keep_uploads
                else:
                    should_keep_minimum = False

                # Delete if older than retention AND we have enough backups remaining
                if age_days > retention_days and not should_keep_minimum:
                    logger.info(f"Deleting old backup: {backup.remote_path} (age: {age_days} days)")

                    if self.dry_run:
                        logger.info(f"Dry run: Would delete {backup.remote_path}")
                        deleted_files.append(backup.remote_path)
                    else:
                        if self.delete(backup.remote_path):
                            deleted_files.append(backup.remote_path)
                            logger.info(f"Successfully deleted {backup.remote_path}")

                            # Update the lists after deletion
                            if is_database:
                                database_backups = [b for b in database_backups if b.remote_path != backup.remote_path]
                            elif is_uploads:
                                uploads_backups = [b for b in uploads_backups if b.remote_path != backup.remote_path]
                        else:
                            logger.warning(f"Failed to delete {backup.remote_path}")
                else:
                    if should_keep_minimum:
                        logger.info(f"Keeping backup (minimum retention): {backup.remote_path} (age: {age_days} days)")
                    else:
                        logger.debug(f"Keeping backup: {backup.remote_path} (age: {age_days} days)")

            logger.info(f"Cleanup summary: Deleted {len(deleted_files)} file(s), "
                       f"Retained {len(database_backups)} database backup(s) and "
                       f"{len(uploads_backups)} uploads backup(s)")

            return deleted_files

        except Exception as e:
            logger.error(f"Cleanup failed: {str(e)}")
            return deleted_files
