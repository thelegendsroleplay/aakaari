<?php get_header(); ?>
<div class="container">
  <?php if ( have_posts() ) : ?>
    <div class="product-grid">
    <?php
    while ( have_posts() ) : the_post();
      if ( 'product' === get_post_type() ) {
        wc_get_template_part( 'content', 'product' );
      } else {
    ?>
      <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div><?php the_excerpt(); ?></div>
      </article>
    <?php
      }
    endwhile;
    ?>
    </div>
  <?php else: ?>
    <p>No products or posts found.</p>
  <?php endif; ?>
</div>
<?php get_footer(); ?>
