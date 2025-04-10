// 문서 목록 관리 클래스
class DocumentListManager {
	constructor() {
		this.initialize();
	}

	initialize() {
		this.bindEvents();
	}

	bindEvents() {
		// 파일 다운로드 버튼 이벤트 바인딩
		// 페이지 타입(회생/파산)에 따라 이벤트 바인딩 최대값 결정
		const maxButtons = this.isRecoveryPage() ? 6 : 4;
		
		for (let i = 1; i <= maxButtons; i++) {
			$(`#filedown${i}`).on('click', (e) => {
				e.preventDefault();
				this.downloadFile(i);
			});
		}
	}
	
	// 현재 페이지가 회생인지 파산인지 확인하는 메소드
	isRecoveryPage() {
		// URL에 recovery가 포함되어 있으면 회생 페이지로 판단
		return window.location.href.indexOf('recovery') > -1;
	}

	downloadFile(fileIndex) {
		// 페이지 타입 결정 (회생/파산)
		const pageType = this.isRecoveryPage() ? 'recovery' : 'bankruptcy';
		
		// 파일 인덱스에 따른 지역과 파일 이름 매핑
		const fileMap = {
			recovery: {
				1: { region: "seoul_etc", fileName: "seoul_etc.hwp" },
				2: { region: "gangneung", fileName: "gangneung.hwp" },
				3: { region: "daegu", fileName: "daegu.hwp" },
				4: { region: "daejeon", fileName: "daejeon.hwp" },
				5: { region: "busan", fileName: "busan.hwp" },
				6: { region: "cheongju", fileName: "cheongju.hwp" }
			},
			bankruptcy: {
				1: { region: "seoul_etc", fileName: "seoul_etc.hwp" },
				2: { region: "gangneung", fileName: "gangneung.hwp" },
				3: { region: "daejeon", fileName: "daejeon.hwp" },
				4: { region: "cheongju", fileName: "cheongju.hwp" }
			}
		};

		// 현재 선택된 파일 정보
		const fileInfo = fileMap[pageType][fileIndex];
		if (!fileInfo) {
			alert('파일 정보를 찾을 수 없습니다.');
			return;
		}

		// 다운로드 요청 처리
		this.processDownload(fileInfo.region, fileInfo.fileName, pageType);
	}

	processDownload(region, fileName, type) {
		// 로딩 표시 (선택사항)
		this.showLoading();

		// 파일 다운로드 URL 생성
		const downloadUrl = `api/download_document_list.php?region=${region}&filename=${encodeURIComponent(fileName)}&case_no=${window.currentCaseNo}&type=${type}`;
		
		// iframe을 사용한 다운로드 처리 (팝업 차단 방지)
		const iframe = document.createElement('iframe');
		iframe.style.display = 'none';
		iframe.src = downloadUrl;
		document.body.appendChild(iframe);
		
		// 짧은 시간 후 iframe 제거
		setTimeout(() => {
			document.body.removeChild(iframe);
			this.hideLoading();
		}, 2000);
	}

	showLoading() {
		// 로딩 인디케이터가 이미 있으면 보여주고, 없으면 생성
		if ($('#loadingIndicator').length === 0) {
			$('body').append(`
				<div id="loadingIndicator">
					<div>파일 다운로드 중입니다...</div>
				</div>
			`);
		} else {
			$('#loadingIndicator').show();
		}
	}

	hideLoading() {
		$('#loadingIndicator').hide();
	}
}

// 문서 목록 탭이 활성화될 때 초기화
$(document).ready(function() {
	window.documentListManager = new DocumentListManager();
});