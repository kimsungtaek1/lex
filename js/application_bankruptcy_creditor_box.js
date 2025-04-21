window.creditorBoxTemplate = `<div class="creditor-box" data-count="{count}">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>채권자정보</span>
                <label for="creditorInfo_b{count}" class="checkbox-space">|</label>
                <input type="checkbox" id="creditorInfo_b{count}" class="creditor-checkbox" data-count="{count}">
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-copy-creditor">복사</button>
                <button type="button" class="btn btn-add-creditor">추가</button>
                <button type="button" class="btn btn-delete-creditor">선택항목 삭제</button>
            </div>
        </div>
    </div>
    <div class="content-wrapper">
        <div class="creditor-form-box" data-index="{count}">
            <div class="left-section">
                <div class="form">
                    <div class="form-title"><span>채권번호</span></div>
                    <div class="form-content">
                        <input type="text" class="form-control creditor-number" value="{count}" readonly>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>채권자명</span></div>
                    <div class="form-content">
                        <input type="text" id="financialInstitution_b{count}" name="financialInstitution_b{count}" class="form-control">
                        <button type="button" class="btn btn-long btn-financial-institution" data-count="{count}">금융기관 검색</button>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>주소</span></div>
                    <div class="form-content form-row">
                        <input type="text" id="address_b{count}" name="address_b{count}" class="form-control">
                        <button type="button" class="btn btn-search address-search" data-target="address_b{count}">주소찾기</button>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>연락처</span></div>
                    <div class="form-content">
                        <input type="text" id="phone_b{count}" name="phone_b{count}" class="form-control">
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>팩스</span></div>
                    <div class="form-content">
                        <input type="text" id="fax_b{count}" name="fax_b{count}" class="form-control">
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>차용 또는 구입일자</span></div>
                    <div class="form-content">
                        <input type="date" id="calculationDate_b{count}" name="calculationDate_b{count}" class="form-control">
                    </div>
                </div>

                <div class="form">
                    <div class="form-title form-notitle"><span>발생원인</span></div>
                    <div class="form-content form-nocontent">
                        <select class="form-select" id="separateBond_b{count}" name="separateBond_b{count}">
                            <option value="금원차용" selected>금원차용</option>
                            <option value="물품구입">물품구입</option>
                            <option value="보증(피보증인 기재)">보증(피보증인 기재)</option>
                            <option value="기타">기타</option>
                        </select>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <input type="text" id="reasonDetail_b{count}" name="reasonDetail_b{count}" class="form-control number-input" placeholder="예 : 신용대출채무 / 담보대출채무">
                    </div>
                </div>
            </div>

            <div class="right-section">
                <div class="form">
                    <div class="form-title"><span>사용처</span></div>
                    <div class="form-content">
                        <input type="text" id="usageDetail_b{count}" name="usageDetail_b{count}" class="form-control form-content-long" placeholder="예 : 2020.11.20자 발급된 신용카드 사용금액 / 2021.11.20자 담보대출 / 2023.11.20자 신용대출">
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>최초채권액</span></div>
                    <div class="form-content form-row">
                        <input type="text" id="initialClaim_b{count}" name="initialClaim_b{count}" class="form-control number-input">
                        <span>원</span>
                    </div>
                </div>
                
                <div class="form">
                    <div class="form-title"><span>잔존원금</span></div>
                    <div class="form-content form-row">
                        <input type="text" id="remainingPrincipal_b{count}" name="remainingPrincipal_b{count}" class="form-control number-input">
                        <span>원</span>
                    </div>
                </div>
                
                <div class="form">
                    <div class="form-title"><span>잔존이자ㆍ<br>지연손해금</span></div>
                    <div class="form-content form-row">
                        <input type="text" id="remainingInterest_b{count}" name="remainingInterest_b{count}" class="form-control number-input">
                        <span>원</span>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>선택사항</span></div>
                    <div class="form-content">
                        <button type="button" class="form-row" id="guarantorManage_b{count}">보증인 관리</button>&nbsp;&nbsp;보증인&nbsp;&nbsp;:&nbsp;&nbsp;<span id="guarantorCount_b{count}">0</span>&nbsp;개
                    </div>
                </div>
                
                <div class="form">
                    <div class="form-title form-notitle"><span></span></div>
                    <div class="form-content form-nocontent">
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span></span></div>
                    <div class="form-content btn-right">
                        <button type="button" id="deleteCreditor_b{count}">삭제</button>
                        <button type="button" id="saveCreditor_b{count}">저장</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>`;