<?php
/**
 * Email Footer
 */

defined('ABSPATH') || exit;

?>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td align="center" valign="top" style="background-color: #f8fafc; padding: 24px 40px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 8px 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                                <?php echo esc_html(get_bloginfo('name')); ?>
                            </p>
                            <?php if (get_option('woocommerce_email_footer_text')) : ?>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: #94a3b8; line-height: 1.5;">
                                <?php echo wp_kses_post(wpautop(wptexturize(apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'))))); ?>
                            </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
