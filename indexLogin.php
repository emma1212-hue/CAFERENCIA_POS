<?php
session_start();

// Recuperar mensaje dinámico
$mensaje = isset($_SESSION['mensaje_login']) ? $_SESSION['mensaje_login'] : "";
$contador = isset($_SESSION['contador']) ? $_SESSION['contador'] : 0;

unset($_SESSION['mensaje_login']);
unset($_SESSION['contador']);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Iniciar Sesion</title>
    <link rel="stylesheet" href="login/css/style.css" />

    <style>
        .mensaje-error {
            color: red;
            margin-bottom: 15px;
            font-size: 15px;
            text-align: center;
            font-weight: 600;
            min-height: 20px;
        }

        .disabled-btn {
            opacity: 0.5;
            pointer-events: none;
        }

        #mensaje.mensaje-error {
    background: transparent !important;
    padding: 0 !important;
    margin: 0 auto !important;
    width: fit-content !important;
    border-radius: 6px !important;
    display: block;
}

    </style>
  </head>
  <body>
    <main>
      <div class="box">
        <div class="inner-box">

          <div class="forms-wrap">

            <!-- Mensaje siempre en el mismo lugar -->
<div class="mensaje-error" id="mensaje" style="display: <?php echo ($mensaje != '' ? 'block' : 'none'); ?>;">
  <?php echo $mensaje; ?>
</div>


            <form action="validarLogin.php" method="POST" autocomplete="off" class="sign-in-form" id="formLogin">
              <div class="logo">
                <img src="login/img/logoCaferencia.png" alt="logo" />
              </div>

              <div class="heading">
                <h2>Iniciar Sesion</h2>
              </div>

              <div class="actual-form">

                <div class="input-wrap">
                  <input type="text" name="usuario" minlength="4" class="input-field" autocomplete="off" required />
                  <label>Usuario</label>
                </div>

                <div class="input-wrap">
                  <input type="password" name="password" minlength="4" class="input-field" autocomplete="off" required />
                  <label>Contraseña</label>
                </div>

                <!-- EL BOTÓN QUE DESHABILITAREMOS -->
                <input type="submit" value="Ingresar" class="sign-btn" id="btnIngresar" />
              </div>
            </form>
          </div>

          <div class="carousel">
            <div class="images-wrapper">
              <img src="login/img/foto1.png" class="image img-1 show" alt="" />
              <img src="login/img/foto2.png" class="image img-2" alt="" />
              <img src="login/img/foto3.jpg" class="image img-3" alt="" />
            </div>

            <div class="text-slider">
              <div class="text-wrap">
                <div class="text-group"></div>
              </div>

              <div class="bullets">
                <span class="active" data-value="1"></span>
                <span data-value="2"></span>
                <span data-value="3"></span>
              </div>
            </div>
          </div>

        </div>
      </div>
    </main>

    <script src="login/js/main.js"></script>

    <script>
      let tiempo = <?php echo $contador; ?>;
      const msg = document.getElementById("mensaje");
      const btn = document.getElementById("btnIngresar");

      if (tiempo > 0) {

          // DESHABILITAR BOTÓN
          btn.classList.add("disabled-btn");

          const intervalo = setInterval(() => {
              msg.textContent = "Bloqueado. Espera " + tiempo + " segundos.";
              tiempo--;

              if (tiempo < 0) {
                  clearInterval(intervalo);
                  // Habilitar el botón nuevamente
                  btn.classList.remove("disabled-btn");
                  msg.textContent = "";
                  window.location.reload();
              }
          }, 1000);
      }
    </script>

  </body>
</html>
