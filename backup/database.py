"""
Database backup module
"""

import subprocess
import logging
from pathlib import Path
from datetime import datetime
from typing import Optional

logger = logging.getLogger(__name__)


class DatabaseBackup:
    """Handles MySQL database backup operations"""

    def __init__(self, config):
        """
        Initialize database backup

        Args:
            config: BackupConfig instance
        """
        self.config = config
        self.mysqldump_path = config.mysqldump_path

    def create_backup(self, output_path: str) -> Optional[str]:
        """
        Create a MySQL dump and compress it with gzip

        Args:
            output_path: Directory to store the backup file

        Returns:
            Path to the compressed backup file, or None on failure
        """
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        db_name = self.config.db_name
        backup_filename = f"{db_name}_database_{timestamp}.sql.gz"
        backup_path = Path(output_path) / backup_filename

        # Ensure output directory exists
        Path(output_path).mkdir(parents=True, exist_ok=True)

        # Build mysqldump command
        cmd = [
            self.mysqldump_path,
            f'--user={self.config.db_user}',
            f'--password={self.config.db_password}',
            f'--host={self.config.db_host}',
            '--single-transaction',  # For InnoDB consistency
            '--quick',               # For large tables
            '--lock-tables=false',   # Don't lock tables
            '--no-tablespaces',      # Skip tablespaces (requires PROCESS privilege)
            self.config.db_name
        ]

        try:
            logger.info(f"Creating database backup: {backup_filename}")

            # Execute mysqldump and pipe to gzip
            with open(backup_path, 'wb') as f:
                # Run mysqldump
                mysqldump_process = subprocess.Popen(
                    cmd,
                    stdout=subprocess.PIPE,
                    stderr=subprocess.PIPE
                )

                # Pipe to gzip
                gzip_process = subprocess.Popen(
                    ['gzip'],
                    stdin=mysqldump_process.stdout,
                    stdout=f,
                    stderr=subprocess.PIPE
                )

                # Allow mysqldump_process to receive a SIGPIPE if gzip exits
                mysqldump_process.stdout.close()

                # Wait for both processes to complete
                gzip_stdout, gzip_stderr = gzip_process.communicate()
                mysqldump_stderr = mysqldump_process.stderr.read()
                mysqldump_process.wait()  # Ensure mysqldump has completed

                # Check for errors
                if mysqldump_process.returncode != 0 and mysqldump_process.returncode is not None:
                    error_msg = mysqldump_stderr.decode('utf-8', errors='replace')
                    logger.error(f"mysqldump failed: {error_msg}")
                    return None

                if gzip_process.returncode != 0:
                    error_msg = gzip_stderr.decode('utf-8', errors='replace')
                    logger.error(f"gzip failed: {error_msg}")
                    return None

            # Get file size and log
            file_size = backup_path.stat().st_size
            logger.info(f"Database backup created: {backup_path} ({self._format_size(file_size)})")

            return str(backup_path)

        except FileNotFoundError as e:
            logger.error(f"Command not found: {str(e)}")
            return None
        except Exception as e:
            logger.error(f"Database backup failed: {str(e)}", exc_info=True)
            return None

    def _format_size(self, size_bytes: int) -> str:
        """
        Format bytes to human readable size

        Args:
            size_bytes: Size in bytes

        Returns:
            Human readable size string (e.g., "2.45 MB")
        """
        for unit in ['B', 'KB', 'MB', 'GB', 'TB']:
            if size_bytes < 1024.0:
                return f"{size_bytes:.2f} {unit}"
            size_bytes /= 1024.0
        return f"{size_bytes:.2f} PB"
