<?php
/**
 * =====================================================================
 *  expedicion.php · Forja de Héroes II · La party y la mazmorra
 * =====================================================================
 *  0613 DWES · UD3 · Tarea de refuerzo (sin nota)
 *  Autor/a: Alumno de 2.º DAW · Variante: Universal (A, B o C)
 *
 *  Estructura OBLIGATORIA (como en el paso 09 del caso guiado):
 *   1) strict_types y carga de inc/reglas.php y del fichero de datos
 *   2) CÁLCULOS: aquí arriba, sin HTML
 *   3) PRESENTACIÓN: HTML con sintaxis alternativa
 *      (foreach (...): ... endforeach;   if (...): ... else: ... endif;)
 *
 *  R8 · Comentarios en todo el código explicando QUÉ hace y POR QUÉ.
 *       Uso de //, # y /* ... *\/ demostrados en los distintos bloques.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/reglas.php';
require_once __DIR__ . '/inc/datos-' . VARIANTE . '.php';

// =====================================================================
// R2 y R3 · LA PARTY (Cálculos de héroes, decisiones y totales)
// =====================================================================

/*
 * Primer bucle: calculamos estadísticas derivadas para cada héroe
 * y las almacenamos directamente en la matriz $party para tener toda
 * la información centralizada antes de calcular los totales.
 */
foreach ($party as $i => $heroe) {
    # Nivel calculado mediante división entera y vida máxima escalada por clase
    $nivel = intdiv($heroe['xp'], XP_POR_NIVEL) + 1;
    $vidaMax = VIDA_BASE + $heroe['constitucion'] * $nivel * MULT_VIDA[$heroe['clase']];
    $pctVida = ($heroe['vida'] / $vidaMax) * 100;

    // R3: Daño según clase con expresión match exhaustiva (sin default para detectar clases no contempladas)
    $danio = match ($heroe['clase']) {
        'Guerrera'  => ($heroe['fuerza'] ** 2) / 10 + $nivel * 2,
        'Hechicero' => $heroe['inteligencia'] * 3 + ($nivel ** 2) / 4,
        'Pícara'    => $heroe['destreza'] * 2 + ($nivel ** 1.5),
        'Clérigo'   => ($heroe['inteligencia'] + $heroe['constitucion']) * 1.5 + $nivel,
    };

    // R3: Determinación del estado vital con estructura condicional if / elseif / else
    if ($heroe['vida'] <= 0) {
        $estado = 'K.O.';
    } elseif ($pctVida < 35) {
        $estado = 'En peligro';
    } elseif ($pctVida < 70) {
        $estado = 'Herido';
    } else {
        $estado = 'En pie';
    }

    // R3: Posición táctica en la formación usando switch con casos agrupados
    switch ($heroe['clase']) {
        case 'Guerrera':
        case 'Clérigo':
            $posicion = 'Primera línea';
            break;
        case 'Pícara':
            $posicion = 'Flanco';
            break;
        case 'Hechicero':
            $posicion = 'Retaguardia';
            break;
        default:
            $posicion = 'Reserva';
            break;
    }

    $poder = (int) round($danio * $nivel);

    // Barra de vida visual de 20 bloques construida con bucle for (sin usar str_repeat)
    $bloquesLlenos = (int) round($pctVida / 100 * BLOQUES_BARRA);
    $barra = '';
    for ($b = 0; $b < BLOQUES_BARRA; $b++) {
        $barra .= ($b < $bloquesLlenos) ? '█' : '░';
    }

    // Almacenamos todos los valores calculados en la posición del héroe en $party
    $party[$i]['nivel']    = $nivel;
    $party[$i]['vidaMax']  = $vidaMax;
    $party[$i]['pctVida']  = $pctVida;
    $party[$i]['danio']    = $danio;
    $party[$i]['poder']    = $poder;
    $party[$i]['estado']   = $estado;
    $party[$i]['posicion'] = $posicion;
    $party[$i]['barra']    = $barra;
}

# Totales globales de la party mediante array_sum y array_column para evitar bucles innecesarios
$vidaParty     = array_sum(array_column($party, 'vida'));
$vidaMaxParty  = array_sum(array_column($party, 'vidaMax'));
$poderParty    = array_sum(array_column($party, 'poder'));
$nivelMedio    = array_sum(array_column($party, 'nivel')) / count($party);

// Segundo bucle: acumulamos el daño y seleccionamos al líder saltando los héroes caídos
$danioParty     = 0.0;
$heroesActivos  = 0;
$lider          = null;
$maxPoderActivo = -1;

foreach ($party as $heroe) {
    if ($heroe['estado'] === 'K.O.') {
        continue; // Un héroe K.O. no aporta daño ni puede ser el líder activo
    }
    $danioParty += $heroe['danio'];
    $heroesActivos++;

    if ($heroe['poder'] > $maxPoderActivo) {
        $maxPoderActivo = $heroe['poder'];
        $lider = $heroe['nombre'];
    }
}

// Detección de clases clave en la party para habilidades pasivas en la mazmorra
$clasesParty = array_column($party, 'clase');
$hayPicara   = in_array('Pícara', $clasesParty, true);
$hayClerigo  = in_array('Clérigo', $clasesParty, true);

// =====================================================================
// R4 · LA EXPEDICIÓN (sala a sala)
// =====================================================================

/*
 * Recorremos las salas de la mazmorra en orden secuencial.
 * El estado de la party (vida, oro, inventario y XP) se va modificando
 * según los eventos de cada sala.
 */
$oro             = $oroInicial;
$xpGanada        = 0;
$diario          = [];
$salasExploradas = 0;

foreach ($salas as $n => $sala) {
    $numSala = $n + 1;
    $salasExploradas++;

    # Evaluamos el tipo de sala con una estructura switch
    switch ($sala['tipo']) {
        case 'vacía':
            $diario[] = "Sala $numSala · {$sala['nombre']}: vacía, la party sigue avanzando.";
            // IMPORTANTE: En PHP, dentro de un switch, continue actúa como break.
            // Para continuar con la siguiente iteración del bucle foreach exterior se usa continue 2.
            continue 2;

        case 'trampa':
            if ($hayPicara) {
                $diario[] = "Sala $numSala · {$sala['nombre']}: trampa evitada gracias a la Pícara.";
            } else {
                $vidaParty -= $sala['valor'];
                $diario[] = "Sala $numSala · {$sala['nombre']}: ¡trampa! −{$sala['valor']} de vida.";
            }
            break;

        case 'combate':
            $perdidaVida = intdiv($sala['valor'], 4);
            $vidaParty -= $perdidaVida;
            $xpGanada  += $sala['valor'];
            $diario[]   = "Sala $numSala · {$sala['nombre']}: combate, −{$perdidaVida} de vida y +{$sala['valor']} XP.";
            break;

        case 'tesoro':
            $oro += $sala['valor'];
            if (isset($sala['objeto'])) {
                $obj = $sala['objeto'];
                $inventario[$obj] = ($inventario[$obj] ?? 0) + 1;
                $diario[] = "Sala $numSala · {$sala['nombre']}: +{$sala['valor']} mo y «{$obj}».";
            } else {
                $diario[] = "Sala $numSala · {$sala['nombre']}: +{$sala['valor']} mo.";
            }
            break;

        case 'descanso':
            $cura = $hayClerigo ? ($sala['valor'] * 2) : $sala['valor'];
            // Curamos sin sobrepasar la vida máxima total de la party
            $vidaParty = min($vidaMaxParty, $vidaParty + $cura);
            if ($hayClerigo) {
                $diario[] = "Sala $numSala · {$sala['nombre']}: descanso, +{$cura} de vida (bendición del Clérigo).";
            } else {
                $diario[] = "Sala $numSala · {$sala['nombre']}: descanso, +{$cura} de vida.";
            }
            break;
    }

    // Si la party agota sus puntos de vida, no puede continuar explorando
    if ($vidaParty <= 0) {
        $vidaParty = 0;
        $diario[]  = "¡La party ha caído en combate en la Sala $numSala! La expedición termina aquí.";
        break; // Detiene el foreach por completo
    }
}

$salasPendientes    = count($salas) - $salasExploradas;
$vidaPartyTrasSalas = $vidaParty;

// =====================================================================
// R5 · EL JEFE
// =====================================================================

# Verificación de requisitos previos al encuentro con el jefe: posesión de la llave y potencia de ataque
$tieneLlave = array_key_exists($jefe['llave'], $inventario);
$golpe      = (int) round($danioParty);
$rondas     = [];

/*
 * Estructura de decisión previa al combate:
 * Si la party no tiene vida o carece de la llave necesaria, no puede entablar combate.
 * En caso contrario, se resuelve el enfrentamiento por rondas mediante un bucle while.
 */
if ($vidaParty <= 0) {
    $resultado = 'Derrota';
    $motivo    = 'no llegó';
} elseif (!$tieneLlave) {
    $resultado = 'Puerta sellada';
    $motivo    = "falta {$jefe['llave']}";
} else {
    $vidaJefe = $jefe['vida'];
    $ronda    = 0;

    // Combate resuelto por turnos con límite superior MAX_RONDAS para evitar bucles infinitos
    while ($vidaJefe > 0 && $vidaParty > 0 && $ronda < MAX_RONDAS) {
        $ronda++;
        // La party ataca primero reduciendo los puntos de vida del jefe
        $vidaJefe = max(0, $vidaJefe - $golpe);

        // Si el jefe sobrevive al asalto, contraataca a la party
        if ($vidaJefe > 0) {
            $vidaParty = max(0, $vidaParty - $jefe['ataque']);
        }

        $rondas[] = [
            'ronda'     => $ronda,
            'vidaJefe'  => $vidaJefe,
            'vidaParty' => $vidaParty,
        ];
    }

    # Evaluación del resultado del enfrentamiento mediante match(true) por condiciones
    $resultado = match (true) {
        $vidaJefe === 0  => 'Victoria',
        $vidaParty === 0 => 'Derrota',
        default          => 'Retirada',
    };

    if ($resultado === 'Victoria') {
        $oro   += $jefe['recompensa'];
        $motivo = "en la ronda $ronda";
    } elseif ($resultado === 'Derrota') {
        $motivo = "en la ronda $ronda";
    } else {
        $motivo = "($ronda rondas)";
    }
}

// =====================================================================
// R4 (cont.) · INVENTARIO FINAL
// =====================================================================

/*
 * Calculamos la tasación total de los objetos acumulados multiplicando cada
 * cantidad por su precio de mercado, y ordenamos una copia por abundancia.
 */
$valorInventario = 0.0;
foreach ($inventario as $objeto => $cantidad) {
    $valorInventario += $cantidad * ($precios[$objeto] ?? 0.0);
}

$totalObjetos       = array_sum($inventario);
$inventarioOrdenado = $inventario;
arsort($inventarioOrdenado); // Ordenación descendente por valor preservando las claves asociativas
$objetoMasAbundante = array_key_first($inventarioOrdenado);

// =====================================================================
// R6 · EL MAPA Y BARRA FINAL
// =====================================================================

/*
 * Análisis de la matriz bidimensional del mapa mediante dos bucles for anidados
 * para contabilizar la frecuencia de cada tipo de casilla y verificar su coherencia.
 */
$conteoCeldas = [];
$numFilasMapa = count($mapa);

for ($f = 0; $f < $numFilasMapa; $f++) {
    $numColsMapa = count($mapa[$f]);
    for ($c = 0; $c < $numColsMapa; $c++) {
        $celda = $mapa[$f][$c];
        $conteoCeldas[$celda] = ($conteoCeldas[$celda] ?? 0) + 1;
    }
}

# Verificamos si la cantidad de casillas de sala 'S' coincide con las salas explorables
$mapaCoherente = (($conteoCeldas['S'] ?? 0) === count($salas));

// Barra de salud final de la party tras todas las vicisitudes y el jefe
$bloquesLlenosParty = (int) round(($vidaParty / $vidaMaxParty) * BLOQUES_BARRA);
$barraParty = '';
for ($b = 0; $b < BLOQUES_BARRA; $b++) {
    $barraParty .= ($b < $bloquesLlenosParty) ? '█' : '░';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expedición · <?= htmlspecialchars($nombreMazmorra) ?> · Forja de Héroes II</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<main>
    <span class="insignia">VARIANTE <?= htmlspecialchars(VARIANTE) ?></span>
    <h1><?= htmlspecialchars($nombreMazmorra) ?></h1>
    <p class="subtitulo">Informe de expedición · líder: <?= htmlspecialchars($lider ?? 'Ninguno') ?></p>

    <h2>La party</h2>
    <table>
        <tr><th>Héroe</th><th>Clase</th><th>Nivel</th><th>Vida</th><th>Daño</th><th>Poder</th><th>Posición</th><th>Estado</th></tr>
        <?php foreach ($party as $heroe): ?>
            <tr>
                <td><?= htmlspecialchars($heroe['nombre']) ?></td>
                <td><?= htmlspecialchars($heroe['clase']) ?></td>
                <td><?= $heroe['nivel'] ?></td>
                <td><span class="barra mini"><?= $heroe['barra'] ?></span> <?= $heroe['vida'] ?>/<?= $heroe['vidaMax'] ?></td>
                <td><?= number_format($heroe['danio'], 1, ',', '.') ?></td>
                <td><?= $heroe['poder'] ?></td>
                <td><?= htmlspecialchars($heroe['posicion']) ?></td>
                <?php if ($heroe['estado'] === 'K.O.' || $heroe['estado'] === 'En peligro'): ?>
                    <td class="peligro"><?= htmlspecialchars($heroe['estado']) ?></td>
                <?php else: ?>
                    <td class="vida"><?= htmlspecialchars($heroe['estado']) ?></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="rejilla">
        <div class="stat"><div class="etq">Poder total</div><div class="val"><?= $poderParty ?></div></div>
        <div class="stat"><div class="etq">Nivel medio</div><div class="val"><?= number_format($nivelMedio, 1, ',', '.') ?></div></div>
        <div class="stat"><div class="etq">Daño por ronda</div><div class="val"><?= number_format($danioParty, 1, ',', '.') ?></div></div>
        <div class="stat"><div class="etq">Héroes activos</div><div class="val"><?= $heroesActivos ?> / <?= count($party) ?></div></div>
    </div>

    <h2>Diario de la expedición</h2>
    <ol class="diario">
        <?php foreach ($diario as $linea): ?>
            <li><?= htmlspecialchars($linea) ?></li>
        <?php endforeach; ?>
    </ol>
    <p>Salas exploradas: <?= $salasExploradas ?> de <?= count($salas) ?>
        <?php if ($salasPendientes > 0): ?>
            <span class="peligro">(<?= $salasPendientes ?> pendientes)</span>
        <?php endif; ?>
        · XP ganada: <?= $xpGanada ?></p>

    <h2>El jefe: <?= htmlspecialchars($jefe['nombre']) ?></h2>
    <p>Vida <?= $jefe['vida'] ?> · ataque <?= $jefe['ataque'] ?> · golpe de la party: <?= $golpe ?> por ronda</p>
    <?php if (!empty($rondas)): ?>
        <table>
            <tr><th>Ronda</th><th>Vida del jefe</th><th>Vida de la party</th></tr>
            <?php foreach ($rondas as $fila): ?>
                <tr>
                    <td><?= $fila['ronda'] ?></td>
                    <td><?= $fila['vidaJefe'] ?></td>
                    <td><?= $fila['vidaParty'] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <p class="resultado <?= ($resultado === 'Victoria') ? 'vida' : 'peligro' ?>">
        <strong><?= htmlspecialchars($resultado) ?></strong> · <?= htmlspecialchars($motivo) ?>
    </p>
    <p class="barra vida">PARTY <?= $barraParty ?> <?= $vidaParty ?>/<?= $vidaMaxParty ?></p>

    <h2>Inventario</h2>
    <table>
        <tr><th>Objeto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
        <?php foreach ($inventarioOrdenado as $objeto => $cantidad): ?>
            <?php
            $precio = $precios[$objeto] ?? 0.0;
            $subtotal = $cantidad * $precio;
            ?>
            <tr>
                <td><?= htmlspecialchars($objeto) ?></td>
                <td><?= $cantidad ?></td>
                <td><?= number_format($precio, 2, ',', '.') ?> mo</td>
                <td><?= number_format($subtotal, 2, ',', '.') ?> mo</td>
            </tr>
        <?php endforeach; ?>
    </table>
    <div class="rejilla">
        <div class="stat"><div class="etq">Objetos</div><div class="val"><?= $totalObjetos ?></div></div>
        <div class="stat"><div class="etq">Valor del inventario</div><div class="val"><?= number_format($valorInventario, 2, ',', '.') ?> mo</div></div>
        <div class="stat"><div class="etq">Oro final</div><div class="val"><?= number_format($oro, 2, ',', '.') ?> mo</div></div>
        <div class="stat"><div class="etq">Más abundante</div><div class="val peq"><?= htmlspecialchars($objetoMasAbundante ?? '-') ?></div></div>
    </div>

    <h2>Mapa de la mazmorra</h2>
    <table class="mapa">
        <?php foreach ($mapa as $fila): ?>
            <tr>
                <?php foreach ($fila as $celda): ?>
                    <td class="<?= htmlspecialchars(LEYENDA_MAPA[$celda][0] ?? 'desconocida') ?>"><?= htmlspecialchars($celda) ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </table>
    <p>
        <?php foreach (LEYENDA_MAPA as $caracter => $detalles): ?>
            <code><?= htmlspecialchars($caracter) ?></code> <?= htmlspecialchars($detalles[1]) ?>: <?= ($conteoCeldas[$caracter] ?? 0) ?> &nbsp;
        <?php endforeach; ?>
    </p>
    <?php if ($mapaCoherente): ?>
        <p class="vida">El mapa es coherente: hay tantas salas en el mapa (<?= $conteoCeldas['S'] ?? 0 ?>) como salas a explorar (<?= count($salas) ?>).</p>
    <?php else: ?>
        <p class="peligro">Incoherencia en el mapa: hay <?= $conteoCeldas['S'] ?? 0 ?> casillas de sala pero <?= count($salas) ?> salas en la lista.</p>
    <?php endif; ?>

    <footer>Informe generado el <?= date('d/m/Y H:i:s') ?> con PHP <?= PHP_VERSION ?> · cada recarga lo recalcula todo desde cero.</footer>
</main>
</body>
</html>
