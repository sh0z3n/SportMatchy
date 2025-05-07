<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/session.php';

// Start session
Session::start();

$db = Database::getInstance();

// Get all sports for filter
$sports = $db->query("SELECT * FROM sports ORDER BY name")->fetchAll();

// Get events with location data
$events = $db->query("
    SELECT e.*, s.name as sport_name, u.username as creator_name,
           (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id AND status = 'joined') as participant_count
    FROM events e
    JOIN sports s ON e.sport_id = s.id
    JOIN users u ON e.creator_id = u.id
    WHERE e.latitude IS NOT NULL AND e.longitude IS NOT NULL
    AND e.start_time > NOW()
    ORDER BY e.start_time ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte des événements - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="map-section">
            <h1 class="section-title">Carte des événements</h1>

            <div class="map-filters">
                <div class="filter-group">
                    <label for="sport-filter">Sport</label>
                    <select id="sport-filter" class="form-control">
                        <option value="">Tous les sports</option>
                        <?php foreach ($sports as $sport): ?>
                            <option value="<?php echo $sport['id']; ?>">
                                <?php echo htmlspecialchars($sport['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="date-filter">Date</label>
                    <select id="date-filter" class="form-control">
                        <option value="">Toutes les dates</option>
                        <option value="today">Aujourd'hui</option>
                        <option value="tomorrow">Demain</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                    </select>
                </div>
            </div>

            <div id="events-map" class="events-map"></div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
        // Initialize map
        const map = new google.maps.Map(document.getElementById('events-map'), {
            center: { lat: 48.8566, lng: 2.3522 }, // Paris
            zoom: 12
        });

        const markers = [];
        const events = <?php echo json_encode($events); ?>;

        // Create markers for each event
        events.forEach(event => {
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(event.latitude), lng: parseFloat(event.longitude) },
                map: map,
                title: event.title
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div class="event-info-window">
                        <h3>${event.title}</h3>
                        <p><i class="fas fa-running"></i> ${event.sport_name}</p>
                        <p><i class="fas fa-user"></i> ${event.creator_name}</p>
                        <p><i class="fas fa-users"></i> ${event.participant_count}/${event.max_participants} participants</p>
                        <p><i class="fas fa-calendar"></i> ${new Date(event.start_time).toLocaleString()}</p>
                        <a href="event.php?id=${event.id}" class="btn btn-primary">Voir l'événement</a>
                    </div>
                `
            });

            marker.addListener('click', () => {
                infoWindow.open(map, marker);
            });

            markers.push({ marker, event });
        });

        // Filter events
        function filterEvents() {
            const sportId = document.getElementById('sport-filter').value;
            const dateFilter = document.getElementById('date-filter').value;

            markers.forEach(({ marker, event }) => {
                let show = true;

                // Filter by sport
                if (sportId && event.sport_id != sportId) {
                    show = false;
                }

                // Filter by date
                if (dateFilter) {
                    const eventDate = new Date(event.start_time);
                    const today = new Date();
                    const tomorrow = new Date(today);
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    const nextWeek = new Date(today);
                    nextWeek.setDate(nextWeek.getDate() + 7);
                    const nextMonth = new Date(today);
                    nextMonth.setMonth(nextMonth.getMonth() + 1);

                    switch (dateFilter) {
                        case 'today':
                            show = show && eventDate.toDateString() === today.toDateString();
                            break;
                        case 'tomorrow':
                            show = show && eventDate.toDateString() === tomorrow.toDateString();
                            break;
                        case 'week':
                            show = show && eventDate >= today && eventDate <= nextWeek;
                            break;
                        case 'month':
                            show = show && eventDate >= today && eventDate <= nextMonth;
                            break;
                    }
                }

                marker.setVisible(show);
            });
        }

        // Add event listeners for filters
        document.getElementById('sport-filter').addEventListener('change', filterEvents);
        document.getElementById('date-filter').addEventListener('change', filterEvents);
    </script>
</body>
</html> 