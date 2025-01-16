<?php
$faqs = [
    ["question" => "Cum mă pot loga în aplicație?", "answer" => "Pentru a te loga, accesează pagina de login, introdu username-ul și parola ta folosite la inregistrare. Apasă pe butonul „Login” pentru a începe să folosești aplicația Wavey."],
    ["question" => "Este aplicația disponibilă offline?", "answer" => "Wavey necesită o conexiune la internet pentru a trimite și primi mesaje în timp real. În cazul în care pierzi conexiunea la internet, aplicația va încerca să se reconecteze automat. Dacă ești offline pentru o perioadă mai lungă, mesajele primite vor fi stocate și livrate imediat ce conexiunea este restabilită."],
    ["question" => "Cum mă pot deconecta din aplicație?", "answer" => "Pentru a te deconecta, apasă pe butonul „Logout” din meniul de sus din partea dreaptă a aplicației. Aceasta te va scoate din sesiune și te va redirecționa către pagina de login."],
    ["question" => "Cum pot începe o conversație cu un agent de suport?", "answer" => "Pentru a începe o conversație, pur și simplu apasă pe butonul „Contact Us” din aplicație și descrie cât mai bine problema."],
    ["question" => "Ce fac dacă vreau să schimb limba aplicației?", "answer" => "Din păcate aplicația Wavey nu permite schimbarea limbii în acest moment."],
    ["question" => "Cum îmi pot actualiza informațiile contului?", "answer" => "Poți actualiza informațiile contului tău accesând secțiunea „Profile” din aplicație și modificând detaliile dorite."],
    ["question" => "Cum îmi pot schimba parola?", "answer" => "Pentru a schimba parola, accesează secțiunea „Profile” din aplicație și apasă pe opțiunea „Change Password”. Urmează pașii pentru a introduce parola actuală și noua parolă."],
    ["question" => "Ce fac dacă nu pot trimite mesaje?", "answer" => "Dacă întâmpini dificultăți la trimiterea mesajelor, verifică dacă ai o conexiune stabilă la internet. Dacă problema persistă, încearcă să te reconectezi pe cont. Dacă problema nu se rezolvă, contactează echipa de suport."],
    ["question" => "Pot să creez un grup?", "answer" => "Da, poți crea un chat de grup. Accesează pagina principală și apasă pe opțiunea „Create Group” și urmează pașii pentru a crea grupul."],
    ["question" => "Este aplicația disponibilă pe toate dispozitivele?", "answer" => "Din păcate aplicația noastră este disponibilă doar pe Desktop momentan. Asigură-te că ai ultima versiune instalată pentru o experiență optimă."]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        h1 {
            text-align: center;
        }

        .faq-container {
            max-width: 800px;
            margin: 0 auto;
        }
 
        .faq-button {
            background-color: #f1f1f1;
            color: #333;
            cursor: pointer;
            padding: 18px;
            width: 100%;
            text-align: left;
            border: none;
            outline: none;
            font-size: 15px;
            transition: background-color 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-button:hover {
            background-color: #ddd;
        }

        .arrow {
            transition: transform 0.3s ease;
        }

        .faq-content {
            padding: 0 18px;
            display: none;
            overflow: hidden;
            background-color: #f9f9f9;
        }

        .faq-content p {
            padding: 15px 0;
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />
</head>
<body>

    <h1>Frequently Asked Questions (FAQ)</h1>

    <div class="faq-container">
        <?php foreach ($faqs as $faq): ?>
            <button class="faq-button">
                <?php echo $faq['question']; ?>
                <i class='fas fa-angle-down arrow'></i>
            </button>
            <div class="faq-content">
                <p><?php echo $faq['answer']; ?></p>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        const faqButtons = document.querySelectorAll('.faq-button');

        faqButtons.forEach(button => {
            button.addEventListener('click', () => {
                const content = button.nextElementSibling;
                const arrow = button.querySelector('.arrow');

                content.style.display = (content.style.display === 'block') ? 'none' : 'block';

                if (content.style.display === 'block') {
                    arrow.style.transform = 'rotate(180deg)';
                } else {
                    arrow.style.transform = 'rotate(0deg)';
                }

                faqButtons.forEach(otherButton => {
                    if (otherButton !== button) {
                        otherButton.nextElementSibling.style.display = 'none';
                        otherButton.querySelector('.arrow').style.transform = 'rotate(0deg)';
                    }
                });
            });
        });

    </script>

</body>
</html>