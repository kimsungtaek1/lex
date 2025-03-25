<div class="content-wrapper">
	<div class="appendix-title">부속서류 3&nbsp;&nbsp;|&nbsp;&nbsp;전부명령된 채권</div>
	
	<div class="left-section">
		<input type="hidden" id="claimNo" value="<?php echo $claim_no; ?>">
		
		<div class="form">
			<div class="form-title"><span>명령법원</span></div>
			<div class="form-content">
				<input type="text" id="court_name" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>사건번호</span></div>
			<div class="form-content">
				<input type="text" id="case_number" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>원채권자</span></div>
			<div class="form-content">
				<input type="text" id="original_creditor" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>제3채무자명</span></div>
			<div class="form-content">
				<input type="text" id="debtor_name" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>전부명령 금액</span></div>
			<div class="form-content form-row">
				<input type="text" id="order_amount" class="form-control number-input">
				<span>원</span>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>전부명령 일자</span></div>
			<div class="form-content">
				<input type="date" id="order_date" class="form-control">
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span>전부명령 채권 범위</span></div>
			<div class="form-content">
				<textarea id="claim_range" class="form-control" rows="3" placeholder="전부명령 대상 채권의 범위를 상세히 기재"></textarea>
			</div>
		</div>

		<div class="form">
			<div class="form-title"><span></span></div>
			<div class="form-content btn-right">
				<button type="button" id="closeButton">닫기</button>
				<button type="button" id="deleteButton">삭제</button>
				<button type="button" id="saveButton">저장</button>
			</div>
		</div>
	</div>
</div>