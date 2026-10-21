@once
<style>
.cm-password-field{display:flex;position:relative;flex:1 1 auto;min-width:0;width:100%;align-items:stretch}
.cm-password-field>input{width:100%;min-width:0;box-sizing:border-box;padding-right:3rem!important}
.cm-password-toggle{position:absolute;right:.25rem;top:50%;transform:translateY(-50%);display:flex;align-items:center;justify-content:center;width:2.5rem;height:2.5rem;border:0;border-radius:.25rem;background:transparent;color:inherit;cursor:pointer;z-index:5}
.cm-password-toggle:focus-visible{outline:2px solid currentColor;outline-offset:1px}
.cm-password-toggle svg{width:1.25rem;height:1.25rem;pointer-events:none}
</style>
<script>
(function () {
    'use strict';
    if (window.cmPasswordVisibilityReady) return;
    window.cmPasswordVisibilityReady = true;
    var sequence = 0;
    var eye = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>';
    var eyeOff = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m3 3 18 18M9.9 5.2A13 13 0 0 1 12 5c6.5 0 10 7 10 7a19 19 0 0 1-3 3.8M6.2 6.2A20 20 0 0 0 2 12s3.5 7 10 7a13 13 0 0 0 5.8-1.8M10 10a3 3 0 0 0 4 4"/></svg>';
    function label(input) {
        return input.getAttribute('aria-label') || (input.labels && input.labels[0] && input.labels[0].textContent.trim()) || input.placeholder || (input.name || 'password').replace(/_/g, ' ');
    }
    function conceal(input, button) {
        input.type = 'password';
        button.setAttribute('aria-pressed', 'false');
        button.setAttribute('aria-label', 'Show ' + label(input));
        button.title = 'Show password';
        button.innerHTML = eye;
    }
    function enhance() {
        if (typeof document === 'undefined' || !document.body) return;
        document.querySelectorAll('input[type="password"]').forEach(function (input) {
            if (input.dataset.cmPasswordToggle || input.type === 'hidden') return;
            input.dataset.cmPasswordToggle = 'true';
            if (!input.id) {
                do { sequence++; } while (document.getElementById('cm-password-' + sequence));
                input.id = 'cm-password-' + sequence;
            }
            var wrapper = document.createElement('span');
            wrapper.className = 'cm-password-field';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'cm-password-toggle';
            button.setAttribute('aria-controls', input.id);
            conceal(input, button);
            button.addEventListener('click', function () {
                if (input.type === 'text') { conceal(input, button); return; }
                input.type = 'text';
                button.setAttribute('aria-pressed', 'true');
                button.setAttribute('aria-label', 'Hide ' + label(input));
                button.title = 'Hide password';
                button.innerHTML = eyeOff;
            });
            wrapper.appendChild(button);
        });
    }
    function hideAll() {
        document.querySelectorAll('[data-cm-password-toggle]').forEach(function (input) {
            var button = input.parentNode.querySelector('.cm-password-toggle');
            if (button) conceal(input, button);
        });
    }
    function start() {
        enhance();
        new MutationObserver(enhance).observe(document.body, {childList: true, subtree: true});
        document.addEventListener('submit', hideAll, true);
        document.addEventListener('visibilitychange', function () { if (document.hidden) hideAll(); });
        window.addEventListener('pagehide', hideAll);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once: true});
    else start();
})();
</script>
@endonce
