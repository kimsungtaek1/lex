<?php
// Include the configuration file to get the database connection
require_once 'config.php';

// Define a function to get all tables and their columns
function getDatabaseSchema($pdo) {
    try {
        // Get all tables
        $tableQuery = "SHOW TABLES";
        $tableStmt = $pdo->query($tableQuery);
        
        $tables = [];
        while ($row = $tableStmt->fetch(PDO::FETCH_NUM)) {
            $tableName = $row[0];
            
            // Get columns for each table
            $columnQuery = "DESCRIBE `$tableName`";
            $columnStmt = $pdo->query($columnQuery);
            $columns = [];
            
            while ($columnData = $columnStmt->fetch()) {
                $columns[] = [
                    'Field' => $columnData['Field'],
                    'Type' => $columnData['Type'],
                    'Null' => $columnData['Null'],
                    'Key' => $columnData['Key'],
                    'Default' => $columnData['Default'],
                    'Extra' => $columnData['Extra']
                ];
            }
            
            $tables[] = [
                'table_name' => $tableName,
                'columns' => $columns
            ];
        }
        
        return ['status' => 'success', 'data' => $tables];
    } catch (PDOException $e) {
        writeLog("Database schema error: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Unable to retrieve table information: ' . $e->getMessage()];
    }
}

// Get the database schema and output as JSON
header('Content-Type: application/json');
echo json_encode(getDatabaseSchema($pdo), JSON_UNESCAPED_UNICODE);