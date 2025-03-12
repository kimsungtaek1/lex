$(document).ready(function() {
	// 아이디 중복 확인
	$('.check-btn').click(function() {
		var userid = $('#userid').val();
		
		if (userid.length < 4) {
			alert('아이디는 4자 이상이어야 합니다.');
			return;
		}
		
		// AJAX 요청으로 중복 확인
		$.ajax({
			url: '../api/index/check_id.php',
			type: 'POST',
			data: {userid: userid},
			success: function(response) {
				if (response === 'available') {
					alert('사용 가능한 아이디입니다.');
					$('#userid').data('checked', true);
				} else {
					alert('이미 사용 중인 아이디입니다.');
					$('#userid').data('checked', false);
				}
			},
			error: function() {
				alert('서버 오류가 발생했습니다. 다시 시도해주세요.');
			}
		});
	});
	
	// 주소 찾기 버튼 클릭 시 다음 우편번호 API 호출
	$('.address-btn').click(function() {
		var targetInput = $(this).data('target') || 'address';
		
		new daum.Postcode({
			oncomplete: function(data) {
				var fullAddress = data.address;
				var extraAddress = '';
				
				if (data.addressType === 'R') {
					if (data.bname !== '') {
						extraAddress += data.bname;
					}
					if (data.buildingName !== '') {
						extraAddress += (extraAddress !== '' ? ', ' + data.buildingName : data.buildingName);
					}
					fullAddress += (extraAddress !== '' ? ' (' + extraAddress + ')' : '');
				}
				
				$('#' + targetInput).val(fullAddress);
				
				// 상세주소 입력 필드로 포커스 이동
				$('#' + targetInput + '_detail').focus();
			}
		}).open();
	});
	
	// 사업자등록번호 포맷 적용 (000-00-00000)
	$('#business_number').on('input', function() {
		var businessNum = $(this).val().replace(/[^0-9]/g, '');
		
		if (businessNum.length > 10) {
			businessNum = businessNum.substr(0, 10);
		}
		
		if (businessNum.length >= 3) {
			businessNum = businessNum.substr(0, 3) + '-' + businessNum.substr(3);
		}
		
		if (businessNum.length >= 6) {
			businessNum = businessNum.substr(0, 6) + '-' + businessNum.substr(6);
		}
		
		$(this).val(businessNum);
	});
	
	// 전화번호 포맷 적용
	$('#phone, #company_tel, #company_fax').on('input', function() {
		var phone = $(this).val().replace(/[^0-9]/g, '');
		
		if (phone.length > 11) {
			phone = phone.substr(0, 11);
		}
		
		if (phone.length >= 3) {
			phone = phone.substr(0, 3) + '-' + phone.substr(3);
		}
		
		if (phone.length >= 8) {
			phone = phone.substr(0, 8) + '-' + phone.substr(8);
		}
		
		$(this).val(phone);
	});
	
	// 비밀번호 확인 검증
	$('#password_confirm').blur(function() {
		var password = $('#password').val();
		var confirm = $(this).val();
		
		if (password && confirm && password !== confirm) {
			alert('비밀번호가 일치하지 않습니다.');
			$(this).val('');
			$(this).focus();
		}
	});
	
	// 폼 제출 전 유효성 검사
	$('#signupForm').submit(function(e) {
		// 비밀번호 일치 확인
		var password = $('#password').val();
		var confirm = $('#password_confirm').val();
		
		if (password !== confirm) {
			e.preventDefault();
			alert('비밀번호가 일치하지 않습니다.');
			$('#password_confirm').focus();
			return false;
		}
		
		// 아이디 중복확인 여부 체크
		if (!$('#userid').data('checked')) {
			e.preventDefault();
			alert('아이디 중복확인을 해주세요.');
			return false;
		}
		
		// 사업자회원인 경우 사업자등록번호 유효성 검사
		var memberType = $('input[name="member_type"]').val();
		if (memberType === 'business') {
			var businessNumber = $('#business_number').val();
			// 사업자등록번호 형식 검사 (000-00-00000)
			var businessNumRegex = /^\d{3}-\d{2}-\d{5}$/;
			
			if (!businessNumRegex.test(businessNumber)) {
				e.preventDefault();
				alert('유효한 사업자등록번호 형식이 아닙니다.');
				$('#business_number').focus();
				return false;
			}
		}
	});
});