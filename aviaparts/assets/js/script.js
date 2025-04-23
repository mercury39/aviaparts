// Функція для показу повідомлення
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    `;
    
    // Додаємо повідомлення на початку головного контейнера
    const mainContainer = document.querySelector('main.container');
    mainContainer.insertBefore(alertDiv, mainContainer.firstChild);
    
    // Автоматично приховуємо повідомлення через 5 секунд
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => {
            alertDiv.remove();
        }, 150);
    }, 5000);
}

// Функція для додавання комплектуючої до порівняння
function addToCompare(partId, partName) {
    // Отримуємо поточний список порівняння з localStorage
    let compareList = JSON.parse(localStorage.getItem('compareList')) || [];
    
    // Перевіряємо, чи вже додана ця комплектуюча
    if (!compareList.some(item => item.id === partId)) {
        // Додаємо, якщо не більше 3-х елементів
        if (compareList.length < 3) {
            compareList.push({
                id: partId,
                name: partName
            });
            localStorage.setItem('compareList', JSON.stringify(compareList));
            showAlert(`Комплектуюча "${partName}" додана до порівняння`, 'success');
            updateCompareCounter();
        } else {
            showAlert('Ви не можете додати більше 3-х елементів для порівняння', 'warning');
        }
    } else {
        showAlert(`Комплектуюча "${partName}" вже додана до порівняння`, 'info');
    }
}

// Функція для видалення комплектуючої з порівняння
function removeFromCompare(partId) {
    let compareList = JSON.parse(localStorage.getItem('compareList')) || [];
    compareList = compareList.filter(item => item.id !== partId);
    localStorage.setItem('compareList', JSON.stringify(compareList));
    updateCompareCounter();
    
    // Якщо ми на сторінці порівняння, оновлюємо її
    if (window.location.pathname.includes('compare.php')) {
        window.location.reload();
    }
}

// Функція для оновлення лічильника порівняння
function updateCompareCounter() {
    const compareList = JSON.parse(localStorage.getItem('compareList')) || [];
    const compareCounter = document.getElementById('compare-counter');
    
    if (compareCounter) {
        compareCounter.textContent = compareList.length;
        
        if (compareList.length > 0) {
            compareCounter.style.display = 'inline-block';
        } else {
            compareCounter.style.display = 'none';
        }
    }
}

// Ініціалізація під час завантаження сторінки
document.addEventListener('DOMContentLoaded', function() {
    // Ініціалізація лічильника порівняння
    updateCompareCounter();
    
    // Ініціалізація випадаючих списків Bootstrap
    const dropdownToggleList = document.querySelectorAll('.dropdown-toggle');
    dropdownToggleList.forEach(function(dropdownToggle) {
        new bootstrap.Dropdown(dropdownToggle);
    });
    
    // Ініціалізація підказок Bootstrap
    const tooltipTriggerList = document.querySelectorAll('[data-toggle="tooltip"]');
    tooltipTriggerList.forEach(function(tooltipTrigger) {
        new bootstrap.Tooltip(tooltipTrigger);
    });
});