# Email queue setup

1. Apply `database\migrations\email_outbox_migration.sql` to the Hardware Inventory database.
2. In Windows Task Scheduler, create a task that runs every minute. Set the program to `C:\xampp\php\php.exe` and the argument to `C:\xampp\htdocs\hardware-inventory\backend\services\email_queue_worker.php`.
3. Ensure the task runs as an account that can access the database and the existing `HIMS_SMTP_*` environment settings. The worker retries temporary SMTP failures and logs items that exhaust its retries.

The web application only queues email after the corresponding database change can be committed. The worker is CLI-only and uses the existing Gmail SMTP configuration.
