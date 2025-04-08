<?php
session_start();
require_once '../../../config.php';

$currentYear = date('Y');
?>
<!DOCTYPE html>
<html>
<head>
    <title>기준중위소득 60% 기준표</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h2><?php echo $currentYear; ?>년 기준중위소득 60% 기준표</h2>
    <table>
        <thead>
            <tr>
                <th>가구원 수</th>
                <th>기준중위소득 60%</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $stmt = $pdo->prepare("SELECT family_members, standard_amount FROM application_income_living_expense_standard WHERE year = :year ORDER BY family_members");
            $stmt->execute(['year' => $currentYear]);
            $standards = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($standards as $standard) {
                echo "<tr>";
                echo "<td>{$standard['family_members']}인 가구</td>";
                echo "<td>" . number_format($standard['standard_amount']) . "원</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>