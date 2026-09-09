<script>
document.addEventListener('DOMContentLoaded', function () {
    const license = document.querySelector('select[name="has_driving_license"]');
    const gender = document.querySelector('select[name="gender"]');
    if (!license || !gender) return;
    const field = license.closest('.col-md-4');
    const fileField = document.getElementById('driving_license_file_field');
    const fileInput = fileField ? fileField.querySelector('input[type="file"]') : null;
    const requiredForMale = license.required;
    function updateDrivingLicenseFile() {
        if (!fileField) return;
        // مرفق الرخصة يظهر فقط للذكور الذين يحملون رخصة قيادة.
        const show = gender.value === 'male' && license.value === '1';
        fileField.hidden = !show;
        fileField.style.display = show ? '' : 'none';
        if (fileInput) {
            fileInput.disabled = !show;
            if (!show) fileInput.value = '';
        }
    }
    function updateDrivingLicense() {
        const male = gender.value === 'male';
        field.hidden = !male;
        field.style.display = male ? '' : 'none';
        license.disabled = !male;
        license.required = male && requiredForMale;
        if (!male) license.value = '';
        updateDrivingLicenseFile();
    }
    gender.addEventListener('change', updateDrivingLicense);
    license.addEventListener('change', updateDrivingLicenseFile);
    if (window.jQuery) {
        jQuery(gender).on('change.drivingLicense', updateDrivingLicense);
        jQuery(license).on('change.drivingLicense', updateDrivingLicenseFile);
    }
    updateDrivingLicense();
});
</script>
