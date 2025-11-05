<?php
/**
 * Generate Tenant Pool SQL INSERT statements
 * Usage: php generate_tenant_pool_sql.php > tenant_pool.sql
 */

$tenantCount = 10;
$itemsPerLine = 10;

echo "-- ============================================\n";
echo "-- Tenant Pool - $tenantCount Pre-allocated Tenants\n";
echo "-- $tenantCount tenant databases are pre-created with empty schema during setup.\n";
echo "-- When a company completes payment, one tenant is assigned from the pool.\n";
echo "-- ============================================\n\n";

echo "-- Clean tenant_pool table to ensure fresh start\n";
echo "TRUNCATE TABLE `tenant_pool`;\n\n";

echo "INSERT INTO `tenant_pool` (`tenant_id`, `is_available`) VALUES\n";

for ($i = 1; $i <= $tenantCount; $i++) {
    echo "($i, TRUE)";

    if ($i < $tenantCount) {
        echo ",";

        // New line every $itemsPerLine items for readability
        if ($i % $itemsPerLine === 0) {
            echo "\n";
        } else {
            echo " ";
        }
    } else {
        echo ";\n";
    }
}

echo "\n-- ✅ $tenantCount tenant pool entries created\n";
