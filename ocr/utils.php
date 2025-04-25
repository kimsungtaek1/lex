<?php
/**
 * 공통 유틸리티 함수
 */

/**
 * HTML 출력용 텍스트 이스케이프
 */
function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * JavaScript 이스케이프
 */
function js_escape($text) {
    return json_encode($text);
}

/**
 * URL 파라미터 이스케이프
 */
function url_param($text) {
    return urlencode($text);
}