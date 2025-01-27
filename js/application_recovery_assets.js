$(document).ready(function() {
    // 라디오 버튼 변경 이벤트
    $('.asset-box input[type="radio"]').on('change', function() {
        const name = $(this).attr('name');
        const selectedValue = $(this).val();
        const $radioGroup = $(`input[name="${name}"]`);
        
        // 라디오 버튼 그룹의 data-selected 속성 업데이트
        $radioGroup.each(function() {
            const isSelected = $(this).val() === selectedValue;
            $(this).attr('data-selected', isSelected.toString());
            $(`label[for="${this.id}"]`).attr('data-selected', isSelected.toString());
        });
    });

    // 초기 상태 설정
    $('.asset-box input[type="radio"]:checked').each(function() {
        const name = $(this).attr('name');
        const selectedValue = $(this).val();
        const $radioGroup = $(`input[name="${name}"]`);
        
        $radioGroup.each(function() {
            const isSelected = $(this).val() === selectedValue;
            $(this).attr('data-selected', isSelected.toString());
            $(`label[for="${this.id}"]`).attr('data-selected', isSelected.toString());
        });
    });
});