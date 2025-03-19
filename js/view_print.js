$(document).ready(function() {
	// 열람/인쇄 버튼 클릭 이벤트 처리
	$('#view_print_btn').on('click', function() {
		// 체크된 항목들 수집
		const checkedItems = [];
		$('input[name="view_print[]"]:checked').each(function() {
			checkedItems.push($(this).val());
		});
		
		// 선택된 항목이 없을 경우 경고 메시지 표시
		if (checkedItems.length === 0) {
			alert('출력할 항목을 하나 이상 선택해주세요.');
			return;
		}
		
		// 현재 URL에서 case_no 파라미터 추출
		const caseNo = window.currentCaseNo;
		
		if (!caseNo) {
			alert('사건 번호가 없습니다. 다시 시도해주세요.');
			return;
		}
		
		// PDF 생성 요청 URL 구성
		let isRecovery = location.pathname.indexOf('application_recovery') > -1;
		let isBankruptcy = location.pathname.indexOf('application_bankruptcy') > -1;
		let apiPath = '';
		
		if (isRecovery) {
			apiPath = '/adm/api/application_recovery/generate_pdf.php';
		} else if (isBankruptcy) {
			apiPath = '/adm/api/application_bankruptcy/generate_pdf.php';
		} else {
			alert('지원되지 않는 경로입니다.');
			return;
		}
		
		// 새 창으로 PDF 요청 전송
		const form = $('<form target="_blank" method="post" action="' + apiPath + '"></form>');
		form.append('<input type="hidden" name="case_no" value="' + caseNo + '">');
		
		// 체크된 항목들을 폼에 추가
		checkedItems.forEach(function(item) {
			form.append('<input type="hidden" name="print_items[]" value="' + item + '">');
		});
		
		// 폼을 body에 추가하고 자동 제출
		$('body').append(form);
		form.submit();
		form.remove();
	});
	
	// 모두 선택/해제 기능 구현
	$('#select_all_items').on('change', function() {
		const isChecked = $(this).prop('checked');
		$('input[name="view_print[]"]').prop('checked', isChecked);
	});
	
	// 개별 항목 체크박스 변경 시 '모두 선택' 체크박스 상태 업데이트
	$('input[name="view_print[]"]').on('change', function() {
		const totalItems = $('input[name="view_print[]"]').length;
		const checkedItems = $('input[name="view_print[]"]:checked').length;
		
		$('#select_all_items').prop('checked', totalItems === checkedItems);
	});
});