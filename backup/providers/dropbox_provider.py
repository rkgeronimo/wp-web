"""
Dropbox storage provider implementation
"""

import os
import logging
from pathlib import Path
from datetime import datetime
from typing import List
import re

try:
    import dropbox
    from dropbox.exceptions import ApiError, AuthError
except ImportError:
    dropbox = None

from .base import StorageProvider, BackupFile

logger = logging.getLogger(__name__)


class DropboxProvider(StorageProvider):
    """Dropbox storage provider implementation"""

    CHUNK_SIZE = 4 * 1024 * 1024  # 4MB chunks for large file uploads

    def __init__(self, config: dict):
        """
        Initialize Dropbox provider

        Args:
            config: Configuration dictionary
        """
        super().__init__(config)

        if dropbox is None:
            raise ImportError("dropbox package is not installed. Run: pip install dropbox")

        self.access_token = config.get('dropbox_access_token')
        self.backup_folder = config.get('dropbox_backup_folder', '/backups')
        self.client = None

        if not self.access_token:
            raise ValueError("Dropbox access token is required")

        self._initialize_client()

    def _initialize_client(self):
        """Initialize Dropbox client"""
        if not self.dry_run:
            try:
                self.client = dropbox.Dropbox(self.access_token)
                logger.debug("Dropbox client initialized")
            except Exception as e:
                logger.error(f"Failed to initialize Dropbox client: {str(e)}")
                raise

    def upload(self, local_path: str, remote_path: str) -> bool:
        """
        Upload file to Dropbox with chunked upload for large files

        Args:
            local_path: Path to local file
            remote_path: Destination path in Dropbox

        Returns:
            True if upload successful, False otherwise
        """
        if self.dry_run:
            logger.info(f"Dry run: Would upload {local_path} to {remote_path}")
            return True

        try:
            file_size = os.path.getsize(local_path)
            filename = Path(local_path).name

            # Ensure remote path starts with /
            if not remote_path.startswith('/'):
                remote_path = f"/{remote_path}"

            logger.info(f"Uploading {filename} ({self._format_size(file_size)}) to Dropbox...")

            with open(local_path, 'rb') as f:
                if file_size <= self.CHUNK_SIZE:
                    # Small file - regular upload
                    logger.debug(f"Using regular upload for {filename}")
                    self.client.files_upload(f.read(), remote_path, mode=dropbox.files.WriteMode('overwrite'))
                else:
                    # Large file - chunked upload
                    logger.debug(f"Using chunked upload for {filename}")
                    self._chunked_upload(f, remote_path, file_size)

            logger.info(f"Successfully uploaded {filename} to Dropbox")
            return True

        except AuthError as e:
            logger.error(f"Dropbox authentication failed: {str(e)}")
            return False
        except ApiError as e:
            logger.error(f"Dropbox API error: {str(e)}")
            return False
        except Exception as e:
            logger.error(f"Upload failed: {str(e)}", exc_info=True)
            return False

    def _chunked_upload(self, file_obj, remote_path: str, file_size: int):
        """
        Upload large files in chunks

        Args:
            file_obj: File object to upload
            remote_path: Destination path in Dropbox
            file_size: Total file size in bytes
        """
        # Start upload session
        upload_session_start_result = self.client.files_upload_session_start(
            file_obj.read(self.CHUNK_SIZE)
        )

        cursor = dropbox.files.UploadSessionCursor(
            session_id=upload_session_start_result.session_id,
            offset=file_obj.tell()
        )

        commit = dropbox.files.CommitInfo(path=remote_path, mode=dropbox.files.WriteMode('overwrite'))

        # Upload chunks
        while file_obj.tell() < file_size:
            remaining = file_size - file_obj.tell()

            if remaining <= self.CHUNK_SIZE:
                # Last chunk - finish upload
                self.client.files_upload_session_finish(
                    file_obj.read(self.CHUNK_SIZE),
                    cursor,
                    commit
                )
            else:
                # More chunks to come
                self.client.files_upload_session_append_v2(
                    file_obj.read(self.CHUNK_SIZE),
                    cursor
                )
                cursor.offset = file_obj.tell()

            # Log progress
            progress = (file_obj.tell() / file_size) * 100
            logger.debug(f"Upload progress: {progress:.1f}%")

    def list_backups(self, prefix: str = '') -> List[BackupFile]:
        """
        List backups from Dropbox folder

        Args:
            prefix: Optional prefix to filter backups

        Returns:
            List of BackupFile objects
        """
        if self.dry_run:
            logger.info("Dry run: Would list backups from Dropbox")
            return []

        backups = []

        try:
            # List files in backup folder
            result = self.client.files_list_folder(self.backup_folder)

            for entry in result.entries:
                if isinstance(entry, dropbox.files.FileMetadata):
                    # Extract timestamp from filename
                    created_at = self._parse_timestamp_from_filename(entry.name)

                    if created_at:
                        backup = BackupFile(
                            local_path='',  # Not applicable for remote files
                            remote_path=entry.path_display,
                            size_bytes=entry.size,
                            created_at=created_at
                        )
                        backups.append(backup)

            logger.info(f"Found {len(backups)} backup(s) in Dropbox")
            return backups

        except ApiError as e:
            if e.error.is_path() and e.error.get_path().is_not_found():
                logger.warning(f"Backup folder not found: {self.backup_folder}")
                return []
            logger.error(f"Failed to list backups: {str(e)}")
            return []
        except Exception as e:
            logger.error(f"Failed to list backups: {str(e)}", exc_info=True)
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
        Delete file from Dropbox

        Args:
            remote_path: Path to file in Dropbox

        Returns:
            True if deletion successful, False otherwise
        """
        if self.dry_run:
            logger.info(f"Dry run: Would delete {remote_path}")
            return True

        try:
            self.client.files_delete_v2(remote_path)
            logger.debug(f"Deleted {remote_path} from Dropbox")
            return True

        except ApiError as e:
            logger.error(f"Failed to delete {remote_path}: {str(e)}")
            return False
        except Exception as e:
            logger.error(f"Failed to delete {remote_path}: {str(e)}", exc_info=True)
            return False

    def test_connection(self) -> bool:
        """
        Test Dropbox connection

        Returns:
            True if connection successful, False otherwise
        """
        if self.dry_run:
            logger.info("Dry run: Skipping connection test")
            return True

        try:
            # Get account info to test connection
            account = self.client.users_get_current_account()
            logger.info(f"Connected to Dropbox account: {account.email}")

            # Try to create backup folder if it doesn't exist
            try:
                self.client.files_create_folder_v2(self.backup_folder)
                logger.info(f"Created backup folder: {self.backup_folder}")
            except ApiError as e:
                if e.error.is_path() and e.error.get_path().is_conflict():
                    logger.debug(f"Backup folder already exists: {self.backup_folder}")
                else:
                    raise

            return True

        except AuthError as e:
            logger.error(f"Dropbox authentication failed: {str(e)}")
            return False
        except ApiError as e:
            logger.error(f"Dropbox API error: {str(e)}")
            return False
        except Exception as e:
            logger.error(f"Connection test failed: {str(e)}", exc_info=True)
            return False

    def _format_size(self, size_bytes: int) -> str:
        """Format bytes to human readable size"""
        for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
            if size_bytes < 1024.0:
                return f"{size_bytes:.2f} {unit}"
            size_bytes /= 1024.0
        return f"{size_bytes:.2f} PB"
