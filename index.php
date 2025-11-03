<?php
/*
Star Wars Characters (SWAPI)
- Carga todos los personajes en UNA petición usando limit
- Desplegable con todos los personajes
- Al enviar, muestra todas las propiedades del personaje
- Estilos con Bootstrap + imagen
- Caché local (JSON) 24h para la lista, para no saturar la API
*/

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function get_json(string $url): array {
	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_CONNECTTIMEOUT => 8,
		CURLOPT_TIMEOUT => 20,
		CURLOPT_HTTPHEADER => ['Accept: application/json'],
	]);
	$body = curl_exec($ch);
	$err  = curl_error($ch);
	$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
	curl_close($ch);

	if ($body === false) throw new RuntimeException("Error de red: $err");
	if ($code < 200 || $code >= 300) throw new RuntimeException("HTTP $code");
	$data = json_decode($body, true);
	if (!is_array($data)) throw new RuntimeException("JSON inválido");
	return $data;
}

$LIST_URL   = 'https://www.swapi.tech/api/people?limit=1000';   // people con limit alto
$DETAIL_URI = 'https://www.swapi.tech/api/people/'; // + {uid}
$CACHE_DIR  = __DIR__ . '/cache';
$CACHE_FILE = $CACHE_DIR . '/people.json';
$TTL        = 60 * 60 * 24; // 24h

$error = null;
$people = [];

try {
	if (!is_dir($CACHE_DIR)) { @mkdir($CACHE_DIR, 0775, true); }
	$useCache = is_file($CACHE_FILE) && (time() - filemtime($CACHE_FILE) < $TTL);

	if ($useCache) {
		$people = json_decode(file_get_contents($CACHE_FILE), true) ?: [];
	} else {
		$resp = get_json($LIST_URL);
		if (isset($resp['result']) && is_array($resp['result'])) {
			$people = array_map(fn($r) => [
				'uid'  => $r['uid']  ?? null,
				'name' => $r['name'] ?? null,
				'url'  => $r['url']  ?? null,
			], $resp['result']);
		} elseif (isset($resp['results']) && is_array($resp['results'])) {
			$people = array_map(fn($r) => [
				'uid'  => $r['uid']  ?? null,
				'name' => $r['name'] ?? null,
				'url'  => $r['url']  ?? null,
			], $resp['results']);
		} else {
			throw new RuntimeException("Estructura inesperada en la lista de personajes");
		}
		file_put_contents($CACHE_FILE, json_encode($people, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
	}
} catch (Throwable $e) {
	$error = "No se pudieron cargar los personajes: " . $e->getMessage();
}

$selectedUid = $_POST['uid'] ?? null;
$details = null;

if ($selectedUid) {
	$DETAIL_URL = $DETAIL_URI . rawurlencode($selectedUid);
	try {
		$dresp = get_json($DETAIL_URL);
		if (isset($dresp['result']['properties'])) {
			$props = $dresp['result']['properties'];
			$details = [
				'name'       => $props['name'] ?? 'Desconocido',
				'properties' => $props,
			];
		} else {
			throw new RuntimeException("No se encontraron propiedades del personaje");
		}
	} catch (Throwable $e) {
		$error = "No se pudo cargar el personaje: " . $e->getMessage();
	}
}

usort($people, fn($a,$b)=>strcmp($a['name']??'', $b['name']??''));
?>
<!doctype html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<title>Star Wars — Personajes</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<style>
		.hero {max-width: 720px; width:100%; height:auto}
		footer {color:#6c757d}
		.prop-key {font-weight:600}
	</style>
</head>
<body class="bg-dark text-light">
	<nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-warning">
		<div class="container">
			<a class="navbar-brand" href="#">Star Wars Characters</a>
		</div>
	</nav>

	<main class="container py-4">
		<div class="text-center mb-4">
			<img class="hero img-fluid rounded" src="https://upload.wikimedia.org/wikipedia/commons/6/6c/Star_Wars_Logo.svg" alt="Star Wars">
		</div>

		<?php if ($error): ?>
			<div class="alert alert-danger" role="alert"><?= h($error) ?></div>
		<?php endif; ?>

		<div class="card bg-secondary-subtle text-dark mb-4 shadow-sm">
			<div class="card-body">
				<form method="post" class="row gy-2 gx-2 align-items-center">
					<div class="col-sm-8">
						<label for="uid" class="form-label mb-1">Personaje</label>
						<select id="uid" name="uid" class="form-select" required>
							<option value="" disabled <?= $selectedUid ? '' : 'selected' ?>>Selecciona un personaje…</option>
							<?php foreach ($people as $p): if (!$p['uid'] || !$p['name']) continue; ?>
								<option value="<?= h($p['uid']) ?>" <?= $selectedUid===$p['uid']?'selected':'' ?>>
									<?= h($p['name']) ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="col-sm-4 d-grid">
						<label class="form-label mb-1 d-none d-sm-block">&nbsp;</label>
						<button type="submit" class="btn btn-warning fw-semibold">Ver detalles</button>
					</div>
				</form>
				<p class="mb-0 small text-muted">
					Lista cacheada localmente durante 24 h para mejorar rendimiento y evitar llamadas innecesarias a la API.
				</p>
			</div>
		</div>

		<?php if ($details): ?>
			<div class="card shadow-sm">
				<div class="card-body">
					<h2 class="card-title mb-3"><?= h($details['name']) ?></h2>
					<div class="row">
						<?php foreach ($details['properties'] as $k => $v): ?>
							<div class="col-md-6 col-lg-4 mb-2">
								<span class="prop-key"><?= h($k) ?>:</span>
								<?= h(is_scalar($v)? (string)$v : json_encode($v)) ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php elseif (!$error): ?>
			<p class="text-center text-muted">Selecciona un personaje para ver sus propiedades.</p>
		<?php endif; ?>
	</main>

	<footer class="container py-4">
		<p class="small mb-0">Fuente de datos: <a class="link-warning" href="https://www.swapi.tech/api/people?limit=1000" target="_blank" rel="noreferrer">SWAPI.tech</a></p>
	</footer>

	<script src="315"></script>
</body>
</html>