#!/usr/bin/env php
<?php
/**
 * Generate SQL file to create N tenant databases with schema
 *
 * Usage: php generate_tenant_sql.php [start] [end] [output_file]
 * Example: php generate_tenant_sql.php 1 50 03_create_tenants.sql
 * Example: php generate_tenant_sql.php 51 100 04_create_more_tenants.sql
 */

$start = isset($argv[1]) ? (int)$argv[1] : 1;
$end = isset($argv[2]) ? (int)$argv[2] : 50;
$output = isset($argv[3]) ? $argv[3] : "03_create_tenants_${start}_${end}.sql";

$schema_file = __DIR__ . '/tenant_schema/schema.sql';

if (!file_exists($schema_file)) {
    die("ERROR: Schema file not found at: $schema_file\n");
}

$schema = file_get_contents($schema_file);

echo "Generating SQL for tenants $start to $end...\n";
echo "Output file: $output\n";

$sql = "-- ============================================\n";
$sql .= "-- Create Tenant Databases ($start to $end) with Schema\n";
$sql .= "-- ============================================\n";
$sql .= "-- Generated automatically by generate_tenant_sql.php\n";
$sql .= "-- Source schema: tenant_schema/schema.sql\n";
$sql .= "-- ============================================\n\n";

// Create databases
$sql .= "-- Step 1: Create databases\n";
for ($i = $start; $i <= $end; $i++) {
    $sql .= "CREATE DATABASE IF NOT EXISTS `candyhire_tenant_$i` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
}
$sql .= "\n";

// Apply schema to each database
$sql .= "-- Step 2: Apply schema to each database\n";
for ($i = $start; $i <= $end; $i++) {
    $sql .= "-- ============================================\n";
    $sql .= "-- Tenant $i\n";
    $sql .= "-- ============================================\n";
    $sql .= "USE `candyhire_tenant_$i`;\n\n";
    $sql .= $schema;
    $sql .= "\n\n";
}

// Write to file
file_put_contents($output, $sql);

$size = filesize($output);
$size_mb = round($size / 1024 / 1024, 2);

echo "✅ Generated successfully!\n";
echo "   File size: $size_mb MB\n";
echo "   Tenants: $start to $end (" . ($end - $start + 1) . " databases)\n";
echo "\n";
echo "To execute:\n";
echo "   mysql -uroot -p < $output\n";
echo "\n";
