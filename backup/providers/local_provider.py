"""
Local filesystem storage provider (for testing)
"""

import shutil
import logging
from pathlib import Path
from datetime import datetime
from typing import List
import re

from .base import StorageProvider, BackupFile

logger = logging.getLogger(__name__)


class LocalProvider(StorageProvider):
    """Local filesystem provider for testing"""

    def __init__(self, config: dict):
        """
        Initialize local provider

        Args:
            config: Configuration dictionary
        """
        super().__init__(config)
        self.backup_dir = config.get('backup_temp_dir', '/tmp/local_backups')
        Path(self.backup_dir).mkdir(parents=True, exist_ok=True)
        logger.info(f"Local backup directory: {self.backup_dir}")

    def upload(self, local_path: str, remote_path: str) -> bool:
        """
        Copy file to local backup directory

        Args:
            local_path: Path to local file
            remote_path: Destination filename

        Returns:
            True if copy successful, False otherwise
        """
        try:
            dest = Path(self.backup_dir) / Path(remote_path).name

            if self.dry_run:
                logger.info(f"Dry run: Would copy {local_path} to {dest}")
                return True

            shutil.copy2(local_path, dest)
            logger.info(f"Copied {local_path} to {dest}")
            return True

        except Exception as e:
            logger.error(f"Failed to copy file: {str(e)}")
            return False

    def list_backups(self, prefix: str = '') -> List[BackupFile]:
        """
        List backups from local directory

        Args:
            prefix: Optional prefix to filter backups

        Returns:
            List of BackupFile objects
        """
        backups = []

        try:
            backup_path = Path(self.backup_dir)

            if not backup_path.exists():
                logger.warning(f"Backup directory not found: {self.backup_dir}")
                return []

            for file_path in backup_path.iterdir():
                if file_path.is_file():
                    # Parse timestamp from filename
                    created_at = self._parse_timestamp_from_filename(file_path.name)

                    if created_at:
                        backup = BackupFile(
                            local_path=str(file_path),
                            remote_path=file_path.name,
                            size_bytes=file_path.stat().st_size,
                            created_at=created_at
                        )
                        backups.append(backup)

            logger.info(f"Found {len(backups)} backup(s) in local directory")
            return backups

        except Exception as e:
            logger.error(f"Failed to list backups: {str(e)}")
            return []

    def _parse_timestamp_from_filename(self, filename: str) -> datetime:
        """
        Parse timestamp from backup filename

        Args:
            filename: Backup filename (e.g., "geronimo_database_20251209_143022.sql.gz")

        Returns:
            datetime object or None if parsing fails
        """
        try:
            # Match pattern: YYYYMMDD_HHMMSS
            pattern = r'(\d{8})_(\d{6})'
            match = re.search(pattern, filename)

            if match:
                date_str = match.group(1)  # YYYYMMDD
                time_str = match.group(2)  # HHMMSS
                return datetime.strptime(f"{date_str}_{time_str}", "%Y%m%d_%H%M%S")

            return None

        except Exception as e:
            logger.debug(f"Failed to parse timestamp from {filename}: {str(e)}")
            return None

    def delete(self, remote_path: str) -> bool:
        """
        Delete file from local directory

        Args:
            remote_path: Filename to delete

        Returns:
            True if deletion successful, False otherwise
        """
        try:
            file_path = Path(self.backup_dir) / remote_path

            if self.dry_run:
                logger.info(f"Dry run: Would delete {file_path}")
                return True

            if file_path.exists():
                file_path.unlink()
                logger.debug(f"Deleted {file_path}")
                return True
            else:
                logger.warning(f"File not found: {file_path}")
                return False

        except Exception as e:
            logger.error(f"Failed to delete {remote_path}: {str(e)}")
            return False

    def test_connection(self) -> bool:
        """
        Test local storage (always succeeds if directory exists)

        Returns:
            True if directory is writable, False otherwise
        """
        try:
            backup_path = Path(self.backup_dir)

            # Ensure directory exists
            backup_path.mkdir(parents=True, exist_ok=True)

            # Test write permission
            test_file = backup_path / '.test'
            test_file.touch()
            test_file.unlink()

            logger.info(f"Local storage ready: {self.backup_dir}")
            return True

        except Exception as e:
            logger.error(f"Local storage test failed: {str(e)}")
            return False
