<?php
// Database updates
error_log("Running database updates...\n");
passthru('drush updatedb --no-cache-clear');
error_log("Database updates complete.\n");
//Clear all cache
error_log("Rebuilding cache.\n");
passthru('drush cr');
error_log("Rebuilding cache complete.\n");
// Import all config changes.
error_log("Importing configuration from yml files...\n");
passthru('drush config-import -y');
error_log("Import of configuration complete.\n");
//Clear all cache
error_log("Rebuilding cache.\n");
passthru('drush cr');
error_log("Rebuilding cache complete.\n");
