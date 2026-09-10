<?php

/* =========================================================
   TRAVELQAZAQ
   Бір файлдық туристік сайт
   PHP + HTML + CSS + JavaScript
   ========================================================= */


/* =========================================================
   DATABASE
   ========================================================= */

$host = "localhost";
$user = "root";
$password = "";
$database = "travelqazaq";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {
    die("MySQL қатесі: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* =========================================================
   PAGE
   ========================================================= */

$page = $_GET["page"] ?? "home";
$id = intval($_GET["id"] ?? 0);


/* =========================================================
   REGISTER
   ========================================================= */

$registerMessage = "";

if (
    $page === "register" &&
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $passwordUser = $_POST["password"] ?? "";

    if (
        $name === "" ||
        $email === "" ||
        $passwordUser === ""
    ) {

        $registerMessage =
            "Барлық өрістерді толтырыңыз.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $registerMessage =
            "Email дұрыс енгізілмеді.";

    } elseif (
        strlen($passwordUser) < 6
    ) {

        $registerMessage =
            "Құпиясөз кемінде 6 таңба болуы керек.";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->bind_param(
            "s",
            $email
        );

        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $registerMessage =
                "Бұл email бұрын тіркелген.";

        } else {

            $hash = password_hash(
                $passwordUser,
                PASSWORD_DEFAULT
            );

            $insert = $conn->prepare(
                "INSERT INTO users
                (name,email,password)
                VALUES (?,?,?)"
            );

            $insert->bind_param(
                "sss",
                $name,
                $email,
                $hash
            );

            if ($insert->execute()) {

                $registerMessage =
                    "Тіркелу сәтті аяқталды.";

            } else {

                $registerMessage =
                    "Тіркелу кезінде қате пайда болды.";
            }
        }
    }
}


/* =========================================================
   LOGIN
   ========================================================= */

$loginMessage = "";

if (
    $page === "login" &&
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $email = trim($_POST["email"] ?? "");
    $passwordUser = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id,name,password
         FROM users
         WHERE email = ?"
    );

    $stmt->bind_param(
        "s",
        $email
    );

    $stmt->execute();

    $userResult =
        $stmt->get_result()->fetch_assoc();

    if (
        $userResult &&
        password_verify(
            $passwordUser,
            $userResult["password"]
        )
    ) {

        $_SESSION["user_id"] =
            $userResult["id"];

        $_SESSION["user_name"] =
            $userResult["name"];

        header(
            "Location: index.php?page=profile"
        );

        exit;

    } else {

        $loginMessage =
            "Email немесе құпиясөз қате.";
    }
}


/* =========================================================
   LOGOUT
   ========================================================= */

if ($page === "logout") {

    session_destroy();

    header(
        "Location: index.php"
    );

    exit;
}


/* =========================================================
   CONTACT
   ========================================================= */

$contactMessage = "";

if (
    $page === "contact" &&
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $name =
        trim($_POST["name"] ?? "");

    $email =
        trim($_POST["email"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $message =
        trim($_POST["message"] ?? "");

    if (
        $name === "" ||
        $email === "" ||
        $phone === "" ||
        $message === ""
    ) {

        $contactMessage =
            "Барлық өрістерді толтырыңыз.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $contactMessage =
            "Email форматы дұрыс емес.";

    } else {

        $fullMessage =
            $message .
            "\nТелефон: " .
            $phone;

        $stmt = $conn->prepare(
            "INSERT INTO messages
            (name,email,message)
            VALUES (?,?,?)"
        );

        $stmt->bind_param(
            "sss",
            $name,
            $email,
            $fullMessage
        );

        if ($stmt->execute()) {

            $contactMessage =
                "Хабарлама сәтті жіберілді!";

        } else {

            $contactMessage =
                "Хабарлама жіберу кезінде қате.";
        }
    }
}


/* =========================================================
   BOOKING
   ========================================================= */

$bookingMessage = "";

if (
    $page === "booking" &&
    $_SERVER["REQUEST_METHOD"] === "POST"
) {

    $tourId =
        intval($_POST["tour_id"] ?? 0);

    $name =
        trim($_POST["name"] ?? "");

    $phone =
        trim($_POST["phone"] ?? "");

    $people =
        intval($_POST["people"] ?? 0);

    $date =
        $_POST["booking_date"] ?? "";

    if (
        $tourId <= 0 ||
        $name === "" ||
        $phone === "" ||
        $people <= 0 ||
        $date === ""
    ) {

        $bookingMessage =
            "Барлық өрістерді толтырыңыз.";

    } else {

        $stmt = $conn->prepare(
            "INSERT INTO bookings
            (tour_id,name,phone,people,booking_date)
            VALUES (?,?,?,?,?)"
        );

        $stmt->bind_param(
            "issis",
            $tourId,
            $name,
            $phone,
            $people,
            $date
        );

        if ($stmt->execute()) {

            $bookingMessage =
                "Брондау сәтті қабылданды!";

        } else {

            $bookingMessage =
                "Брондау кезінде қате пайда болды.";
        }
    }
}


/* =========================================================
   SEARCH
   ========================================================= */

$search = trim(
    $_GET["q"] ?? ""
);

if ($search !== "") {

    $like =
        "%" . $search . "%";

    $stmt = $conn->prepare(
        "SELECT *
         FROM tours
         WHERE name LIKE ?
         OR country LIKE ?
         OR description LIKE ?
         ORDER BY id DESC"
    );

    $stmt->bind_param(
        "sss",
        $like,
        $like,
        $like
    );

    $stmt->execute();

    $tours =
        $stmt->get_result();

} else {

    $tours =
        $conn->query(
            "SELECT *
             FROM tours
             ORDER BY id DESC"
        );
}


/* =========================================================
   CURRENT TOUR
   ========================================================= */

$currentTour = null;

if (
    $page === "tour" &&
    $id > 0
) {

    $stmt = $conn->prepare(
        "SELECT *
         FROM tours
         WHERE id = ?"
    );

    $stmt->bind_param(
        "i",
        $id
    );

    $stmt->execute();

    $currentTour =
        $stmt->get_result()
        ->fetch_assoc();
}

?>
<!DOCTYPE html>

<html lang="kk">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>
TravelQazaq
</title>


<style>

/* =========================================================
   RESET
   ========================================================= */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html {
    scroll-behavior: smooth;
}

body {
    font-family:
        Arial,
        Helvetica,
        sans-serif;

    background:
        #f5f8f6;

    color:
        #17231c;

    line-height:
        1.6;
}

a {
    text-decoration: none;
    color: inherit;
}

button,
input,
textarea,
select {
    font-family: inherit;
}

img {
    max-width: 100%;
    display: block;
}


/* =========================================================
   CONTAINER
   ========================================================= */

.container {

    width: 92%;

    max-width: 1200px;

    margin:
        0 auto;
}


/* =========================================================
   HEADER
   ========================================================= */

header {

    position: sticky;

    top: 0;

    z-index: 1000;

    background:
        rgba(255,255,255,0.97);

    border-bottom:
        1px solid #e1e9e3;

    box-shadow:
        0 4px 20px rgba(0,0,0,0.04);
}

.navbar {

    min-height: 78px;

    display: flex;

    align-items: center;

    gap: 20px;
}

.logo {

    font-size: 28px;

    font-weight: 800;

    color:
        #143b27;

    white-space: nowrap;
}

.logo span {

    color:
        #2da563;
}

.nav-links {

    display: flex;

    align-items: center;

    gap: 5px;

    margin-left: auto;
}

.nav-links a {

    padding:
        10px 13px;

    border-radius:
        10px;

    color:
        #45564b;

    font-weight:
        600;

    transition:
        0.25s;
}

.nav-links a:hover {

    color:
        #238b54;

    background:
        #eaf7ee;

    transform:
        translateY(-2px);
}

.menu-button {

    display: none;

    border: none;

    background:
        #eaf7ee;

    padding:
        10px 13px;

    border-radius:
        10px;

    font-size:
        20px;

    cursor: pointer;
}


/* =========================================================
   SEARCH
   ========================================================= */

.header-search {

    display:
        flex;

    border:
        1px solid #dce6df;

    border-radius:
        12px;

    overflow:
        hidden;
}

.header-search input {

    width:
        150px;

    border:
        none;

    outline:
        none;

    padding:
        9px 10px;
}

.header-search button {

    border:
        none;

    background:
        #e9f7ee;

    padding:
        0 12px;

    cursor:
        pointer;
}


/* =========================================================
   HERO
   ========================================================= */

.hero {

    min-height:
        700px;

    position:
        relative;

    background-image:
        url(
        "https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1800&q=85"
        );

    background-size:
        cover;

    background-position:
        center;
}

.hero::before {

    content:
        "";

    position:
        absolute;

    inset:
        0;

    background:
        linear-gradient(
            90deg,
            rgba(3,20,10,0.82),
            rgba(3,20,10,0.35)
        );
}

.hero-content {

    position:
        relative;

    z-index:
        2;

    padding-top:
        180px;

    max-width:
        800px;

    color:
        white;
}

.hero-label {

    display:
        inline-block;

    background:
        rgba(255,255,255,0.12);

    border:
        1px solid rgba(255,255,255,0.3);

    padding:
        8px 13px;

    border-radius:
        30px;

    letter-spacing:
        2px;

    font-size:
        12px;
}

.hero h1 {

    font-size:
        clamp(42px, 6vw, 76px);

    line-height:
        1.05;

    margin:
        22px 0;
}

.hero h1 span {

    color:
        #69df99;
}

.hero p {

    font-size:
        19px;

    max-width:
        700px;

    color:
        #e9f3ed;
}

.hero-buttons {

    display:
        flex;

    gap:
        12px;

    margin-top:
        30px;
}


/* =========================================================
   BUTTONS
   ========================================================= */

.btn {

    display:
        inline-flex;

    align-items:
        center;

    justify-content:
        center;

    padding:
        13px 21px;

    border-radius:
        11px;

    font-weight:
        700;

    cursor:
        pointer;

    border:
        1px solid transparent;

    transition:
        0.25s;
}

.btn:hover {

    transform:
        translateY(-3px);

    box-shadow:
        0 12px 25px rgba(20,60,40,0.16);
}

.btn-green {

    background:
        #26965b;

    color:
        white;
}

.btn-white {

    background:
        white;

    color:
        #1b5134;
}

.btn-outline {

    border:
        1px solid #26965b;

    color:
        #26965b;

    background:
        white;
}

.btn-small {

    padding:
        9px 13px;

    font-size:
        13px;
}


/* =========================================================
   SECTION
   ========================================================= */

.section {

    padding:
        90px 0;
}

.section-gray {

    background:
        #edf5ef;
}

.section-dark {

    background:
        #10271a;

    color:
        white;
}

.section-title {

    text-align:
        center;

    margin-bottom:
        45px;
}

.section-title small {

    color:
        #29945a;

    font-weight:
        800;

    letter-spacing:
        2px;
}

.section-title h2 {

    font-size:
        40px;

    margin:
        8px 0;
}

.section-title p {

    color:
        #68766f;
}


/* =========================================================
   ABOUT
   ========================================================= */

.about-grid {

    display:
        grid;

    grid-template-columns:
        1.3fr 1fr;

    gap:
        50px;

    align-items:
        center;
}

.about-text p {

    font-size:
        17px;

    color:
        #56675e;

    margin-bottom:
        18px;
}

.stats {

    display:
        grid;

    grid-template-columns:
        repeat(3,1fr);

    gap:
        15px;
}

.stat {

    background:
        white;

    border:
        1px solid #dfe9e2;

    padding:
        25px 15px;

    border-radius:
        17px;

    text-align:
        center;

    transition:
        0.25s;
}

.stat:hover {

    transform:
        translateY(-5px);
}

.stat strong {

    display:
        block;

    font-size:
        30px;

    color:
        #26965b;
}

.stat span {

    color:
        #68776f;
}


/* =========================================================
   CARDS
   ========================================================= */

.cards {

    display:
        grid;

    gap:
        22px;
}

.cards-4 {

    grid-template-columns:
        repeat(4,1fr);
}

.cards-3 {

    grid-template-columns:
        repeat(3,1fr);
}


/* =========================================================
   SERVICE
   ========================================================= */

.service-card {

    background:
        white;

    border:
        1px solid #e0e9e3;

    border-radius:
        20px;

    padding:
        28px;

    transition:
        0.3s;

    box-shadow:
        0 8px 30px rgba(20,60,40,0.05);
}

.service-card:hover {

    transform:
        translateY(-8px);

    box-shadow:
        0 20px 45px rgba(20,60,40,0.13);
}

.service-icon {

    font-size:
        38px;
}

.service-card h3 {

    margin:
        13px 0 7px;
}

.service-card p {

    color:
        #66756d;
}

.service-card a {

    display:
        inline-block;

    color:
        #24915a;

    font-weight:
        700;

    margin-top:
        15px;
}


/* =========================================================
   TOUR CARD
   ========================================================= */

.tour-card {

    overflow:
        hidden;

    background:
        white;

    border:
        1px solid #e0e9e3;

    border-radius:
        20px;

    transition:
        0.3s;

    box-shadow:
        0 8px 30px rgba(20,60,40,0.05);
}

.tour-card:hover {

    transform:
        translateY(-7px);

    box-shadow:
        0 20px 45px rgba(20,60,40,0.14);
}

.tour-card img {

    width:
        100%;

    height:
        230px;

    object-fit:
        cover;
}

.tour-body {

    padding:
        20px;
}

.tour-country {

    color:
        #29945a;

    font-size:
        13px;

    font-weight:
        800;
}

.tour-body h3 {

    font-size:
        22px;

    margin:
        5px 0;
}

.tour-body p {

    color:
        #66756d;

    min-height:
        75px;
}

.tour-bottom {

    display:
        flex;

    align-items:
        center;

    justify-content:
        space-between;

    gap:
        10px;

    margin-top:
        15px;
}

.tour-price {

    font-size:
        19px;

    font-weight:
        800;

    color:
        #208951;
}


/* =========================================================
   PAGE TITLE
   ========================================================= */

.page-title {

    padding:
        85px 0;

    background:
        linear-gradient(
            135deg,
            #e4f5e9,
            #ffffff
        );
}

.page-title h1 {

    font-size:
        50px;

    margin:
        5px 0;
}

.page-title p {

    color:
        #63736a;
}


/* =========================================================
   FORMS
   ========================================================= */

.form-box {

    max-width:
        650px;

    margin:
        auto;

    background:
        white;

    border:
        1px solid #dfe9e2;

    border-radius:
        20px;

    padding:
        35px;

    box-shadow:
        0 15px 40px rgba(20,60,40,0.08);
}

.form-group {

    margin-bottom:
        18px;
}

.form-group label {

    display:
        block;

    font-weight:
        700;

    margin-bottom:
        7px;
}

.form-group input,
.form-group textarea,
.form-group select {

    width:
        100%;

    padding:
        13px;

    border:
        1px solid #d8e4dc;

    border-radius:
        10px;

    outline:
        none;

    background:
        white;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {

    border-color:
        #26965b;

    box-shadow:
        0 0 0 3px
        rgba(38,150,91,0.1);
}

.alert {

    padding:
        14px;

    margin-bottom:
        20px;

    border-radius:
        10px;

    background:
        #e7f7ec;

    color:
        #197343;
}


/* =========================================================
   DETAIL
   ========================================================= */

.detail {

    display:
        grid;

    grid-template-columns:
        1.1fr .9fr;

    gap:
        55px;

    align-items:
        center;
}

.detail-image {

    width:
        100%;

    height:
        520px;

    object-fit:
        cover;

    border-radius:
        25px;
}

.detail h1 {

    font-size:
        50px;

    line-height:
        1.1;

    margin:
        15px 0;
}

.detail-description {

    color:
        #607068;

    font-size:
        18px;
}

.detail-price {

    font-size:
        34px;

    font-weight:
        800;

    color:
        #218d56;

    margin:
        22px 0;
}


/* =========================================================
   CONTACT
   ========================================================= */

.contact-grid {

    display:
        grid;

    grid-template-columns:
        .8fr 1.2fr;

    gap:
        40px;
}

.contact-info {

    padding:
        30px;

    background:
        #12301f;

    color:
        white;

    border-radius:
        20px;
}

.contact-info h2 {

    margin-bottom:
        20px;
}

.contact-info p {

    margin:
        15px 0;

    color:
        #d5e4da;
}


/* =========================================================
   FAQ
   ========================================================= */

.faq {

    max-width:
        800px;

    margin:
        12px auto;

    background:
        white;

    border:
        1px solid #dfe8e2;

    border-radius:
        12px;

    overflow:
        hidden;
}

.faq-question {

    width:
        100%;

    display:
        flex;

    justify-content:
        space-between;

    padding:
        18px;

    background:
        white;

    border:
        none;

    cursor:
        pointer;

    font-weight:
        700;

    text-align:
        left;
}

.faq-answer {

    display:
        none;

    padding:
        0 18px 18px;

    color:
        #68776f;
}

.faq.active
.faq-answer {

    display:
        block;
}


/* =========================================================
   FEATURES
   ========================================================= */

.features {

    display:
        grid;

    grid-template-columns:
        repeat(4,1fr);

    gap:
        15px;
}

.feature {

    border:
        1px solid
        rgba(255,255,255,.15);

    padding:
        22px;

    border-radius:
        15px;

    background:
        rgba(255,255,255,.04);
}


/* =========================================================
   CTA
   ========================================================= */

.cta {

    padding:
        80px 0;

    text-align:
        center;

    background:
        linear-gradient(
            135deg,
            #dff4e6,
            #ffffff
        );
}

.cta h2 {

    font-size:
        40px;

    margin-bottom:
        10px;
}

.cta p {

    color:
        #63736a;

    margin-bottom:
        25px;
}


/* =========================================================
   FOOTER
   ========================================================= */

.footer {

    background:
        #10251a;

    color:
        #dce8e0;

    padding-top:
        55px;
}

.footer-grid {

    display:
        grid;

    grid-template-columns:
        1.5fr 1fr 1fr 1fr;

    gap:
        30px;

    padding-bottom:
        45px;
}

.footer h3 {

    color:
        white;

    margin-bottom:
        12px;
}

.footer a {

    display:
        block;

    color:
        #b6c8bd;

    margin:
        7px 0;
}

.footer a:hover {

    color:
        #71df9c;
}

.copyright {

    border-top:
        1px solid
        rgba(255,255,255,.1);

    text-align:
        center;

    padding:
        20px;

    color:
        #9eb1a5;
}


/* =========================================================
   ERROR
   ========================================================= */

.error-page {

    text-align:
        center;

    padding:
        130px 0;
}

.error-page strong {

    font-size:
        130px;

    color:
        #26965b;
}

.error-page h1 {

    font-size:
        40px;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media(max-width:1000px) {

    .cards-4 {

        grid-template-columns:
            repeat(2,1fr);
    }

    .features {

        grid-template-columns:
            repeat(2,1fr);
    }

    .about-grid {

        grid-template-columns:
            1fr;
    }

    .detail {

        grid-template-columns:
            1fr;
    }

    .contact-grid {

        grid-template-columns:
            1fr;
    }

    .footer-grid {

        grid-template-columns:
            repeat(2,1fr);
    }
}


@media(max-width:700px) {

    .navbar {

        position:
            relative;
    }

    .menu-button {

        display:
            block;

        margin-left:
            auto;
    }

    .nav-links {

        display:
            none;

        position:
            absolute;

        top:
            70px;

        left:
            0;

        right:
            0;

        background:
            white;

        padding:
            15px;

        flex-direction:
            column;

        box-shadow:
            0 15px 30px rgba(0,0,0,.1);
    }

    .nav-links.open {

        display:
            flex;
    }

    .header-search {

        display:
            none;
    }

    .cards-3,
    .cards-4 {

        grid-template-columns:
            1fr;
    }

    .stats {

        grid-template-columns:
            1fr;
    }

    .features {

        grid-template-columns:
            1fr;
    }

    .hero {

        min-height:
            620px;
    }

    .hero-content {

        padding-top:
            130px;
    }

    .hero h1 {

        font-size:
            43px;
    }

    .hero p {

        font-size:
            16px;
    }

    .hero-buttons {

        flex-direction:
            column;
    }

    .hero-buttons .btn {

        width:
            100%;
    }

    .section {

        padding:
            60px 0;
    }

    .page-title h1 {

        font-size:
            38px;
    }

    .detail-image {

        height:
            300px;
    }

    .detail h1 {

        font-size:
            38px;
    }

    .footer-grid {

        grid-template-columns:
            1fr;
    }
}


@media(max-width:450px) {

    .container {

        width:
            94%;
    }

    .logo {

        font-size:
            22px;
    }

    .form-box {

        padding:
            22px;
    }

    .tour-card img {

        height:
            200px;
    }

}


/* =========================================================
   ANIMATIONS
   ========================================================= */

@keyframes fadeUp {

    from {

        opacity:
            0;

        transform:
            translateY(30px);
    }

    to {

        opacity:
            1;

        transform:
            translateY(0);
    }
}


@keyframes float {

    0% {

        transform:
            translateY(0);
    }

    50% {

        transform:
            translateY(-10px);
    }

    100% {

        transform:
            translateY(0);
    }
}


.hero-content {

    animation:
        fadeUp .9s ease;
}


.service-card {

    animation:
        fadeUp .7s ease;
}


.tour-card {

    animation:
        fadeUp .7s ease;
}


/* =========================================================
   EXTRA UI
   ========================================================= */

.scroll-top {

    position:
        fixed;

    right:
        20px;

    bottom:
        20px;

    width:
        45px;

    height:
        45px;

    border-radius:
        50%;

    border:
        none;

    background:
        #26965b;

    color:
        white;

    cursor:
        pointer;

    display:
        none;

    z-index:
        999;
}

.scroll-top.show {

    display:
        block;
}

.modal {

    display:
        none;

    position:
        fixed;

    inset:
        0;

    background:
        rgba(0,0,0,.65);

    z-index:
        5000;

    align-items:
        center;

    justify-content:
        center;

    padding:
        20px;
}

.modal.show {

    display:
        flex;
}

.modal-box {

    width:
        min(600px,100%);

    background:
        white;

    border-radius:
        20px;

    padding:
        35px;

    position:
        relative;

    animation:
        fadeUp .3s ease;
}

.modal-close {

    position:
        absolute;

    top:
        15px;

    right:
        18px;

    border:
        none;

    background:
        transparent;

    font-size:
        25px;

    cursor:
        pointer;
}


/* =========================================================
   END CSS
   ========================================================= */

</style>

</head>


<body>


<!-- =======================================================
     HEADER
======================================================= -->

<header>

<div class="container navbar">

<a
    class="logo"
    href="index.php"
>
    Travel<span>Qazaq</span>
</a>


<button
    class="menu-button"
    id="menuButton"
>
    ☰
</button>


<nav
    class="nav-links"
    id="navLinks"
>

<a href="index.php">
    Басты бет
</a>

<a href="index.php?page=about">
    Біз туралы
</a>

<a href="index.php?page=services">
    Қызметтер
</a>

<a href="index.php?page=catalog">
    Каталог
</a>

<a href="index.php?page=contact">
    Байланыс
</a>

<a href="index.php?page=booking">
    Брондау
</a>

<?php if(isset($_SESSION["user_id"])): ?>

<a href="index.php?page=profile">
    Профиль
</a>

<a href="index.php?page=logout">
    Шығу
</a>

<?php else: ?>

<a href="index.php?page=login">
    Кіру
</a>

<?php endif; ?>

</nav>


<form
    class="header-search"
    method="get"
>

<input
    type="hidden"
    name="page"
    value="catalog"
>

<input
    type="text"
    name="q"
    placeholder="Тур іздеу..."
    value="<?=htmlspecialchars($search)?>"
>

<button>
    🔎
</button>

</form>


</div>

</header>


<main>


<?php

/* =========================================================
   HOME
========================================================= */

if ($page === "home"):

?>

<section class="hero">

<div class="container hero-content">

<span class="hero-label">
    TRAVELQAZAQ
</span>

<h1>
    Армандаған саяхатыңды
    <span>бүгін баста</span>
</h1>

<p>
    Қазақстан және әлем бойынша
    қызықты бағыттарды таңдаңыз,
    турларды салыстырыңыз және
    саяхатыңызды жоспарлаңыз.
</p>

<div class="hero-buttons">

<a
    class="btn btn-green"
    href="index.php?page=catalog"
>
    Турларды көру
</a>

<a
    class="btn btn-white"
    href="index.php?page=about"
>
    Біз туралы
</a>

</div>

</div>

</section>


<section class="section">

<div class="container">

<div class="section-title">

<small>ABOUT</small>

<h2>
    TravelQazaq дегеніміз не?
</h2>

<p>
    Саяхат жоспарлауды жеңілдететін
    заманауи туристік веб-жоба.
</p>

</div>


<div class="about-grid">

<div class="about-text">

<p>
    TravelQazaq — пайдаланушыларға
    түрлі туристік бағыттарды қарап,
    салыстырып және брондауға
    мүмкіндік беретін веб-сайт.
</p>

<p>
    Жоба PHP, MySQL, HTML,
    CSS және JavaScript
    технологиялары арқылы жасалды.
</p>

<a
    class="btn btn-green"
    href="index.php?page=about"
>
    Толық ақпарат
</a>

</div>


<div class="stats">

<div class="stat">

<strong>
    50+
</strong>

<span>
    Бағыт
</span>

</div>


<div class="stat">

<strong>
    1200+
</strong>

<span>
    Клиент
</span>

</div>


<div class="stat">

<strong>
    24/7
</strong>

<span>
    Қолдау
</span>

</div>

</div>

</div>

</div>

</section>


<section class="section section-gray">

<div class="container">

<div class="section-title">

<small>SERVICES</small>

<h2>
    Біздің қызметтер
</h2>

</div>


<div class="cards cards-4">


<div class="service-card">

<div class="service-icon">
    ✈️
</div>

<h3>
    Тур таңдау
</h3>

<p>
    Каталогтан өзіңізге қажетті
    туристік бағытты таңдаңыз.
</p>

<a
    href="index.php?page=catalog"
>
    Каталог →
</a>

</div>


<div class="service-card">

<div class="service-icon">
    🧭
</div>

<h3>
    Саяхат кеңесі
</h3>

<p>
    Сапар бағытын таңдауға
    көмектесетін ақпарат.
</p>

<a
    href="index.php?page=services"
>
    Толығырақ →
</a>

</div>


<div class="service-card">

<div class="service-icon">
    📅
</div>

<h3>
    Онлайн брондау
</h3>

<p>
    Таңдалған турды онлайн
    түрде брондаңыз.
</p>

<a
    href="index.php?page=booking"
>
    Брондау →
</a>

</div>


<div class="service-card">

<div class="service-icon">
    💬
</div>

<h3>
    Қолдау
</h3>

<p>
    Сұрақтарыңызды бізге
    хабарлама арқылы жіберіңіз.
</p>

<a
    href="index.php?page=contact"
>
    Байланысу →
</a>

</div>


</div>

</div>

</section>


<section class="section">

<div class="container">

<div class="section-title">

<small>CATALOG</small>

<h2>
    Танымал турлар
</h2>

<p>
    Ең қызықты бағыттарды таңдаңыз.
</p>

</div>


<div class="cards cards-3">

<?php

$homeTours =
    $conn->query(
        "SELECT *
         FROM tours
         ORDER BY id DESC
         LIMIT 6"
    );

while(
    $tour =
    $homeTours->fetch_assoc()
):

?>

<article class="tour-card">

<img
    src="<?=htmlspecialchars($tour["image"])?>"
    alt="<?=htmlspecialchars($tour["name"])?>"
>

<div class="tour-body">

<div class="tour-country">
    <?=htmlspecialchars($tour["country"])?>
</div>

<h3>
    <?=htmlspecialchars($tour["name"])?>
</h3>

<p>
    <?=htmlspecialchars($tour["description"])?>
</p>

<div class="tour-bottom">

<span class="tour-price">
    <?=number_format(
        $tour["price"],
        0,
        ",",
        " "
    )?> ₸
</span>

<a
    class="btn btn-green btn-small"
    href="index.php?page=tour&id=<?=$tour["id"]?>"
>
    Көру
</a>

</div>

</div>

</article>

<?php endwhile; ?>

</div>

</div>

</section>


<section class="section section-dark">

<div class="container">

<div class="section-title">

<small style="color:#71df9c">
    WHY US
</small>

<h2>
    Неге TravelQazaq?
</h2>

</div>


<div class="features">

<div class="feature">
    ✓ Ыңғайлы каталог
</div>

<div class="feature">
    ✓ Жылдам брондау
</div>

<div class="feature">
    ✓ Қарапайым іздеу
</div>

<div class="feature">
    ✓ Responsive дизайн
</div>

</div>

</div>

</section>


<section class="cta">

<div class="container">

<h2>
    Келесі саяхатыңды жоспарла
</h2>

<p>
    Өзіңе ұнайтын бағытты тауып,
    саяхатты бүгін баста.
</p>

<a
    class="btn btn-green"
    href="index.php?page=catalog"
>
    Каталогқа өту
</a>

</div>

</section>


<?php


/* =========================================================
   ABOUT
========================================================= */

elseif ($page === "about"):

?>

<section class="page-title">

<div class="container">

<span>
    ABOUT US
</span>

<h1>
    Біз туралы
</h1>

<p>
    TravelQazaq жобасы туралы толық ақпарат.
</p>

</div>

</section>


<section class="section">

<div class="container about-grid">

<div>

<h2>
    Жобаның мақсаты
</h2>

<p>
    TravelQazaq сайтының негізгі
    мақсаты — саяхатқа қызығатын
    адамдарға туристік бағыттарды
    бір жерден табуға мүмкіндік беру.
</p>

<br>

<p>
    Пайдаланушы каталогты қарайды,
    іздеу жасайды, турдың толық
    ақпаратын көреді және брондау
    формасын толтыра алады.
</p>

<br>

<h3>
    Қолданылған технологиялар
</h3>

<ul style="margin:20px;">

<li>
    HTML5
</li>

<li>
    CSS3
</li>

<li>
    JavaScript
</li>

<li>
    PHP
</li>

<li>
    MySQL
</li>

</ul>

</div>


<div class="service-card">

<div class="service-icon">
    🌍
</div>

<h2>
    TravelQazaq
</h2>

<p>
    Қазақстаннан әлемге саяхат.
</p>

<br>

<p>
    Біз пайдаланушыға қарапайым,
    түсінікті және заманауи
    интерфейс ұсынуға тырысамыз.
</p>

</div>

</div>

</section>


<?php


/* =========================================================
   SERVICES
========================================================= */

elseif ($page === "services"):

?>

<section class="page-title">

<div class="container">

<span>
    SERVICES
</span>

<h1>
    Біздің қызметтер
</h1>

<p>
    Саяхатқа арналған негізгі
    мүмкіндіктер.
</p>

</div>

</section>


<section class="section">

<div class="container">

<div class="cards cards-4">


<div class="service-card">

<div class="service-icon">
    ✈️
</div>

<h2>
    Тур таңдау
</h2>

<p>
    Турлар каталогынан бағыт,
    ел және баға бойынша
    өзіңізге қажетті турды таңдаңыз.
</p>

</div>


<div class="service-card">

<div class="service-icon">
    🧭
</div>

<h2>
    Кеңес беру
</h2>

<p>
    Саяхат бағытын анықтау үшін
    пайдалы ақпаратпен танысыңыз.
</p>

</div>


<div class="service-card">

<div class="service-icon">
    📅
</div>

<h2>
    Брондау
</h2>

<p>
    Турды онлайн брондап,
    саяхат күнін көрсетіңіз.
</p>

</div>


<div class="service-card">

<div class="service-icon">
    💬
</div>

<h2>
    Кері байланыс
</h2>

<p>
    Сұрақтарыңызды жіберіп,
    бізбен байланыста болыңыз.
</p>

</div>


</div>

</div>

</section>


<section class="section section-gray">

<div class="container">

<div class="section-title">

<h2>
    Жиі қойылатын сұрақтар
</h2>

</div>


<div class="faq">

<button
    class="faq-question"
>
    Турды қалай брондаймын?
    <span>+</span>
</button>

<div class="faq-answer">

Каталогтан қажетті турды
таңдап, толық ақпарат бетіне
өтіңіз. Брондау батырмасын
басып, форманы толтырыңыз.

</div>

</div>


<div class="faq">

<button
    class="faq-question"
>
    Турларды қалай іздеймін?
    <span>+</span>
</button>

<div class="faq-answer">

Жоғарғы іздеу жолағына
ел немесе тур атауын
жазыңыз.

</div>

</div>


<div class="faq">

<button
    class="faq-question"
>
    Сайт мобильді құрылғыда жұмыс істей ме?
    <span>+</span>
</button>

<div class="faq-answer">

Иә. Сайт desktop, tablet
және mobile экрандарына
бейімделген.

</div>

</div>

</div>

</section>


<?php


/* =========================================================
   CATALOG
========================================================= */

elseif ($page === "catalog"):

?>

<section class="page-title">

<div class="container">

<span>
    CATALOG
</span>

<h1>
    Турлар каталогы
</h1>

<p>
    Өзіңізге ұнайтын бағытты таңдаңыз.
</p>

</div>

</section>


<section class="section">

<div class="container">


<form
    class="form-box"
    style="
        max-width:100%;
        margin-bottom:40px;
        display:flex;
        gap:10px;
    "
    method="get"
>

<input
    type="hidden"
    name="page"
    value="catalog"
>

<input
    name="q"
    style="
        flex:1;
        padding:14px;
        border:1px solid #dce6df;
        border-radius:10px;
    "
    placeholder="Тур, ел немесе қала іздеу..."
    value="<?=htmlspecialchars($search)?>"
>

<button
    class="btn btn-green"
>
    Іздеу
</button>

</form>


<div class="cards cards-3">

<?php

if (
    $tours &&
    $tours->num_rows > 0
):

while(
    $tour =
    $tours->fetch_assoc()
):

?>

<article class="tour-card">

<img
    src="<?=htmlspecialchars($tour["image"])?>"
    alt="<?=htmlspecialchars($tour["name"])?>"
>

<div class="tour-body">

<div class="tour-country">
    <?=htmlspecialchars($tour["country"])?>
</div>

<h3>
    <?=htmlspecialchars($tour["name"])?>
</h3>

<p>
    <?=htmlspecialchars($tour["description"])?>
</p>

<div class="tour-bottom">

<span class="tour-price">
    <?=number_format(
        $tour["price"],
        0,
        ",",
        " "
    )?> ₸
</span>

<a
    class="btn btn-green btn-small"
    href="index.php?page=tour&id=<?=$tour["id"]?>"
>
    Толық ақпарат
</a>

</div>

</div>

</article>

<?php

endwhile;

else:

?>

<div
    style="
        grid-column:1/-1;
        text-align:center;
        padding:70px;
    "
>

<h2>
    Тур табылмады
</h2>

<p>
    Басқа іздеу сөзін енгізіп көріңіз.
</p>

</div>

<?php endif; ?>

</div>

</div>

</section>


<?php


/* =========================================================
   TOUR DETAIL
========================================================= */

elseif ($page === "tour"):

?>

<?php if ($currentTour): ?>

<section class="section">

<div class="container detail">

<img
    class="detail-image"
    src="<?=htmlspecialchars($currentTour["image"])?>"
    alt="<?=htmlspecialchars($currentTour["name"])?>"
>


<div>

<span
    class="tour-country"
>
    <?=htmlspecialchars(
        $currentTour["country"]
    )?>
</span>

<h1>
    <?=htmlspecialchars(
        $currentTour["name"]
    )?>
</h1>

<p class="detail-description">

<?=htmlspecialchars(
    $currentTour["description"]
)?>

</p>

<div class="detail-price">

<?=number_format(
    $currentTour["price"],
    0,
    ",",
    " "
)?> ₸

</div>

<ul
    style="
        margin:20px;
    "
>

<li>
    Толық тур ақпараты
</li>

<li>
    Онлайн брондау
</li>

<li>
    Кері байланыс
</li>

</ul>

<a
    class="btn btn-green"
    href="index.php?page=booking&tour=<?=$currentTour["id"]?>"
>
    Осы турды брондау
</a>

<a
    class="btn btn-outline"
    href="index.php?page=catalog"
>
    ← Каталог
</a>

</div>

</div>

</section>

<?php else: ?>

<section class="error-page">

<div class="container">

<strong>
    404
</strong>

<h1>
    Тур табылмады
</h1>

<a
    class="btn btn-green"
    href="index.php?page=catalog"
>
    Каталогқа қайту
</a>

</div>

</section>

<?php endif; ?>


<?php


/* =========================================================
   BOOKING
========================================================= */

elseif ($page === "booking"):

$selectedTour =
    intval($_GET["tour"] ?? 0);

?>

<section class="page-title">

<div class="container">

<span>
    BOOKING
</span>

<h1>
    Турды брондау
</h1>

<p>
    Мәліметтерді толтырыңыз.
</p>

</div>

</section>


<section class="section">

<div class="container">

<div class="form-box">

<?php if ($bookingMessage): ?>

<div class="alert">

<?=htmlspecialchars(
    $bookingMessage
)?>

</div>

<?php endif; ?>


<form
    method="post"
    id="bookingForm"
>

<div class="form-group">

<label>
    Тур
</label>

<select
    name="tour_id"
    required
>

<option value="">
    Тур таңдаңыз
</option>

<?php

$bookingTours =
    $conn->query(
        "SELECT id,name
         FROM tours
         ORDER BY name"
    );

while(
    $t =
    $bookingTours->fetch_assoc()
):

?>

<option
    value="<?=$t["id"]?>"
    <?=$selectedTour == $t["id"]
        ? "selected"
        : ""?>
>

<?=htmlspecialchars(
    $t["name"]
)?>

</option>

<?php endwhile; ?>

</select>

</div>


<div class="form-group">

<label>
    Аты-жөні
</label>

<input
    type="text"
    name="name"
    required
    placeholder="Аты-жөніңіз"
>

</div>


<div class="form-group">

<label>
    Телефон
</label>

<input
    type="text"
    name="phone"
    required
    placeholder="+7 700 123 45 67"
>

</div>


<div class="form-group">

<label>
    Адам саны
</label>

<input
    type="number"
    name="people"
    min="1"
    max="20"
    value="1"
    required
>

</div>


<div class="form-group">

<label>
    Саяхат күні
</label>

<input
    type="date"
    name="booking_date"
    required
>

</div>


<button
    class="btn btn-green"
    style="width:100%;"
>
    Брондауды жіберу
</button>

</form>

</div>

</div>

</section>


<?php


/* =========================================================
   CONTACT
========================================================= */

elseif ($page === "contact"):

?>

<section class="page-title">

<div class="container">

<span>
    CONTACT
</span>

<h1>
    Бізбен байланыс
</h1>

<p>
    Сұрақтарыңыз болса,
    бізге жазыңыз.
</p>

</div>

</section>


<section class="section">

<div class="container contact-grid">


<div class="contact-info">

<h2>
    TravelQazaq
</h2>

<p>
    📍 Алматы, Қазақстан
</p>

<p>
    📞 +7 700 123 45 67
</p>

<p>
    ✉ info@travelqazaq.kz
</p>

<p>
    🕘 09:00 — 18:00
</p>

</div>


<div class="form-box">

<?php if ($contactMessage): ?>

<div class="alert">

<?=htmlspecialchars(
    $contactMessage
)?>

</div>

<?php endif; ?>


<form
    method="post"
    id="contactForm"
>

<div class="form-group">

<label>
    Аты-жөні
</label>

<input
    type="text"
    name="name"
    required
>

</div>


<div class="form-group">

<label>
    Email
</label>

<input
    type="email"
    name="email"
    required
>

</div>


<div class="form-group">

<label>
    Телефон
</label>

<input
    type="text"
    name="phone"
    required
>

</div>


<div class="form-group">

<label>
    Хабарлама
</label>

<textarea
    name="message"
    rows="6"
    required
></textarea>

</div>


<button
    class="btn btn-green"
    style="width:100%;"
>
    Жіберу
</button>

</form>

</div>

</div>

</section>


<?php


/* =========================================================
   REGISTER
========================================================= */

elseif ($page === "register"):

?>

<section class="section">

<div class="container">

<div class="form-box">

<h1>
    Тіркелу
</h1>

<p>
    TravelQazaq аккаунтын жасаңыз.
</p>


<?php if ($registerMessage): ?>

<div class="alert">

<?=htmlspecialchars(
    $registerMessage
)?>

</div>

<?php endif; ?>


<form
    method="post"
    id="registerForm"
>

<div class="form-group">

<label>
    Аты-жөні
</label>

<input
    type="text"
    name="name"
    required
>

</div>


<div class="form-group">

<label>
    Email
</label>

<input
    type="email"
    name="email"
    required
>

</div>


<div class="form-group">

<label>
    Құпиясөз
</label>

<input
    type="password"
    name="password"
    minlength="6"
    required
>

</div>


<button
    class="btn btn-green"
    style="width:100%;"
>
    Тіркелу
</button>

</form>


<p style="margin-top:20px;">

Аккаунтыңыз бар ма?

<a
    href="index.php?page=login"
    style="color:#26965b;font-weight:bold;"
>
    Кіру
</a>

</p>

</div>

</div>

</section>


<?php


/* =========================================================
   LOGIN
========================================================= */

elseif ($page === "login"):

?>

<section class="section">

<div class="container">

<div class="form-box">

<h1>
    Жүйеге кіру
</h1>

<p>
    TravelQazaq аккаунтына кіріңіз.
</p>


<?php if ($loginMessage): ?>

<div class="alert">

<?=htmlspecialchars(
    $loginMessage
)?>

</div>

<?php endif; ?>


<form
    method="post"
>

<div class="form-group">

<label>
    Email
</label>

<input
    type="email"
    name="email"
    required
>

</div>


<div class="form-group">

<label>
    Құпиясөз
</label>

<input
    type="password"
    name="password"
    required
>

</div>


<button
    class="btn btn-green"
    style="width:100%;"
>
    Кіру
</button>

</form>


<p style="margin-top:20px;">

Аккаунт жоқ па?

<a
    href="index.php?page=register"
    style="color:#26965b;font-weight:bold;"
>
    Тіркелу
</a>

</p>

</div>

</div>

</section>


<?php


/* =========================================================
   PROFILE
========================================================= */

elseif ($page === "profile"):

?>

<section class="section">

<div class="container">

<?php if (!isset($_SESSION["user_id"])): ?>

<div class="form-box">

<h1>
    Профиль
</h1>

<p>
    Профильді көру үшін
    жүйеге кіріңіз.
</p>

<a
    class="btn btn-green"
    href="index.php?page=login"
>
    Кіру
</a>

</div>

<?php else: ?>

<?php

$userId =
    intval($_SESSION["user_id"]);

$stmt =
    $conn->prepare(
        "SELECT *
         FROM users
         WHERE id = ?"
    );

$stmt->bind_param(
    "i",
    $userId
);

$stmt->execute();

$profile =
    $stmt->get_result()
    ->fetch_assoc();

?>

<div class="form-box">

<h1>
    Жеке кабинет
</h1>

<h2>
    <?=htmlspecialchars(
        $profile["name"]
    )?>
</h2>

<p>
    Email:
    <?=htmlspecialchars(
        $profile["email"]
    )?>
</p>

<br>

<a
    class="btn btn-green"
    href="index.php?page=bookings"
>
    Брондау тарихы
</a>

<a
    class="btn btn-outline"
    href="index.php?page=logout"
>
    Шығу
</a>

</div>

<?php endif; ?>

</div>

</section>


<?php


/* =========================================================
   BOOKINGS
========================================================= */

elseif ($page === "bookings"):

?>

<section class="section">

<div class="container">

<h1>
    Брондау тарихы
</h1>

<br>


<div
    style="
        overflow-x:auto;
        background:white;
        border-radius:15px;
    "
>

<table
    style="
        width:100%;
        border-collapse:collapse;
    "
>

<tr
    style="
        background:#eaf7ee;
    "
>

<th style="padding:14px;">
    ID
</th>

<th style="padding:14px;">
    Тур
</th>

<th style="padding:14px;">
    Аты
</th>

<th style="padding:14px;">
    Телефон
</th>

<th style="padding:14px;">
    Адам
</th>

<th style="padding:14px;">
    Күн
</th>

</tr>


<?php

$bookingList =
    $conn->query(
        "SELECT
            b.*,
            t.name AS tour_name
         FROM bookings b
         LEFT JOIN tours t
         ON b.tour_id=t.id
         ORDER BY b.id DESC"
    );

while(
    $b =
    $bookingList->fetch_assoc()
):

?>

<tr>

<td style="padding:14px;">
    <?=$b["id"]?>
</td>

<td style="padding:14px;">
    <?=htmlspecialchars(
        $b["tour_name"] ?? "—"
    )?>
</td>

<td style="padding:14px;">
    <?=htmlspecialchars(
        $b["name"]
    )?>
</td>

<td style="padding:14px;">
    <?=htmlspecialchars(
        $b["phone"]
    )?>
</td>

<td style="padding:14px;">
    <?=$b["people"]?>
</td>

<td style="padding:14px;">
    <?=$b["booking_date"]?>
</td>

</tr>

<?php endwhile; ?>

</table>

</div>

</div>

</section>


<?php


/* =========================================================
   404
========================================================= */

else:

?>

<section class="error-page">

<div class="container">

<strong>
    404
</strong>

<h1>
    Page Not Found
</h1>

<p>
    Сіз іздеген бет табылмады.
</p>

<br>

<a
    class="btn btn-green"
    href="index.php"
>
    Басты бетке қайту
</a>

</div>

</section>

<?php endif; ?>


</main>


<!-- =======================================================
     FOOTER
======================================================= -->

<footer class="footer">

<div class="container footer-grid">


<div>

<h3>
    TravelQazaq
</h3>

<p>
    Қазақстаннан әлемге
    қауіпсіз әрі қызықты саяхат.
</p>

</div>


<div>

<h3>
    Бөлімдер
</h3>

<a href="index.php">
    Басты бет
</a>

<a href="index.php?page=about">
    Біз туралы
</a>

<a href="index.php?page=services">
    Қызметтер
</a>

<a href="index.php?page=catalog">
    Каталог
</a>

<a href="index.php?page=contact">
    Байланыс
</a>

</div>


<div>

<h3>
    Байланыс
</h3>

<p>
    +7 700 123 45 67
</p>

<p>
    info@travelqazaq.kz
</p>

<p>
    Алматы, Қазақстан
</p>

</div>


<div>

<h3>
    Әлеуметтік желілер
</h3>

<a href="#">
    Instagram
</a>

<a href="#">
    Telegram
</a>

<a href="#">
    TikTok
</a>

</div>


</div>


<div class="copyright">

© 2026 TravelQazaq.
Барлық құқықтар қорғалған.

</div>

</footer>


<!-- =======================================================
     JAVASCRIPT
======================================================= -->

<script>


/* =========================================================
   MOBILE MENU
========================================================= */

const menuButton =
    document.getElementById(
        "menuButton"
    );

const navLinks =
    document.getElementById(
        "navLinks"
    );


if (menuButton) {

    menuButton.addEventListener(
        "click",
        function() {

            navLinks.classList.toggle(
                "open"
            );

        }
    );

}


/* =========================================================
   FAQ
========================================================= */

const faqQuestions =
    document.querySelectorAll(
        ".faq-question"
    );


faqQuestions.forEach(
    function(question) {

        question.addEventListener(
            "click",
            function() {

                const faq =
                    question.parentElement;

                faq.classList.toggle(
                    "active"
                );

                const icon =
                    question.querySelector(
                        "span"
                    );

                if (
                    faq.classList.contains(
                        "active"
                    )
                ) {

                    icon.textContent =
                        "−";

                } else {

                    icon.textContent =
                        "+";
                }

            }
        );

    }
);


/* =========================================================
   FORM VALIDATION
========================================================= */

const forms =
    document.querySelectorAll(
        "form"
    );


forms.forEach(
    function(form) {

        form.addEventListener(
            "submit",
            function(event) {

                const fields =
                    form.querySelectorAll(
                        "input, textarea, select"
                    );

                let valid = true;


                fields.forEach(
                    function(field) {

                        field.style.borderColor =
                            "#d8e4dc";


                        if (
                            field.hasAttribute(
                                "required"
                            ) &&
                            field.value.trim() === ""
                        ) {

                            valid = false;

                            field.style.borderColor =
                                "#d44747";
                        }


                        if (
                            field.type === "email" &&
                            field.value !== ""
                        ) {

                            const emailPattern =
                                /^[^\s@]+@[^\s@]+\.[^\s@]+$/;


                            if (
                                !emailPattern.test(
                                    field.value
                                )
                            ) {

                                valid = false;

                                field.style.borderColor =
                                    "#d44747";
                            }

                        }

                    }
                );


                if (!valid) {

                    event.preventDefault();

                    alert(
                        "Өтінемін, барлық өрістерді дұрыс толтырыңыз."
                    );

                }

            }
        );

    }
);


/* =========================================================
   SCROLL ANIMATION
========================================================= */

const animatedItems =
    document.querySelectorAll(
        ".service-card, .tour-card, .stat"
    );


const observer =
    new IntersectionObserver(
        function(entries) {

            entries.forEach(
                function(entry) {

                    if (
                        entry.isIntersecting
                    ) {

                        entry.target.style.opacity =
                            "1";

                        entry.target.style.transform =
                            "translateY(0)";
                    }

                }
            );

        },
        {
            threshold: 0.15
        }
    );


animatedItems.forEach(
    function(item) {

        item.style.opacity =
            "0";

        item.style.transform =
            "translateY(25px)";

        item.style.transition =
            "0.6s ease";

        observer.observe(
            item
        );

    }
);


/* =========================================================
   PHONE MASK
========================================================= */

const phoneInputs =
    document.querySelectorAll(
        'input[name="phone"]'
    );


phoneInputs.forEach(
    function(input) {

        input.addEventListener(
            "input",
            function() {

                let value =
                    input.value.replace(
                        /[^0-9+]/g,
                        ""
                    );

                if (
                    value.length > 16
                ) {

                    value =
                        value.substring(
                            0,
                            16
                        );
                }

                input.value =
                    value;

            }
        );

    }
);


/* =========================================================
   DATE MINIMUM
========================================================= */

const dateInputs =
    document.querySelectorAll(
        'input[type="date"]'
    );


const today =
    new Date()
        .toISOString()
        .split("T")[0];


dateInputs.forEach(
    function(input) {

        input.min =
            today;

    }
);


/* =========================================================
   CLOSE NAV AFTER CLICK
========================================================= */

document
    .querySelectorAll(
        "#navLinks a"
    )
    .forEach(
        function(link) {

            link.addEventListener(
                "click",
                function() {

                    if (navLinks) {

                        navLinks.classList.remove(
                            "open"
                        );

                    }

                }
            );

        }
    );


/* =========================================================
   CONSOLE TEST
========================================================= */

console.log(
    "TravelQazaq JavaScript іске қосылды."
);

console.log(
    "PHP + MySQL + HTML + CSS + JS"
);

console.log(
    "Responsive interface дайын."
);

</script>


</body>

</html>