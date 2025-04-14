<div class="asset-box">
  <div id="view_print_container">
    <div class="content-wrapper">
      <div class="left-section">
        <div class="section-header">
          <div class="creditor-title">
            <div class="checkbox-group">
              <span>출력 용도에 맞게 선택하신 후 열람/인쇄를 클릭하시기 바랍니다. 중복 선택도 가능합니다. </span>
            </div>
          </div>
        </div>
        <div class="view-print-list">
			<div class="checkbox-group flex-column">
				<div class="view-print-select-all">
					<input type="checkbox" id="select_all_items" name="select_all">
					<label for="select_all_items">모두 선택</label>
				</div>
				<div class="view-print-item">
					<input type="checkbox" id="view_print_item_0" name="view_print[]" value="파산 및 면책신청서">
					<label for="view_print_item_0">파산 및 면책신청서</label>
				</div>
				<div class="view-print-item">
					<input type="checkbox" id="view_print_item_1" name="view_print[]" value="채권자목록 열람">
					<label for="view_print_item_1">채권자목록 열람</label>
				</div>
				<div class="view-print-item">
					<input type="checkbox" id="view_print_item_2" name="view_print[]" value="재산목록 열람">
					<label for="view_print_item_2">재산목록 열람</label>
				</div>
				<div class="view-print-item">
					<input type="checkbox" id="view_print_item_3" name="view_print[]" value="수입지출목록 열람">
					<label for="view_print_item_3">수입지출목록 열람</label>
				</div>
				<div class="view-print-item">
					<input type="checkbox" id="view_print_item_4" name="view_print[]" value="진술서 열람">
					<label for="view_print_item_4">진술서 열람</label>
				</div>
				<input type="hidden" id="case_no" value="<?php echo isset($_GET['case_no']) ? htmlspecialchars($_GET['case_no']) : ''; ?>">
			</div>
			<div class="view-print-button">
				<button type="button" class="btn-save btn-long" id="view_print_btn">열람/인쇄</button>
			</div>
        </div>	
      </div>
      <div class="right-section">
      </div>
    </div>
  </div>
</div>