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

    def cleanup_old_backups(self, retention_days: int = 7) -> List[str]:
        """
        Delete backups older than retention_days

        Args:
            retention_days: Number of days to retain backups

        Returns:
            List of deleted file paths
        """
        deleted_files = []

        try:
            logger.info(f"Listing backups for cleanup (retention: {retention_days} days)...")
            backups = self.list_backups()

            now = datetime.now()

            for backup in backups:
                age_days = (now - backup.created_at).days

                if age_days > retention_days:
                    logger.info(f"Deleting old backup: {backup.remote_path} (age: {age_days} days)")

                    if self.dry_run:
                        logger.info(f"Dry run: Would delete {backup.remote_path}")
                        deleted_files.append(backup.remote_path)
                    else:
                        if self.delete(backup.remote_path):
                            deleted_files.append(backup.remote_path)
                            logger.info(f"Successfully deleted {backup.remote_path}")
                        else:
                            logger.warning(f"Failed to delete {backup.remote_path}")
                else:
                    logger.debug(f"Keeping backup: {backup.remote_path} (age: {age_days} days)")

            return deleted_files

        except Exception as e:
            logger.error(f"Cleanup failed: {str(e)}")
            return deleted_files
