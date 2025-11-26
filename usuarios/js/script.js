
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirmar_password');
const passwordMatch = document.querySelector('.password-match');
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
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 3000);
    });
});