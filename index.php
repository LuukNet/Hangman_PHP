<?php

session_start();

define(
    'ALPHABET', 
    ["A", "B", "C", "D", "E", "F", "G", "H" , "I" , "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"]
);

define(
    'WORDS',
    ["ABYSS", "ANARCHISM","AVENUE", "BAGPIPES", "BANJO", "BLIZZARD", "CARTOON", "COBWEB", "CRYPTOGRAM", "DANGEROUS", "DIRECTORY", "DWARVES",
    "EMPLOYED", "ENVIRONMENT", "EPIDEMIC", "ESPIONAGE", "EXODUS", "EXTREME", "FACTORY", "FISHHOOK", "FOREIGN", "GALLON", "GENEROUS", "GROWTH",
    "HAIKU", "HAPPINESS", "HYPHEN", "IDENTICAL", "IVORY", "INVESTIGATE", "JACKPOT", "JELLY", "JUKEBOX", "KALEIDOSCOPE", "KAYAK", "KILOBYTE", 
    "LEGENDARY", "LOYAL", "LUCRATIVE", "MEGAHERTZ", "MICROWAVE", "MONOLITH", "NARROW", "NEGOTIATION", "NEIGHBOUR", "OMNIPOTENT", "OUTLANDISH", "OXYGEN",
    "PACIFIC", "PIXEL", "PNEUMONIA", "QUARTZ", "QUEUE", "QUICK", "RETRO", "RHYTHM", "ROAM", "SHARK", "STRONGHOLD", "SUBSTANCE", 
    "THUMBSCREW", "TORNADO", "TRANSPARENT", "ULTRA", "UNWORTHY", "UPTOWN", "VACANT", "VODKA", "VOLCANO", "WACKO", "WHISKEY", "WITHER",
    "XENON", "XIPHOID", "XYLOPHONE", "YACHT", "YETI", "YOGURT", "ZINC", "ZEBRA", "ZODIAC"]
);

$gameHTML = file_get_contents("game.html");
$choiceHTML = file_get_contents("Html/choice.html");
$inputHTML = file_get_contents("Html/input.html");
$sectionHTML = file_get_contents("Html/section.html");
$formHTML = file_get_contents("Html/form.html");
$endHTML = file_get_contents("Html/end.html");

function randomizeWord() {
    $num = rand(0,100);

    if ($num < 81) {
        $randomWord = WORDS[$num];
    } else {
        $randomWord = file_get_contents("https://random-word-api.vercel.app/api?words=1&type=uppercase");
        $randomWord = trim($randomWord,'["]');
    }

    return $randomWord;
}

function generateSessions($stringL) {
    $_SESSION['correct'] = '';
    $_SESSION['wrong'] = '';
    $_SESSION['state'] = 1;
    $_SESSION['lives'] = 6;

    for ($i = 0; $i < $stringL; $i++) { 
        $_SESSION['correct'] .= ' _';
    }
}

function checkCorrectCharacters($stringL, $char) {
    $no = 0;

    for ($i = 0; $i < $stringL; $i++) {
        if ($char == $_SESSION['theWord'][$i]) {
            $_SESSION['correct'] = substr_replace($_SESSION['correct'], $char, $i + $i + 1, 1);
        } else {
            $no++;
        }
    }

    return $no;
}

function addWrongCharacters($char) {
    $_SESSION['state']++;
    $_SESSION['lives']--;
    $_SESSION["$char"] = 'wrong';

    if ($_SESSION['wrong'] == '') {
        $_SESSION['wrong'] .= "$char";
    } else {
        $_SESSION['wrong'] .= ", $char";
    }
}

function addAlphabetInput($alphabet) {
    $charactersHTML = '';

    foreach ($alphabet as $char) {
        if (isset($_SESSION["$char"])) {
            if ($_SESSION["$char"] == 'correct') {
                $characterHTML = file_get_contents("Html/character.html");
                $characterHTML = str_replace('{disabled}', 'class="correct" disabled', $characterHTML);
                $charactersHTML .= str_replace('{character}', $char, $characterHTML);
            } else {
                $characterHTML = file_get_contents("Html/character.html");
                $characterHTML = str_replace('{disabled}', 'class="wrong" disabled', $characterHTML);
                $charactersHTML .= str_replace('{character}', $char, $characterHTML);
            }
        } else {
            $characterHTML = file_get_contents("Html/character.html");
            $characterHTML = str_replace('{disabled}', 'class="character"', $characterHTML);
            $charactersHTML .= str_replace('{character}', $char, $characterHTML);
        }
    }

    return $charactersHTML;
}

function checkAllGuessed() {
    $end = false;

    if (!str_contains($_SESSION['correct'], '_')) {
        $end = true;
    }
    
    return $end;
}

function addSpaces($stringL) {
    $word = ' ' . $_SESSION['theWord'];

    for ($i = 0; $i < $stringL - 1; $i++) { 
        $j = $i * 2 + 2;
        $word = substr($word, 0, $j) . ' ' . substr($word, $j);
    }

    return $word;
}

if (isset($_POST['retry'])) {
    session_unset();
}

// If The Game Type Isn't Chosen Yet

if (!isset($_POST['wordType']) && !isset($_SESSION['wordType'])) {
    $gameHTML = str_replace('{main}', $choiceHTML, $gameHTML);
        
    echo $gameHTML;
} elseif (isset($_POST['wordType']) && !isset($_SESSION['wordType'])) {
    $_SESSION['wordType'] = $_POST['wordType'];
}

// If The Game Type Has Been Chosen

if (isset($_SESSION['wordType'])) {
    // But The Word Doesn't Exist Yet

    //setWord
    if (!isset($_SESSION['theWord']) && !isset($_POST['ownWord'])) {
        // If The Game Type Is Own Word, Or Random Word

        if ($_SESSION['wordType'] == 'Own Word') {
            // Echo Input For Own Word

            $inputHTML = str_replace('{status}', '', $inputHTML);
            $gameHTML = str_replace('{main}', $inputHTML, $gameHTML);
        
            echo $gameHTML;
        } else {
            $_SESSION['theWord'] = randomizeWord();
        }
    }
}

// If The Word Exists

if (isset($_SESSION['theWord']) || isset($_POST['ownWord'])) {
    // Validate Own Word

    if (isset($_POST['ownWord'])) {
        if (ctype_alpha($_POST['ownWord'])) {
            $ownWord = strtoupper($_POST['ownWord']);
            $_SESSION['theWord'] = $ownWord;
        } else {
            $error = 'Word can only contain alphabetical letters.';

            $inputHTML = str_replace('{status}', $error, $inputHTML);
            $gameHTML = str_replace('{main}', $inputHTML, $gameHTML);
        
            echo $gameHTML;

            exit;
        }
    }

    // THE GAME!

    $wordLength = strlen($_SESSION['theWord']);

    // create the sessions correct and wrong

    if (!isset($_SESSION['correct']) && !isset($_SESSION['wrong'])) {
        generateSessions($wordLength);
    }

    if (isset($_POST['character'])) {
        $character = trim($_POST['character']);

        if (!isset($_SESSION["$character"])) {
            // loop through word, to check if there is an identical character

            $false = checkCorrectCharacters($wordLength, $character);

            // if none are found then add it to wrong characters

            if ($false == $wordLength) {
                addWrongCharacters($character);
            } else {
                $_SESSION["$character"] = 'correct';
            }
        }
    }

    // add every character to charactersHTML

    $charactersHTML = addAlphabetInput(ALPHABET);

    // display the game or end

    $state = $_SESSION['state'];
    $lives = $_SESSION['lives'];

    // check if all characters are guessed

    $end = checkAllGuessed();

    // put spaces between the letters in theWord

    $theWord = addSpaces($wordLength);


    if ($lives == 0) {
        $endHTML = str_replace('{end}', 'Player Was Hanged X_X', $endHTML);
        $sectionHTML = str_replace('{middle}', $endHTML, $sectionHTML);
        $sectionHTML = str_replace('{word}', $theWord, $sectionHTML);
    } elseif ($end) {
        $endHTML = str_replace('{end}', 'The Word Was Guessed!', $endHTML);
        $sectionHTML = str_replace('{middle}', $endHTML, $sectionHTML);
        $sectionHTML = str_replace('{word}', $theWord, $sectionHTML);
    } else {
        $formHTML = str_replace('{length}', $wordLength, $formHTML);
        $formHTML = str_replace('{alphabet}', $charactersHTML, $formHTML);
        $formHTML = str_replace('{wrong}', $_SESSION['wrong'], $formHTML);
        $sectionHTML = str_replace('{middle}', $formHTML, $sectionHTML);
        $sectionHTML = str_replace('{word}', $_SESSION['correct'], $sectionHTML);
    }


    $sectionHTML = str_replace('{img}', "Images/$state.png", $sectionHTML);
    $sectionHTML = str_replace('{life}', $lives, $sectionHTML);
    $gameHTML = str_replace('{main}', $sectionHTML, $gameHTML);

    echo $gameHTML;
}