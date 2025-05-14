<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À propos - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<main class="container" style="max-width:900px;">
    <section class="about-hero" style="display:flex;align-items:center;gap:2rem;margin-bottom:2.5rem;background:var(--card-bg);border-radius:1.2rem;box-shadow:0 2px 16px rgba(67,176,71,0.10);padding:2rem 1.5rem;flex-wrap:wrap;">
        <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=400&q=80" alt="Team" style="border-radius:1rem;width:320px;max-width:100%;box-shadow:0 2px 16px rgba(67,176,71,0.12);flex:1 1 220px;">
        <div style="flex:2 1 320px;min-width:220px;">
            <h1 style="margin-top:0;">À propos de SportMatchy</h1>
            <p style="font-size:1.15em;line-height:1.7;">SportMatchy est votre plateforme de rencontre sportive. Trouvez des partenaires, rejoignez des événements et partagez votre passion pour le sport.</p>
        </div>
    </section>
    <section class="about-mission" style="margin-bottom:2.5rem;background:var(--card-bg);border-radius:1.2rem;box-shadow:0 2px 16px rgba(67,176,71,0.10);padding:2rem 1.5rem;display:flex;align-items:center;gap:2rem;flex-wrap:wrap;">
        <img src="https://images.unsplash.com/photo-1464983953574-0892a716854b?auto=format&fit=crop&w=400&q=80" alt="Mission" style="border-radius:1rem;width:220px;max-width:100%;box-shadow:0 2px 16px rgba(67,176,71,0.12);flex:1 1 120px;">
        <div style="flex:2 1 320px;min-width:220px;">
            <h2 style="margin-top:0;">Notre mission</h2>
            <p style="font-size:1.1em;line-height:1.7;">Nous croyons que le sport rapproche les gens. Notre mission est de faciliter la création de communautés sportives locales et de rendre la pratique sportive accessible à tous.</p>
        </div>
    </section>
    <section class="about-team" style="background:var(--card-bg);border-radius:1.2rem;box-shadow:0 2px 16px rgba(67,176,71,0.10);padding:2rem 1.5rem;">
        <h2 style="margin-top:0;">L'équipe</h2>
        <div style="display:flex;gap:2rem;flex-wrap:wrap;justify-content:center;">
            <div style="text-align:center;">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTD0ljYyvoMTTemEAMpfHwc-DMlDsFtft9fbA&s" alt="Mokhtar" style="border-radius:50%;width:120px;height:120px;object-fit:cover;box-shadow:0 2px 8px rgba(67,176,71,0.12);">
                <div>Mokhtar</div>
            </div>
            <div style="text-align:center;">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRxFFJUsl9Qt4mknNSiPhEmVsHYulLtxV9zOQ&s" alt="Abdelkrim" style="border-radius:50%;width:120px;height:120px;object-fit:cover;box-shadow:0 2px 8px rgba(67,176,71,0.12);">
                <div>Abdelkrim</div>
            </div>
        </div>
    </section>
</main>
<?php require_once 'includes/footer.php'; ?>
</body>
</html> 