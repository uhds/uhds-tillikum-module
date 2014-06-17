# Importing bookings

## Overview

1. Obtain booking export CSV from room selection
2. Test and import the bookings

## Obtain the booking export

Generate this CSV from room selection. There should be a script for doing so in
the `bin` directory at the root of the housing application.

## Test and import the bookings

Use `bin/import-bookings` with the `-d` flag to test, and without the `-d` flag
to persist the changes. The only other argument is the file to import. It should
be a CSV. **Note** that the CSV should not have headers, remove them before
attempting to import.
