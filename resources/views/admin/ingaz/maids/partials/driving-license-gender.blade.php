<script>
document.addEventListener('DOMContentLoaded', function () {
    const license = document.querySelector('select[name="has_driving_license"]');
    const gender = document.querySelector('select[name="gender"]');
    if (!license || !gender) return;
    const field = license.closest('.col-md-4');
    const requiredForMale = license.required;
    function updateDrivingLicense() {
        const male = gender.value === 'male';
        field.hidden = !male;
        field.style.display = male ? '' : 'none';
        license.disabled = !male;
        license.required = male && requiredForMale;
        if (!male) license.value = '';
    }
    gender.addEventListener('change', updateDrivingLicense);
    if (window.jQuery) jQuery(gender).on('change.drivingLicense', updateDrivingLicense);
    updateDrivingLicense();
});
</script>
