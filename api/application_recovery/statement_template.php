<!-- 최종학력 섹션 -->
<div class="asset-box" data-type="education">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>최종학력</span>
      </div>
    </div>
  </div>
  <div id="education_container">
    <div class="content-wrapper">
      <div class="left-section">
        <div class="form">
          <div class="form-title">
            <span>학교명</span>
          </div>
          <div class="form-content">
            <input type="text" name="school_name" id="school_name" class="form-control">
          </div>
        </div>
        <div class="form">
          <div class="form-title">
            <span>졸업시기</span>
          </div>
          <div class="form-content">
            <input type="date" name="graduation_date" id="graduation_date" class="form-control">
          </div>
        </div>
      </div>
      <div class="right-section">
        <div class="form">
          <div class="form-title">
            <span>졸업여부</span>
          </div>
          <div class="form-content">
            <div class="radio">
              <input type="radio" id="graduation_status_graduated" name="graduation_status" value="졸업">
              <label for="graduation_status_graduated">졸업</label>
              <input type="radio" id="graduation_status_dropout" name="graduation_status" value="중퇴">
              <label for="graduation_status_dropout">중퇴</label>
            </div>
          </div>
        </div>
        <div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content form-nocontent btn-right">
            <button type="button" class="btn-save" id="education_save_btn">저장</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 과거경력 섹션 -->
<div class="asset-box" data-type="career">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>과거경력 ㅣ 최근 경력부터 기재</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_career">추가</button>
      </div>
    </div>
  </div>
  <div id="career_container"></div>
</div>

<!-- 과거 결혼/이혼 경력 섹션 -->
<div class="asset-box" data-type="marriage">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>과거 결혼/이혼 경력</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_marriage">추가</button>
      </div>
    </div>
  </div>
  <div id="marriage_container"></div>
</div>

<!-- 현재주거상황 섹션 -->
<div class="asset-box" data-type="housing">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>현재주거상황</span>
      </div>
    </div>
  </div>
  <div id="housing_container">
    <div class="content-wrapper">
      <div class="left-section">
        <div class="form">
          <div class="form-title">
            <span>주거상황</span>
          </div>
          <div class="form-content">
            <select class="form-select form-content-short" id="" name="">
				<option value="㉠ 신청인 소유주택" selected>㉠ 신청인 소유주택</option>
				<option value="㉡ 사택 또는 기숙사">㉡ 사택 또는 기숙사</option>
				<option value="㉢ 임차(전월세)주택">㉢ 임차(전월세)주택</option>
				<option value="㉣ 친족 소유 주택에 무상거주">㉣ 친족 소유 주택에 무상거주</option>
				<option value="㉤ 진족 외 소유 주택에 무상거주">㉤ 진족 외 소유 주택에 무상거주</option>
				<option value="㉥ 기타">㉥ 기타</option>
			</select>
          </div>
        </div>
        <div class="form">
          <div class="form-title">
            <span>㉠ 신청인 소유주택</span>
          </div>
          <div class="form-content">
            <input type="text" id="" name="" class="form-control">
          </div>
        </div>
		<div class="form">
          <div class="form-title">
            <span>㉡ 사택 또는 기숙사</span>
          </div>
          <div class="form-content">
            임대보증금 ㅣ<input type="text" id="" name="" class="form-control form-content-short"> 원
          </div>
        </div>
		<div class="form">
          <div class="form-title form-notitle">
            <span>㉢ 임차(전월세)주택</span>
          </div>
          <div class="form-content">
            임대료 ㅣ 월&nbsp;<input type="text" id="" name="" class="form-control form-content-short"> 원&nbsp;&nbsp;연체액 ㅣ <input type="text" id="" name="" class="form-control form-content-short"> 원
          </div>
        </div>
		<div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content">
            임차인성명 ㅣ&nbsp;<input type="text" id="" name="" class="form-control form-content-short">
          </div>
        </div>
		<div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content">
            부연설명 ㅣ&nbsp;<input type="text" id="" name="" class="form-control form-content-short"> 원
          </div>
        </div>
      </div>
      <div class="right-section">
        <div class="form">
          <div class="form-title">
            <span>㉣ 친족 소유 주택에<br>무상거주</span>
          </div>
          <div class="form-content">
            소유자성명 ㅣ&nbsp;<input type="text" id="" name="" class="form-control form-content-short">
          </div>
        </div>
        <div class="form">
          <div class="form-title">
            <span>㉤ 친족 외 소유<br>주택에 무상거주</span>
          </div>
          <div class="form-content">
            신청인과의 관계 ㅣ&nbsp;<input type="text" id="" name="" class="form-control form-content-short">
          </div>
        </div>
		<div class="form">
          <div class="form-title">
            <span>㉥ 기타</span>
          </div>
          <div class="form-content">
            <input type="text" id="" name="" class="form-control">
          </div>
        </div>
		<div class="form">
          <div class="form-title">
            <span>거주 시작 시점 </span>
          </div>
          <div class="form-content">
            <input type="text" id="" name="" class="form-control">
          </div>
        </div>
        <div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content form-nocontent form-content-2 btn-right">
			<button type="button" class="btn-save" id="housing_save_btn">저장</button>
			<div style="width:100%">
			※ ①또는 ②항을 선택한 분은 주택의 등기부등본을 첨부하여 주십시오.<br>
			※ ②또는 ③항을 선택한 분은 임대차계약서(전월세계약서) 또는 사용하기서 사본을 첨부하여 주십시오.<br>
			※ ④또는 ⑤항을 선택한 분은 소유자 작성의 거주 증명서를 첨부하여 주십시오.<br>
			</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- 소송/압류 경험 섹션 -->
<div class="asset-box" data-type="lawsuit">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>채권자로부터 소송, 지급명령, 전부명령, 압류, 가압류 등을 받은 경험</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="add_lawsuit">추가</button>
      </div>
    </div>
  </div>
  <div id="lawsuit_container"></div>
</div>

<!-- 개인회생절차 사유 섹션 -->
<div class="asset-box" data-type="bankruptcyReason">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>개인회생절차에 이르게 된 사정 ㅣ 중복 선택 가능</span>
      </div>
    </div>
  </div>
  <div id="bankruptcy_reason_container">
    <div class="content-wrapper">
      <div class="left-section">
        <div class="form">
          <div class="form-title form-notitle">
            <span>항목</span>
          </div>
          <div class="form-content">
            <div class="checkbox-group">
                <input type="checkbox" id="" name="bankruptcy_reason[]" value="생활비 부족">
                <label for="">생활비 부족</label>
                <input type="checkbox" id="" name="bankruptcy_reason[]" value="병원비 과다지출">
                <label for="">병원비 과다지출</label>
            </div>
          </div>
        </div>
		<div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content">
            <div class="checkbox-group">
                <input type="checkbox" id="" name="bankruptcy_reason[]" value="교육비 과다지출">
                <label for="">교육비 과다지출</label>
                <input type="checkbox" id="" name="bankruptcy_reason[]" value="음식, 음주, 여행, 도박 또는 취미활동">
                <label for="">음식, 음주, 여행, 도박 또는 취미활동</label>
            </div>
          </div>
        </div>
		<div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content">
            <div class="checkbox-group">
                <input type="checkbox" id="" name="bankruptcy_reason[]" value="점포 운영의 실패">
                <label for="">점포 운영의 실패</label>
                <input type="checkbox" id="" name="bankruptcy_reason[]" value="타인 채무의 보증">
                <label for="">타인 채무의 보증</label>
            </div>
          </div>
        </div>
		<div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content">
            <div class="checkbox-group">
                <input type="checkbox" id="" name="bankruptcy_reason[]" value="주식투자 실패">
                <label for="">주식투자 실패</label>
                <input type="checkbox" id="" name="bankruptcy_reason[]" value="사기피해">
                <label for="">사기피해</label>
            </div>
          </div>
        </div>
      </div>
      <div class="right-section">
        <div class="form">
          <div class="form-title">
            <span>항목</span>
          </div>
          <div class="form-content">
            <div class="checkbox-group">
                <input type="checkbox" id="reason_extra" name="bankruptcy_reason[]" value="기타">
                <label for="reason_extra">기타</label>
            </div>
          </div>
        </div>
		<div class="form">
          <div class="form-title">
            <span>기타내용</span>
          </div>
          <div class="form-content">
            <input type="text" id="" name="" class="form-control">
          </div>
        </div>
		<div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content form-nocontent">
          </div>
        </div>
        <div class="form">
          <div class="form-title form-notitle">
            <span></span>
          </div>
          <div class="form-content form-nocontent btn-right">
            <button type="button" class="btn-save" id="bankruptcy_reason_save_btn">저장</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="asset-box" data-type="" id="debtCause">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>채무자가 많은 채무를 부담하게 된 사정 및 개인회생절차 개시의 신청에 이르게 된 사정에 관하여 구체적으로 기재 | 추가 기재 시 별지 사용</span>
      </div>
      <div class="button-group">
        <button type="button" class="btn btn-add2" id="">별지</button>
      </div>
    </div>
  </div>
	<table class="long-table">
		<tr>
			<td><input type="text" class="form-control"><button type="button" class="btn-save" id="housing_save_btn">저장</button></td>
		</tr>
    </table>
</div>

<!-- 과거 면책절차 이용상황 섹션 -->
<div class="asset-box" data-type="debtRelief">
  <div class="section-header">
    <div class="creditor-title">
      <div class="checkbox-group">
        <span>과거 면책절차 등의 이용상황 ㅣ 과거 면책절차 등을 이용하였다면 해당란에 기재</span>
      </div>
    </div>
  </div>
  <div id="debt_relief_container">
	<div class="content-wrapper">
	  
	  <div class="left-section">
		<table class="long-table">
			<tbody>
				<tr>
					<th>|&nbsp;&nbsp;절차</th>
					<th>|&nbsp;&nbsp;법원 또는 기관</th>
					<th>|&nbsp;&nbsp;신청시기</th>
					<th>|&nbsp;&nbsp;현재까지 상황</th>
				</tr>
			</tbody>
		</table>
		<div class="form">
		  <div class="form-title">
			<span>파산, 면책절차</span>
		  </div>
		  <div class="form-content">
			<input type="text" class="form-control"><input type="text" class="form-control"><input type="text" class="form-control">
		  </div>
		</div>
		<div class="form">
		  <div class="form-title">
			<span>화의, 회생, <br>개인회생 절차</span>
		  </div>
		  <div class="form-content">
			<input type="text" class="form-control"><input type="text" class="form-control"><input type="text" class="form-control">
		  </div>
		</div>
		<div class="form">
		  <div class="form-title">
			<span></span>
		  </div>
		  <div class="form-content">
		  </div>
		</div>
	  </div>
	  <div class="right-section">
		<table class="long-table">
			<tbody>
				<tr>
					<th>|&nbsp;&nbsp;절차</th>
					<th>|&nbsp;&nbsp;법원 또는 기관</th>
					<th>|&nbsp;&nbsp;신청시기</th>
					<th>|&nbsp;&nbsp;현재까지 상황</th>
				</tr>
			</tbody>
		</table>
		<div class="form">
		  <div class="form-title">
			<span>신용회복위원회 <br>워크아웃</span>
		  </div>
		  <div class="form-content">
			<input type="text" class="form-control"><input type="text" class="form-control"><input type="text" class="form-control">회
		  </div>
		</div>
		<div class="form">
		  <div class="form-title">
			<span>배드뱅크</span>
		  </div>
		  <div class="form-content">
			<input type="text" class="form-control"><input type="text" class="form-control"><input type="text" class="form-control">원 변제
		  </div>
		</div>
		<div class="form">
		  <div class="form-title form-notitle">
			<span></span>
		  </div>
		  <div class="form-content form-nocontent btn-right">
			<button type="button" class="btn-save debt_relief_save_btn">저장</button>
			<div style="width:100%;">※ 신청일 전 10년 내에 회의사건, 파산사건 또는 개인회생사건을 신청한 사실이 있는 때에는 그 관련서류 1통을 제출하여야 합니다.</div>
		  </div>
		</div>
	  </div>
	</div>
  </div>
</div>