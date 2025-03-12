window.creditorBoxTemplate = `<div class="creditor-box" data-count="{count}">
    <div class="section-header">
        <div class="creditor-title">
            <div class="checkbox-group">
                <span>채권자정보</span>
                <label for="creditorInfo{count}" class="checkbox-space">|</label>
                <input type="checkbox" id="creditorInfo{count}" class="creditor-checkbox" data-count="{count}">
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
                        <input type="text" id="financialInstitution{count}" name="financialInstitution" class="form-control">
                        <button type="button" class="btn btn-long btn-financial-institution" data-count="{count}">금융기관 검색</button>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>주소</span></div>
                    <div class="form-content form-row">
                        <input type="text" id="address{count}" name="address" class="form-control">
                        <button type="button" class="btn btn-search address-search" data-target="address{count}">주소찾기</button>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>연락처</span></div>
                    <div class="form-content">
                        <input type="text" id="phone{count}" name="phone" class="form-control">
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>팩스</span></div>
                    <div class="form-content">
                        <input type="text" id="fax{count}" name="fax" class="form-control">
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>원금</span></div>
                    <div class="form-content form-row">
                        <input type="text" id="principal{count}" name="principal" class="form-control number-input">
                        <span>원</span>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>채권현재액(원금)<br>산정근거</span></div>
                    <div class="form-content">
                        <input type="text" id="principalCalculation{count}" name="principalCalculation" class="form-control" placeholder="부채증명서 참고(산정기준일 : 2000.00.00)"/>
                        <input type="date" id="calculationDate{count}" name="calculationDate" class="form-control" onchange="updateCalculations({count})">
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>이자</span></div>
                    <div class="form-content form-row">
                        <input type="text" id="interest{count}" name="interest" class="form-control number-input">
                        <span>원</span>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>채권현재액(이자)<br>산정근거</span></div>
                    <div class="form-content">
                        <input type="text" id="interestCalculation{count}" name="interestCalculation" class="form-control" placeholder="부채증명서 참고(산정기준일 : 2000.00.00)" readonly/>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title form-notitle"><span>연체이율</span></div>
                    <div class="form-content form-nocontent">
                        <span>연&nbsp;&nbsp;&nbsp;</span><input type="number" id="defaultRate{count}" name="defaultRate" class="form-control" step="0.1" min="0" max="100" placeholder="약정이자">
                        <span>%</span>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span></span></div>
                    <div class="form-content">
                    </div>
                </div>
            </div>

            <div class="right-section">
                <div class="form">
                    <div class="form-title"><span>채권원인</span></div>
                    <div class="form-content">
                        <input type="text" id="claimReason{count}" name="claimReason" class="form-control form-content-long" placeholder="예 : 2020.11.20자 발급된 신용카드 사용금액 / 2021.11.20자 담보대출 / 2023.11.20자 신용대출">
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>채권내용</span></div>
                    <div class="form-content textarea">
                        <textarea id="claimContent{count}" name="claimContent" class="form-control" placeholder="원리금                            원 중 원금                          원에 대한      .        .        부터 완제일까지 약정이자의 비율에 의한 금원" rows="2"></textarea>
                        <button type="button" class="btn btn-secondary auto-fill" data-count="{count}">자동입력</button>
                    </div>
                </div>
                
                <div class="form">
                    <div class="form-title"><span>인격구분</span></div>
                    <div class="form-content">
                        <select class="form-select" id="entityType{count}" name="entityType">
                            <option value="">선택하세요</option>
                            <option value="자연인">자연인</option>
                            <option value="법인" selected>법인</option>
                            <option value="권리능력없는법인">권리능력없는법인(비법인)</option>
                            <option value="국가">국가</option>
                            <option value="지방자치단체">지방자치단체</option>
                        </select>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>부속서류</span></div>
                    <div class="form-content">
                        <div class="form-group">
                            <button type="button" class="btn-nomargin" onclick="openAppendixWindow({count})">선택</button>
                            <span>1. 별제권부채권</span>
                            <select class="form-select" id="separateBond{count}" name="separateBond">
                                <option value="(근)저당권설정" selected>(근)저당권설정</option>
                                <option value="질권설정/채권양도(전세보증금)">질권설정/채권양도(전세보증금)</option>
                                <option value="최우선변제임차권">최우선변제임차권</option>
                                <option value="우선변제임차권">우선변제임차권</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn-nomargin" onclick="openOtherClaimWindow({count})">선택</button>
                            <span>2. 다툼있는 채권</span>
                        </div>
                    </div>
                </div>
                
                <div class="form">
                    <div class="form-title"></div>
                    <div class="form-content">
                        <div class="form-group">
                            <button type="button" class="btn-nomargin" onclick="openAppendixWindow({count})">선택</button>
                            <span>3. 전부명령된 채권</span>
                        </div>
                        <div class="form-group">
                            <button type="button" class="btn-nomargin" onclick="openOtherClaimWindow({count})">선택</button>
                            <span>4. 기타(보증선 채무등)</span>
                        </div>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>기타미확정채권</span></div>
                    <div class="form-content">
                        <button type="button" class="btn-nomargin" onclick="openOtherClaimWindow({count})">선택</button>
                        <span>기타미확정채권(신탁재산 등)&ensp;|&ensp;</span><span id="otherClaimCount{count}"></span><span>개</span>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span>보증인이 있는 채무</span></div>
                    <div class="form-content">
                        <button type="button" class="btn-nomargin" onclick="openGuaranteedDebtWindow({count})">선택</button>
                        <span>보증인이 있는 채무(가지번호)&ensp;|&ensp;</span>
                        <select class="form-select" id="guaranteedDebt{count}" name="guaranteedDebt">
                            <option value="미발생" selected>미발생</option>
                            <option value="일부대위변제">일부대위변제</option>
                            <option value="전부대위변제" >전부대위변제</option>
                        </select>
                        <span>&ensp;|&ensp;</span><span id="guaranteedDebtCount{count}"></span><span>개</span>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title form-notitle"><span>선택사항</span></div>
                    <div class="form-content">
                        <div class="form-group">
                            <input type="checkbox" id="priorityPayment{count}" name="priorityPayment" class="form-check-input">
                            <label class="form-check-label" for="priorityPayment{count}">우선변제</label>
                        </div>
                        <div class="form-group">
                            <input type="checkbox" id="undeterminedClaim{count}" name="undeterminedClaim" class="form-check-input">
                            <label class="form-check-label" for="undeterminedClaim{count}">미확정채권</label>
                        </div>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span></span></div>
                    <div class="form-content">
                    
                        <div class="form-group">
                            <input type="checkbox" id="pensionDebt{count}" name="pensionDebt" class="form-check-input">
                            <label class="form-check-label" for="pensionDebt{count}">각종연금법상채무</label>
                        </div>
                        <div class="form-group">
                            <input type="checkbox" id="mortgageRestructuring{count}" name="mortgageRestructuring" class="form-check-input">
                            <label class="form-check-label" for="mortgageRestructuring{count}">주택담보대출채권 채무재조정 프로그램신청서</label>
                        </div>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title form-notitle"></div>
                    <div class="form-content form-nocontent">
                        <span>※ 세금과 같은 우선변제 채권의 경우 원금란에 원금+이자 금액을, 이자란에 0원을 입력해주십시오.</span>
                    </div>
                </div>

                <div class="form">
                    <div class="form-title"><span></span></div>
                    <div class="form-content btn-right">
                        <button type="button" id="deleteCreditor{count}">삭제</button>
						<button type="button" id="saveCreditor{count}">저장</button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>`;