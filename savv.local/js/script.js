document.addEventListener('DOMContentLoaded', function() {
    
    var form = document.getElementById('surveyForm');
    if (!form) return;
    
    var q1Radios = document.querySelectorAll('input[name="q1"]');
    var q2Block = document.getElementById('q2Block');
    var q2Radios = document.querySelectorAll('input[name="q2"]');
    
    function toggleQ2Block() {
        var finished = document.querySelector('input[name="q1"][data-finished]:checked');
        
        if (finished) {
            q2Block.classList.remove('hidden');
            q2Radios.forEach(function(radio) {
                radio.required = true;
            });
        } else {
            q2Block.classList.add('hidden');
            q2Radios.forEach(function(radio) {
                radio.required = false;
                radio.checked = false;
            });
            var other = document.getElementById('q2_other');
            if (other) {
                other.value = '';
                other.disabled = true;
                other.required = false;
            }
        }
    }
    
    function toggleOtherFields() {
        var triggers = document.querySelectorAll('[data-other]');
        triggers.forEach(function(trigger) {
            var targetId = trigger.dataset.other;
            var target = document.getElementById(targetId);
            if (!target) return;
            
            var isActive = false;
            
            if (trigger.type === 'checkbox') {
                isActive = trigger.checked;
            } else if (trigger.type === 'radio') {
                isActive = trigger.checked;
            }
            
            if (isActive) {
                target.disabled = false;
                target.required = true;
                target.focus();
            } else {
                target.disabled = true;
                target.required = false;
                target.value = '';
            }
        });
    }
    
    function toggleQ12Other() {
        var q12Yes = document.getElementById('q12_yes');
        var q12Text = document.getElementById('q12_text');
        if (!q12Yes || !q12Text) return;
        
        if (q12Yes.checked) {
            q12Text.disabled = false;
            q12Text.required = true;
            q12Text.focus();
        } else {
            q12Text.disabled = true;
            q12Text.required = false;
            q12Text.value = '';
        }
    }
    
    function validateForm() {
        var requiredFields = form.querySelectorAll('[required]');
        var allValid = true;
        
        requiredFields.forEach(function(field) {
            if (field.type === 'radio') {
                var name = field.name;
                var checked = form.querySelector('input[name="' + name + '"]:checked');
                if (!checked) {
                    allValid = false;
                }
            } else if (field.type === 'checkbox' && field.required) {
                var name = field.name;
                var checked = form.querySelector('input[name="' + name + '"]:checked');
                if (!checked) {
                    allValid = false;
                }
            } else if (field.value.trim() === '') {
                allValid = false;
            }
        });
        
        if (!allValid) {
            alert('Пожалуйста, ответьте на все обязательные вопросы (отмечены *).');
            return false;
        }
        
        var textareas = form.querySelectorAll('textarea[maxlength]');
        for (var i = 0; i < textareas.length; i++) {
            var ta = textareas[i];
            if (ta.value.length > parseInt(ta.maxLength)) {
                alert('Текст не должен превышать ' + ta.maxLength + ' символов.');
                ta.focus();
                return false;
            }
        }
        
        return true;
    }
    
    q1Radios.forEach(function(radio) {
        radio.addEventListener('change', toggleQ2Block);
        radio.addEventListener('click', toggleQ2Block);
    });
    
    document.querySelectorAll('input[data-other]').forEach(function(input) {
        input.addEventListener('change', toggleOtherFields);
        input.addEventListener('click', toggleOtherFields);
    });
    
    var q12Yes = document.getElementById('q12_yes');
    var q12No = document.getElementById('q12_no');
    if (q12Yes) {
        q12Yes.addEventListener('change', toggleQ12Other);
        q12Yes.addEventListener('click', toggleQ12Other);
    }
    if (q12No) {
        q12No.addEventListener('change', toggleQ12Other);
        q12No.addEventListener('click', toggleQ12Other);
    }
    
    form.addEventListener('submit', function(e) {
        toggleQ2Block();
        toggleOtherFields();
        toggleQ12Other();
        
        if (!validateForm()) {
            e.preventDefault();
        }
    });

    toggleQ2Block();
    toggleOtherFields();
    toggleQ12Other();
    
    console.log('✅ Опрос загружен, поля "Другое" работают!');
});