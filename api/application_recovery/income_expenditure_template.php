<!-- 급여 수입목록 -->
<div class="asset-box" id="salaryIncomeSection">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>급여 수입목록</span>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title">
                    <span>월평균 소득금액</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_salary_avg_income" value="">원
                    <button type="button" id="iex_salary_calc_btn" class="btn btn-long">월평균소득계산기</button>
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span>급여 압류 등 유무</span>
                </div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="iex_salary_seizure_y" name="iex_salary_seizure" value="Y">
                        <label for="iex_salary_seizure_y">유</label>
                        <input type="radio" id="iex_salary_seizure_n" name="iex_salary_seizure" value="N">
                        <label for="iex_salary_seizure_n">무</label>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span>연간 환산금액</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_salary_yearly_income" value="">원
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content">※ 월평균소득계산기를 통해 이미 정보가 존재하는 경우, 수동으로 금액을 임의로 정정한 경우 기존의 데이터와 일치하지 않을 수 있습니다.</div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title">
                    <span>직장명</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_company_name" value="">
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span>직위</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_position" value="">
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span>근무기간</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_work_period" value="">
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content">
                    <button class="btn-save" id="iex_salary_save_btn">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 자영 수입목록 -->
<div class="asset-box" id="businessIncomeSection">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>자영 수입목록</span>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title">
                    <span>수입명목</span>
                </div>
                <div class="form-content">
                    <select class="form-select" id="iex_business_type" name="iex_business_type">
                        <option value="사업소득" selected>사업소득</option>
                        <option value="영업소득">영업소득</option>
                        <option value="농업소득">농업소득</option>
                        <option value="임대소득">임대소득</option>
                        <option value="기타(임의입력)">기타(임의입력)</option>
                    </select>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;기타&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="text" class="form-control" id="iex_business_type_etc" value="">
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span>월간 수입금액</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_monthly_income" value="">원
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span>연간 환산금액</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_yearly_income" value="">원
                </div>
            </div>
			<div class="form">
                <div class="form-title">
                </div>
                <div class="form-content">
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title">
                    <span>상호</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_business_name" value="">
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span>업종</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_business_sector" value="">
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span>종사경력</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_business_career" value="">
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content">
                    <button class="btn-save" id="iex_business_save_btn">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 가족관계 -->
<div class="asset-box" id="familyRelationshipSection">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>가족관계</span>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-add2" id="iex_family_add_btn">추가</button>
            </div>
        </div>
    </div>

    <table class="long-table">
        <tr>
            <th>|&nbsp;&nbsp;관계</th>
            <th>|&nbsp;&nbsp;성명</th>
            <th>|&nbsp;&nbsp;연령</th>
            <th>|&nbsp;&nbsp;동거여부 및 기간</th>
            <th>|&nbsp;&nbsp;직업</th>
            <th>|&nbsp;&nbsp;월수입</th>
            <th>|&nbsp;&nbsp;재산총액</th>
            <th>|&nbsp;&nbsp;부양유무</th>
            <th></th>
        </tr>
    </table>
</div>

<!-- 생계비산정 -->
<div class="asset-box">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>생계비산정</span>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-content form-header-long">
                    <div>|&nbsp;&nbsp;
                        <select class="form-select" id="iex_year" name="iex_year">
							<!-- 이 부분을 JavaScript에서 동적으로 채울 것입니다 -->
						</select>
                    </div>
                    <div class="form-divide">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;1인 가구</div>
                    <div class="form-divide">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;2인 가구</div>
                    <div class="form-divide">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;3인 가구</div>
                    <div class="form-divide">&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;4인 가구</div>
                    <div class="form-divide">&nbsp;&nbsp;|&nbsp;&nbsp;5인 가구</div>
                    <div class="form-divide">&nbsp;|&nbsp;&nbsp;6인 가구</div>
                </div>
            </div>
            <div class="form">
				<div class="form-title" style="border-top-left-radius:0.3vw">
					<span>기준중위소득 60%</span>
				</div>
				<div class="form-content" id="standard_amount_container">
					<input type="text" readonly data-family-members="1">
					<input type="text" readonly data-family-members="2">
					<input type="text" readonly data-family-members="3">
					<input type="text" readonly data-family-members="4">
					<input type="text" readonly data-family-members="5">
					<input type="text" readonly data-family-members="6">
				</div>
			</div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title form-notitle">
                    <span>가족생계비범위</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control form-content-short" id="iex_family_count" value="">인
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span></span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control form-content-short" id="iex_range_min" value="">원 부터&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="text" class="form-control form-content-short" id="iex_range_max" value="">원
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 생계비 및 재단채권 등 산정 -->
<div class="asset-box">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>생계비 및 재단채권 등 산정</span>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title">
                    <span>생계비 범위 선택</span>
                </div>
                <div class="form-content">
                    <div class="radio">
                        <input type="radio" id="iex_expense_range_y" name="iex_expense_range" value="Y">
                        <label for="iex_expense_range_y">기준 범위 내 생계비</label>
                        <input type="radio" id="iex_expense_range_n" name="iex_expense_range" value="N">
                        <label for="iex_expense_range_n">기준 범위 초과 생계비</label>
                    </div>
                </div>
            </div>
            <div class="form">
                <div class="form-title form-notitle">
                    <span>생계비 금액 및<br>비율</span>
                </div>
                <div class="form-content form-nocontent">
                    생계비 금액&nbsp;&nbsp;|&nbsp;&nbsp;<input type="text" id="iex_living_expense" name="iex_living_expense" value="">원
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="checkbox" id="iex_direct_input" name="iex_direct_input">
                    <label for="iex_direct_input">생계비금액 직접입력</label>
                </div>
            </div>
            <div class="form">
                <div class="form-title form-notitle"></div>
                <div class="form-content form-nocontent">
                    (기준 중위소득의 &nbsp;&nbsp;<input type="text" class="form-control form-content-short5" id="iex_income_ratio" value="">%)에 해당
                </div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content">
                    ※ 추가 생계비가 있는 경우 생계비금액에 자동합산됩니다.
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span>추가생계비</span>
                </div>
                <div class="form-content">
                    기준범위 초과시&nbsp;&nbsp;ㅣ&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" class="form-control" id="iex_additional_expense" value="">원
                    <button type="button" id="iex_expense_calc_btn" class="btn btn-long6">추가생계비입력</button>
                </div>
            </div>
            <div class="form">
                <div class="form-title form-notitle">
                    <span>개인회생재단채권</span>
                </div>
                <div class="form-content">
                    외부회생위원보수율&nbsp;&nbsp;ㅣ&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" class="form-control" id="iex_trustee_fee_rate" value="">%
                    <button type="button" id="iex_trustee_fee_btn" class="btn btn-long6">외부회생위원 보수</button>
                </div>
            </div>
            <div class="form">
                <div class="form-title">
                    <span></span>
                </div>
                <div class="form-content">
                    기타재단채권(양육비 등)&nbsp;&nbsp;ㅣ&nbsp;&nbsp;<input type="text" class="form-control" id="iex_other_fee" value="">원
                    <button type="button" id="iex_other_fee_btn" class="btn btn-long6">기타재단채권</button>
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="twotwo-box">
                <div id="iex-livingExpenseBox">
                    <div class="iex-title">생계비</div>
                    <div class="iex-box">
                        <input type="text" class="form-control" id="iex-livingExpenseValue" placeholder="0" readonly>
                        <div class="iex-value">&nbsp;원</div>
                    </div>
                </div>
                <div id="iex-monthPaymentBox">
                    <div class="iex-title">월변제금</div>
                    <div class="iex-box">
                        <input type="text" class="form-control" id="iex-monthPaymentValue" placeholder="0" readonly>
                        <div class="iex-value">&nbsp;원</div>
                    </div>
                </div>
                <div id="iex-monthCountBox">
                    <div class="iex-title">개월수</div>
                    <div class="iex-box">
                        <input type="text" class="form-control" id="iex-monthCountValue" placeholder="0">
                        <div class="iex-value">&nbsp;개월</div>
                    </div>
                </div>
                <div id="iex-repaymentRateBox">
                    <div class="iex-title">변제율</div>
                    <div class="iex-box">
                        <input type="text" class="form-control" id="iex-repaymentRateValue" placeholder="0">
                        <div class="iex-value">&nbsp;%</div>
                    </div>
                </div>
                <button type="button" class="btn" id="iex-calcPlanBtn">변제계획안 계산설정</button>
            </div>
        </div>
    </div>
</div>

<!-- 변제계획안 10항 -->
<div class="asset-box">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>변제계획안 10항</span>
            </div>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="left-section">
            <div class="form">
                <div class="form-title">
                    <span>제목</span>
                </div>
                <div class="form-content">
                    <input type="text" class="form-control" id="iex_plan_title" value="">
                </div>
            </div>
            <div class="form">
                <div class="form-title form-title-3">
                    <span>내용</span>
                </div>
                <div class="form-content form-content-3 textarea">
                    <textarea id="iex_plan_content" name="iex_plan_content" class="form-control" placeholder="" rows="6"></textarea>
                </div>
            </div>
        </div>
        <div class="right-section">
            <div class="form">
                <div class="form-title">
                    <span>변제계획안 10항<br>작성 가이드</span>
                </div>
                <div class="form-content">[변제계획안 10항]이 보정권고에 포함되어 있을 경우,<br>해당 항목에 보정권고에 명시된 내용을 그대로 추가하여 작성하시면 됩니다.</div>
            </div>
			<div class="form">
                <div class="form-title form-notitle"></div>
                <div class="form-content form-nocontent"></div>
            </div>
			<div class="form">
                <div class="form-title form-notitle"></div>
                <div class="form-content form-nocontent"></div>
            </div>
            <div class="form">
                <div class="form-title"></div>
                <div class="form-content btn-right">
                    <button class="btn-delete" id="iex_plan_delete_btn">삭제</button>
                    <button class="btn-save" id="iex_plan_save_btn">저장</button>
                </div>
            </div>
        </div>
    </div>
</div>