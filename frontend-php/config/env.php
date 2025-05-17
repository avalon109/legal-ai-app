<?php
// Production environment configuration
putenv('TRA_DB_HOST=localhost');
putenv('TRA_DB_USER=tra@volanticsystems.nl');
putenv('TRA_DB_PASS=Hack123!');
putenv('TRA_DB_NAME=tenant_rights');
putenv('APP_ENV=production');
putenv('DEBUG_MODE=false'); 