"""
Filesystem backup module
"""

import tarfile
import logging
from pathlib import Path
from datetime import datetime
from typing import Optional

logger = logging.getLogger(__name__)


class FilesystemBackup:
    """Handles filesystem backup operations (uploads folder)"""

    def __init__(self, config):
        """
        Initialize filesystem backup

        Args:
            config: BackupConfig instance
        """
        self.config = config
        self.uploads_path = Path(config.uploads_path)

    def create_backup(self, output_path: str) -> Optional[str]:
        """
        Create a compressed tar archive of the uploads folder

        Args:
            output_path: Directory to store the backup file

        Returns:
            Path to the compressed backup file, or None on failure
        """
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        backup_filename = f"uploads_{timestamp}.tar.gz"
        backup_path = Path(output_path) / backup_filename

        # Ensure output directory exists
        Path(output_path).mkdir(parents=True, exist_ok=True)

        try:
            logger.info(f"Creating uploads backup: {backup_filename}")

            # Check if uploads folder exists
            if not self.uploads_path.exists():
                logger.error(f"Uploads folder not found: {self.uploads_path}")
                return None

            # Create compressed tar archive
            with tarfile.open(backup_path, 'w:gz') as tar:
                tar.add(
                    self.uploads_path,
                    arcname='uploads',
                    recursive=True
                )

            # Get file size and log
            file_size = backup_path.stat().st_size
            logger.info(f"Uploads backup created: {backup_path} ({self._format_size(file_size)})")

            return str(backup_path)

        except PermissionError as e:
            logger.error(f"Permission denied accessing uploads folder: {str(e)}")
            return None
        except Exception as e:
            logger.error(f"Uploads backup failed: {str(e)}", exc_info=True)
            return None

    def _format_size(self, size_bytes: int) -> str:
        """
        Format bytes to human readable size

        Args:
            size_bytes: Size in bytes

        Returns:
            Human readable size string (e.g., "3.87 MB")
        """
        for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
            if size_bytes < 1024.0:
                return f"{size_bytes:.2f} {unit}"
            size_bytes /= 1024.0
        return f"{size_bytes:.2f} PB"
