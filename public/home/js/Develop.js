if (document.getElementById('submitAdd')) {
    document.getElementById('submitAdd').addEventListener('click', function (e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitAdd');
        const originalText = submitBtn.textContent || submitBtn.value;
        const form = document.getElementById("thisForm");
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return false;
        }
        
        const formData = new FormData(form);
        
        const pets = [];
        var petGroups = document.querySelectorAll('.pet-group');
        var hasError = false;
        
        petGroups.forEach(function(petGroup) {
            var petData = {};
            var fields = petGroup.querySelectorAll('[data-field]');
            fields.forEach(function(fieldElem) {
                var field = fieldElem.getAttribute('data-field');
                if (fieldElem.type === 'radio') {
                    var radioGroup = petGroup.querySelectorAll('input[type="radio"][data-field="' + field + '"]');
                    var isChecked = false;
                    radioGroup.forEach(function(radio) {
                        if (radio.checked) {
                            isChecked = true;
                            petData[field] = radio.value;
                        }
                    });
                    if (fieldElem.required && !isChecked) {
                        hasError = true;
                        fieldElem.focus();
                        layui.layer.msg('Please select ' + field, {icon: 2, time: 3000});
                    }
                } else {
                    petData[field] = fieldElem.value || '';
                    if (fieldElem.required && !fieldElem.value.trim()) {
                        hasError = true;
                        fieldElem.focus();
                        layui.layer.msg('Please enter ' + field, {icon: 2, time: 3000});
                    }
                }
            });
            var uploadInput = petGroup.querySelector('.upload-file-input[data-field="vaccinationRecord"]');
            if (uploadInput && $(uploadInput).data('file-url')) {
                petData['vaccinationRecord'] = $(uploadInput).data('file-url');
            }
            pets.push(petData);
        });
        
        if (hasError) {
            return false;
        }

        formData.append('information', JSON.stringify(pets));
        
        const validateUrl = "/api/FormValidate";
        axios.post(validateUrl, formData)
            .then(function (response) {
                const data = response.data;
                if (data.code > 0) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Submitting...';
                    const submitUrl = "/api/FormSubmit";
                    return axios.post(submitUrl, formData);
                } else {
                    layui.layer.msg(data.info, {icon: 2, time: 3000});
                    return Promise.reject('validation failed');
                }
            })
            .then(function (response) {
                const data = response.data;
                layui.layer.msg(data.info, {icon: 1, time: 3000});
                setTimeout(() => window.location.reload(), 3000);
            })
            .catch(function (error) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                if (error !== 'validation failed') {
                    console.log(error);
                    layui.layer.msg('Submission failed, please try again later!', {icon: 2, time: 3000});
                }
            });
    })
}