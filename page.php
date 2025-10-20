<?php
get_header();
?>
<div class="container">
  <?php
  while ( have_posts() ) : the_post();
    the_title('<h1>','</h1>');
    the_content();
  endwhile;
  ?>
</div>
<?php get_footer(); ?>
