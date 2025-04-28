document.addEventListener('DOMContentLoaded', function() {
    // Инициализация слайдера специалистов
    initSpecialistsSlider();
    
    // Обработка кликов по кнопкам "Записаться на прием"
    setupAppointmentButtons();
    
    // Инициализация анимаций при скролле
    setupScrollAnimations();
    
    // Обработка формы подписки на новости (если есть)
    setupNewsletterForm();
});

// Инициализация слайдера специалистов
function initSpecialistsSlider() {
    const slider = document.querySelector('.specialists-slider');
    if (!slider) return;
    
    let isDown = false;
    let startX;
    let scrollLeft;
    
    slider.addEventListener('mousedown', (e) => {
        isDown = true;
        slider.classList.add('active');
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
    });
    
    slider.addEventListener('mouseleave', () => {
        isDown = false;
        slider.classList.remove('active');
    });
    
    slider.addEventListener('mouseup', () => {
        isDown = false;
        slider.classList.remove('active');
    });
    
    slider.addEventListener('mousemove', (e) => {
        if(!isDown) return;
        e.preventDefault();
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX) * 2;
        slider.scrollLeft = scrollLeft - walk;
    });
    
    // Добавляем обработчики для тач-устройств
    slider.addEventListener('touchstart', (e) => {
        isDown = true;
        slider.classList.add('active');
        startX = e.touches[0].pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
    });
    
    slider.addEventListener('touchend', () => {
        isDown = false;
        slider.classList.remove('active');
    });
    
    slider.addEventListener('touchmove', (e) => {
        if(!isDown) return;
        e.preventDefault();
        const x = e.touches[0].pageX - slider.offsetLeft;
        const walk = (x - startX) * 2;
        slider.scrollLeft = scrollLeft - walk;
    });
}

// Настройка кнопок записи на прием
function setupAppointmentButtons() {
    const appointmentButtons = document.querySelectorAll('[data-appointment]');
    
    appointmentButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Если кнопка в слайдере специалистов, открываем модальное окно
            if (this.closest('.specialist-card')) {
                e.preventDefault();
                const specialistId = this.getAttribute('data-specialist-id') || '';
                window.location.href = `pages/appointment.html${specialistId ? '?specialist=' + specialistId : ''}`;
            }
        });
    });
}

// Анимации при скролле
function setupScrollAnimations() {
    const animateElements = document.querySelectorAll('.advantage-card, .service-card, .specialist-card');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, {
        threshold: 0.1
    });
    
    animateElements.forEach(el => {
        observer.observe(el);
    });
}

// Обработка формы подписки на новости
function setupNewsletterForm() {
    const newsletterForm = document.getElementById('newsletterForm');
    if (!newsletterForm) return;
    
    newsletterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const emailInput = this.querySelector('input[type="email"]');
        const email = emailInput.value.trim();
        
        if (!validateEmail(email)) {
            showAlert('Пожалуйста, введите корректный email', 'error');
            return;
        }
        
        // Здесь можно добавить AJAX-запрос к серверу
        showAlert('Спасибо за подписку!', 'success');
        this.reset();
    });
}

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

// Загрузка специалистов через AJAX (если нужно)
function loadFeaturedSpecialists() {
    fetch('php/specialists.php?action=get_featured')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderSpecialists(data.specialists);
            } else {
                console.error('Ошибка загрузки специалистов:', data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
        });
}

// Отрисовка специалистов (если загружаем через AJAX)
function renderSpecialists(specialists) {
    const container = document.querySelector('.specialists-slider');
    if (!container) return;
    
    container.innerHTML = '';
    
    specialists.forEach(specialist => {
        const specialistCard = document.createElement('div');
        specialistCard.className = 'specialist-card';
        specialistCard.innerHTML = `
            <div class="specialist-image">
                <img src="${specialist.photo || 'assets/images/doctor-placeholder.jpg'}" alt="${specialist.full_name}">
            </div>
            <div class="specialist-info">
                <h3>${specialist.full_name}</h3>
                <p class="specialty">${specialist.specialization}</p>
                <p class="department">${specialist.department}</p>
                <div class="specialist-actions">
                    <a href="pages/appointment.html?specialist=${specialist.id}" class="btn btn-primary">Записаться</a>
                    <button class="btn btn-secondary view-details" data-specialist-id="${specialist.id}">Подробнее</button>
                </div>
            </div>
        `;
        
        container.appendChild(specialistCard);
    });
    
    // Инициализируем обработчики для новых кнопок
    setupAppointmentButtons();
}

// Инициализация карты (если есть на странице)
function initMap() {
    const mapElement = document.querySelector('.contact-map');
    if (!mapElement) return;
    
    // Здесь можно инициализировать Яндекс.Карты или Google Maps
    // Например:
    /*
    ymaps.ready(function() {
        const map = new ymaps.Map(mapElement, {
            center: [55.76, 37.64],
            zoom: 15
        });
        
        const placemark = new ymaps.Placemark([55.76, 37.64], {
            hintContent: 'Центр Семейной Медицины',
            balloonContent: 'г. Москва, ул. Медицинская, 15'
        });
        
        map.geoObjects.add(placemark);
    });
    */
    
    console.log('Карта инициализирована');
}

// Плавный скролл к секциям
function setupSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                const headerHeight = document.querySelector('.header').offsetHeight;
                const targetPosition = targetElement.offsetTop - headerHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
}