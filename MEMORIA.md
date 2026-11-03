# Memoria · Tarea de refuerzo UD3 «Forja de Héroes II»

**Alumno/a:** TODO (Francisco José) · **Variante:** A · **Fecha:** Octubre / Noviembre 2026

---

## 1. Comprobación de resultados

He probado mi página con los datos de la Variante A y coinciden todos los valores con la tabla del enunciado:

| Dato | Valor esperado (enunciado) | Valor de mi página | ¿Coincide? |
|---|---|---|---|
| Poder total de la party | 1243 | 1243 | Sí |
| Daño por ronda | 205,6 | 205,6 | Sí |
| Líder | Aldric | Aldric | Sí |
| Vida al salir de las salas | 305 (de 774) | 305 (de 774) | Sí |
| Resultado frente al jefe (y ronda) | Victoria en la ronda 5 | Victoria en la ronda 5 | Sí |
| Oro final | 2.260,75 mo | 2.260,75 mo | Sí |
| Valor del inventario | 151,20 mo | 151,20 mo | Sí |
| Salas en el mapa | 7 | 7 | Sí |

*(Nota: si en `inc/reglas.php` cambio la constante a 'B' o 'C', también clava todos los resultados de esas variantes).*

---

## 2. Bucles y documento resultante

- Captura del código del `foreach` en el editor y del Ctrl+U en el navegador:
  ![Foreach y Ctrl+U](capturas/01-party-bucles.png)

- **En el código hay una sola fila `<tr>`; en el Ctrl+U, ¿cuántas? ¿Por qué?**
  Aparecen 4 filas `<tr>` (una por cada héroe del array `$party`). Pasa porque el bucle `foreach` se ejecuta en el servidor y va pintando una fila en cada vuelta. El navegador no ve el bucle, solo recibe las 4 filas ya creadas.

- **¿Aparece en el Ctrl+U algo de `endforeach` o de `if`? ¿Por qué?**
  No, no sale nada de PHP. El servidor ejecuta todo el código PHP antes de enviar la página y al navegador solo le llega HTML puro.

---

## 3. Experimento: predigo el efecto de un cambio

Añado la sala `['nombre' => 'Derrumbe', 'tipo' => 'combate', 'valor' => 2400],` al inicio de `$salas`.

1. **Predicción**:
   - **Salas**: Solo explorará 1 sala. El combate quita `2400 / 4 = 600` de vida. Como la party entra con 355 de vida, muere en la primera sala y el `break` detiene el recorrido. Quedarán 7 salas pendientes.
   - **Jefe**: Derrota (`no llegó`), porque entra con 0 de vida y el primer `if` del jefe marca derrota sin llegar a pelear.
   - **Mapa**: Saldrá el aviso de mapa incoherente, porque la lista tiene 8 salas pero en el mapa solo hay 7 casillas con la letra 'S'.

2. **Resultado real**:
   ![Experimento Derrumbe](capturas/02-experimento-derrumbe.png)
   Ocurre exactamente lo predicho.

3. **Sentencia de corte y tabla de rondas**:
   - El bucle lo corta la sentencia `break;` dentro del `if ($vidaParty <= 0)`.
   - La tabla de rondas no se muestra porque tiene la condición `if (!empty($rondas))` y, al no haber combate, ese array está vacío.

---

## 4. Experimento: match frente a switch

1. **Cambio la clase a `'Bardo'`**:
   ![Error con Bardo](capturas/03-error-bardo.png)
   - Da un error fatal: `Fatal error: Uncaught UnhandledMatchError`.
   - Ocurre porque `match` es estricto y exhaustivo. Al no existir el caso `'Bardo'` ni haber puesto `default`, PHP no sabe qué devolver y para la ejecución en seco.

2. **Posición del Bardo en el switch**:
   - Le asigna `'Reserva'`.
   - El `switch` no falla porque tiene un caso `default:` que recoge cualquier valor que no coincida con los anteriores.

3. **¿Cuál prefiero en un programa real?**:
   Para la lógica del juego (daño, cálculos importantes) prefiero `match` sin default, porque si meto una clase nueva me avisa del fallo al momento y no sigo jugando con datos erróneos. Para cosas visuales o secundarias, prefiero `switch` con `default` para que la página no reviente.

---

## 5. Experimento: continue dentro de switch

1. **Cambio `continue 2;` por `continue;` en `'vacía'`**:
   Sale este aviso: `Warning: "continue" targeting switch is equivalent to "break". Did you mean to use "continue 2"?`.

2. **Explicación**:
   - En PHP, dentro de un `switch`, un `continue;` normal actúa como un `break;` (solo sale del switch y sigue ejecutando el resto de la vuelta del foreach).
   - Con `continue 2;` le decimos que salte dos niveles: se sale del switch y salta a la siguiente iteración del `foreach` de las salas.

---

## 6. Uso de IA (obligatorio declararlo)

- **¿Has usado alguna IA? ¿Para qué?**
  Sí, la usé para consultar dudas sobre la sintaxis de PHP 8 y comprobar los cálculos de las variantes.
- **Ejemplo**:
  Le pregunté cómo saltar a la siguiente sala desde dentro de un `switch`. Me explicó que hacía falta usar `continue 2;` en lugar de `continue;` porque en PHP el `switch` cuenta como estructura de salto y un `continue` simple genera un Warning en PHP 8. Comprobé que efectivamente salía ese aviso en el experimento 5 al quitar el 2.
