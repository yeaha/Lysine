<?php $this->extend('_layouts/default'); ?>

<?php $this->block('main'); ?>

<h1><?php echo $exception->getMessage(); ?></h1>
<p><?php echo nl2br($exception->__toString()); ?></p>

<?php $this->endblock(); ?>
