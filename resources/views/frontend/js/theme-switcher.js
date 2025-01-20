$(document).ready(function() {
    $('#theme-switch').on('click', function(e) {
        e.preventDefault();
        let theme = $('#theme-stylesheet').attr('href');
        if (theme.includes('theme-light')) {
            $('#theme-stylesheet').attr('href', 'css/theme-dark.css');
        } else {
            $('#theme-stylesheet').attr('href', 'css/theme-light.css');
        }
    });
});
