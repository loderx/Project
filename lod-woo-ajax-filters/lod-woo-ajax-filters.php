<?php
/**
 * Plugin Name: LOD Woo AJAX Filters
 * Description: Lightweight WooCommerce AJAX filters for price and product attributes.
 * Version: 1.0.2
 * Author: LOD
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 7.0
 */

if (!defined('ABSPATH')) exit;

final class LOD_Woo_Ajax_Filters {
    const VERSION = '1.0.2';
    const OPTION  = 'lod_waf_settings';

    public function __construct() {
        add_action('plugins_loaded', [$this, 'boot']);
    }

    public function boot() {
        if (!class_exists('WooCommerce')) return;

        add_action('wp_enqueue_scripts', [$this, 'assets']);
        add_shortcode('lod_ajax_filters', [$this, 'shortcode']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function assets() {
        if (!is_shop() && !is_product_taxonomy() && !is_product_category() && !is_product_tag()) return;

        wp_enqueue_style(
            'lod-waf',
            plugin_dir_url(__FILE__) . 'assets/lod-waf.css',
            [],
            self::VERSION
        );
        wp_enqueue_script(
            'lod-waf',
            plugin_dir_url(__FILE__) . 'assets/lod-waf.js',
            [],
            self::VERSION,
            true
        );
        wp_localize_script('lod-waf', 'LOD_WAF', [
            'loading' => __('Зареждане…', 'lod-waf'),
            'noProducts' => __('Не бяха намерени продукти, отговарящи на критериите Ви.', 'lod-waf'),
        ]);
    }

    public function defaults() {
        return [
            'show_price' => 1,
            'show_counts' => 1,
            'columns'     => 2,
            'attributes'  => [],
        ];
    }

    public function settings() {
        return wp_parse_args((array) get_option(self::OPTION, []), $this->defaults());
    }

    public function register_settings() {
        register_setting('lod_waf_group', self::OPTION, [$this, 'sanitize_settings']);
    }

    public function sanitize_settings($input) {
        $out = $this->defaults();
        $out['show_price'] = !empty($input['show_price']) ? 1 : 0;
        $out['show_counts'] = !empty($input['show_counts']) ? 1 : 0;
        $out['columns'] = (isset($input['columns']) && (int)$input['columns'] === 1) ? 1 : 2;
        $out['attributes'] = [];

        if (!empty($input['attributes']) && is_array($input['attributes'])) {
            foreach ($input['attributes'] as $attribute_id) {
                $attribute_id = absint($attribute_id);
                if ($attribute_id && wc_get_attribute($attribute_id)) {
                    $out['attributes'][] = $attribute_id;
                }
            }
        }
        return $out;
    }

    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            'LOD AJAX Filters',
            'LOD AJAX Filters',
            'manage_woocommerce',
            'lod-waf',
            [$this, 'settings_page']
        );
    }

    public function settings_page() {
        if (!current_user_can('manage_woocommerce')) return;
        $settings = $this->settings();
        $attributes = wc_get_attribute_taxonomies();
        ?>
        <div class="wrap">
            <h1>LOD Woo AJAX Filters</h1>
            <form method="post" action="options.php">
                <?php settings_fields('lod_waf_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">Цена</th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[show_price]" value="1" <?php checked($settings['show_price'], 1); ?>> Показвай филтър по цена</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Броячи</th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION); ?>[show_counts]" value="1" <?php checked($settings['show_counts'], 1); ?>> Показвай броя на продуктите</label></td>
                    </tr>
                    <tr>
                        <th scope="row">Колони</th>
                        <td>
                            <select name="<?php echo esc_attr(self::OPTION); ?>[columns]">
                                <option value="1" <?php selected($settings['columns'], 1); ?>>1 колона</option>
                                <option value="2" <?php selected($settings['columns'], 2); ?>>2 колони</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Атрибути</th>
                        <td>
                            <?php if ($attributes) : foreach ($attributes as $attribute) : ?>
                                <label style="display:block;margin-bottom:8px;">
                                    <input type="checkbox"
                                           name="<?php echo esc_attr(self::OPTION); ?>[attributes][]"
                                           value="<?php echo esc_attr($attribute->attribute_id); ?>"
                                           <?php checked(in_array((int)$attribute->attribute_id, array_map('intval', $settings['attributes']), true)); ?>>
                                    <?php echo esc_html($attribute->attribute_label); ?>
                                </label>
                            <?php endforeach; else : ?>
                                <p>Няма създадени глобални WooCommerce атрибути.</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcode:</strong> <code>[lod_ajax_filters]</code></p>
        </div>
        <?php
    }

    private function archive_product_ids() {
        $tax_query = [];

        if (is_product_category() || is_product_tag() || is_product_taxonomy()) {
            $term = get_queried_object();
            if ($term instanceof WP_Term) {
                $tax_query[] = [
                    'taxonomy' => $term->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => [(int)$term->term_id],
                ];
            }
        }

        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ];
        if ($tax_query) $args['tax_query'] = $tax_query;

        return get_posts($args);
    }

    private function price_bounds($product_ids) {
        global $wpdb;
        if (!$product_ids) return [0, 0];
        $ids = implode(',', array_map('absint', $product_ids));
        $row = $wpdb->get_row("SELECT MIN(CAST(meta_value AS DECIMAL(20,4))) AS min_price, MAX(CAST(meta_value AS DECIMAL(20,4))) AS max_price FROM {$wpdb->postmeta} WHERE meta_key = '_price' AND post_id IN ($ids)");
        return [
            isset($row->min_price) ? floor((float)$row->min_price) : 0,
            isset($row->max_price) ? ceil((float)$row->max_price) : 0,
        ];
    }

    public function shortcode() {
        if (!function_exists('wc_get_attribute')) return '';

        $settings = $this->settings();
        $product_ids = $this->archive_product_ids();
        $columns = (int)$settings['columns'] === 1 ? 1 : 2;

        ob_start();
        ?>
        <form class="lod-waf" method="get" data-columns="<?php echo esc_attr($columns); ?>">
            <?php
            foreach ($_GET as $key => $value) {
                if (strpos($key, 'filter_') === 0 || in_array($key, ['min_price','max_price','paged','product-page'], true)) continue;
                if (is_array($value)) continue;
                printf('<input type="hidden" name="%s" value="%s">', esc_attr($key), esc_attr(wp_unslash($value)));
            }
            ?>

            <?php if (!empty($settings['show_price'])) :
                [$min, $max] = $this->price_bounds($product_ids);
                $selected_min = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) wc_clean(wp_unslash($_GET['min_price'])) : $min;
                $selected_max = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) wc_clean(wp_unslash($_GET['max_price'])) : $max;
            ?>
                <section class="lod-waf-section lod-waf-price" data-default-min="<?php echo esc_attr($min); ?>" data-default-max="<?php echo esc_attr($max); ?>">
                    <h3>ФИЛТРИРАНЕ ПО ЦЕНА</h3>
                    <div class="lod-waf-range">
                        <input type="range" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>" value="<?php echo esc_attr($selected_min); ?>" step="1" data-role="min-range">
                        <input type="range" min="<?php echo esc_attr($min); ?>" max="<?php echo esc_attr($max); ?>" value="<?php echo esc_attr($selected_max); ?>" step="1" data-role="max-range">
                    </div>
                    <div class="lod-waf-price-row">
                        <span>Цена: <strong data-role="price-label"><?php echo wp_kses_post(wc_price($selected_min)); ?> — <?php echo wp_kses_post(wc_price($selected_max)); ?></strong></span>
                        <button type="submit">ФИЛТЪР</button>
                    </div>
                    <input type="hidden" name="min_price" value="<?php echo isset($_GET['min_price']) ? esc_attr($selected_min) : ''; ?>" data-role="min-price">
                    <input type="hidden" name="max_price" value="<?php echo isset($_GET['max_price']) ? esc_attr($selected_max) : ''; ?>" data-role="max-price">
                </section>
            <?php endif; ?>

            <?php foreach ((array)$settings['attributes'] as $attribute_id) :
                $attribute = wc_get_attribute(absint($attribute_id));
                if (!$attribute) continue;
                $taxonomy = wc_attribute_taxonomy_name($attribute->slug);
                if (!taxonomy_exists($taxonomy)) continue;

                $terms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => true,
                    'object_ids' => $product_ids,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ]);
                if (is_wp_error($terms) || !$terms) continue;

                $param = 'filter_' . wc_attribute_taxonomy_slug($taxonomy);
                $selected = isset($_GET[$param]) ? array_filter(array_map('sanitize_title', explode(',', wc_clean(wp_unslash($_GET[$param]))))) : [];
            ?>
                <section class="lod-waf-section">
                    <h3><?php echo esc_html(mb_strtoupper($attribute->name)); ?></h3>
                    <div class="lod-waf-terms lod-waf-cols-<?php echo esc_attr($columns); ?>">
                        <?php foreach ($terms as $term) : ?>
                            <label class="lod-waf-term">
                                <input type="checkbox" name="<?php echo esc_attr($param); ?>[]" value="<?php echo esc_attr($term->slug); ?>" <?php checked(in_array($term->slug, $selected, true)); ?>>
                                <span class="lod-waf-name"><?php echo esc_html($term->name); ?></span>
                                <?php if (!empty($settings['show_counts'])) : ?><span class="lod-waf-count"><?php echo esc_html($term->count); ?></span><?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <div class="lod-waf-actions">
                <button type="submit" class="lod-waf-apply">ПРИЛОЖИ ФИЛТРИТЕ</button>
                <button type="button" class="lod-waf-clear">ИЗЧИСТИ</button>
            </div>
        </form>
        <?php
        return ob_get_clean();
    }
}

new LOD_Woo_Ajax_Filters();
