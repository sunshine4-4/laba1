document.addEventListener('DOMContentLoaded', function() {
    // Инициализация страницы
    initPage();
    
    // Загрузка данных о специалистах
    loadSpecialists();
    
    // Настройка обработчиков событий
    setupEventListeners();
});

// Инициализация страницы
function initPage() {
    // Проверяем, есть ли параметр specialist в URL (для предварительного выбора)
    const urlParams = new URLSearchParams(window.location.search);
    const specialistId = urlParams.get('specialist');
    
    if (specialistId) {
        // Если есть ID специалиста в URL, откроем его модальное окно после загрузки данных
        sessionStorage.setItem('openSpecialistId', specialistId);
    }
}

// Загрузка данных о специалистах
function loadSpecialists() {
    showLoading(true);
    
    // Здесь можно использовать fetch для загрузки данных с сервера
    // Для примера используем имитацию загрузки
    setTimeout(() => {
        fetch('php/specialists.php?action=get_all')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderSpecialists(data.specialists);
                    
                    // Проверяем, нужно ли открыть модальное окно специалиста
                    const specialistIdToOpen = sessionStorage.getItem('openSpecialistId');
                    if (specialistIdToOpen) {
                        const specialist = data.specialists.find(s => s.id == specialistIdToOpen);
                        if (specialist) {
                            openSpecialistModal(specialist);
                        }
                        sessionStorage.removeItem('openSpecialistId');
                    }
                } else {
                    showError(data.message || 'Ошибка загрузки данных');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                showError('Ошибка загрузки данных. Пожалуйста, попробуйте позже.');
            })
            .finally(() => {
                showLoading(false);
            });
    }, 500);
}

// Отрисовка списка специалистов
function renderSpecialists(specialists) {
    const container = document.getElementById('specialistsContainer');
    if (!container) return;
    
    // Очищаем контейнер
    container.innerHTML = '';
    
    if (!specialists || specialists.length === 0) {
        container.innerHTML = `
            <div class="no-results">
                <i class="fas fa-user-md"></i>
                <p>Специалисты не найдены</p>
            </div>
        `;
        return;
    }
    
    // Группируем специалистов по отделениям
    const departments = {};
    specialists.forEach(specialist => {
        if (!departments[specialist.department]) {
            departments[specialist.department] = [];
        }
        departments[specialist.department].push(specialist);
    });
    
    // Создаем секции для каждого отделения
    for (const [department, departmentSpecialists] of Object.entries(departments)) {
        const departmentSection = document.createElement('div');
        departmentSection.className = 'department-section';
        
        const departmentTitle = document.createElement('h3');
        departmentTitle.className = 'department-title';
        departmentTitle.textContent = department;
        departmentSection.appendChild(departmentTitle);
        
        const specialistsGrid = document.createElement('div');
        specialistsGrid.className = 'specialists-grid';
        
        departmentSpecialists.forEach(specialist => {
            const specialistCard = createSpecialistCard(specialist);
            specialistsGrid.appendChild(specialistCard);
        });
        
        departmentSection.appendChild(specialistsGrid);
        container.appendChild(departmentSection);
    }
}

// Создание карточки специалиста
function createSpecialistCard(specialist) {
    const card = document.createElement('div');
    card.className = 'specialist-card';
    card.dataset.id = specialist.id;
    
    card.innerHTML = `
        <div class="specialist-image">
            <img src="${specialist.photo || '../assets/images/doctor-placeholder.jpg'}" 
                 alt="${specialist.full_name}" 
                 onerror="this.src='../assets/images/doctor-placeholder.jpg'">
        </div>
        <div class="specialist-info">
            <h3>${specialist.full_name}</h3>
            <p class="specialization">${specialist.specialization}</p>
            <p class="department">${specialist.department}</p>
            <p class="experience">Стаж: ${specialist.experience || '—'} лет</p>
            <div class="specialist-actions">
                <a href="appointment.html?specialist=${specialist.id}" class="btn btn-primary">
                    Записаться
                </a>
                <button class="btn btn-secondary view-details" data-id="${specialist.id}">
                    Подробнее
                </button>
            </div>
        </div>
    `;
    
    return card;
}

// Настройка обработчиков событий
function setupEventListeners() {
    // Поиск специалистов
    const searchInput = document.getElementById('searchSpecialist');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterSpecialists();
        });
    }
    
    // Фильтр по отделениям
    const departmentFilter = document.getElementById('department');
    if (departmentFilter) {
        departmentFilter.addEventListener('change', function() {
            filterSpecialists();
        });
    }
    
    // Обработка кликов по кнопкам "Подробнее"
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-details') || e.target.closest('.view-details')) {
            e.preventDefault();
            const button = e.target.classList.contains('view-details') ? e.target : e.target.closest('.view-details');
            const specialistId = button.dataset.id;
            
            // Находим специалиста (в реальном приложении можно запросить с сервера)
            const specialistCard = document.querySelector(`.specialist-card[data-id="${specialistId}"]`);
            if (specialistCard) {
                // В реальном приложении здесь должен быть запрос к серверу за полной информацией
                const specialist = {
                    id: specialistId,
                    full_name: specialistCard.querySelector('h3').textContent,
                    specialization: specialistCard.querySelector('.specialization').textContent,
                    department: specialistCard.querySelector('.department').textContent,
                    experience: specialistCard.querySelector('.experience').textContent.replace('Стаж: ', '').replace(' лет', ''),
                    photo: specialistCard.querySelector('.specialist-image img').src,
                    education: 'Информация об образовании загружается...',
                    bio: 'Подробная информация о специалисте загружается...',
                    schedule: 'График работы: Пн-Пт с 9:00 до 18:00'
                };
                
                openSpecialistModal(specialist);
            }
        }
    });
}

// Фильтрация специалистов
function filterSpecialists() {
    const searchTerm = document.getElementById('searchSpecialist').value.toLowerCase();
    const departmentFilter = document.getElementById('department').value;
    
    const specialistCards = document.querySelectorAll('.specialist-card');
    let hasVisibleCards = false;
    
    specialistCards.forEach(card => {
        const name = card.querySelector('h3').textContent.toLowerCase();
        const specialization = card.querySelector('.specialization').textContent.toLowerCase();
        const department = card.querySelector('.department').textContent;
        
        const matchesSearch = name.includes(searchTerm) || specialization.includes(searchTerm);
        const matchesDepartment = departmentFilter === 'all' || department === departmentFilter;
        
        if (matchesSearch && matchesDepartment) {
            card.style.display = '';
            hasVisibleCards = true;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Показываем сообщение, если ничего не найдено
    const noResults = document.querySelector('.no-results');
    if (!hasVisibleCards) {
        if (!noResults) {
            const container = document.getElementById('specialistsContainer');
            container.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>Специалисты по вашему запросу не найдены</p>
                </div>
            `;
        }
    } else if (noResults) {
        noResults.remove();
    }
    
    // Обновляем заголовки отделений
    updateDepartmentHeaders();
}

// Обновление заголовков отделений (скрываем пустые)
function updateDepartmentHeaders() {
    const departmentSections = document.querySelectorAll('.department-section');
    
    departmentSections.forEach(section => {
        const grid = section.querySelector('.specialists-grid');
        const visibleCards = grid.querySelectorAll('.specialist-card:not([style*="display: none"])');
        
        if (visibleCards.length === 0) {
            section.style.display = 'none';
        } else {
            section.style.display = '';
        }
    });
}

// Открытие модального окна специалиста
function openSpecialistModal(specialist) {
    const modal = document.getElementById('specialistModal');
    const modalContent = document.getElementById('modalSpecialistInfo');
    
    if (!modal || !modalContent) return;
    
    modalContent.innerHTML = `
        <div class="specialist-details">
            <div class="specialist-image">
                <img src="${specialist.photo || '../assets/images/doctor-placeholder.jpg'}" 
                     alt="${specialist.full_name}"
                     onerror="this.src='../assets/images/doctor-placeholder.jpg'">
            </div>
            <div class="specialist-info">
                <h3>${specialist.full_name}</h3>
                <p class="specialty">${specialist.specialization}</p>
                <span class="department">${specialist.department}</span>
                <p class="experience">Стаж работы: ${specialist.experience || '—'} лет</p>
                <p class="education"><strong>Образование:</strong> ${specialist.education || '—'}</p>
                <p class="schedule"><strong>График работы:</strong> ${specialist.schedule || '—'}</p>
                <div class="bio">${specialist.bio || 'Нет дополнительной информации'}</div>
            </div>
        </div>
    `;
    
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Загружаем полную информацию о специалисте с сервера
    loadFullSpecialistInfo(specialist.id);
}

// Загрузка полной информации о специалисте
function loadFullSpecialistInfo(specialistId) {
    fetch(`php/specialists.php?action=get_details&id=${specialistId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateSpecialistModal(data.specialist);
            } else {
                console.error('Ошибка загрузки данных:', data.message);
            }
        })
        .catch(error => {
            console.error('Ошибка:', error);
        });
}

// Обновление модального окна с полученными данными
function updateSpecialistModal(specialist) {
    const modalContent = document.getElementById('modalSpecialistInfo');
    if (!modalContent) return;
    
    const infoContainer = modalContent.querySelector('.specialist-info');
    if (!infoContainer) return;
    
    if (specialist.education) {
        const educationElement = infoContainer.querySelector('.education');
        if (educationElement) {
            educationElement.innerHTML = `<strong>Образование:</strong> ${specialist.education}`;
        }
    }
    
    if (specialist.bio) {
        const bioElement = infoContainer.querySelector('.bio');
        if (bioElement) {
            bioElement.innerHTML = specialist.bio;
        }
    }
    
    if (specialist.schedule) {
        const scheduleElement = infoContainer.querySelector('.schedule');
        if (scheduleElement) {
            scheduleElement.innerHTML = `<strong>График работы:</strong> ${specialist.schedule}`;
        }
    }
}

// Показать/скрыть индикатор загрузки
function showLoading(show) {
    const container = document.getElementById('specialistsContainer');
    if (!container) return;
    
    if (show) {
        container.innerHTML = `
            <div class="loading">
                <div class="spinner"></div>
                <p>Загрузка данных...</p>
            </div>
        `;
    }
}

// Показать сообщение об ошибке
function showError(message) {
    const container = document.getElementById('specialistsContainer');
    if (!container) return;
    
    container.innerHTML = `
        <div class="error">
            <i class="fas fa-exclamation-triangle"></i>
            <p>${message}</p>
            <button class="btn btn-primary" onclick="window.location.reload()">Попробовать снова</button>
        </div>
    `;
}