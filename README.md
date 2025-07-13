# Projekta tehniskais apraksts: Backend

Šis dokuments sniedz detalizētu tehnisku pārskatu par "Spānijas kailgliemezis" backend projektu, kas izstrādāts, izmantojot CodeIgniter 4 ietvaru. Tajā aprakstīta projekta arhitektūra, komponentes, izmantotās tehnoloģijas un API galapunkti.

## 1. Projekta pārskats

"Spānijas kailgliemezis" backend projekts ir RESTful API, kas nodrošina datu pārvaldību frontend aplikācijai. Tā galvenais mērķis ir glabāt, atgūt, modificēt un dzēst informāciju par kartītēm (ieteikumiem), rakstiem, video, tēmām un autoriem, kā arī nodrošināt administratora autentifikāciju, izmantojot JSON Web Tokens (JWT).

## 2. Projekta struktūra

Projekts ir strukturēts atbilstoši CodeIgniter 4 standarta arhitektūrai. Svarīgākās mapes un faili ir:

```text
app/
├── Common.php            # Vispārējas, globālas funkcijas
├── Config/               # Konfigurācijas faili
│   ├── App.php           # Galvenā aplikācijas konfigurācija
│   ├── Autoload.php      # Autoloading konfigurācija (helpers, namespaces)
│   ├── CORS.php          # CORS (Cross-Origin Resource Sharing) konfigurācija
│   ├── Constants.php     # Globālās konstantes (piem., UPLOAD_PATH)
│   ├── Database.php      # Datubāzes savienojuma konfigurācija
│   ├── ...               # Citas konfigurācijas
├── Controllers/          # Kontrolieri
│   ├── Api/              # API kontrolieri
│   │   ├── Article.php   # Rakstu API
│   │   ├── Auth.php      # Autentifikācijas API
│   │   ├── Author.php    # Autoru API
│   │   ├── Card.php      # Kartīšu (ieteikumu) API
│   │   ├── CardImage.php # Kartīšu attēlu API
│   │   ├── Theme.php     # Tēmu API
│   │   └── Video.php     # Video API
│   └── BaseController.php # Bāzes kontrolieris ar koplietojamu funkcionalitāti
├── Filters/              # Filtri (piem., autentifikācijai)
│   └── AuthFilter.php    # JWT autentifikācijas filtrs
├── Helpers/              # Palīgfunkcijas
│   └── FileOperationsHelper.php # Faila operāciju palīgfunkcijas
├── Language/             # Valodu faili
├── Models/               # Modeļi (datubāzes mijiedarbībai)
│   ├── ArticleModel.php
│   ├── AuthorModel.php
│   ├── CardImageModel.php
│   ├── CardModel.php
│   ├── ThemeModel.php
│   ├── UserModel.php
│   └── VideoModel.php
├── Validation/           # Pielāgotas validācijas
│   └── UserRules.php     # Lietotāja validācijas noteikumi
└── Views/                # Skati (galvenokārt kļūdu lapām)
    ├── errors/
    └── welcome_message.php
    
```

## 3. Komponentu uzskaitījums un funkcijas

### `/app/Controllers/Api` - API Kontrolieri

Visi API kontrolieri manto no `CodeIgniter\RESTful\ResourceController` (vai `App\Controllers\BaseController`, kas var nodrošināt papildu RESTful funkcionalitāti, piemēram, CORS apstrādi) un nodrošina standarta RESTful API galapunktus (GET, POST, PUT, DELETE) katrai resursu entītei.

* **`Article.php`**: Pārvalda rakstu izveidi, lasīšanu, atjaunināšanu un dzēšanu. Atbalsta jaunu autoru pievienošanu, ja autors vēl neeksistē.
* **`Auth.php`**: Nodrošina administratora pieteikšanās funkcionalitāti, ģenerējot un atgriežot JWT tokenu pēc veiksmīgas autentifikācijas.
* **`Author.php`**: Pārvalda autoru CRUD operācijas. Atjaunināšanas funkcija apstrādā gadījumus, kad ienākošie dati var būt nepilnīgi.
* **`Card.php`**: Pārvalda kartīšu CRUD operācijas. Ietver sarežģītu loģiku attēlu (CardImage) augšupielādei, atjaunināšanai un dzēšanai, izmantojot `FileOperationsHelper`. Izmanto datubāzes transakcijas, lai nodrošinātu datu integritāti. Atbalsta jaunu autoru izveidi gan kartītēm, gan attēliem.
* **`CardImage.php`**: Pārvalda atsevišķu kartīšu attēlu CRUD operācijas. Attēlu augšupielādes loģika ir definēta `create` metodē, savukārt atjaunināšanas metode pieņem URL.
* **`Theme.php`**: Pārvalda tēmu CRUD operācijas.
* **`Video.php`**: Pārvalda video izveidi, lasīšanu, atjaunināšanu un dzēšanu. Atbalsta gan video failu augšupielādi, gan video saišu (URL) ievadi. Izmanto datubāzes transakcijas un `FileOperationsHelper` failu pārvaldībai. Apstrādā jaunu autoru pievienošanu.

### `/app/Controllers` - Bāzes kontrolieris

* **`BaseController.php`**: Nodrošina kopīgu funkcionalitāti visiem kontrolieriem, tostarp CORS "preflight" (OPTIONS) pieprasījumu apstrādi. Tas atbild par `Access-Control-Allow-*` galveņu iestatīšanu, lai atļautu pieprasījumus no atļautiem frontend domēniem.

### `/app/Models` - Modeļi

Modeļi nodrošina saskarni ar datubāzes tabulām, ietverot validācijas noteikumus un laiku zīmogus (`created_at`, `updated_at`).

* **`ArticleModel.php`**: Rakstu datu modelis.
* **`AuthorModel.php`**: Autoru datu modelis.
* **`CardImageModel.php`**: Kartīšu attēlu datu modelis.
* **`CardModel.php`**: Kartīšu datu modelis.
* **`ThemeModel.php`**: Tēmu datu modelis.
* **`UserModel.php`**: Lietotāju datu modelis, ko izmanto autentifikācijai.
* **`VideoModel.php`**: Video datu modelis.

### `/app/Helpers` - Palīgfunkcijas

* **`FileOperationsHelper.php`**: Satur utilītfunkcijas failu pārvaldībai:
    * `normalize_filename(string $title, string $extension)`: Izveido drošu un unikālu faila nosaukumu, nomainot latviešu burtus un speciālos simbolus.
    * `upload_file_with_structure(\CodeIgniter\HTTP\Files\UploadedFile $uploadedFile, string $baseDir, int $themeId, int $cardId, string $recordTitle)`: Augšupielādē failu, izveidojot mapju struktūru balstoties uz tēmas un kartītes ID. Atgriež publiski pieejamu URL.
    * `delete_file_by_url(string $fileUrl)`: Dzēš failu, balstoties uz tā URL, un mēģina dzēst tukšas direktorijas.
    * `delete_directory_recursive(string $dirPath)`: Rekursīvi dzēš mapi un visu tās saturu.

### `/app/Filters` - Filtri

* **`AuthFilter.php`**: JWT autentifikācijas filtrs. Pārbauda `Authorization` galveni, dekodē JWT tokenu, izmantojot `JWT_SECRET_KEY` no `.env` faila. Ja tokens ir nederīgs vai nav atrasts, atgriež 401 Unauthorized atbildi.

### `/app/Validation` - Pielāgotas Validācijas

* **`UserRules.php`**: Satur pielāgotu validācijas noteikumu `validateUser`, kas pārbauda lietotājvārdu un paroli, salīdzinot nehešoto paroli ar datubāzē saglabāto hešu.

## 4. Datubāze un API galapunkti

* **Datubāze**: Projekts ir paredzēts darbam ar MySQL/MariaDB datubāzi, izmantojot `MySQLi` draiveri. Paredzētais datubāzes nosaukums ir `spanijaskailgliemezis_db`.
* **API Bāzes URL**: `http://localhost:8080/api` (izvietošanas vidē būs jāatjaunina).
* **Galvenie API galapunkti**:
    * **Publiskie (bez autentifikācijas)**:
        * `GET /api/authors`
        * `GET /api/authors/{id}`
        * `GET /api/themes`
        * `GET /api/themes/{id}`
        * `GET /api/cards`
        * `GET /api/cards/{id}`
        * `GET /api/articles`
        * `GET /api/articles/{id}`
        * `GET /api/videos`
        * `GET /api/videos/{id}`
        * `POST /api/admin/login` (autentifikācija)
    * **Aizsargātie (nepieciešams JWT tokens `Authorization: Bearer <token>` galvenē)**:
        * `POST /api/authors`
        * `PUT /api/authors/{id}`
        * `DELETE /api/authors/{id}`
        * `POST /api/themes`
        * `PUT /api/themes/{id}`
        * `DELETE /api/themes/{id}`
        * `POST /api/cards`
        * `PUT /api/cards/{id}`
        * `DELETE /api/cards/{id}`
        * `POST /api/card_images`
        * `PUT /api/card_images/{id}`
        * `DELETE /api/card_images/{id}`
        * `POST /api/articles`
        * `PUT /api/articles/{id}`
        * `DELETE /api/articles/{id}`
        * `POST /api/videos`
        * `PUT /api/videos/{id}`
        * `DELETE /api/videos/{id}`

## 5. Konfigurācija

* **`app/Config/App.php`**: Definē bāzes URL, noklusējuma lokalizāciju un laika joslu.
* **`app/Config/Autoload.php`**: Ielādē palīgfunkcijas, piemēram, `FileOperationsHelper`.
* **`app/Config/CORS.php`**: Konfigurē atļautās izcelsmes (origins) CORS pieprasījumiem, kas ir būtiski, ja frontend un backend atrodas dažādos domēnos vai portos. `allowedOrigins` ir iestatīts uz `['http://localhost:5173']`.
* **`app/Config/Constants.php`**: Definē globālās konstantes, tostarp `UPLOAD_PATH` failu augšupielādes direktorijai (`writable/uploads/`).
* **`app/Config/Database.php`**: Iestata datubāzes savienojuma parametrus (resursdators, lietotājvārds, parole, datubāze, draiveris).
* **`app/Config/Filters.php`**: Definē filtru aliasus (piem., `authFilter` un `cors`) un piesaista `cors` filtru visām API metodēm (GET, POST, PUT, DELETE, OPTIONS).
* **`app/Config/Routes.php`**: Centralizēti definē visus API maršrutus, ieskaitot publiskos, aizsargātos un `OPTIONS` preflight pieprasījumu apstrādi. `OPTIONS` maršruts izsauc `BaseController::optionsResponse` metodi, kas atbild par CORS galveņu iestatīšanu.
* **`app/Config/Validation.php`**: Reģistrē pielāgotus validācijas noteikumus, piemēram, `App\Validation\UserRules`.
* **`app/Config/Boot/development.php`**: Iestata kļūdu ziņošanu un `CI_DEBUG` uz `true` izstrādes vidē, nodrošinot detalizētu kļūdu informāciju.
* **`app/Config/Boot/production.php`**: Iestata kļūdu ziņošanu uz `0` un `CI_DEBUG` uz `false` produkcijas vidē, lai paslēptu sensitīvu informāciju no publiskas apskates.

## 6. Izmantotās tehnoloģijas

* **CodeIgniter 4**: PHP ietvars tīmekļa aplikāciju izstrādei.
* **PHP**: Programmēšanas valoda.
* **Composer**: PHP atkarību pārvaldnieks.
* **JWT (Firebase/php-jwt)**: JSON Web Token bibliotēka drošai autentifikācijai un autorizācijai.
* **MySQL/MariaDB**: Racionālā datubāze datu glabāšanai.

## 7. Izvietošana

Backend ir standarta PHP aplikācija, ko var izvietot jebkurā PHP atbalstošā tīmekļa serverī (piem., Apache, Nginx).
1.  **Servera konfigurācija**: Nodrošiniet, ka serveris ir konfigurēts, lai kalpotu CodeIgniter 4 aplikāciju (parasti norādot dokumenta sakni uz `public` mapi).
2.  **Atkarību instalēšana**: Izmantojiet Composer, lai instalētu visas PHP atkarības: `composer install`.
3.  **`.env` fails**: Izveidojiet `.env` failu projekta saknes direktorijā un konfigurējiet datubāzes savienojuma datus un JWT slepeno atslēgu:
    ```dotenv
    # .env
    database.default.hostname = localhost
    database.default.database = spanijaskailgliemezis_db
    database.default.username = your_db_user
    database.default.password = your_db_password
    database.default.DBDebug = true

    JWT_SECRET_KEY = your_super_secret_jwt_key # Nomainiet ar spēcīgu, unikālu atslēgu
    ```
4.  **Datubāzes migrācijas**: Palaidiet datubāzes migrācijas, lai izveidotu nepieciešamās tabulas: `php spark migrate`.
5.  **Failu atļaujas**: Nodrošiniet, ka `writable/` mapei ir rakstīšanas atļaujas serverim, lai varētu saglabāt sesijas, kešatmiņu un augšupielādētos failus.
6.  **CORS konfigurācija**: Atjauniniet `app/Config/CORS.php` failā `allowedOrigins`, lai iekļautu jūsu frontend domēnu, ja tas atšķiras no `http://localhost:5173`.