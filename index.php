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
    var requestAnimationFrame = window.requestAnimationFrame || window.mozRequestAnimationFrame || window.webkitRequestAnimationFrame || window.msRequestAnimationFrame;
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
var blocks                = [];
canvas.width              = width;
canvas.height             = height;

// Images
var backgroundImage       = {
    image: new Image(),
    x: 0,
    freeze: false
}
backgroundImage.image.src = "/images/water-faded.png";

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

generateBlocksArray();


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
function checkShouldStartGame() {
    if (keys[13]) {
        gameStarted = true;
        render();
    }
}

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

    render();
}

function render() {
    ctx.clearRect(0,0,width,height);
    renderBackground();
    renderHealth();

    if (player.alive) {
        animateCharacter();
        renderBlocks();
        requestAnimationFrame(movePlayer);
    } else if(gameover) {
        ctx.font = "50px Impact";
        ctx.fillStyle = "white";
        ctx.textAlign = "center";
        ctx.fillText('Game Over!', width/2, height/2);
        renderCharacterDeath();
        renderBlocks(false);
        requestAnimationFrame(animateDeathSequence);
    } else if(continueCountdown > 0) {
        animateCharacter();
        renderCountDown();
    } else {
        renderCharacterDeath();
        renderBlocks(false);
        requestAnimationFrame(animateDeathSequence);
    }


}

function renderHealth() {
    ctx.font = "20px Times New Roman";
    ctx.fillStyle = "white";
    ctx.textAlign = "left";
    ctx.fillText(player.health, 10, 20);
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
        zombifyCharacter();
    }

    window.setTimeout(function() {
        render();
    },1000);

}

function renderBlocks(animateBlocks = true) {
    for (var i = 0; i < blocks.length; i++) {
        var posX = animateBlocks ? blocks[i].x-- : blocks[i].x;
        ctx.drawImage(
            blocks[i].image,
            0,
            0,
            blocks[i].width,
            blocks[i].height,
            posX,
            blocks[i].y,
            blocks[i].width,
            blocks[i].height
        );

        if (posX + blocks[i].width <= 0) {
            blocks.splice(i, 1);
            var startPos = blocks[blocks.length - 1].x + 100;
            var endPos = blocks[blocks.length - 1].x + blocks[blocks.length - 1].width + 50;
            pushBlock(startPos, endPos);
        }
    }
}

function generateBlocksArray() {
    for (var i = 0; i < 20; i++) {
        var startPos = blocks[i - 1] ? blocks[i - 1].x + 100 : 100;
        var endPos = blocks[i - 1] ? blocks[i - 1].x + blocks[i -1].width + 50 : 150 + 48;
        pushBlock(startPos, endPos);
    }
}

function pushBlock(startPos, endPos) {
    blocks.push({
        image: new Image(),
        width: 64,
        height: 48,
        x: Math.floor(Math.random()*(endPos-startPos+1)+startPos),
        y: Math.floor(Math.random()*((height - 100)-0+1)+0)
    });

    blocks[blocks.length - 1].image.src = "/images/block.png";
}

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

function killPlayer() {
    backgroundImage.freeze = true;
    player.alive = false;
    player.health--;
    deathPosition = player.y - 25;

    if (player.health == 0) {
        gameover = true;
    }
}

function zombifyCharacter() {
    backgroundImage.freeze = false;
    player.facing = 'right';
    player.alive = true;
    bounce = true;
}


</script>
</body>
</html>
