$(document).ready(function() {
	// 모든 약관에 동의
	$('#agree_all').change(function() {
		var isChecked = $(this).prop('checked');
		$('input[type="checkbox"]').prop('checked', isChecked);
	});
	
	// 개별 약관 체크 상태에 따라 전체 동의 체크박스 상태 변경
	$('.terms-agree input[type="checkbox"]').change(function() {
		var allChecked = true;
		$('.terms-agree input[type="checkbox"]').each(function() {
			if (!$(this).prop('checked')) {
				allChecked = false;
				return false;
			}
		});
		$('#agree_all').prop('checked', allChecked);
	});
	
	// 다음 버튼 클릭 이벤트
	$('#nextBtn').click(function() {
		// 필수 약관 동의 확인
		var requiredChecked = true;
		$('.required-check').each(function() {
			if (!$(this).prop('checked')) {
				requiredChecked = false;
				return false;
			}
		});
		
		if (!requiredChecked) {
			alert('필수 약관에 모두 동의해야 합니다.');
			return;
		}
		
		// 폼 제출
		$('#agreementForm').submit();
	});
});