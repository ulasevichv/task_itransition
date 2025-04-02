## Requirements:

- PHP version: ^8.2
- Laravel version: ^11.0
- Command: php artisan product:import-from-csv --file="<absolute_path>" --testMode=1

## Notes on implementation:

- It's assumed that original DB-table presents in your system - it's not included in pull-request, only modifications are.
- Source CSV-file is parsed in one go. This could be done in several steps, but I considered it as an over-complication.
- Database selects and inserts are split into groups, as it should be.
- The script shows literally all possible errors for each line (can be many errors for one line).
- Unit tests are not implemented, unfortunately, as I'm very limited in time lately. I can add them, if it's really required.