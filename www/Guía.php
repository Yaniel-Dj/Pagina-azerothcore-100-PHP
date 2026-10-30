<?php
session_start();
require_once __DIR__ . '/lang.php';

$guias = [
  ['slug'=>'mineria','nombre'=>'Minería','color'=>'#b8923e','icono'=>'fa-mountain','niveles'=>[
    ['1–65','Durotar, Dun Morogh, Elwynn, Teldrassil, Mulgore, Tirisfal','Cobre. Funde hasta skill ~25, luego solo minar sube. Prioriza cuevas: Foso de las Sombras (Durotar), Cuevas de Dun Morogh, mina de Elwynn.'],
    ['65–125','Los Baldíos, Mil Agujas, Crestagrana, Bosque de Argénteos','Estaño principal; la plata sale mezclada. Funde Bronce (cobre+estaño) hasta ~110 para subir rápido. Mil Agujas es la mejor zona.'],
    ['125–175','Tierras Altas de Arathi, Mil Agujas, Tierras del Interior','Hierro. A 155 aprendes Oro (raro, sale pegado al hierro). Arathi es la zona reina: vetas abundantes y accesibles.'],
    ['175–245','Arathi, Tierras del Interior, Feralas, Tanaris','Mithril base hasta 245. A 230 aprendes Veraplata (rara). Combina Arathi para mithril, Feralas/Tanaris para veraplata.'],
    ['245–275','Frondavil, Cráter de Un\'Goro, Silithus','Aprendes Torio. Frondavil es lo más seguro (zona 45–55). Un\'Goro más vetas pero más mobs. Silithus reserva para después.'],
    ['275–300','Silithus, Frondavil, Tierras de la Peste del Este','Torio puro. Silithus es la mejor zona pero es 55–60. Funde Barras de Torio: se venden bien en subasta.'],
    ['300–325','Península del Fuego Infernal, Marisma de Zangar','Aprende Gran Maestro en Honor Hold / Thrallmar y empieza Fel Iron. Fundir sube hasta ~310.'],
    ['325–350','Nagrand, Espina de Sangre, Marisma de Zangar','Fel Iron hasta ~340 y luego Adamantita. Nagrand tiene cuevas con ambas menas juntas.'],
    ['350–375','Nagrand, Valle Sombraluna, Espina de Sangre','Adamantita pura hasta 375. Sombraluna rinde más pero mobs 67–70; ve con cuidado.'],
    ['375–400','Tundra Boreal, Fiordo Aquilonal','Viaja a Rasganorte, aprende Gran Maestro en Valgarde / Vengeance Landing. Cobalto en Tundra Boreal (Fizzcrank Airstrip).'],
    ['400–425','Cuenca de Sholazar, Zul\'Drak, Colinas Pardas','Saronita. Sholazar es la mejor: mucho verde, poca hostilidad. El Titanio sale mezclado: guárdalo.'],
    ['425–450','Sholazar, Corona de Hielo, Cumbres Tormentosas','Saronita hasta 450. El Titanio se vende muy caro. ¡Felicidades, 450 Minería!'],
  ]],
  ['slug'=>'herboristeria','nombre'=>'Herboristería','color'=>'#5a8a5e','icono'=>'fa-leaf','niveles'=>[
    ['1–70','Durotar, Elwynn, Teldrassil, Mulgore, Tirisfal, Dun Morogh','Hierba paz y hoja de tierra. Aparecen casi en cualquier sitio de zona inicial; no vendas nada, todo sirve.'],
    ['70–115','Mulgore, Los Baldíos, Bosque de Argénteos, Loch Modan','Raíz de tierra y hoja de vida. Los Baldíos es la mejor por densidad.'],
    ['115–160','Los Baldíos, Ashenvale, Trabalomas, Mil Agujas','Hierba bruja y espina de oro. Sigue en Baldíos hasta 130, luego salta a Ashenvale.'],
    ['160–210','Desolace, Tuercespina, Feralas, Tierras del Interior','Loto cárdeno y hierba de fuego. Tuercespina es buena: hierbas junto a agua.'],
    ['210–245','Feralas, Tierras del Interior, Tuercespina','Hierba de ensueño y sangrerregia. Feralas tiene mucha densidad.'],
    ['245–280','Feralas, Tuercespina, Tierras del Interior','Sangrerregia y hierba de ensueño. Si subes con Alquimia, guarda todo.'],
    ['280–300','Frondavil, Un\'Goro, Silithus','Ginseng y loto cárdeno. Frondavil cómodo; Un\'Goro más denso pero peligroso.'],
    ['300–325','Fuego Infernal, Marisma de Zangar','Aprende Gran Maestro y empieza hierba vil. Zangar tiene mucho junco de esporas.'],
    ['325–350','Nagrand, Bosque de Terokkar','Hierba de maná y fuego vil. Nagrand es la más tranquila para farmear.'],
    ['350–375','Nagrand, Tormenta Abisal','Hierba de maná y Campanilla de las Sombras. Sigue en Nagrand hasta 375.'],
    ['375–400','Tundra Boreal, Fiordo Aquilonal','Aprende Gran Maestro en Rasganorte y empieza roña de oro. Tundra es la mejor zona.'],
    ['400–425','Sholazar, Zul\'Drak','Adelfilla y loto helado. Sholazar es la reina: mucho verde y poca amenaza.'],
    ['425–450','Sholazar, Corona de Hielo','Loto helado y adelfilla. Guarda Loto helado: es oro puro en subasta.'],
  ]],
  ['slug'=>'peleteria','nombre'=>'Peletería','color'=>'#7a6040','icono'=>'fa-paw','niveles'=>[
    ['1–75','Durotar, Mulgore, Elwynn, Dun Morogh, Teldrassil','Desuella bestias 5–15. Cuero ligero. Todo lo que veas con pelaje, desuéllelo.'],
    ['75–150','Los Baldíos, Mil Agujas, Argénteos, Loch Modan','Cuero medio. Bestias 15–30: jabalíes, raptors, osos. Los Baldíos rinde mucho.'],
    ['150–225','Tuercespina, Tierras del Interior, Mil Agujas','Cuero pesado. Bestias 30–45: raptors de Tuercespina son la mina de oro.'],
    ['225–275','Feralas, Tanaris, Un\'Goro','Cuero grueso. Bestias 45–55. Un\'Goro tiene dinosaurios que dan cuero en abundancia.'],
    ['275–300','Silithus, Frondavil, Peste del Este','Cuero grueso y escamas. Silithus tiene insectos que sueltan quitina útil.'],
    ['300–330','Fuego Infernal, Marisma de Zangar','Cuero de Terrallende. Jabalíes infernales y bestias de Zangar.'],
    ['330–375','Nagrand, Terokkar, Sombraluna','Cuero exótico. Nagrand es la mejor: clefthoofs por todas partes.'],
    ['375–405','Tundra Boreal, Fiordo Aquilonal','Cuero boreal. Bestias de Rasganorte: osos, lobos. Tundra es más fácil.'],
    ['405–450','Sholazar, Zul\'Drak, Corona de Hielo','Cuero helado y escamas. Sholazar tiene bestias exóticas muy rentables.'],
  ]],
  ['slug'=>'sastreria','nombre'=>'Sastrería','color'=>'#7a5a86','icono'=>'fa-scissors','niveles'=>[
    ['1–75','Cualquier capital','Tela de lino. La sacas de humanoides nivel 5–15 o compras en subasta. Empieza con camisas y cinturones.'],
    ['75–150','Los Baldíos, Argénteos, Mil Agujas','Tela de lana. Humanoides nivel 15–30. Funde y sube con bolsas de lana.'],
    ['150–225','Tuercespina, Tierras del Interior','Seda y mageweave. Piratas de Tuercespina sueltan mucha seda.'],
    ['225–300','Feralas, Tanaris, Silithus','Tela de runa y tejido mágico. Humanoides 50–60. Feralas es la mejor zona.'],
    ['300–375','Terrallende completo','Seda vil y tejido de hechicería. Compra en subasta si puedes; farmear tarda.'],
    ['375–450','Rasganorte completo','Paño helado y tejido de escarcha. Sube con bolsas y capas básicas.'],
  ]],
  ['slug'=>'herreria','nombre'=>'Herrería','color'=>'#6b7a8a','icono'=>'fa-hammer','niveles'=>[
    ['1–75','Capital + subasta','Barras de cobre y piedra rugiente. Compra cobre en subasta o mina tú mismo.'],
    ['75–150','Subasta / mina','Barras de bronce y hierro. Funde bronce tú mismo (cobre+estaño).'],
    ['150–225','Subasta / mina','Barras de acero y mithril. Fundir con carbón. Necesitas Mina si no compras.'],
    ['225–300','Frondavil / Silithus','Barras de torio. Funde todo el torio que consigas: sube mucho skill.'],
    ['300–375','Terrallende','Barras de fel iron y adamantita. Compra en subasta o mina en Fuego Infernal.'],
    ['375–450','Rasganorte','Barras de cobalto y saronita. Cobalto es barato; saronita más caro.'],
  ]],
  ['slug'=>'joyeria','nombre'=>'Joyería','color'=>'#a05a86','icono'=>'fa-gem','niveles'=>[
    ['1–50','Subasta','Barras de cobre + gemas verdes baratas (ágata, ojo de tigre). Cuesta poco, sube rápido.'],
    ['50–150','Subasta','Bronce, plata, hierro + gemas comunes. Anillos y collares básicos.'],
    ['150–225','Subasta','Mithril y oro + gemas verdes. Necesitas algo de oro inicial.'],
    ['225–300','Subasta','Torio + gemas de Azeroth. Tramo caro si no minas tú mismo.'],
    ['300–375','Terrallende + subasta','Fel iron + gemas de Terrallende. Corta gemas para subir.'],
    ['375–450','Rasganorte + subasta','Cobalto, saronita + gemas de Rasganorte. Muy rentable al final por los epics.'],
  ]],
  ['slug'=>'alquimia','nombre'=>'Alquimia','color'=>'#5a8a8a','icono'=>'fa-flask','niveles'=>[
    ['1–60','Zonas iniciales','Hierba paz y hoja de tierra. Compra en subasta o recoge tú con Herboristería.'],
    ['60–150','Nivel medio','Raíz de tierra y hoja de vida. Pociones básicas de curación.'],
    ['150–225','Zona media-alta','Hierba bruja y loto cárdeno. Elixires útiles para levear.'],
    ['225–300','Zona alta','Hierba de ensueño y ginseng. Pociones de maná que se venden bien.'],
    ['300–375','Terrallende','Hierba vil y hierba de maná. Sube con elixires y transmutaciones.'],
    ['375–450','Rasganorte','Roña de oro y loto helado. Frascos de raid son el objetivo final.'],
  ]],
  ['slug'=>'encantamiento','nombre'=>'Encantamiento','color'=>'#6b6ba0','icono'=>'fa-wand-magic-sparkles','niveles'=>[
    ['1–50','Objetos verdes nivel 1–15','Desencanta verdes baratos de subasta. Polvo arcano.'],
    ['50–150','Objetos verdes nivel 15–40','Esencia mágica. Compra verdes baratos o farmea humanoides.'],
    ['150–225','Objetos verdes 40–55','Esencia astral y eterna. Los verdes suben de precio aquí.'],
    ['225–300','Objetos verdes 55–60','Esencia ilusoria y onírica. Los verdes de 60 cuestan más.'],
    ['300–375','Terrallende','Polvo abisal de verdes de Terrallende. Compra verdes al 60–70.'],
    ['375–450','Rasganorte','Polvo infinito de verdes de Rasganorte. Tramo caro pero muy rentable al final.'],
  ]],
  ['slug'=>'ingenieria','nombre'=>'Ingeniería','color'=>'#8a6b4a','icono'=>'fa-gears','niveles'=>[
    ['1–75','Mina o subasta','Barras de cobre + piedra rugiente. Bombas básicas.'],
    ['75–150','Subasta','Bronce y hierro. Artefactos de nivel medio y munición.'],
    ['150–225','Subasta','Acero y mithril. Mirones y aparatos útiles para PvP.'],
    ['225–300','Subasta','Torio + gemas y cueros. Robots y gafas de nivel 60.'],
    ['300–375','Terrallende','Fel iron y adamantita. Empiezas a hacer gafas épicas.'],
    ['375–450','Rasganorte','Cobalto y saronita. Gafas 200 y monturas míticas.'],
  ]],
  ['slug'=>'inscripcion','nombre'=>'Inscripción','color'=>'#8a7048','icono'=>'fa-scroll','niveles'=>[
    ['1–75','Hierbas iniciales','Pigmentos de hierba paz y hoja de tierra. Necesitas Herboristería o subasta.'],
    ['75–150','Hierbas medias','Raíz de tierra y hoja de vida. Glifos menores.'],
    ['150–225','Hierbas medias-altas','Hierba bruja y loto cárdeno. Pergaminos de mejora.'],
    ['225–300','Hierbas altas','Hierba de ensueño y ginseng. Glifos importantes.'],
    ['300–375','Terrallende','Pigmentos de hierbas vil. Glifos para 70.'],
    ['375–450','Rasganorte','Pigmentos de Rasganorte. Glifos 80 y mejoras de hombro.'],
  ]],
  ['slug'=>'desuello','nombre'=>'Desuello','color'=>'#7a4a3a','icono'=>'fa-bone','niveles'=>[
    ['1–75','Zonas iniciales','Bestias 5–15. Cuero ligero. Desuella TODO lo que mates.'],
    ['75–150','Los Baldíos, Mil Agujas, Argénteos, Loch Modan','Bestias 15–30. Cuero medio. Los Baldíos es la mina.'],
    ['150–225','Tuercespina, Tierras del Interior, Mil Agujas','Bestias 30–45. Cuero pesado. Raptors de Tuercespina rinden mucho.'],
    ['225–275','Feralas, Tanaris, Un\'Goro','Bestias 45–55. Cuero grueso. Un\'Goro con dinosaurios es ideal.'],
    ['275–300','Silithus, Frondavil, Peste del Este','Bestias 55–60. Cuero grueso y escamas.'],
    ['300–375','Terrallende','Cuero de Terrallende. Clefthoofs de Nagrand son la fuente principal.'],
    ['375–450','Rasganorte','Cuero boreal y helado. Sholazar es la mejor zona.'],
  ]],
  ['slug'=>'cocina','nombre'=>'Cocina','color'=>'#a0703e','icono'=>'fa-utensils','niveles'=>[
    ['1–40','Zonas iniciales','Carne de jabalí y lobo. Cualquier bestia cercana a capital.'],
    ['40–150','Los Baldíos, Crestagrana','Carne de nivel medio. Compra recetas en capitales.'],
    ['150–225','Tierras del Interior, Tanaris','Carne 30–45. Recetas en Tantún / Gadgetzan.'],
    ['225–300','Feralas, Silithus','Carne 45–60. Recetas de festín en Feralas.'],
    ['300–375','Fuego Infernal, Nagrand','Carne de Terrallende. Recetas de festín para grupos.'],
    ['375–450','Tundra Boreal, Sholazar','Carne de Rasganorte. Festines de raid son el objetivo.'],
  ]],
  ['slug'=>'pesca','nombre'=>'Pesca','color'=>'#4a7090','icono'=>'fa-fish','niveles'=>[
    ['1–75','Capitales y zonas iniciales','Cualquier agua dulce sirve. Compra caña básica en el instructor.'],
    ['75–150','Baldíos, Argénteos, Loch Modan','Aguas medias. Peces para cocina media.'],
    ['150–225','Tuercespina, Tierras del Interior, Tanaris','Aguas de nivel medio-alto. Sube con la caña que compraste.'],
    ['225–300','Feralas, Silithus, Frondavil','Aguas de nivel alto. Peces raros para recetas.'],
    ['300–375','Terrallende (Fuego Infernal, Nagrand, Zangar)','Aguas de Terrallende. Peces para cocina 70.'],
    ['375–450','Rasganorte (Tundra, Sholazar, Corona)','Aguas heladas. Pez glacial y recetas de festín 80.'],
  ]],
  ['slug'=>'primeros_auxilios','nombre'=>'Primeros Auxilios','color'=>'#8a4a4a','icono'=>'fa-kit-medical','niveles'=>[
    ['1–75','Tela de lino','Humanoides 5–15. Compra en subasta si no farmeas.'],
    ['75–150','Tela de lana','Humanoides 15–30. Vendas gruesas de lana.'],
    ['150–225','Tela de seda','Humanoides 30–40. Vendas de seda.'],
    ['225–300','Tela mageweave y runa','Humanoides 40–60. Vendas de mageweave y runa.'],
    ['300–375','Tela de seda vil','Terrallende. Vendas de seda vil y antídotos.'],
    ['375–450','Paño helado','Rasganorte. Vendas de escarcha para raids.'],
  ]],
];
function e($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}
?>
<!DOCTYPE html>
<html lang="<?= $idioma ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e(t('guias_titulo')) ?> · 1–450</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{--panel:#161d25;--border:#2a333d;--gold:#c9a961;--gold-dim:#8a7439;--text:#d8dde3;--muted:#7a8590}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;font-family:'Segoe UI',-apple-system,system-ui,sans-serif;color:var(--text);line-height:1.55;font-size:14px;
  background:radial-gradient(ellipse 100% 400px at 50% 0%,#1a2634 0%,transparent 70%),linear-gradient(180deg,rgba(10,15,20,.92) 0%,rgba(13,19,25,.96) 100%),url('img/fondo.jpg') center/cover fixed no-repeat;min-height:100vh}
.header{text-align:center;padding:2rem 1rem 2rem;border-bottom:1px solid var(--border);position:relative}
.header-top{display:flex;justify-content:space-between;align-items:center;gap:.6rem;margin-bottom:1.4rem;flex-wrap:wrap}
.btn-volver{display:inline-flex;align-items:center;gap:.4rem;padding:.5rem .9rem;background:rgba(201,169,97,.08);border:1px solid var(--gold-dim);border-radius:3px;color:var(--gold);text-decoration:none;font-size:.68rem;letter-spacing:1.5px;text-transform:uppercase;transition:all .2s;font-family:inherit}
.btn-volver:hover{background:var(--gold);color:#0a0f14;border-color:var(--gold)}
.btn-volver i{font-size:.75rem}
.lang{display:inline-flex;align-items:center;gap:2px;padding:3px;background:rgba(0,0,0,.3);border:1px solid rgba(255,255,255,.08);border-radius:20px;margin-left:auto}
.lang a{display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border-radius:14px;font-size:.7rem;font-weight:700;text-decoration:none;color:var(--muted);transition:.2s;white-space:nowrap}
.lang a:hover{color:var(--gold);background:rgba(201,169,97,.08)}
.lang a.active{background:linear-gradient(180deg,rgba(201,169,97,.28),rgba(201,169,97,.14));color:var(--gold);border:1px solid rgba(201,169,97,.45);box-shadow:0 0 10px rgba(201,169,97,.2)}
.header h1{margin:0;font-family:Georgia,serif;font-weight:400;font-size:clamp(1.5rem,3.5vw,2.1rem);color:var(--gold);letter-spacing:4px;text-transform:uppercase}
.ornament{display:flex;align-items:center;justify-content:center;gap:1rem;margin:1.2rem auto;color:var(--gold-dim);font-size:.55rem}
.ornament::before,.ornament::after{content:"";width:60px;height:1px;background:linear-gradient(90deg,transparent,var(--gold-dim),transparent)}
.header p{margin:0;font-size:.72rem;color:var(--muted);letter-spacing:2px;text-transform:uppercase}

.nav{position:sticky;top:0;z-index:50;display:flex;overflow-x:auto;padding:0;background:rgba(10,15,20,.96);backdrop-filter:blur(10px);border-bottom:1px solid var(--border);scrollbar-width:none}
.nav::-webkit-scrollbar{display:none}
.nav a{flex:0 0 auto;display:inline-flex;align-items:center;gap:.45rem;padding:.85rem .95rem;text-decoration:none;color:var(--muted);font-size:.66rem;text-transform:uppercase;letter-spacing:1.5px;white-space:nowrap;border-right:1px solid var(--border);transition:color .25s,background .25s}
.nav a:last-child{border-right:none}
.nav a i{color:var(--c);font-size:.82rem;opacity:.65;transition:opacity .25s,filter .25s}
.nav a:hover{color:var(--gold);background:rgba(201,169,97,.04)}
.nav a:hover i,.nav a.active i{opacity:1;filter:drop-shadow(0 0 5px var(--c))}
.nav a.active{color:var(--gold);background:rgba(201,169,97,.08)}

.content{max-width:820px;margin:0 auto;padding:2.5rem 1.2rem 5rem}
.section{margin-bottom:2.8rem;scroll-margin-top:70px}
.section-title{display:flex;align-items:center;gap:.7rem;margin:0 0 .85rem;padding-bottom:.6rem;border-bottom:1px solid var(--border);
  font-family:Georgia,serif;font-weight:400;font-size:1.2rem;color:var(--gold);letter-spacing:1.5px;text-transform:uppercase}
.section-title i{color:var(--c);font-size:1.05rem;width:1.2em;text-align:center;filter:drop-shadow(0 0 6px var(--c))}
.section-title .max{margin-left:auto;font-family:'Courier New',monospace;font-size:.72rem;color:var(--c);letter-spacing:2px;font-weight:600}

.levels{list-style:none;padding:0;margin:0;background:var(--panel);border:1px solid var(--border);border-radius:2px}
.levels li{display:grid;grid-template-columns:80px 1fr;gap:1rem;padding:.85rem 1.1rem;border-bottom:1px solid rgba(42,51,61,.6);transition:background .15s}
.levels li:last-child{border-bottom:none}
.levels li:hover{background:rgba(201,169,97,.03)}
.range{color:var(--c);font-family:'Courier New',monospace;font-size:.82rem;font-weight:600;letter-spacing:.5px;padding-top:.1rem}
.info .zone{display:block;color:var(--text);font-size:.88rem;font-weight:500;margin-bottom:.25rem}
.info .note{display:block;color:var(--muted);font-size:.78rem;line-height:1.5;font-style:italic}

@media(max-width:520px){
  .levels li{grid-template-columns:1fr;gap:.25rem;padding:.7rem .9rem}
  .content{padding:1.8rem .9rem 4rem}
  .section-title{font-size:1.05rem}
  .nav a{padding:.75rem .7rem;font-size:.6rem;gap:.35rem}
  .nav a i{font-size:.75rem}
  .header-top{flex-direction:column;align-items:stretch;gap:.5rem}
  .btn-volver,.lang{justify-content:center;margin:0 auto}
}
</style>
</head>
<body>

<header class="header">
  <div class="header-top">
    <a href="index.php" class="btn-volver"><i class="fa-solid fa-arrow-left"></i> <?= e(t('guias_volver')) ?></a>
    <span class="lang">
      <a href="?lang=es" class="<?= $idioma === 'es' ? 'active' : '' ?>">🇪🇸 ES</a>
      <a href="?lang=en" class="<?= $idioma === 'en' ? 'active' : '' ?>">🇬🇧 EN</a>
    </span>
  </div>
  <h1><?= e(t('guias_titulo')) ?></h1>
  <div class="ornament">◆</div>
  <p><?= e(t('guias_subtitulo')) ?></p>
</header>

<nav class="nav" id="nav">
  <?php foreach ($guias as $g): ?>
    <a href="#p-<?= e($g['slug']) ?>" style="--c:<?= e($g['color']) ?>">
      <i class="fa-solid <?= e($g['icono']) ?>"></i><?= e($g['nombre']) ?>
    </a>
  <?php endforeach; ?>
</nav>

<main class="content">
  <?php foreach ($guias as $g): ?>
    <section class="section" id="p-<?= e($g['slug']) ?>" style="--c:<?= e($g['color']) ?>">
      <h2 class="section-title">
        <i class="fa-solid <?= e($g['icono']) ?>"></i><?= e($g['nombre']) ?>
        <span class="max">1 — 450</span>
      </h2>
      <ul class="levels">
        <?php foreach ($g['niveles'] as $n): ?>
          <li>
            <span class="range"><?= e($n[0]) ?></span>
            <div class="info">
              <span class="zone"><?= e($n[1]) ?></span>
              <span class="note"><?= e($n[2]) ?></span>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endforeach; ?>
</main>

<script>
const links = document.querySelectorAll('.nav a');
const sections = [...links].map(a => document.querySelector(a.getAttribute('href')));
const io = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (!e.isIntersecting) return;
    links.forEach(a => a.classList.toggle('active', a.getAttribute('href') === '#'+e.target.id));
  });
}, { rootMargin:'-45% 0px -50% 0px', threshold:0 });
sections.forEach(s => s && io.observe(s));
</script>
</body>
</html>
