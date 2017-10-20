<!doctype html>
<html>
<head>
    <title>My fancy game</title>
</head>
<body>

    <canvas id="canvas" style="border:1px solid #000"></canvas>

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
var width                 = 500;
var height                = 200;
var keys                  = [];
var friction              = 0.8;
var gravity               = 0.005;
var deathPosition         = 0;
var bounce                = true;
var continueCountdown     = 0;
var gameover              = false;
var uninteractables       = [];
var rupees                = [];
var score                 = 0;
var gameLength            = 0;
canvas.width              = width;
canvas.height             = height;

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

// Start Screen
ctx.fillRect(0, 0, width, height);
ctx.font = "50px Impact";
ctx.fillStyle = "white";
ctx.textAlign = "center";
ctx.fillText("Oh Shit", width/2, height/2);

ctx.font = "20px Arial";
ctx.fillText("Press Enter To Start", width/2, height/2 + 50);

generateUninteractablesArray();
generateRupeesArray();

// Event Listeners
document.body.addEventListener("keydown", function(e) {
    keys[e.keyCode] = true;
    if (!gameStarted) {
        checkShouldStartGame();
    }
});

document.body.addEventListener("keyup", function(e) {
    keys[e.keyCode] = false;
});


// Functions
// should code _ functions
function checkShouldStartGame() {
    if (keys[13]) {
        gameStarted = true;
        render();
    }
}

function checkShouldRemoveUninteractable() {
    for (var i = 0; i < uninteractables.length; i++) {
        var coversX = (player.x > uninteractables[i].x) && (player.x < uninteractables[i].x + uninteractables[i].width);
        var coversY = (player.y > uninteractables[i].y) && (player.y < uninteractables[i].y + uninteractables[i].height);
        if (coversX && coversY) {
            uninteractables.splice(i, 1);
        }
    }
}

function checkUninteractableCollision() {
    for (var i = 0; i < uninteractables.length; i++) {
        var coversX = (player.x >= uninteractables[i].x - (player.width - 2)) && (player.x <= uninteractables[i].x + uninteractables[i].width);
        var coversY = (player.y >= uninteractables[i].y - (player.width - 2)) && (player.y <= uninteractables[i].y + uninteractables[i].height);

        if (coversX && coversY && uninteractables[i].type === 'cucco') {
            killPlayer();
        }
    }
}

function checkRupeeCollision() {
    for (var i = 0; i < rupees.length; i++) {
        var x = hitRupeeX(rupees[i]);
        var y = hitRupeeY(rupees[i]);
        if ((x && y) || (player.x == rupees[i].x && player.y == rupees[i].y)) {
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

// moving functions
function movePlayer(){
    //up and down
    if (keys[38]) {
        // up arrow
        player.velY = 1;
        player.y -= player.velY;
    } else {
        if (player.velY < player.speed) {
            player.velY += gravity;
        }
        player.y += player.velY;
    }

    // stops player from going past top edge
    if(player.y <= 0){
        player.y = 0;
    }

    // this stops player from going past bottom edge
    if(player.y >= height-player.height){
        player.y = height - player.height;
        killPlayer();
    }

   // left and right
    if (keys[39]) {
        // right arrow
        player.facing = 'right';
        if (player.velX < player.speed) {
            player.velX++;
         }
    }
    if (keys[37]) {
        // left arrow
        player.facing = 'left';
        if (player.velX > -player.speed) {
            player.velX--;
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

    checkRupeeCollision();
    checkUninteractableCollision();

    render();
}

// render functions
function render() {
    ctx.clearRect(0,0,width,height);
    renderBackground();

    if (player.alive) {
        animateCharacter();
        renderRupees();
        renderUninteractables();
        requestAnimationFrame(movePlayer);
    } else if(gameover) {
        renderRupees(false);
        renderUninteractables(false);
        ctx.font = "50px Impact";
        ctx.fillStyle = "white";
        ctx.textAlign = "center";
        ctx.fillText('Game Over!', width/2, height/2);
        renderCharacterDeath();
        requestAnimationFrame(animateDeathSequence);
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
    ctx.font = "24px Impact";
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
    ctx.font = "50px Impact";
    ctx.fillStyle = "white";
    ctx.textAlign = "center";
    ctx.fillText(continueCountdown, width/2, height/2);

    continueCountdown--;

    if (continueCountdown == 0) {
        zombifyPlayer();
    }

    window.setTimeout(function() {
        render();
    },1000);
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
        var uninteractableType = Math.random() >= 0.5
            ? 'cucco'
            : 'block';
        var hasPreviousUninteractable = uninteractables[i - 1];
        var startPos = hasPreviousUninteractable ? uninteractables[i - 1].x + 100 : 100;
        var endPos = hasPreviousUninteractable ? uninteractables[i - 1].x + uninteractables[i -1].width + 100 : 200;
        pushUninteractable(startPos, endPos, uninteractableType);
    }
}

function renderUninteractables(animateUninteractables = true) {
    for (var i = 0; i < uninteractables.length; i++) {
        var posX = animateUninteractables ? uninteractables[i].x-- : uninteractables[i].x;
        ctx.drawImage(
            uninteractables[i].image,
            0,
            0,
            uninteractables[i].width,
            uninteractables[i].height,
            posX,
            uninteractables[i].y,
            uninteractables[i].width,
            uninteractables[i].height
        );

        if (posX + uninteractables[i].width <= 0) {
            uninteractables.splice(i, 1);
            var startPos = uninteractables[uninteractables.length - 1].x + 100;
            var endPos = uninteractables[uninteractables.length - 1].x + uninteractables[uninteractables.length - 1].width + 100;
            pushUninteractable(startPos, endPos);
        }
    }
}

function pushUninteractable(startPos, endPos, uninteractableType) {
    var data = {
        width: 64,
        height: 48,
        src: '/images/sprites/block.png'
    };

    if (uninteractableType == 'cucco') {
        data['width'] = 28;
        data['height'] = 32;
        data['src'] = '/images/sprites/cucco.png';
    }

    uninteractables.push({
        image: new Image(),
        width: data['width'],
        height: data['height'],
        type: uninteractableType,
        x: Math.floor(Math.random()*(endPos-startPos+1)+startPos),
        y: Math.floor(Math.random()*((height - data['height'])-0+1)+0)
    });

    uninteractables[uninteractables.length - 1].image.src = data['src'];
}


</script>
</body>
</html>
