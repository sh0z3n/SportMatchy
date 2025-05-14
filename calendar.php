<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
Session::start();
if (!Session::isLoggedIn()) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Calendrier des événements - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    #calendar { max-width: 900px; margin: 2rem auto; background: #fff; border-radius: 1rem; box-shadow: 0 2px 16px rgba(67,176,71,0.08); padding: 2rem; }
    .fc-toolbar-title { color: #43b047; }
    .modal-bg { display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.3); z-index:999; align-items:center; justify-content:center; }
    .modal { background:#fff; border-radius:1rem; padding:2rem; min-width:320px; max-width:90vw; box-shadow:0 2px 16px rgba(0,0,0,0.12); }
    </style>
</head>
<body>
<?php include 'includes/header.php'; ?>
<div id="calendar"></div>
<div class="modal-bg" id="event-modal-bg">
    <div class="modal" id="event-modal">
        <h2 id="modal-title"></h2>
        <div id="modal-details"></div>
        <div style="margin-top:1.5rem;display:flex;gap:1rem;">
            <button id="modal-participate" class="btn btn-primary">Participer</button>
            <a id="modal-chat" class="btn btn-secondary" href="#">Chat</a>
            <button id="modal-close" class="btn btn-outline">Fermer</button>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        events: '/api/events-calendar.php',
        eventClick: function(info) {
            fetch('/api/events-calendar.php?id=' + info.event.id)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('modal-title').textContent = data.title;
                    document.getElementById('modal-details').innerHTML =
                        '<b>Date:</b> ' + data.start_time + '<br>' +
                        '<b>Lieu:</b> ' + data.location + '<br>' +
                        '<b>Description:</b> ' + data.description;
                    document.getElementById('modal-participate').onclick = function() {
                        fetch('/api/join-event.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ event_id: data.id })
                        }).then(r => r.json()).then(resp => {
                            if (resp.success) alert('Participation confirmée!');
                            else alert(resp.error || 'Erreur.');
                        });
                    };
                    document.getElementById('modal-chat').href = '/chat.php?event_id=' + data.id;
                    document.getElementById('event-modal-bg').style.display = 'flex';
                });
        }
    });
    calendar.render();
    document.getElementById('modal-close').onclick = function() {
        document.getElementById('event-modal-bg').style.display = 'none';
    };
    document.getElementById('event-modal-bg').onclick = function(e) {
        if (e.target === this) this.style.display = 'none';
    };
});
</script>
</body>
</html> 