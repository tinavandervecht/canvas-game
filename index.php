<!doctype html>
<html>
<head>
    <title>My fancy game</title>

    <style>
        @font-face {
            font-family: 'Triforce';
            src: url('/fonts/Triforce.eot');
            src: url('/fonts/Triforce.eot?#iefix') format('embedded-opentype'),
                url('/fonts/Triforce.woff') format('woff'),
                url('/fonts/Triforce.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Return of Ganon';
            src: url('/fonts/ReturnOfGanonReg.eot');
            src: url('/fonts/ReturnOfGanonReg.eot?#iefix') format('embedded-opentype'),
                url('/fonts/ReturnOfGanonReg.woff') format('woff'),
                url('/fonts/ReturnOfGanonReg.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        .game {
            position:relative;
            width:750px;
        }
        #highScoreForm {
            position:absolute;
            top: 50%;
            width:100%;
            text-align:center;
        }

        .hidden {
            display:none;
        }

        input {
            padding:5px;
        }

        input:focus {
            outline:none;
        }
    </style>
</head>
<body>

    <canvas id="canvas" style="border:1px solid #000"></canvas>
    <audio preload="auto" id="titleThemeAudio">
      <source src="audio/title-theme.mp3" type="audio/mpeg">
      <source src="audio/title-theme.ogg" type="audio/ogg">
    </audio>

    <audio preload="auto" id="getRupeeAudio">
      <source src="audio/rupees/get_rupee.mp3" type="audio/mpeg">
      <source src="audio/rupees/get_rupee.ogg" type="audio/ogg">
    </audio>

    <audio preload="auto" id="swimmingAudio">
      <source src="audio/link/swimming.mp3" type="audio/mpeg">
      <source src="audio/link/swimming.ogg" type="audio/ogg">
    </audio>

    <audio preload="auto" id="hurtAudio">
      <source src="audio/link/scream.mp3" type="audio/mpeg">
      <source src="audio/link/scream.ogg" type="audio/ogg">
    </audio>

    <audio preload="auto" id="backgroundAudio" loop="loop">
      <source src="audio/water_temple.mp3" type="audio/mpeg">
      <source src="audio/water_temple.ogg" type="audio/ogg">
    </audio>

<script>
    var getHighScores = <?php echo file_get_contents('./highscores.json'); ?>;
    var highScores = getHighScores.slice(0);
    highScores.sort(function(a,b) {
        return a.score - b.score;
    });
    highScores.reverse();
</script>
<script>
// requestAnimationFrame and fallback
(function() {
    var requestAnimationFrame = window.requestAnimationFrame
        || window.mozRequestAnimationFrame
        || window.webkitRequestAnimationFrame
        || window.msRequestAnimationFrame;
    window.requestAnimationFrame = requestAnimationFrame;
})();

// Variables
var gameStarted           = false;
var canvas                = document.getElementById("canvas");
var ctx                   = canvas.getContext("2d");
var width                 = 750;
var height                = 350;
var keys                  = [];
var friction              = 0.8;
var gravity               = 0.005;
var deathPosition         = 0;
var bounce                = true;
var continueCountdown     = 0;
var gameover              = false;
var uninteractables       = [];
var uninteractableType    = 'block';
var rupees                = [];
var score                 = 0;
var gameLength            = 0;
var gamePaused            = false;
canvas.width              = width;
canvas.height             = height;
var playBtn               = {
    left: 0,
    right: 0,
    top: 0,
    bottom: 0
}
var highScoreBtn          = {
    left: 0,
    right: 0,
    top: 0,
    bottom: 0
}
var returnMainMenuBtn    = {
    left: 0,
    right: 0,
    top: 0,
    bottom: 0
}

// Images
var backgroundImage       = {
    image: new Image(),
    x: 0,
    freeze: false
}
backgroundImage.image.src = "/images/water-faded.png";

var heartImage            = {
    image: new Image(),
    width: 15,
    height: 12
}
heartImage.image.src = "/images/sprites/heart.png";

var rupeesData            = {
    image: new Image(),
    width: 8,
    height: 14,
    types: {
        1: { type: 'green', x: 0, value: 1 },
        2: { type: 'blue', x: 7, value: 5 },
        3: { type: 'red', x: 24, value: 20 },
        4: { type: 'purple', x: 31, value: 50 },
        5: { type: 'yellow', x: 15, value: 200 }
    }
}
rupeesData.image.src = "/images/sprites/rupees.png";

// player data
var player                = {
    image: new Image(),
    x : 50,
    y : height / 2,
    width : 17,
    height : 15,
    speed: 3,
    velX: 0,
    velY: 1.5,
    sourceX: 0,
    sourceY: 0,
    totalFrames: 2,
    currentFrame: 1,
    fpsCount: 0,
    facing: 'right',
    health: 3,
    alive: true
};
player.image.src          = "/images/sprites/link.png";
player.animations         = {
    right: {
        1: {
            sourceX: 0,
            sourceY: 0
        },
        2: {
            sourceX: player.width,
            sourceY: 0
        }
    },
    left: {
        1: {
            sourceX: (player.width * 2),
            sourceY: 0
        },
        2: {
            sourceX: (player.width * 3),
            sourceY: 0
        }
    }
}

loadGame();
generateUninteractablesArray();
generateRupeesArray();

// Event Listeners
document.body.addEventListener("keydown", function(e) {
    keys[e.keyCode] = true;
    if(keys[32] && gameStarted) {
        checkShouldPauseGame();
    }
});

document.body.addEventListener("keyup", function(e) {
    keys[e.keyCode] = false;
});

canvas.addEventListener("mousemove", function(e) {
    var mousePos = getMousePos(canvas, e);
    e.target.style.cursor = 'default';

    if ((mousePos.x >= playBtn.left && mousePos.x <= playBtn.right)
        && (mousePos.y >= playBtn.top && mousePos.y <= playBtn.bottom)) {
            e.target.style.cursor = 'pointer';
    }

    if ((mousePos.x >= highScoreBtn.left && mousePos.x <= highScoreBtn.right)
        && (mousePos.y >= highScoreBtn.top && mousePos.y <= highScoreBtn.bottom)) {
            e.target.style.cursor = 'pointer';
    }

    if ((mousePos.x >= returnMainMenuBtn.left && mousePos.x <= returnMainMenuBtn.right)
        && (mousePos.y >= returnMainMenuBtn.top && mousePos.y <= returnMainMenuBtn.bottom)) {
            e.target.style.cursor = 'pointer';
    }
});

canvas.addEventListener("click", function(e) {
    var mousePos = getMousePos(canvas, e);

    if ((mousePos.x >= playBtn.left && mousePos.x <= playBtn.right)
        && (mousePos.y >= playBtn.top && mousePos.y <= playBtn.bottom)) {
        returnMainMenuBtn = {};
        playBtn = {};
        highScoreBtn = {};
        gameStarted = true;
        startBackgroundAudio();
        render();
    }

    if ((mousePos.x >= highScoreBtn.left && mousePos.x <= highScoreBtn.right)
        && (mousePos.y >= highScoreBtn.top && mousePos.y <= highScoreBtn.bottom)) {
        returnMainMenuBtn = {};
        playBtn = {};
        highScoreBtn = {};
        renderHighScoresList();
    }

    if ((mousePos.x >= returnMainMenuBtn.left && mousePos.x <= returnMainMenuBtn.right)
        && (mousePos.y >= returnMainMenuBtn.top && mousePos.y <= returnMainMenuBtn.bottom)) {
        returnMainMenuBtn = {};
        playBtn = {};
        highScoreBtn = {};
        resetGame();
        loadGame();
    }
});

// Functions
// should code _ functions
function checkShouldRemoveUninteractable() {
    for (var i = 0; i < uninteractables.length; i++) {
        var coversX = (player.x > uninteractables[i].x) && (player.x < uninteractables[i].x + uninteractables[i].width);
        var coversY = (player.y > uninteractables[i].y) && (player.y < uninteractables[i].y + uninteractables[i].height);
        if (coversX && coversY) {
            uninteractables.splice(i, 1);
        }
    }
}

function checkShouldPauseGame() {
    if (gamePaused) {
        gamePaused = false;
        render();
    } else {
        gamePaused = true;
    }
}

function startBackgroundAudio() {
    document.getElementById('titleThemeAudio').pause();
    document.getElementById('titleThemeAudio').currentTime = 0;
    document.getElementById('backgroundAudio').play();
}

function collidesWithCucco() {
    for (var i = 0; i < uninteractables.length; i++) {
        var coversX = (player.x >= uninteractables[i].x - (player.width - 2)) && (player.x <= uninteractables[i].x + uninteractables[i].width);
        var coversY = (player.y >= uninteractables[i].y - (player.width - 2)) && (player.y <= uninteractables[i].y + uninteractables[i].height);

        if (coversX && coversY && uninteractables[i].type === 'cucco') {
            killPlayer();
        }
    }
}

function collidesWithRupee() {
    for (var i = 0; i < rupees.length; i++) {
        var x = hitRupeeX(rupees[i]);
        var y = hitRupeeY(rupees[i]);
        if ((x && y) || (player.x == rupees[i].x && player.y == rupees[i].y)) {
            document.getElementById('getRupeeAudio').play();
            score += rupeesData.types[rupees[i].type].value;
            rupees.splice(i,1);
        }
    }
}

function hitRupeeX(rupee) {
    var x_plusWidthGreater = player.x + player.width >= rupee.x;
    var x_minusWidthGreater = player.x - player.width >= rupee.x;
    var x_plusWidthLess = player.x + player.width <= rupee.x + rupeesData.width;
    var x_minusWidthLess = player.x - player.width <= rupee.x + rupeesData.width;

    return (x_plusWidthGreater || x_minusWidthGreater) && (x_plusWidthLess || x_minusWidthLess);
}

function hitRupeeY(rupee) {
    var y_plusHeightGreater = player.y + player.height >= rupee.y;
    var y_minusHeightGreater = player.y - player.height >= rupee.y;
    var y_plusHeightLess = player.y + player.height <= rupee.y + rupeesData.height;
    var y_minusHeightLess = player.y - player.height <= rupee.y + rupeesData.height;

    return (y_plusHeightGreater || y_minusHeightGreater) && (y_plusHeightLess || y_minusHeightLess);
}

function collidesWithBlock(sideToCheck) {
    for (var i = 0; i < uninteractables.length; i++) {
        if (uninteractables[i].type === 'block') {
            var coversY = false;
            var coversX = false;

            if (sideToCheck === 'left' || sideToCheck === 'right') {
                coversY = player.y + player.height >= uninteractables[i].y
                    && player.y <= uninteractables[i].y + uninteractables[i].height;

                if (sideToCheck === 'left') {
                    coversX = player.x - player.width >= uninteractables[i].x - (uninteractables[i].width / 1.5)
                        && player.x + player.width <= uninteractables[i].x + (uninteractables[i].width / 2);
                }

                if (sideToCheck === 'right') {
                    coversX = player.x >= uninteractables[i].x + uninteractables[i].width
                         && player.x <= uninteractables[i].x + uninteractables[i].width + 5;
                }
            }

            if (sideToCheck === 'top' || sideToCheck === 'bottom') {
                coversX = (player.x + (player.width / 2) >= uninteractables[i].x)
                    && (player.x <= uninteractables[i].x + uninteractables[i].width);

                if (sideToCheck === 'top') {
                    coversY = player.y - (player.height / 2) >= uninteractables[i].y - (uninteractables[i].height / 2)
                        && player.y - (player.height / 2) <= uninteractables[i].y - (uninteractables[i].height / 2) + 10;
                }

                if (sideToCheck === 'bottom') {
                    coversY = player.y - (player.height + (player.height / 1.5)) <= uninteractables[i].y + (uninteractables[i].height / 2)
                        && player.y - (player.height + (player.height / 1.5)) >= uninteractables[i].y;
                }
            }

            if (coversX && coversY) {
                return true;
            }
        }
    }
}

// moving functions
function movePlayer(){
    //up and down
    if (keys[38]) {
        // up arrow
        if (!collidesWithBlock('bottom')) {
            player.velY = 1;
            player.y -= player.velY;
        }
    } else {
        // down arrow
        if (player.velY < player.speed) {
            player.velY += gravity;
        }
        if (!collidesWithBlock('top')) {
            player.y += player.velY;
        }
    }

    // stops player from going past top edge
    if(player.y <= 0){
        player.y = 0;
    }

    // this stops player from going past bottom edge
    if(player.y >= height - player.height){
        player.y = height - player.height;
        killPlayer();
    }

   // left and right
    if (keys[39]) {
        // right arrow
        player.facing = 'right';
        if (player.velX < player.speed) {
            if (!collidesWithBlock('left')) {
                player.velX++;
            }
         }
    }
    if (keys[37]) {
        // left arrow
        player.facing = 'left';
        if (player.velX > -player.speed) {
            if (!collidesWithBlock('right')) {
                player.velX--;
            }
        }
    }

    if (!keys[37]) {
        player.x--;
    }

    player.velX *= friction;
    player.x += player.velX;

    //this stops player from going past side edges
    if (player.x >= width-player.width) {
        player.x = width-player.width;
    } else if (player.x <= 0) {
        killPlayer();
    }

    collidesWithRupee();
    collidesWithCucco();

    render();
}

function resetGame() {
    score = 0;
    document.getElementById('highScoreForm').classList.add("hidden");
    document.getElementById('hurtAudio').pause();
    document.getElementById('hurtAudio').currentTime = 0;
    zombifyPlayer();
    player.x = 50;
    player.y = height / 2;
    player.facing = 'right';
    player.health = 3;
    gameLength = 0;
    gameover = false;
    gameStarted = false;
}

function loadGame() {
    document.getElementById('titleThemeAudio').play();
    ctx.clearRect(0,0,width,height);
    var startBg = new Image();
    startBg.src = '/images/background.gif';
    startBg.onload = function() {
        ctx.drawImage(startBg, 0, 0, width, height);
    };
    ctx.textAlign = 'center';

    fadeIn('The Legend of Tina', width / 2, 100, 68, 'Triforce');
    fadeIn('Best with sound!', width / 2 + 230, 115, 15, 'Return of Ganon');
    fadeIn('Play', width / 2, 200, 20, 'Return of Ganon', playBtn);
    fadeIn('High Scores', width / 2, 230, 20, 'Return of Ganon', highScoreBtn);
    fadeIn(
        'All icons and verbiage that remind you of Zelda belongs to Nintendo. Pls don\'t sue',
        width / 2,
        height - 12,
        12,
        'Arial'
    );
}

// render functions
function render() {
    ctx.clearRect(0,0,width,height);
    renderBackground();

    if (gamePaused) {
        renderRupees(false);
        renderUninteractables(false);
        ctx.font = "68px Triforce";
        ctx.fillStyle = "black";
        ctx.textAlign = "center";
        ctx.fillText('Paused', width/2, height/2);
    } else if (player.alive) {
        animateCharacter();
        renderRupees();
        renderUninteractables();
        requestAnimationFrame(movePlayer);
    } else if(gameover) {
        renderRupees(false);
        renderUninteractables(false);
        renderCharacterDeath();
        renderGameOverScreen();
        gameLength = 0;
    } else if(continueCountdown > 0) {
        checkShouldRemoveUninteractable();
        renderRupees(false);
        renderUninteractables(false);
        renderCountDown();
        animateCharacter();
        gameLength = 0;
    } else {
        renderCharacterDeath();
        renderRupees(false);
        renderUninteractables(false);
        requestAnimationFrame(animateDeathSequence);
        gameLength = 0;
    }

    gameLength++;

    renderScore();
    renderHealth();

}

function renderHealth() {
    var pos = 10;
    for (var i = 1; i <= 3; i++) {
        if (i <= player.health) {
            ctx.drawImage(
                heartImage.image,
                heartImage.width,
                0,
                heartImage.width,
                heartImage.height,
                pos,
                10,
                heartImage.width,
                heartImage.height
            );
            pos = pos + 17;
        } else {
            ctx.drawImage(
                heartImage.image,
                0,
                0,
                heartImage.width,
                heartImage.height,
                pos,
                10,
                heartImage.width,
                heartImage.height
            );
            pos = pos + 17;
        }
    }
}

function renderScore() {
    ctx.font = "20px Triforce";
    ctx.fillStyle = "white";
    ctx.textAlign = "right";
    ctx.fillText(score, width - 20, 30);
}

function renderBackground() {
    ctx.drawImage(backgroundImage.image, backgroundImage.x, 0);
    ctx.drawImage(backgroundImage.image, -width + backgroundImage.x, 0);
    if (! backgroundImage.freeze) {
        backgroundImage.x++;

        if (backgroundImage.x == width) {
            backgroundImage.x = 0;
        }
    }
}

function renderCharacterDeath() {
    ctx.drawImage(
        player.image,
        0,
        player.height,
        player.width,
        player.height,
        player.x,
        player.y,
        player.width,
        player.height
    );
}

function renderCountDown() {
    ctx.font = "125px Return of Ganon";
    ctx.fillStyle = "black";
    ctx.textAlign = "center";
    ctx.fillText(continueCountdown, width/2, height/1.5);

    continueCountdown--;

    if (continueCountdown == 0) {
        zombifyPlayer();
    }

    window.setTimeout(function() {
        render();
    },1000);
}

function renderGameOverScreen() {
    ctx.fillStyle = "black";
    ctx.fillRect(0, 0, width, height);
    ctx.fillStyle = "#FFFFFF";
    ctx.textAlign = "center";
    ctx.font = '50px Triforce';
    ctx.fillText('Game Over', width / 2, 100);

    var pointText = 'Well, you tried - You got ' + score + '. Maybe try again?';

    if (score > 5) {
        pointText = 'Good Job! You managed to get ' + score + '!';
    }

    if (score > 500) {
        pointText = 'Holy moly! You managed to get ' + score + '!  You\'re definitely one of a kind!';
    }

    ctx.font = '20px Return of Ganon';
    ctx.fillText(pointText, width / 2, 150);

    ctx.textAlign = "right";
    ctx.font = '20px Return of Ganon';
    ctx.fillText('Return to Main Menu', width - 30, 30);
    buildTextObj(returnMainMenuBtn,'Return to Main Menu',width - 30, 30, 20);

    document.getElementById('highScoreForm').classList.remove("hidden");
}

// rupee functions
function renderRupees(animateRupees = true) {
    var visibleRupees = 0;

    for (var i = 0; i < rupees.length; i++) {
        var posX = animateRupees ? rupees[i].x-- : rupees[i].x;
        drawRupee(rupees[i].type, posX, rupees[i].y);

        if (posX + rupeesData.width <= 0) {
            rupees.splice(i, 1);
            addRupee();
        } else {
            visibleRupees++;
        }
    }

    var randomlyAddChances = [false, true];
    if (randomlyAddChances[Math.floor(Math.random() * randomlyAddChances.length)] && visibleRupees < 5) {
        addRupee();
    }
}

function getAvailableRupeeKeys() {
    if (gameLength > 5000) {
        return 5;
    }

    if (gameLength > 3000) {
        return 4;
    }

    if (gameLength > 1500) {
        return 3;
    }

    if (gameLength > 500) {
        return 2;
    }

    return 1;
}

function getCuccoLikelihood() {
    if (gameLength > 5000) {
        return 75;
    }

    if (gameLength > 1500) {
        return 50;
    }

    return 25;
}

function addRupee() {
    var startPos = width;
    var endPos = Math.floor(Math.random()*((width * 2)-width+1)+width);
    var rupeeType = Math.floor(Math.random()*(getAvailableRupeeKeys()-1+1)+1);
    rupees.push({
        type: rupeeType,
        x: Math.floor(Math.random()*(endPos-startPos+1)+startPos),
        y: Math.floor(Math.random()*((height - 100)-0+1)+0)
    });
}

function generateRupeesArray() {
    var maxAmt = Math.floor(Math.random()*(5-1+1)+1);

    for(var i = 0; i < maxAmt; i++) {
        addRupee();
    }
}

function drawRupee(typeKey, posX, posY) {
    ctx.drawImage(
        rupeesData.image,
        rupeesData.types[typeKey].x,
        0,
        rupeesData.width,
        rupeesData.height,
        posX,
        posY,
        rupeesData.width,
        rupeesData.height
    );
}

// animation functions
function animateCharacter() {
    ctx.drawImage(
        player.image,
        player.animations[player.facing][player.currentFrame].sourceX,
        player.animations[player.facing][player.currentFrame].sourceY,
        player.width,
        player.height,
        player.x,
        player.y,
        player.width,
        player.height
    );

    if (player.fpsCount > 24) {
        if (player.currentFrame == player.totalFrames) {
            player.currentFrame = 0;
        }

        document.getElementById('swimmingAudio').play();

        player.currentFrame++;

        player.fpsCount = 0;
    } else {
        player.fpsCount++;
    }
}

function animateDeathSequence() {
    if (player.y > deathPosition && player.y > 0 && bounce) {
        player.y--;
    } else {
        if (gameover || player.y < deathPosition + 50) {
            bounce = false;
            player.y++;
        } else {
            player.x = 50;
            player.y = height / 2;
            continueCountdown = 3;
        }
    }

    render();
}

// player life functions
function killPlayer() {
    document.getElementById('backgroundAudio').pause();
    document.getElementById('backgroundAudio').currentTime = 0;
    document.getElementById('hurtAudio').play();
    player.facing = 'right';
    backgroundImage.freeze = true;
    player.alive = false;
    player.health--;
    deathPosition = player.y - 25;

    if (player.health == 0) {
        gameover = true;
    }
}

function zombifyPlayer() {
    backgroundImage.freeze = false;
    player.alive = true;
    bounce = true;
}

// block functions
function generateUninteractablesArray() {
    for (var i = 0; i < 20; i++) {
        setUninteractableType();
        var hasPreviousUninteractable = uninteractables[i - 1];
        var startPos = hasPreviousUninteractable ? uninteractables[i - 1].x + uninteractables[i - 1].width : 100;
        var endPos = hasPreviousUninteractable ? uninteractables[i - 1].x + uninteractables[i - 1].width + 100 : 200;
        pushUninteractable(startPos, endPos);
    }
}

function renderUninteractables(animateUninteractables = true) {
    for (var i = 0; i < uninteractables.length; i++) {
        var posX = animateUninteractables ? uninteractables[i].x-- : uninteractables[i].x;

        if (uninteractables[i].type === 'block') {
            renderBlock(uninteractables[i], posX);
        } else {
            renderCucco(uninteractables[i], posX);
        };

        if (posX + uninteractables[i].width <= 0) {
            uninteractables.splice(i, 1);
            setUninteractableType();
            var startPos = uninteractables[uninteractables.length - 1].x + uninteractables[uninteractables.length - 1].width;
            var endPos = uninteractables[uninteractables.length - 1].x + uninteractables[uninteractables.length - 1].width + 100;
            pushUninteractable(startPos, endPos);
        }
    }
}

function renderBlock(block, posX) {
    ctx.drawImage(
        block.image,
        0,
        0,
        block.width,
        block.height,
        posX,
        block.y,
        block.width,
        block.height
    );
}

function renderCucco(cucco, posX) {
    ctx.drawImage(
        cucco.image,
        cucco.animations[cucco.currentFrame].sourceX,
        cucco.animations[cucco.currentFrame].sourceY,
        cucco.width,
        cucco.height,
        posX,
        cucco.y,
        cucco.width,
        cucco.height
    );

    if (cucco.fpsCount > 12) {
        if (cucco.currentFrame == cucco.totalFrames) {
            cucco.currentFrame = 0;
        }

        cucco.currentFrame++;

        cucco.fpsCount = 0;
    } else {
        cucco.fpsCount++;
    }
}

function renderHighScoresList() {
    ctx.clearRect(0,0,width,height);
    ctx.fillStyle = "black";
    ctx.fillRect(0, 0, width, height);
    ctx.textAlign = 'right';
    fadeIn('Return to Main Menu', width - 75, 30, 20, 'Return of Ganon', returnMainMenuBtn);
    ctx.textAlign = 'center';
    fadeIn('High Scores', width / 2, 75, 40, 'Triforce');

    var xPos = 125;
    for (var i = 0; i < 5; i++) {
        var obj = highScores[i];
        fadeIn('[' + (i + 1) + ']', 200, xPos - 5, 20, 'Return of Ganon');
        fadeIn(obj['username'], 250, xPos, 40, 'Return of Ganon');
        fadeIn(obj['score'], 500, xPos, 40, 'Return of Ganon');

        xPos = xPos + 50;
    }
}

function pushUninteractable(startPos, endPos) {
    if (uninteractableType == 'cucco') {
        uninteractables.push({
            image: new Image(),
            width: 30,
            height: 32,
            type: uninteractableType,
            x: Math.floor(Math.random()*(endPos-startPos+1)+startPos),
            y: Math.floor(Math.random()*((height - 32)-0+1)+0),
            totalFrames: 2,
            currentFrame: Math.floor(Math.random()*(2-1+1)+1),
            fpsCount: 0,
        });

        uninteractables[uninteractables.length - 1].image.src = '/images/sprites/cucco.png';

        uninteractables[uninteractables.length - 1].animations = {
            1: {
                sourceX: 0,
                sourceY: 0
            },
            2: {
                sourceX: uninteractables[uninteractables.length - 1].width,
                sourceY: 0
            }
        }
    } else {
        uninteractables.push({
            image: new Image(),
            width: 64,
            height: 48,
            type: uninteractableType,
            x: Math.floor(Math.random()*(endPos-startPos+1)+startPos),
            y: Math.floor(Math.random()*((height - 48)-0+1)+0)
        });

        uninteractables[uninteractables.length - 1].image.src = '/images/sprites/block.png';
    }

}

function setUninteractableType() {
    uninteractableType = Math.floor(Math.random()*(100-1+1)+1) < getCuccoLikelihood()
        ? 'cucco'
        : 'block';
}

function fadeIn(text, x, y, fontSize, font, textVar = null) {
    var alpha = 0.0,
        interval = setInterval(function () {
            ctx.font = fontSize + 'px ' + font;
            ctx.fillStyle = "rgba(255, 255, 255, " + alpha + ")";
            if (textVar) {
                buildTextObj(textVar,text,x,y,fontSize);
            }
            ctx.fillText(text, x, y);
            if (alpha < .4) {
                alpha = alpha + 0.05;
            } else {
                clearInterval(interval);
            }
        }, 50);
}

function buildTextObj(textObj, text, x, y, height) {
    var width = ctx.measureText(text).width;
    textObj.top = y - height;
    textObj.bottom = y;
    textObj.left = x - width;
    textObj.right = x + width;
}

function getMousePos(canvas, evt) {
        var rect = canvas.getBoundingClientRect();
        return {
            x: evt.clientX - rect.left,
            y: evt.clientY - rect.top
        };
    }

</script>
</body>
</html>
