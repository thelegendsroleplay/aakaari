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

    if (is_user_logged_in()) {
        $final_reseller_href = function_exists('aakaari_get_dashboard_url') ? aakaari_get_dashboard_url() : home_url('/my-account/');
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
          <h3>Browse & Order Products</h3>
          <p>Order any product at wholesale prices. Single units allowed, no MOQ required.</p>
        </div>

        <div class="how-step">
          <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
              <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.496 6.033h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286a.237.237 0 0 0 .241.247zm2.325 6.443c.61 0 1.029-.394 1.029-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94 0 .533.425.927 1.01.927z"/>
            </svg>
          </div>
          <div class="step-num">03</div>
          <h3>Set Your Price & Sell Under Your Brand</h3>
          <p>Set your own price and collect payments directly. Pay Aakaari the base price when ordering.</p>
        </div>

        <div class="how-step">
          <div class="icon-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" viewBox="0 0 16 16">
              <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
              <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
            </svg>
          </div>
          <div class="step-num">04</div>
          <h3>Place Order & Track Delivery</h3>
          <p>Pay base price upfront. We handle printing, shipping, and provide tracking. (No COD.)</p>
        </div>
      </div>
    </div>
  </section>

  <!-- CUSTOMER REVIEWS SECTION -->
  <section class="aakaari-customer-reviews">
    <div class="container">
      <div class="reviews-header">
        <div class="reviews-title">
          <h2>What Our Resellers Say</h2>
          <p class="muted">Real feedback from verified resellers</p>
        </div>
      </div>

      <div class="reviews-grid">
        <article class="review-card">
          <div class="review-rating">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
          </div>
          <p class="review-text">"Aakaari has completely transformed my business. The profit margins are incredible, and the support team is always there when I need them. Highly recommended!"</p>
          <div class="review-author">
            <div class="author-info">
              <strong>Rajesh Kumar</strong>
              <span>Mumbai, Maharashtra</span>
            </div>
          </div>
        </article>

        <article class="review-card">
          <div class="review-rating">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
          </div>
          <p class="review-text">"The quality of products and fast shipping has helped me build a loyal customer base. My monthly earnings have doubled since joining Aakaari!"</p>
          <div class="review-author">
            <div class="author-info">
              <strong>Priya Sharma</strong>
              <span>Delhi, NCR</span>
            </div>
          </div>
        </article>

        <article class="review-card">
          <div class="review-rating">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#fbbf24" viewBox="0 0 16 16">
              <path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 0.054z"/>
            </svg>
          </div>
          <p class="review-text">"No inventory management, no storage costs - just pure profit! The dashboard is easy to use and tracking orders is seamless. Best decision I made for my business."</p>
          <div class="review-author">
            <div class="author-info">
              <strong>Amit Patel</strong>
              <span>Ahmedabad, Gujarat</span>
            </div>
          </div>
        </article>
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

    if (is_user_logged_in()) {
        $final_reseller_href = function_exists('aakaari_get_dashboard_url') ? aakaari_get_dashboard_url() : home_url('/my-account/');
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