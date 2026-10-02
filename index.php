<?php
/**
 * index.php - Video-pagina met comments-sectie
 *
 * Alles gebeurt op deze ene pagina:
 *   1. Het formulier wordt getoond.
 *   2. Bij verzenden (POST) valideren we de gegevens met PHP.
 *   3. Is alles goed? Dan opslaan in de database en de pagina opnieuw laden.
 *   4. Is er iets fout? Dan tonen we foutmeldingen en blijft het formulier ingevuld.
 *   5. Onderaan worden alle comments uit de database getoond.
 */

// Composer-packages laden (hier gebruiken we Carbon voor "x minuten geleden").
// Eerst `composer install` uitvoeren, dan bestaat de map "vendor".
require __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

// ---------------------------------------------------------------
// 1. Verbinding maken met de database (pas aan naar jouw situatie)
// ---------------------------------------------------------------
$host    = 'mysql_db';
$dbnaam  = 'video_comments';
$gebruiker = 'root';
$wachtwoord = 'root';

try {
    // PDO is de moderne manier om met een database te praten in PHP.
    $db = new PDO(
        "mysql:host=$host;dbname=$dbnaam;charset=utf8mb4",
        $gebruiker,
        $wachtwoord,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION] // toon fouten als exception
    );
} catch (PDOException $e) {
    // Geen verbinding? Stop en toon een nette melding (geen wachtwoorden lekken!).
    die('Databasefout: ' . $e->getMessage());
}

// ---------------------------------------------------------------
// 2. Variabelen voorbereiden
// ---------------------------------------------------------------
$naam = $email = $commentaar = '';   // ingevulde waarden (om het formulier opnieuw te vullen)
$fouten = [];                        // hier verzamelen we alle foutmeldingen

// ---------------------------------------------------------------
// 3. Is het formulier verzonden? Dan valideren we alles
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // trim() haalt spaties aan het begin en einde weg.
    // "?? ''" voorkomt een fout als het veld helemaal niet is meegestuurd.
    $naam       = trim($_POST['naam'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $commentaar = trim($_POST['commentaar'] ?? '');

    // --- Naam controleren ---
    if ($naam === '') {
        $fouten['naam'] = 'Vul je naam in.';
    } elseif (mb_strlen($naam) > 50) {
        $fouten['naam'] = 'Je naam mag maximaal 50 tekens zijn.';
    }

    // --- E-mail controleren ---
    if ($email === '') {
        $fouten['email'] = 'Vul je e-mailadres in.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // filter_var met FILTER_VALIDATE_EMAIL controleert of het een geldig adres is.
        $fouten['email'] = 'Dit is geen geldig e-mailadres.';
    } elseif (mb_strlen($email) > 100) {
        $fouten['email'] = 'Je e-mailadres mag maximaal 100 tekens zijn.';
    }

    // --- Commentaar controleren ---
    if ($commentaar === '') {
        $fouten['commentaar'] = 'Schrijf een commentaar.';
    } elseif (mb_strlen($commentaar) > 1000) {
        $fouten['commentaar'] = 'Je commentaar mag maximaal 1000 tekens zijn.';
    }

    // --- Geen fouten? Dan opslaan in de database ---
    if (empty($fouten)) {

        // Prepared statement: de ? zijn plekken waar de waarden veilig in komen.
        // Zo kan niemand SQL-injectie doen via het formulier.
        // De kolommen "id" en "aangemaakt_op" vullen MySQL zelf in.
        $stmt = $db->prepare(
            'INSERT INTO comments (naam, email, commentaar) VALUES (?, ?, ?)'
        );
        $stmt->execute([$naam, $email, $commentaar]);

        // Stuur de bezoeker door naar dezelfde pagina (GET).
        // Zo wordt het formulier niet opnieuw verzonden bij het verversen (F5).
        header('Location: ' . $_SERVER['PHP_SELF'] . '#comments');
        exit;
    }
}

// ---------------------------------------------------------------
// 4. Alle comments ophalen, nieuwste bovenaan
// ---------------------------------------------------------------
$comments = $db->query(
    'SELECT naam, commentaar, aangemaakt_op FROM comments ORDER BY aangemaakt_op DESC, id DESC'
)->fetchAll(PDO::FETCH_ASSOC);

/**
 * Korte hulpfunctie: maakt tekst veilig om in HTML te tonen.
 * htmlspecialchars() zet < en > om naar &lt; en &gt;,
 * waardoor HTML of JavaScript van hackers niet wordt uitgevoerd.
 */
function veilig(string $tekst): string
{
    return htmlspecialchars($tekst, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Video met comments</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f6; margin: 0; color: #222; }
        .pagina { max-width: 720px; margin: 0 auto; padding: 20px; }
        .video { position: relative; padding-bottom: 56.25%; height: 0; } /* 16:9 verhouding */
        .video iframe { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; border-radius: 8px; }
        form, .comment { background: #fff; padding: 16px; border-radius: 8px; margin-top: 16px; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input, textarea { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; font: inherit; }
        input.fout, textarea.fout { border-color: #c0392b; }
        .foutmelding { color: #c0392b; font-size: 0.9em; margin-top: 4px; }
        button { margin-top: 12px; padding: 10px 18px; background: #2563eb; color: #fff; border: 0; border-radius: 4px; cursor: pointer; }
        .comment { display: flex; gap: 12px; }
        .avatar { flex: 0 0 40px; height: 40px; border-radius: 50%; background: #2563eb; color: #fff;
                  display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .tijd { color: #777; font-size: 0.85em; margin-left: 6px; font-weight: normal; }
        .comment p { margin: 4px 0 0; white-space: pre-line; } /* behoud enters in comment */
    </style>
</head>
<body>
<div class="pagina">

    <h1>Mijn favoriete video</h1>

    <!-- De video: een YouTube-embed. Vervang de link na /embed/ door jouw video-ID. -->
    <div class="video">
        <iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"
                title="Video" allowfullscreen></iframe>
    </div>

    <h2>Plaats een commentaar</h2>

    <!-- Het formulier stuurt zichzelf naar dezelfde pagina (method="post").
         novalidate: we laten de browser niet controleren, zodat je de PHP-controle kunt testen.
         Haal dat attribuut weg als je ook de browser-controle wilt. -->
    <form method="post" action="<?= veilig($_SERVER['PHP_SELF']) ?>" novalidate>

        <label for="naam">Naam</label>
        <!-- value="..." zet de eerder ingevulde waarde terug (veilig gemaakt met htmlspecialchars) -->
        <input type="text" id="naam" name="naam" maxlength="50"
               value="<?= veilig($naam) ?>" class="<?= isset($fouten['naam']) ? 'fout' : '' ?>">
        <?php if (isset($fouten['naam'])): ?>
            <div class="foutmelding"><?= veilig($fouten['naam']) ?></div>
        <?php endif; ?>

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" maxlength="100"
               value="<?= veilig($email) ?>" class="<?= isset($fouten['email']) ? 'fout' : '' ?>">
        <?php if (isset($fouten['email'])): ?>
            <div class="foutmelding"><?= veilig($fouten['email']) ?></div>
        <?php endif; ?>

        <label for="commentaar">Commentaar</label>
        <textarea id="commentaar" name="commentaar" rows="4" maxlength="1000"
                  class="<?= isset($fouten['commentaar']) ? 'fout' : '' ?>"><?= veilig($commentaar) ?></textarea>
        <?php if (isset($fouten['commentaar'])): ?>
            <div class="foutmelding"><?= veilig($fouten['commentaar']) ?></div>
        <?php endif; ?>

        <button type="submit">Plaatsen</button>
    </form>

    <h2 id="comments"><?= count($comments) ?> commentaren</h2>

    <?php foreach ($comments as $c): ?>
        <?php
            // Initialen-avatar: eerste letter van de naam, in hoofdletter.
            $initiaal = mb_strtoupper(mb_substr($c['naam'], 0, 1));

            // Carbon: maak van de datum uit de database "3 minuten geleden".
            $geleden = Carbon::parse($c['aangemaakt_op'])->locale('nl')->diffForHumans();
        ?>
        <div class="comment">
            <div class="avatar"><?= veilig($initiaal) ?></div>
            <div>
                <strong><?= veilig($c['naam']) ?></strong>
                <span class="tijd"><?= veilig($geleden) ?></span>
                <p><?= veilig($c['commentaar']) ?></p>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($comments)): ?>
        <p>Nog geen commentaren. Wees de eerste!</p>
    <?php endif; ?>

</div>
</body>
</html>
