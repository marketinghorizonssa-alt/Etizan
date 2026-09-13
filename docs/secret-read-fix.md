# Hostinger ingest secret compatibility

The runtime secret remains outside the public web root and outside GitHub. This change only normalizes one known Hostinger cron line-ending artifact: if the parsed ingest token is exactly 48 hexadecimal characters plus one trailing literal `n`, the reader strips only that final `n`. Other values are left unchanged.
