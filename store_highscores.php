<?php
    $highscores = file_get_contents('./highscores.json');
    $highscores = json_decode($highscores, true);
    $highscores[count($highscores)] = $_POST;

    file_put_contents('./highscores.json', json_encode($highscores));
?>
