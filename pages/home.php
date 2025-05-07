<?php
// Récupération des événements à venir
$db = getDBConnection();
$stmt = $db->query("
    SELECT e.*, s.name as sport_name, u.username as creator_name,
           COUNT(ep.user_id) as participant_count
    FROM events e
    JOIN sports s ON e.sport_id = s.id
    JOIN users u ON e.creator_id = u.id
    LEFT JOIN event_participants ep ON e.id = ep.event_id AND ep.status = 'joined'
    WHERE e.start_time > NOW() AND e.status = 'active'
    GROUP BY e.id
    ORDER BY e.start_time ASC
    LIMIT 6
");
$upcomingEvents = $stmt->fetchAll();

// Récupération des sports disponibles
$stmt = $db->query("SELECT * FROM sports ORDER BY name");
$sports = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="display-4">Bienvenue sur <?php echo APP_NAME; ?></h1>
        <p class="lead">Trouvez des partenaires pour vos activités sportives et rejoignez des événements près de chez vous !</p>
    </div>
    <?php if (!$isLoggedIn): ?>
    <div class="col-md-4 text-end">
        <a href="/?page=register" class="btn btn-primary btn-lg">S'inscrire</a>
        <a href="/?page=login" class="btn btn-outline-primary btn-lg">Se connecter</a>
    </div>
    <?php endif; ?>
</div>

<!-- Sports populaires -->
<section class="mb-5">
    <h2>Sports populaires</h2>
    <div class="row g-4">
        <?php foreach ($sports as $sport): ?>
        <div class="col-md-4 col-lg-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-<?php echo $sport['icon'] ?? 'running'; ?> fa-3x mb-3"></i>
                    <h5 class="card-title"><?php echo htmlspecialchars($sport['name']); ?></h5>
                    <a href="/?page=events&sport=<?php echo $sport['id']; ?>" class="btn btn-outline-primary">Voir les événements</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Événements à venir -->
<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Événements à venir</h2>
        <a href="/?page=events" class="btn btn-outline-primary">Voir tous les événements</a>
    </div>
    <div class="row g-4">
        <?php foreach ($upcomingEvents as $event): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                    <h6 class="card-subtitle mb-2 text-muted">
                        <i class="fas fa-<?php echo $event['icon'] ?? 'running'; ?>"></i>
                        <?php echo htmlspecialchars($event['sport_name']); ?>
                    </h6>
                    <p class="card-text">
                        <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($event['start_time'])); ?><br>
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?><br>
                        <i class="fas fa-users"></i> <?php echo $event['participant_count']; ?>/<?php echo $event['max_participants']; ?> participants
                    </p>
                    <a href="/?page=event-details&id=<?php echo $event['id']; ?>" class="btn btn-primary">Voir les détails</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Comment ça marche -->
<section class="mb-5">
    <h2 class="text-center mb-4">Comment ça marche ?</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="text-center">
                <i class="fas fa-user-plus fa-3x mb-3 text-primary"></i>
                <h4>1. Créez votre profil</h4>
                <p>Inscrivez-vous et personnalisez votre profil avec vos sports préférés et votre niveau.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center">
                <i class="fas fa-calendar-plus fa-3x mb-3 text-primary"></i>
                <h4>2. Créez ou rejoignez un événement</h4>
                <p>Créez votre propre événement ou rejoignez ceux qui vous intéressent.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="text-center">
                <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                <h4>3. Pratiquez votre sport</h4>
                <p>Rencontrez d'autres passionnés et pratiquez votre sport favori !</p>
            </div>
        </div>
    </div>
</section> 