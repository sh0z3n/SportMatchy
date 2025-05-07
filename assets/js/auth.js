document.addEventListener('DOMContentLoaded', function() {
    // Password strength meter
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const registerForm = document.getElementById('registerForm');

    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength++;
            
            // Contains number
            if (/\d/.test(password)) strength++;
            
            // Contains lowercase
            if (/[a-z]/.test(password)) strength++;
            
            // Contains uppercase
            if (/[A-Z]/.test(password)) strength++;
            
            // Contains special char
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            // Update strength indicator
            const strengthIndicator = document.createElement('div');
            strengthIndicator.className = 'password-strength';
            
            let strengthText = '';
            let strengthClass = '';
            
            switch(strength) {
                case 0:
                case 1:
                    strengthText = 'Très faible';
                    strengthClass = 'very-weak';
                    break;
                case 2:
                    strengthText = 'Faible';
                    strengthClass = 'weak';
                    break;
                case 3:
                    strengthText = 'Moyen';
                    strengthClass = 'medium';
                    break;
                case 4:
                    strengthText = 'Fort';
                    strengthClass = 'strong';
                    break;
                case 5:
                    strengthText = 'Très fort';
                    strengthClass = 'very-strong';
                    break;
            }
            
            strengthIndicator.innerHTML = `
                <div class="strength-bar">
                    <div class="strength-level ${strengthClass}" style="width: ${(strength / 5) * 100}%"></div>
                </div>
                <span class="strength-text">${strengthText}</span>
            `;
            
            // Remove existing indicator if any
            const existingIndicator = document.querySelector('.password-strength');
            if (existingIndicator) {
                existingIndicator.remove();
            }
            
            this.parentNode.appendChild(strengthIndicator);
        });
    }

    // Password confirmation check
    if (confirmPasswordInput && passwordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Form submission with AJAX
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            if (!this.checkValidity()) {
                return;
            }

            const formData = new FormData(this);
            
            // Show loading state
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Inscription en cours...';

            // Send AJAX request
            fetch('api/register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to success page or home
                    window.location.href = 'index.php';
                } else {
                    // Show error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-error';
                    errorDiv.textContent = data.message || 'Une erreur est survenue';
                    
                    const existingError = document.querySelector('.alert-error');
                    if (existingError) {
                        existingError.remove();
                    }
                    
                    registerForm.insertBefore(errorDiv, registerForm.firstChild);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-error';
                errorDiv.textContent = 'Une erreur est survenue lors de la communication avec le serveur';
                
                const existingError = document.querySelector('.alert-error');
                if (existingError) {
                    existingError.remove();
                }
                
                registerForm.insertBefore(errorDiv, registerForm.firstChild);
            })
            .finally(() => {
                // Reset button state
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            });
        });
    }

    // Username availability check
    const usernameInput = document.getElementById('username');
    if (usernameInput) {
        let timeout = null;
        
        usernameInput.addEventListener('input', function() {
            clearTimeout(timeout);
            
            const username = this.value.trim();
            if (username.length < 3) return;
            
            timeout = setTimeout(() => {
                fetch(`api/check-username.php?username=${encodeURIComponent(username)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.available) {
                            this.setCustomValidity('Ce nom d\'utilisateur est déjà pris');
                        } else {
                            this.setCustomValidity('');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 500);
        });
    }

    // Email availability check
    const emailInput = document.getElementById('email');
    if (emailInput) {
        let timeout = null;
        
        emailInput.addEventListener('input', function() {
            clearTimeout(timeout);
            
            const email = this.value.trim();
            if (!email) return;
            
            timeout = setTimeout(() => {
                fetch(`api/check-email.php?email=${encodeURIComponent(email)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.available) {
                            this.setCustomValidity('Cette adresse email est déjà utilisée');
                        } else {
                            this.setCustomValidity('');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 500);
        });
    }
}); 