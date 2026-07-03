<?php

echo "Password Admin: <br>";
echo password_hash("admin123", PASSWORD_DEFAULT);

echo "<br><br>";

echo "Password User: <br>";
echo password_hash("user123", PASSWORD_DEFAULT);

?>
