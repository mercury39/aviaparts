<?php
require_once 'includes/header.php';
?>

<div class="hero-section about-hero">
    <div class="container">
        <h1>Про AviaParts</h1>
        <p>Дізнайтеся більше про нашу компанію та нашу місію в авіаційній галузі</p>
    </div>
</div>

<div class="container">
    <div class="row mt-5">
        <div class="col-md-6">
            <h2>Хто ми</h2>
            <p>AviaParts - провідна українська платформа для класифікації та пошуку комплектуючих для літаків. Наша мета - зробити процес пошуку та порівняння авіаційних деталей максимально зручним та ефективним.</p>
            <p>Заснована у 2023 році групою ентузіастів авіації та IT-фахівців, наша платформа швидко стала незамінним інструментом для професіоналів галузі та приватних власників літаків.</p>
        </div>
        <div class="col-md-6">
            <div class="card bg-light">
                <div class="card-body">
                    <h3>Наші принципи</h3>
                    <ul>
                        <li>Точність та актуальність інформації</li>
                        <li>Зручність використання для користувачів</li>
                        <li>Постійне оновлення бази даних</li>
                        <li>Співпраця з провідними виробниками</li>
                        <li>Підтримка української авіаційної галузі</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-12">
            <h2>Наша місія</h2>
            <p>Ми прагнемо створити найбільш повну та актуальну базу даних авіаційних комплектуючих в Україні. Наша місія - допомогти спеціалістам авіаційної галузі, ремонтним службам та власникам літаків знаходити оптимальні рішення для своїх потреб.</p>
            <p>AviaParts постійно розвивається, додаючи нові функції та можливості для наших користувачів.</p>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-4 mb-4">
            <div class="feature-box">
                <i class="fas fa-database mb-3"></i>
                <h3>Повна база даних</h3>
                <p>Тисячі комплектуючих деталей з детальними характеристиками та сумісністю</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="feature-box">
                <i class="fas fa-headset mb-3"></i>
                <h3>Технічна підтримка</h3>
                <p>Наші експерти завжди готові допомогти з підбором необхідних комплектуючих</p>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="feature-box">
                <i class="fas fa-sync mb-3"></i>
                <h3>Регулярні оновлення</h3>
                <p>Ми постійно оновлюємо нашу базу даних новими моделями та характеристиками</p>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-12">
            <h2>Наша команда</h2>
            <p>За платформою AviaParts стоїть талановитий розробник, який створив і підтримує цей проект.</p>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-md-6 offset-md-3 mb-4">
            <div class="card team-card h-100">
                <img src="/aviaparts/assets/images/team/team1.jpg" class="card-img-top" alt="Розробник проекту" onerror="this.src='/aviaparts/assets/images/no-image.jpg'">
                <div class="card-body text-center">
                    <h5 class="card-title">Андрій Білоус</h5>
                    <p class="card-text text-muted">Розробник проекту</p>
                    <p class="card-text">Досвід розробки: 1 місяць</p>
                    <p class="card-text">Відповідальний за дизайн, розробку та підтримку сайту AviaParts. Спеціалізується на веб-розробці з використанням PHP, MySQL, HTML, CSS та JavaScript.</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-5">
        <div class="col-md-8 offset-md-2">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h3>Маєте запитання?</h3>
                    <p>Зв'яжіться з нами, і ми будемо раді допомогти вам з будь-якими питаннями</p>
                    <a href="/aviaparts/contact.php" class="btn btn-primary btn-lg">Зв'язатися з нами</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>