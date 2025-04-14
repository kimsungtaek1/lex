// 모두 선택 체크박스와 개별 체크박스 간의 상호작용 관리
$(document).ready(function() {
	// 모두 선택 체크박스 이벤트
	$('#bankruptcy_select_all_items').change(function() {
		// 모두 선택 체크박스 상태에 따라 모든 개별 체크박스 상태 변경
		$('input[name="bankruptcy_view_print[]"]').prop('checked', $(this).prop('checked'));
	});

	// 개별 체크박스 이벤트
	$('input[name="bankruptcy_view_print[]"]').change(function() {
		// 모든 개별 체크박스가 선택되었는지 확인
		const allChecked = $('input[name="bankruptcy_view_print[]"]').length === $('input[name="bankruptcy_view_print[]"]:checked').length;
		// 모두 선택 체크박스 상태 업데이트
		$('#bankruptcy_select_all_items').prop('checked', allChecked);
	});

	// 열람/인쇄 버튼 클릭 이벤트
	$('#bankruptcy_view_print_btn').click(function() {
		alert('test');
		// 선택된 항목 확인
		const selectedItems = [];
		$('input[name="bankruptcy_view_print[]"]:checked').each(function() {
			selectedItems.push($(this).val());
		});

		// 선택된 항목이 없으면 알림 표시
		if (selectedItems.length === 0) {
			alert('출력할 항목을 선택해주세요.');
			return;
		}

		// 현재 사건 번호 가져오기
		const caseNo = $('#bankruptcy_case_no').val() || currentCaseNo;
		
		if (!caseNo) {
			alert('사건 정보를 찾을 수 없습니다.');
			return;
		}

		// AJAX 대신 폼 제출 방식 사용
		const form = $('<form></form>').attr({
			method: 'post',
			action: '/adm/api/application_bankruptcy/generate_pdf.php',
			target: '_blank'
		}).appendTo('body');

		// 사건 번호 필드 추가
		$('<input>').attr({
			type: 'hidden',
			name: 'case_no',
			value: caseNo
		}).appendTo(form);

		// 선택된 항목들 필드 추가
		selectedItems.forEach(function(item) {
			$('<input>').attr({
				type: 'hidden',
				name: 'bankruptcy_print_items[]',
				value: item
			}).appendTo(form);
		});

		// 폼 제출
		form.submit();
		
		// 사용 후 폼 제거
		form.remove();
	});
});