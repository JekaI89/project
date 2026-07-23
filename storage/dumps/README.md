# SQL dumps are not stored in git (GitHub 100 MB file limit).

Place product dump here as product_YYYYMMDD.sql and run:

    powershell -File .\scripts\db-setup.ps1 -Force

Or use the deploy zip package which includes the dump.
