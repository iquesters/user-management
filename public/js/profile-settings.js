// Settings page (resources/views/profile/profile-setting.blade.php).
// The forms themselves are schema forms (form.js); this only keeps the open
// section in the URL (?tab=) so a reload, or a redirect after saving, stays on it.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#settingsNav [data-bs-toggle="pill"]').forEach(function (button) {
        button.addEventListener('shown.bs.tab', function () {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', button.dataset.section);
            history.replaceState(null, '', url);
        });
    });
});
