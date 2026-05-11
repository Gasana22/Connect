<?php
function sanitize_input($data) {
  return htmlspecialchars(strip_tags(trim($data)));
}


function link_hashtags($text) {
    return preg_replace(
        '/#(\w+)/', 
        '<a href="hashtag.php?tag=$1" class="hashtag">#$1</a>', 
        $text
    );
}
?>