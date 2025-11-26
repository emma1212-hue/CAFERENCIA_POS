
    let usuarioSeleccionado = null;

  
    function cargarUsuario(id, nombre, usuario, rol) {
        
        document.querySelectorAll('.user-row').forEach(row => {
            row.classList.remove('selected');
        });
       
        event.currentTarget.classList.add('selected');
      
        document.getElementById('idUsuario').value = id;
        document.getElementById('nombre').value = nombre;
        document.getElementById('nombreDeUsuario').value = usuario;
        
       
        document.querySelectorAll('input[name="rolUsuario"]').forEach(radio => {
            radio.checked = (radio.value === rol);
        });
        
        
        document.getElementById('password').value = '';
        document.getElementById('confirmar_password').value = '';
        document.getElementById('passwordMatch').textContent = '';
        document.getElementById('usuarioStatus').textContent = '';
        
        
        usuarioSeleccionado = id;
        document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-save"></i> Actualizar Usuario';
        document.querySelector('form').action = 'php/actualizar_usuario.php';
    }

    
    function resetearFormulario() {
        document.getElementById('idUsuario').value = '';
        document.getElementById('nombre').value = '';
        document.getElementById('nombreDeUsuario').value = '';
        document.getElementById('password').value = '';
        document.getElementById('confirmar_password').value = '';
        document.getElementById('passwordMatch').textContent = '';
        document.getElementById('usuarioStatus').textContent = '';
        
        
        document.querySelectorAll('input[name="rolUsuario"]').forEach(radio => {
            radio.checked = false;
        });
        
       
        document.querySelectorAll('.user-row').forEach(row => {
            row.classList.remove('selected');
        });
        
       
        usuarioSeleccionado = null;
        document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-save"></i> Guardar Usuario';
        document.querySelector('form').action = 'php/guardar_usuario.php';
    }

    
    function ocultarMensajes() {
        const alerts = document.querySelectorAll('.alert');
        console.log('Encontré', alerts.length, 'mensajes'); // Para debug
        
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 500);
            }, 3000);
        });
    }

    
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmar_password');
    const passwordMatch = document.getElementById('passwordMatch');
    const nombreDeUsuarioInput = document.getElementById('nombreDeUsuario');
    const saveButton = document.querySelector('button[type="submit"]');

    
    const usuarioStatus = document.createElement('div');
    usuarioStatus.className = 'availability-status';
    nombreDeUsuarioInput.parentNode.appendChild(usuarioStatus);

   
    nombreDeUsuarioInput.addEventListener('blur', function() {
        const usuario = this.value.trim();
        
        if (usuario === '') {
            usuarioStatus.textContent = '';
            saveButton.disabled = false; 
            return;
        }

        
        fetch('../usuarios/php/verificar_usuario.php?usuario=' + encodeURIComponent(usuario))
            .then(response => response.json())
            .then(data => {
                if (data.existe) {
                    usuarioStatus.textContent = 'Este usuario ya existe';
                    usuarioStatus.style.color = 'var(--error)';
                    saveButton.disabled = true;
                } else {
                    usuarioStatus.textContent = 'Usuario disponible';
                    usuarioStatus.style.color = 'var(--success)';
                    saveButton.disabled = false; 
                }
            })
            .catch(error => {
                console.error('Error:', error);
                saveButton.disabled = false; 
            });
    });

   
    confirmPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;
        
        if (confirmPassword === '') {
            passwordMatch.textContent = '';
            passwordMatch.style.color = '';
        } else if (password === confirmPassword) {
            passwordMatch.textContent = 'Las contraseñas coinciden';
            passwordMatch.style.color = 'var(--success)';
        } else {
            passwordMatch.textContent = 'Las contraseñas no coinciden';
            passwordMatch.style.color = 'var(--error)';
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const btnNuevo = document.getElementById('btnNuevo');
        const btnGuardar = document.getElementById('btnGuardar');
        
        
        btnNuevo.addEventListener('click', resetearFormulario);
        
        
        ocultarMensajes();
    });

    window.addEventListener('load', function() {
        ocultarMensajes();
    });

    
    setTimeout(ocultarMensajes, 1000);
    setTimeout(ocultarMensajes, 2000);
