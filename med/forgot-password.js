document.addEventListener('DOMContentLoaded', function() {
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const successMessage = document.getElementById('successMessage');
    
    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const emailInput = this.querySelector('input[type="email"]');
            const email = emailInput.value.trim();
            
            if (!validateEmail(email)) {
                showAlert('Пожалуйста, введите корректный email', 'error');
                return;
            }
            
            // Здесь можно добавить AJAX-запрос к серверу
            // Для примера используем имитацию отправки
            
            // Показываем индикатор загрузки
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
            
            // Имитация запроса к серверу
            setTimeout(() => {
                // В реальном приложении здесь будет fetch или axios запрос
                console.log('Запрос на восстановление пароля для:', email);
                
                // Скрываем форму и показываем сообщение об успехе
                forgotPasswordForm.classList.add('hidden');
                successMessage.classList.remove('hidden');
                
                // Восстанавливаем кнопку (на случай, если нужно вернуть форму)
                submitButton.disabled = false;
                submitButton.innerHTML = 'Отправить инструкции';
            }, 1500);
        });
    }
});

// Валидация email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Показать уведомление
function showAlert(message, type = 'success') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    document.body.appendChild(alert);
    
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