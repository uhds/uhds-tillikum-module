# Importing rates

## Overview

1. Obtain the new rate sheet from assignments
2. Test and import the rate sheet

## Obtain the rate sheet

Assignments has a formatted rate sheet which is used from year to year for the
import. Obtain this rate sheet.

## Test and import the rate sheet

Use `bin/import-rate-sheet` with the `-d` flag to test, and without the `-d`
flag to persist the changes. The only other argument is the file to import. It
should be a CSV. **Note** that the CSV should not have headers, remove them
before attempting to import. If you forget it's no big deal, the script will
just warn you that it cannot find the rate ID for that row.
