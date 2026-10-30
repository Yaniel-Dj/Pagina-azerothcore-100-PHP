function cambiarTab(tab, el) {
    document.querySelectorAll('.tab').forEach(function(t) { t.classList.remove('activo'); });
    document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('activo'); });
    el.classList.add('activo');
    document.getElementById('tab-' + tab).classList.add('activo');
}

function mostrarSeccion(sec) {
    ['tienda', 'noticias', 'gm'].forEach(function(s) {
        var e = document.getElementById('seccion-' + s);
        if (e) e.style.display = (s === sec) ? 'block' : 'none';
    });
}
