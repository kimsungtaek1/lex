<?php
/**
* SMS 발송을 관장하는 메인 클래스이다.
*
* 접속, 발송, URL발송, 결과등의 실질적으로 쓰이는 모든 부분이 포함되어 있다.
*/
class SMS {
  var $icode_key;
  var $socket_host;
  var $socket_port;
  var $Data = array();
  var $Result = array();

  // SMS 서버 접속
  function SMS_con($host, $port, $key) {
    $this->socket_host = $host;
    $this->socket_port = $port;
    $this->icode_key = $key;
  }
    
  function Init() {
    $this->Data = array();    // 발송하기 위한 패킷내용이 배열로 들어간다.
    $this->Result = array();    // 발송결과값이 배열로 들어간다.
  }

  /**
   * 발송 패킷 생성
   * Add(수신번호목록(배열), 발신번호, 발송내용(2000자이내), 제목(옵션, 30자이내), 예약일자(옵션, 12자리)
   */
  function Add($strTelList, $strCallBack, $strData, $strSubject='', $strDate='') {
    // 개행치환
    $strData = preg_replace("/\r\n/","\n",$strData);
    $strData = preg_replace("/\r/","\n",$strData);
    
    // 문자 타입별 Port 설정.
    $sendType = strlen($strData)>90 ? 1 : 0; // 0: SMS / 1: LMS
    if($sendType==0) $strSubject = "";

    $strCallBack = CutChar($strCallBack, 12);       // 회신번호
      
    /** LMS 제목 **/
    /*
    제목필드의 값이 없을 경우 단말기 기종및 설정에 따라 표기 방법이 다름
    1.설정에서 제목필드보기 설정 Disable -> 제목필드값을 넣어도 미표기
    2.설정에서 제목필드보기 설정 Enable  -> 제목을 넣지 않을 경우 제목없음으로 자동표시
            
    제목의 첫글자에 "<",">", 개행문자가 있을경우 단말기종류 및 통신사에 따라 메세지 전송실패 -> 글자를 체크하거나 취환처리요망
    $strSubject = str_replace("\r\n", " ", $strSubject); 
    $strSubject = str_replace("<", "[", $strSubject); 
    $strSubject = str_replace(">", "]", $strSubject); 
    */

    $strSubject = CutCharUtf8($strSubject,30);
    $strData    = CutCharUtf8($strData,2000);

    /* 필수 항목에 대해 정상적인 코드인지 검사 과정.
    개발 방식에 따라 활용 
    $Error = CheckCommonTypeDest($strTelList); // 번호 검사
    $Error = IsVaildCallback($strCallBack);
    $Error = CheckCommonTypeDate($strDate);
    */

    
    foreach ($strTelList as $tel) {
      if(empty($tel)) continue;
      $list = array(
        "key" => $this->icode_key, 
        "tel" => $tel,
        "cb" => $strCallBack,
        "msg" => $strData
      );
      if(!empty($strSubject)) $list['title'] = $strSubject;
      if(!empty($strDate)) $list['date'] = $strDate;
      $packet = json_encode($list);
      $this->Data[] = '06'.str_pad(strlen($packet), 4, "0", STR_PAD_LEFT).$packet;
    }
    return true; 
  }

  /**
   * 문자발송 및 결과정보를 수신합니다.
   */
	function Send() {
		$fsocket = fsockopen($this->socket_host,$this->socket_port, $errno, $errstr, 2);
		if (!$fsocket) return false;
		set_time_limit(300);

		foreach($this->Data as $puts) {
			fputs($fsocket, $puts);
			$gets = '';  // $gets 변수 초기화
			while(!$gets) { $gets = fgets($fsocket,32); }

			$chk = preg_match("/\"tel\":\"([0-9]*)\"/", substr($puts,6), $matches);
			$desc = $matches[1];
			$resultCode = substr($gets,6,2);
			if ($resultCode == '00' || $resultCode == '17') { // 17은 접수(발송)대기.
				$this->Result[] = $resultCode.":".substr($gets,8,12).":".substr($gets,20,11);
			} else {
				$this->Result[] = $desc.":Error(".substr($gets,6,2).")";
				if(substr($gets,6,2) >= "80") break;
			}
			$gets = "";
		}

		fclose($fsocket);
		$this->Data = array();
		return true;
	}

}

/**
 * 원하는 문자열의 길이를 원하는 길이만큼 공백을 넣어 맞추도록 합니다.
 *
 * @param   text  원하는 문자열입니다.
 *          size  원하는 길이입니다.
 * @return        변경된 문자열을 넘깁니다.
 */
function FillSpace($text,$size) {
  for ($i=0; $i<$size; $i++) $text.= " ";
  $text = substr($text,0,$size);
  return $text;
}

/**
 * 원하는 문자열을 원하는 길에 맞는지 확인해서 조정하는 기능을 합니다.
 *
 * @param   word  원하는 문자열입니다.
 *          cut   원하는 길이입니다.
 * @return        변경된 문자열입니다.
 */
function CutChar($word, $cut) {
  $word=substr($word,0,$cut); // 필요한 길이만큼 취함.
  for ($k = $cut-1; $k > 1; $k--) {     
    if (ord(substr($word,$k,1))<128) break; // 한글값은 160 이상.
  }
  $word = substr($word, 0, $cut-($cut-$k+1)%2);
  return $word;
}

function CutCharUtf8($word, $cut) {
  preg_match_all('/[\xE0-\xFF][\x80-\xFF]{2}|./', $word, $match); // target for BMP

  $m = $match[0];
  $slen = strlen($word); // length of source string
  if ($slen <= $cut) return $word;
  
  $ret = array();
  $count = 0;
  for ($i=0; $i < $cut; $i++) {
      $count += (strlen($m[$i]) > 1)?2:1;
      if ($count > $cut) break;
      $ret[] = $m[$i];
  }

  return join('', $ret);
}


/**
 * 잘못된 수신번호 목록을 리턴합니다.
 *
 * @param   strTelList  발송번호 배열.
 * @return              잘못된 수신번호 목록.
 */
function CheckCommonTypeDest($strTelList) {
  $result = '';
  foreach ($strTelList as $tel) {
    $tel = preg_replace("/[^0-9]/","",$tel);
    if(!preg_match("/^(0[173][0136789])([0-9]{3,4})([0-9]{4})$/", $tel)) $result .= $tel.',';
  }
  return $result;
}


/**
 * 회신번호 유효성 여부조회 
 * 한국인터넷진흥원 권고사항
 *
 * @param  string callback  회신번호
 * @return                  처리결과입니다
 */
function IsVaildCallback($callback){
  $_callback = preg_replace('/[^0-9]/', '', $callback);
  if (!preg_match("/^(02|0[3-6]\d|01(0|1|3|5|6|7|8|9)|070|080|007)\-?\d{3,4}\-?\d{4,5}$/", $_callback) && 
    !preg_match("/^(15|16|18)\d{2}\-?\d{4,5}$/", $_callback)) return "회신번호오류";    
  if (preg_match("/^(02|0[3-6]\d|01(0|1|3|5|6|7|8|9)|070|080)\-?0{3,4}\-?\d{4}$/", $_callback)) return "회신번호오류";
  return '';
}

/**
 * 문자열을 JSON 사용가능 타입으로 변환한다.
 */
function EscapeJsonString($value) {
  $escapers =     array('\\',  '"');
  $replacements = array('\\\\', '\"');
  $result = str_replace($escapers, $replacements, $value);
  return $result;
}

/**
 * 예약날짜의 값이 정확한 값인지 확인합니다.
 *
 * @param   string strDate  예약시간
 * @return                  처리결과입니다
 */
function CheckCommonTypeDate($strDate) {
  $strDate = preg_replace("/[^0-9]/", "", $strDate);
  if ($strDate){
    if(strlen($strDate) != 12) return '예약날짜오류';
    if (!checkdate(substr($strDate,4,2),substr($strDate,6,2),substr($rsvTime,0,4))) return "예약날짜오류";        
    if (substr($strDate,8,2)>23 || substr($strDate,10,2)>59) return "예약시간오류";        
  }
  return '';
}
?>