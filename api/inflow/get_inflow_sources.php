<<<<<<< HEAD
<?
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT inflow_name 
            FROM inflow_sources 
            ORDER BY inflow_name";
            
    $stmt = $pdo->query($sql);
    $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $sources
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
=======
<?
require_once '../../config.php';
header('Content-Type: application/json');

try {
    $sql = "SELECT inflow_name 
            FROM inflow_sources 
            ORDER BY inflow_name";
            
    $stmt = $pdo->query($sql);
    $sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $sources
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
>>>>>>> 719d7c8 (Delete all files)
}