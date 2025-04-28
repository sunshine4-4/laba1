document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = this.querySelector('[name="email"]').value.trim();
            const password = this.querySelector('[name="password"]').value;
            const remember = this.querySelector('[name="remember"]').checked;
            
            // Валидация полей
            if (!email || !password) {
                showAlert('Все поля обязательны для заполнения', 'error');
                return;
            }
            
            if (!validateEmail(email)) {
                showAlert('Пожалуйста, введите корректный email', 'error');
                return;
            }
            
            // Отправка данных на сервер
            submitLoginForm(email, password, remember);
        });
    }
});

function submitLoginForm(email, password, remember) {
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    formData.append('remember', remember ? '1' : '0');
    
    const submitButton = document.querySelector('#loginForm button[type="submit"]');
    submitButton.disabled = true;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Вход...';
    
    fetch('php/login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Успешный вход, перенаправляем пользователя
            window.location.href = data.redirect || '../index.html';
        } else {
            showAlert(data.message || 'Ошибка входа', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Ошибка соединения с сервером', 'error');
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.textContent = 'Войти';
    });
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    const form = document.querySelector('.auth-form');
    if (form) {
        form.insertBefore(alert, form.firstChild);
    } else {
        document.body.appendChild(alert);
    }
    
    setTimeout(() => {
        alert.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => {
            alert.remove();
        }, 300);
    }, 3000);
}