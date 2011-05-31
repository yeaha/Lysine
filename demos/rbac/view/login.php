<?php $this->extend('_layouts/default'); ?>

<?php $this->block('main'); ?>
<p>
Email:dev@demo.ly Password:abc<br/>
OR<br/>
Email:admin@demo.ly Password:admin
</p>
<form method="post">
    <table align="center">
        <caption>Login</caption>
        <tr><td>Email: </td><td><input type="text" name="email"/></td></tr>
        <tr><td>Password: </td><td><input type="password" name="passwd"/></td></tr>
        <tr><td colspan="2" align="center"><button>Login</button><a href="/register">Register</a></td></tr>
    </table>
</form>
<?php $this->endblock(); ?>
