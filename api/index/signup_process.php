<?php
require_once '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header("Location: signup_step1.php");
	exit;
}

// POST 데이터 수집
$agree_terms = $_POST['agree_terms'] ?? '';
$agree_privacy = $_POST['agree_privacy'] ?? '';
$agree_marketing = $_POST['agree_marketing'] ?? 'N';
$member_type = $_POST['member_type'] ?? 'personal';
$userid = trim($_POST['userid'] ?? '');
$password = trim($_POST['password'] ?? '');
$password_confirm = trim($_POST['password_confirm'] ?? '');

// 개인회원과 사업자회원 공통 필드
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$company_name = trim($_POST['company_name'] ?? '');
$company_tel = trim($_POST['company_tel'] ?? '');
$company_fax = trim($_POST['company_fax'] ?? '');
$company_address = trim($_POST['company_address'] ?? '');

// 개인/사업자 회원별 특화 필드
if ($member_type === 'personal') {
	$name = trim($_POST['name'] ?? '');
	$representative = ''; // 개인은 대표자명 필드가 없음
	$business_number = ''; // 개인은 사업자등록번호 필드가 없음
} else {
	$name = trim($_POST['representative'] ?? ''); // 사업자의 경우 대표자명을 name 필드에 저장
	$representative = trim($_POST['representative'] ?? '');
	$business_number = trim($_POST['business_number'] ?? '');
}

// 필수 필드 검증
if (empty($userid) || empty($password) || 
	empty($phone) || empty($email) || empty($company_name) || 
	empty($company_tel) || empty($company_address)) {
	echo "<script>alert('모든 필수 항목을 입력해주세요.'); history.back();</script>";
	exit;
}

// 개인/사업자별 추가 필수 필드 검증
if ($member_type === 'personal' && empty($name)) {
	echo "<script>alert('성명을 입력해주세요.'); history.back();</script>";
	exit;
} else if ($member_type === 'business' && 
		  (empty($representative) || empty($business_number))) {
	echo "<script>alert('대표자명과 사업자등록번호를 입력해주세요.'); history.back();</script>";
	exit;
}

// 비밀번호 일치 확인
if ($password !== $password_confirm) {
	echo "<script>alert('비밀번호가 일치하지 않습니다.'); history.back();</script>";
	exit;
}

try {
	// 아이디 중복 확인
	$check_query = "SELECT COUNT(*) FROM employee WHERE employee_id = ?";
	$check_stmt = $pdo->prepare($check_query);
	$check_stmt->execute([$userid]);
	
	if ($check_stmt->fetchColumn() > 0) {
		echo "<script>alert('이미 사용 중인 아이디입니다.'); history.back();</script>";
		exit;
	}
	
	// 비밀번호 해시화
	$hashed_password = password_hash($password, PASSWORD_DEFAULT);
	
	// 기본값 설정
	$position = '일반'; // 기본 직위
	$department = '미지정'; // 기본 부서
	$status = '재직'; // 기본 상태
	$auth = 0; // 기본 권한
	$initial_page = '["case.php"]'; // 기본 초기 페이지
	$font_size = '11px'; // 기본 폰트 크기
	
	// 현재 날짜를 입사일로 설정
	$hire_date = date('Y-m-d');
	
	// DB에 저장
	$insert_query = "INSERT INTO employee (
		employee_id, password, name, position, department, 
		email, phone, hire_date, status, auth, 
		initial_page, font_size, memo,
		company_name, company_tel, company_fax, company_address,
		business_number, member_type, representative
	) VALUES (
		?, ?, ?, ?, ?, 
		?, ?, ?, ?, ?, 
		?, ?, ?,
		?, ?, ?, ?,
		?, ?, ?
	)";
	
	$insert_stmt = $pdo->prepare($insert_query);
	$insert_stmt->execute([
		$userid, $hashed_password, $name, $position, $department,
		$email, $phone, $hire_date, $status, $auth,
		$initial_page, $font_size, '',
		$company_name, $company_tel, $company_fax, $company_address,
		$business_number, $member_type, $representative
	]);
	
	// 세션에 회원 정보 저장 (가입 완료 페이지에서 표시하기 위함)
	session_start();
	$_SESSION['signup_name'] = $name;
	$_SESSION['signup_userid'] = $userid;
	$_SESSION['signup_company'] = $company_name;
	$_SESSION['signup_member_type'] = $member_type;
	
	// 가입 완료 페이지로 이동
	header("Location: ../../page/signup_step3.php");
	exit;
	
} catch (PDOException $e) {
	echo "<script>alert('회원가입 중 오류가 발생했습니다. 관리자에게 문의하세요.'); history.back();</script>";
	// 개발 시에만 사용하고 실제 서비스에서는 제거할 것
	// error_log("회원가입 오류: " . $e->getMessage());
	exit;
}
?>