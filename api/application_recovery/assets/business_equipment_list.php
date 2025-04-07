<link rel="stylesheet" href="../../../css/business_equipment_list.css">

<div class="content-wrapper">
    <div class="appendix-title">시설비품목록표</div>
    
    <div class="appendix-table">
        <div class="table-header">
            <div class="col">| 번호</div>
            <div class="col">| 품목</div>
            <div class="col">| 구입시기</div>
            <div class="col">| 수량</div>
            <div class="col">| 중고시세</div>
			<div class="col">| 청산가치판단금액</div>
			<div class="col">| 압류유무</div>
            <div class="col">| 합계</div>
        </div>
        
        <div id="equipment-list"></div>
        
    </div>
    <div class="total">
		<span>|&nbsp;&nbsp;&nbsp;총합계</span>
		<div class="total-amount">0원</div>
	</div>

	<div class="button">
		<button type="button" id="closeButton">닫기</button>
		<button type="button" id="saveButton">저장</button>
		<button type="button" id="addButton">추가</button>
	</div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    const caseNo = new URLSearchParams(window.location.search).get('case_no');

	function createRow(index) {
		return `
			<div class="form" data-asset-id="${index}">
				<div class="form-content">
					<div class="col"><input type="number" class="item-no" value="${index}"></div>
					<div class="col"><input type="text" class="item-name"></div>
					<div class="col"><input type="month" class="purchase-date"></div>
					<div class="col"><input type="number" class="quantity" min="0"></div>
					<div class="col"><input type="text" class="item-price" data-type="money"></div>
					<div class="col"><input type="text" class="liquidation-value" data-type="money"></div>
					<div class="col">
						<select class="is-seized">
							<option value="N" selected>무</option>
							<option value="Y">유</option>
						</select>
					</div>
					<div class="col">
						<input type="text" class="item-total" readonly>
						<button type="button" class="delete-row">삭제️</button>
					</div>
				</div>
			</div>
		`;
	}

    function addRow() {
		const container = $('#equipment-list');
		const index = container.children().length + 1;
		container.append(createRow(index)); // 새로운 행은 ID 없음
	}

    function formatMoney(amount) {
        return amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function unformatMoney(str) {
        return parseInt(str.replace(/,/g, "")) || 0;
    }

    function calculateRowTotal(row) {
        const quantity = parseInt($(row).find('.quantity').val()) || 0;
        const price = unformatMoney($(row).find('.item-price').val());
        const total = quantity * price;
        $(row).find('.item-total').val(formatMoney(total));
        calculateTotal();
    }

    function calculateTotal() {
        let total = 0;
        $('.item-total').each(function() {
            total += unformatMoney($(this).val());
        });
        $('.total-amount').text(formatMoney(total) + '원');
    }

	function loadEquipmentData() {
		$.ajax({
			url: '/adm/api/application_recovery/assets/asset_api.php',
			type: 'GET',
			data: { case_no: caseNo, asset_type: 'business_equipment' },
			dataType: 'json',
			success: function(response) {
				console.log("불러온 데이터:", response);
				const container = $('#equipment-list');
				container.empty();

				if (response.success && response.data.length > 0) {
					// 🔹 property_no 기준으로 정렬
					response.data.sort((a, b) => a.property_no - b.property_no);

					response.data.forEach((item, index) => {
						container.append(createRow(index + 1));
						const row = container.children().last();
						
						row.attr('data-asset-id', item.property_no);
						row.find('.item-no').val(index + 1);
						row.find('.item-name').val(item.item_name);
						row.find('.purchase-date').val(item.purchase_date);
						row.find('.quantity').val(item.quantity);
						row.find('.item-price').val(formatMoney(item.used_price));
						row.find('.liquidation-value').val(formatMoney(item.liquidation_value || 0));
						row.find('.is-seized').val(item.is_seized || 'N');
						calculateRowTotal(row);
					});
				}

				checkAndMaintainRows();
			},
			error: function(xhr, status, error) {
				console.error("데이터 로드 오류:", error);
			}
		});
	}

	function saveEquipmentData() {
		const equipmentData = [];
		let newPropertyNo = 1; // 🔹 저장할 때 강제로 1부터 재배열

		$('#equipment-list .form').each(function() {
			const itemName = $(this).find('.item-name').val();
			
			if (itemName) {
				equipmentData.push({
					case_no: caseNo,
					asset_type: 'business_equipment',
					property_no: newPropertyNo, // 🔹 삭제된 번호 고려하지 않고 강제 순차 번호 부여
					item_name: itemName,
					purchase_date: $(this).find('.purchase-date').val(),
					quantity: $(this).find('.quantity').val(),
					used_price: unformatMoney($(this).find('.item-price').val()),
					liquidation_value: unformatMoney($(this).find('.liquidation-value').val()),
					is_seized: $(this).find('.is-seized').val(),
					total: unformatMoney($(this).find('.item-total').val())
				});

				// 🔹 각 행의 data-asset-id도 업데이트
				$(this).attr('data-asset-id', newPropertyNo);
				$(this).find('.col:first').text(newPropertyNo);

				newPropertyNo++; // 다음 번호 증가
			}
		});

		console.log("저장할 데이터:", equipmentData);

		if (equipmentData.length > 0) {
			const savePromises = equipmentData.map(data =>
				$.ajax({
					url: '/adm/api/application_recovery/assets/asset_api.php',
					type: 'POST',
					data: data,
					error: function(xhr, status, error) {
						console.error("에러 발생:", error);
						alert('저장 중 오류가 발생했습니다.');
					}
				})
			);

			Promise.all(savePromises)
				.then(() => {
					alert('저장되었습니다.');
				})
				.catch(() => {
					alert('저장 중 오류가 발생했습니다.');
				});
		}
	}
	
	$(document).on('click', '.delete-row', function() {
		const row = $(this).closest('.form');
		const property_no = row.attr('data-asset-id'); // 삭제할 `property_no`

		console.log("삭제 요청 property_no:", property_no);

		if (property_no) {
			if (!confirm("이 항목을 삭제하시겠습니까?")) return;

			$.ajax({
				url: '/adm/api/application_recovery/assets/asset_api.php',
				type: 'DELETE',
				data: {
					case_no: caseNo,
					asset_type: 'business_equipment',
					property_no: property_no
				},
				success: function(response) {
					try {
						let jsonResponse = typeof response === "string" ? JSON.parse(response) : response;
						console.log("삭제 완료:", jsonResponse);

						if (jsonResponse.success) {
							row.remove(); // 🔹 클라이언트에서 행 제거
							updateRowNumbers(); // 🔹 삭제 후 property_no 재정렬
							saveUpdatedPropertyNumbers(); // 🔹 서버에 재정렬된 property_no 저장 (중요!)
							checkAndMaintainRows();
						} else {
							alert("삭제 실패: " + jsonResponse.message);
						}
					} catch (e) {
						console.error("JSON 파싱 오류:", e);
						alert('삭제 응답 처리 중 오류가 발생했습니다.');
					}
				},
				error: function(xhr, status, error) {
					console.error("삭제 오류:", error);
					alert('삭제 중 오류가 발생했습니다.');
				}
			});
		}
	});

	
	function checkAndMaintainRows() {
		const container = $('#equipment-list');
		const rowCount = container.children().length;

		if (rowCount < 10) {
			for (let i = rowCount; i < 10; i++) {
				container.append(createRow(i + 1));
			}
		}

		updateRowNumbers();
		calculateTotal();
	}
	
	function updateRowNumbers() {
		$('#equipment-list .form').each(function(index) {
			const newPropertyNo = index + 1; // 🔹 1부터 다시 정렬
			$(this).attr('data-asset-id', newPropertyNo);
			$(this).find('.col:first').text(newPropertyNo);
		});
	}
	
	function saveUpdatedPropertyNumbers() {
		const updatedData = [];

		$('#equipment-list .form').each(function(index) {
			const itemName = $(this).find('.item-name').val();

			if (itemName) {
				updatedData.push({
					case_no: caseNo,
					asset_type: 'business_equipment',
					property_no: index + 1, // 🔹 1부터 재정렬하여 저장
					item_name: itemName,
					purchase_date: $(this).find('.purchase-date').val(),
					quantity: $(this).find('.quantity').val(),
					used_price: unformatMoney($(this).find('.item-price').val()),
					liquidation_value: unformatMoney($(this).find('.liquidation-value').val()),
					is_seized: $(this).find('.is-seized').val(),
					total: unformatMoney($(this).find('.item-total').val())
				});
			}
		});

		console.log("재정렬된 데이터 저장:", updatedData);

		if (updatedData.length > 0) {
			$.ajax({
				url: '/adm/api/application_recovery/assets/asset_api.php',
				type: 'POST',
				data: { case_no: caseNo, asset_type: 'business_equipment', update_list: updatedData },
				success: function(response) {
					console.log("재정렬 데이터 저장 완료:", response);
				},
				error: function(xhr, status, error) {
					console.error("재정렬 데이터 저장 오류:", error);
				}
			});
		}
	}

    // 이벤트 핸들러 등록
    $(document).on('input', '[data-type="money"]', function() {
        let val = $(this).val().replace(/[^\d]/g, '');
        $(this).val(formatMoney(val));
        if ($(this).hasClass('item-price') || $(this).hasClass('quantity')) {
            calculateRowTotal($(this).closest('.form'));
        }
    });

    $(document).on('input', '.quantity', function() {
        calculateRowTotal($(this).closest('.form'));
    });

    $('#addButton').on('click', addRow);
    $('#saveButton').on('click', saveEquipmentData);
    $('#closeButton').on('click', function() {
        window.close();
    });

    // 초기 데이터 로드
    loadEquipmentData();
});
</script>