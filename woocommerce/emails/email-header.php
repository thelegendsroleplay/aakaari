<?php
/**
 * Email Header
 */

defined('ABSPATH') || exit;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title><?php echo get_bloginfo('name', 'display'); ?></title>
</head>
<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" style="background-color: #f1f5f9;">
        <tr>
            <td align="center" valign="top" style="padding: 40px 20px;">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); overflow: hidden;">
                    <!-- Header with Logo -->
                    <tr>
                        <td align="center" valign="top" style="background: linear-gradient(135deg, #2563EB 0%, #1e40af 100%); padding: 32px 20px;">
                            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/logo-3.png'); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" style="max-width: 200px; height: auto; display: block; margin: 0 auto;">
                        </td>
                    </tr>
                    <!-- Email Heading -->
                    <?php if (!empty($email_heading)) : ?>
                    <tr>
                        <td align="center" valign="top" style="padding: 32px 40px 0 40px;">
                            <h2 style="color: #1e293b; font-size: 24px; font-weight: 600; margin: 0 0 8px 0; line-height: 1.3;">
                                <?php echo esc_html($email_heading); ?>
                            </h2>
                            <div style="width: 60px; height: 3px; background: #2563EB; margin: 16px auto 0 auto; border-radius: 2px;"></div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <!-- Content Area -->
                    <tr>
                        <td align="left" valign="top" style="padding: 32px 40px; color: #475569; font-size: 15px; line-height: 1.6;">
