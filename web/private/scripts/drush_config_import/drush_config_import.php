<?php
// Database updates
echo "Running database updates...\n";
passthru('drush updatedb --no-cache-clear');
echo "Database updates complete.\n";
//Clear all cache
echo "Rebuilding cache.\n";
passthru('drush cr');
echo "Rebuilding cache complete.\n";
// Import all config changes.
echo "Importing configuration from yml files...\n";
passthru('drush config-import -y');
echo "Import of configuration complete.\n";
//Clear all cache
echo "Rebuilding cache.\n";
passthru('drush cr');
echo "Rebuilding cache complete.\n";
