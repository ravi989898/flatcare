// Marketing pages: the round "back to top" button (bottom right) shows once
// the visitor has scrolled down a screen and smooth-scrolls back to the top.
(function () {
    var button = document.getElementById('back-to-top');
    if (!button) {
        return;
    }

    var update = function () {
        button.classList.toggle('is-visible', window.scrollY > window.innerHeight * 0.6);
    };

    window.addEventListener('scroll', update, { passive: true });
    update();

    button.addEventListener('click', function () {
        var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        document.getElementById('main')?.focus({ preventScroll: true });
    });
})();
