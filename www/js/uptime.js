(function() {
    var el = document.getElementById('uptime-display');
    if (!el) return;
    var segundos = parseInt(el.getAttribute('data-segundos'), 10);
    if (isNaN(segundos) || segundos < 0) return;

    function formatear(s) {
        var d = Math.floor(s / 86400);
        var h = Math.floor((s % 86400) / 3600);
        var m = Math.floor((s % 3600) / 60);
        var sec = s % 60;
        if (d > 0) return d + 'd ' + h + 'h ' + m + 'm';
        if (h > 0) return h + 'h ' + m + 'm';
        if (m > 0) return m + 'm ' + sec + 's';
        return sec + 's';
    }

    setInterval(function() {
        segundos++;
        el.textContent = '⏱️ ' + formatear(segundos);
    }, 1000);
})();
