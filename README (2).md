# Video met comments

## Installeren
1. Importeer `database.sql` (phpMyAdmin → Importeren).
2. Open een terminal in deze map en voer uit: `composer install`
   (dit maakt de map `vendor/` aan met het package **Carbon**).
3. Pas bovenin `index.php` de database-gegevens aan (gebruiker/wachtwoord).
4. Zet de map in je webserver (bijv. XAMPP `htdocs`) en open `index.php`,
   of start: `php -S localhost:8000`

## Wat zit erin
- `index.php`   – pagina, formulier, validatie, opslaan, tonen
- `database.sql` – dump van de tabel `comments`
- `composer.json` – package `nesbot/carbon` (hoe lang geleden)

## Beveiliging
- E-mail wordt gecontroleerd met `filter_var(..., FILTER_VALIDATE_EMAIL)` in PHP.
- Opslaan met PDO prepared statements (tegen SQL-injectie).
- Alles wordt met `htmlspecialchars()` getoond (tegen HTML/JS-injectie).
