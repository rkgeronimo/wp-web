"""
Email notification module
"""

import smtplib
import logging
import os
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from datetime import datetime
from typing import Optional, List

logger = logging.getLogger(__name__)


class EmailNotifier:
    """Handles email notifications for backup failures"""

    def __init__(self, config):
        """
        Initialize email notifier

        Args:
            config: BackupConfig instance
        """
        self.config = config
        self.enabled = config.email_enabled

    def send_failure_notification(self, error_message: str,
                                   backup_logs: Optional[List[str]] = None):
        """
        Send email notification on backup failure

        Args:
            error_message: Error message to include in email
            backup_logs: Optional list of log messages to include
        """
        if not self.enabled:
            logger.info("Email notifications disabled, skipping")
            return

        try:
            # Create message
            msg = MIMEMultipart()
            msg['From'] = self.config.email_from
            msg['To'] = self.config.email_to
            msg['Subject'] = f'[BACKUP FAILED] WordPress Backup - {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}'

            # Get hostname
            try:
                hostname = os.uname().nodename
            except:
                hostname = 'unknown'

            # Email body
            body = f"""WordPress Backup Failure Notification

Backup process failed at {datetime.now().strftime("%Y-%m-%d %H:%M:%S")}

Server: {hostname}
Database: {self.config.db_name}
Provider: {self.config.backup_provider}

Error Details:
{error_message}
"""

            if backup_logs:
                body += "\n\n" + "="*60 + "\n"
                body += "Recent Log Entries (last 50 lines):\n"
                body += "="*60 + "\n"
                body += "\n".join(backup_logs[-50:])

            body += "\n\n" + "="*60 + "\n"
            body += "This is an automated message from the WordPress backup script.\n"
            body += "Please investigate and resolve the issue.\n"

            msg.attach(MIMEText(body, 'plain'))

            # Send email
            logger.info(f"Sending failure notification to {self.config.email_to}...")

            with smtplib.SMTP(self.config.smtp_host, self.config.smtp_port) as server:
                if self.config.smtp_use_tls:
                    server.starttls()

                if self.config.smtp_user and self.config.smtp_password:
                    server.login(self.config.smtp_user, self.config.smtp_password)

                server.send_message(msg)

            logger.info(f"Failure notification sent to {self.config.email_to}")

        except Exception as e:
            logger.error(f"Failed to send email notification: {str(e)}", exc_info=True)

    def send_success_notification(self, backup_files: List[dict]):
        """
        Optional: Send success notification with backup details

        Args:
            backup_files: List of backup file information
        """
        if not self.enabled:
            return

        # This is optional and can be implemented if needed
        # For now, we only send failure notifications as per requirements
        logger.debug("Success notifications not enabled")
