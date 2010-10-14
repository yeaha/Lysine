<?php $this->extend('_layouts/default'); ?>

<?php $this->block('main'); ?>

<?php echo showException($exception); ?>

<?php $this->endblock(); ?>

<?php
function showException($exception) {
    $output = '<h1>'. $exception->getMessage() .'</h1>';
    $output .= '<p>'. nl2br($exception->getTraceAsString()) .'</p>';

    if ($exception instanceof \Lysine\Error) {
        $output .= '<h2>More Information</h2>';
        $output .= '<pre>'. var_export($exception->getMore(), true) .'</pre>';
    }

    if ($previous = $exception->getPrevious())
        $output = showException($previous) . $output;

    return $output;
}
