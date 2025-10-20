<?php
/**
 * Template Name: Home Page (Aakaari)
 */

get_header(); ?>

<main id="site-content" role="main">

  <!-- HERO SECTION -->
  <section class="aakaari-hero-section">
    <div class="container">
      <div class="aakaari-hero-inner">
        <div class="aakaari-hero-left">
          <div class="badge">🚀 India's Fastest Growing B2B Platform</div>
          <h1 class="hero-title">
            Wholesale for Resellers.<br/>
            <span class="hero-sub">Buy Low. Sell High.</span>
          </h1>
          <p class="hero-subtext">Start your dropshipping business with 50-100% profit margins. No inventory, no risk. We handle storage, packing, and shipping — you focus on selling.</p>

<div class="hero-ctas">
    <?php
    // We define the links again here in case header.php logic isn't available
    $reseller_page_id = get_option('reseller_page_id');
    $reseller_link = $reseller_page_id ? get_permalink($reseller_page_id) : home_url('/become-a-reseller/');
    $login_link = home_url('/login/');
    $dashboard_link = home_url('/dashboard/');

    if (is_user_logged_in()) {
        $final_reseller_href = $dashboard_link;
    } else {
        $final_reseller_href = add_query_arg('redirect_to', urlencode($reseller_link), $login_link);
    }
    ?>
    <a class="btn btn-primary" href="<?php echo esc_url($final_reseller_href); ?>">Become a Reseller</a>
    <a class="btn btn-outline" href="<?php echo esc_url(home_url('/contact')); ?>">Contact Sales</a>
</div>

          <ul class="hero-stats">
            <li><strong>10K+</strong><span>Active Resellers</span></li>
            <li><strong>50K+</strong><span>Products Shipped</span></li>
            <li><strong>₹2Cr+</strong><span>Commissions Paid</span></li>
            <li><strong>98%</strong><span>Satisfaction Rate</span></li>
          </ul>
        </div>

        <div class="aakaari-hero-right">
          <?php
          if (has_post_thumbnail()) {
              echo get_the_post_thumbnail(get_queried_object_id(), 'full', array('class' => 'hero-image', 'loading' => 'eager'));
          } else {
              $fallback = get_template_directory_uri() . '/assets/images/hero-fallback.jpg';
              echo '<img class="hero-image" src="' . esc_url($fallback) . '" alt="Aakaari Wholesale Platform" loading="eager">';
          }
          ?>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS SECTION -->
  <section class="aakaari-how-it-works" id="how-it-works">
    <div class="container">
      <div class="section-header">
        <h2>How It Works</h2>
        <p class="lead">Start your dropshipping journey in 4 simple steps</p>
      </div>

      <div class="how-steps">
        <div class="how-step">
          <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
              <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816zM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
            </svg>
          </div>
          <div class="step-num">01</div>
          <h3>Register & Get Verified</h3>
          <p>Sign up and complete KYC. Get approved within 24 hours.</p>
        </div>

        <div class="how-step">
          <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
              <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
            </svg>
          </div>
          <div class="step-num">02</div>
          <h3>Browse & Order</h3>
          <p>Choose from 1500+ products at wholesale prices with minimum order quantity.</p>
        </div>

        <div class="how-step">
          <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
              <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247zm2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/>
            </svg>
          </div>
          <div class="step-num">03</div>
          <h3>Sell & Earn</h3>
          <p>Share product links, sell to customers. We ship directly to them.</p>
        </div>

        <div class="how-step">
          <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
              <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
              <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
            </svg>
          </div>
          <div class="step-num">04</div>
          <h3>Track & Grow</h3>
          <p>Real-time tracking. Earn commissions. Withdraw to your bank instantly.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- FEATURED PRODUCTS SECTION -->
  <section class="aakaari-featured-products">
    <div class="container">
      <div class="featured-header">
        <div class="featured-title">
          <h2>Featured Products</h2>
          <p class="muted">High-margin bestsellers</p>
        </div>
        <div class="featured-cta">
          <a class="btn btn-outline" href="<?php echo esc_url(get_post_type_archive_link('product') ?: home_url('/shop')); ?>">
            View All <span aria-hidden="true">→</span>
          </a>
        </div>
      </div>

      <div class="featured-grid">
      <?php
      if (class_exists('WooCommerce')) {
          $args = array(
              'limit' => 8,
              'status' => 'publish',
              'orderby' => 'date',
              'order' => 'DESC',
              'featured' => true,
          );
          $products = wc_get_products($args);

          if (!empty($products)) {
              foreach ($products as $product) {
                  $pid = $product->get_id();
                  $link = get_permalink($pid);
                  $image = $product->get_image('woocommerce_thumbnail', array('alt' => esc_attr($product->get_name()), 'loading' => 'lazy'));
                  $title = $product->get_name();

                  // Prices
                  $regular_price = $product->get_regular_price();
                  $sale_price = $product->get_sale_price();

                  // Wholesale price
                  $wholesale_price = get_post_meta($pid, '_wholesale_price', true);
                  if (!$wholesale_price) {
                      $wholesale_price = get_post_meta($pid, 'wholesale_price', true);
                  }

                  // MOQ
                  $moq = get_post_meta($pid, '_moq', true);
                  if (!$moq) $moq = get_post_meta($pid, 'moq', true);
                  if (!$moq) $moq = '—';

                  // Rating
                  $avg_rating = floatval($product->get_average_rating());
                  $rating_count = $product->get_rating_count();
                  $rating_html = wc_get_rating_html($avg_rating, $rating_count);

                  // Margin calculation
                  $margin_label = 'High Margin';
                  if ($wholesale_price && is_numeric($wholesale_price) && is_numeric($regular_price) && floatval($wholesale_price) > 0) {
                      $margin = round(((floatval($regular_price) - floatval($wholesale_price)) / floatval($wholesale_price)) * 100);
                      $margin_label = $margin . '% Margin';
                  }

                  echo '<article class="fp-card" itemscope itemtype="https://schema.org/Product">';
                    echo '<a class="fp-thumb-link" href="' . esc_url($link) . '" aria-label="View ' . esc_attr($title) . '">';
                      echo '<div class="fp-thumb">';
                        echo $image;
                        echo '<span class="fp-badge">' . esc_html($margin_label) . '</span>';
                      echo '</div>';
                    echo '</a>';

                    echo '<div class="fp-body">';
                      echo '<h3 class="fp-title" itemprop="name"><a href="' . esc_url($link) . '">' . esc_html($title) . '</a></h3>';

                      if ($rating_count > 0) {
                          echo '<div class="fp-rating" itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
                            echo $rating_html;
                            echo ' <span class="fp-review-count">(<span itemprop="reviewCount">' . intval($rating_count) . '</span>)</span>';
                            echo '<meta itemprop="ratingValue" content="' . esc_attr($avg_rating) . '">';
                          echo '</div>';
                      }

                      echo '<div class="fp-pricing">';
                        echo '<div class="fp-col">';
                          echo '<div class="label">Wholesale</div>';
                          echo '<div class="value" itemprop="offers" itemscope itemtype="https://schema.org/Offer">';
                            if ($wholesale_price && is_numeric($wholesale_price)) {
                                echo wc_price($wholesale_price);
                                echo '<meta itemprop="price" content="' . esc_attr($wholesale_price) . '">';
                                echo '<meta itemprop="priceCurrency" content="INR">';
                            } else {
                                echo '<span class="muted">—</span>';
                            }
                          echo '</div>';
                        echo '</div>';
                        echo '<div class="fp-col fp-col-right">';
                          echo '<div class="label">MRP</div>';
                          echo '<div class="value retail">' . ($regular_price ? wc_price($regular_price) : '<span class="muted">—</span>') . '</div>';
                        echo '</div>';
                      echo '</div>';

                      echo '<div class="fp-meta">MOQ: <span class="moq">' . esc_html($moq) . '</span></div>';

                      echo '<div class="fp-actions">';
                        if ($product->is_type('simple')) {
                            $add_url = esc_url(add_query_arg('add-to-cart', $pid, wc_get_cart_url()));
                            echo '<a href="' . $add_url . '" class="btn btn-primary fp-order-now" data-product-id="' . esc_attr($pid) . '">Order Now</a>';
                        } else {
                            echo '<a href="' . esc_url($link) . '" class="btn btn-primary fp-order-now">View Product</a>';
                        }
                        echo '<button class="btn btn-outline fp-quickview" data-product-id="' . esc_attr($pid) . '" aria-label="Quick view ' . esc_attr($title) . '">Quick View</button>';
                      echo '</div>';

                    echo '</div>';
                  echo '</article>';
              }
          } else {
              echo '<div class="no-products">';
                echo '<p>No featured products found. Mark products as "Featured" in the product list to display them here.</p>';
              echo '</div>';
          }
      } else {
          echo '<div class="no-products">';
            echo '<p>Please install and activate WooCommerce to display featured products.</p>';
          echo '</div>';
      }
      ?>
      </div>
    </div>
  </section>

  <!-- RESELLER CTA SECTION -->
  <section class="reseller-cta-section">
    <div class="container">
      <h2>Ready to Start Your Journey?</h2>
      <p>Join 10,000+ resellers who are building successful businesses with Aakaari</p>
      <div class="cta-button-wrapper">
    <?php
    // Use the same logic as the hero button
    $reseller_page_id = get_option('reseller_page_id');
    $reseller_link = $reseller_page_id ? get_permalink($reseller_page_id) : home_url('/become-a-reseller/');
    $login_link = home_url('/login/');
    $dashboard_link = home_url('/dashboard/');

    if (is_user_logged_in()) {
        $final_reseller_href = $dashboard_link;
    } else {
        $final_reseller_href = add_query_arg('redirect_to', urlencode($reseller_link), $login_link);
    }
    ?>
    <a href="<?php echo esc_url($final_reseller_href); ?>" class="btn-reseller">
        Become a Reseller Today
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
            <path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/>
        </svg>
    </a>
</div>
    </div>
  </section>

</main>

<?php get_footer(); ?>