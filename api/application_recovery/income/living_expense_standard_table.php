<?php
session_start();
require_once '../../../config.php';

$currentYear = date('Y');

// 사용 가능한 모든 연도 조회
$yearStmt = $pdo->query("SELECT DISTINCT year FROM application_income_living_expense_standard ORDER BY year DESC");
$availableYears = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

// GET 파라미터로 선택된 연도 받기 (기본값은 현재 연도)
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;
?>
<!DOCTYPE html>
<html>
<head>
    <title>기준중위소득 60% 기준표</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .year-selector {
            margin-bottom: 20px;
            text-align: center;
        }
        select {
            padding: 5px;
            font-size: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="year-selector">
        <label for="year-select">연도 선택: </label>
        <select id="year-select" onchange="changeYear(this.value)">
            <?php foreach ($availableYears as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $year == $selectedYear ? 'selected' : ''; ?>>
                    <?php echo $year; ?>년
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <h2><?php echo $selectedYear; ?>년 기준중위소득 60% 기준표</h2>
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
            $stmt->execute(['year' => $selectedYear]);
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

    <script>
    function changeYear(year) {
        // 선택된 연도로 페이지 리로드
        window.location.href = `?year=${year}`;
    }
    </script>
</body>
</html>