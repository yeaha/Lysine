<?php
$links = array(
    '<a href="/user">User Page</a>',
    '<a href="/admin">Admin Page</a>',
);

$links[] = \Model\User::current()->hasRole('anonymous')
         ? '<a href="/login">Login</a>'
         : '<a href="/logout">Logout</a>';
?>

<div id="header"><?php echo implode('&nbsp;/&nbsp;', $links); ?></div>
