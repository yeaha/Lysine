<?php $this->extend('_layouts/default'); ?>

<?php $this->block('main'); ?>
<h1>User</h1>
<pre><?php echo var_export($user->toArray(), true); ?></pre>

<h1>Role</h1>
<pre><?php echo var_export($user->roles, true); ?></pre>
<?php $this->endblock(); ?>
