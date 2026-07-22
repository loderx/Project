<?php
/**
 * Plugin Name: LOD Woo AJAX Filters
 * Description: Lightweight WooCommerce product filters with reliable attribute filtering and optional AJAX refresh.
 * Version: 1.0.3
 * Author: LOD Corporation
 * Text Domain: lod-woo-ajax-filters
 * Requires Plugins: woocommerce
 */

defined( 'ABSPATH' ) || exit;

final class LOD_Woo_Ajax_Filters_103 {
    const VERSION = '1.0.3';
    const OPTION  = 'lod_waf_settings';

    public static function init() {
        add_shortcode( 'lod_ajax_filters', array( __CLASS__, 'shortcode' ) );
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
        add_action( 'wp_ajax_lod_waf_products', array( __CLASS__, 'ajax_products' ) );
        add_action( 'wp_ajax_nopriv_lod_waf_products', array( __CLASS__, 'ajax_products' ) );
    }

    private static function defaults() {
        return array(
            'show_price'  => 1,
            'show_counts' => 1,
            'columns'     => 2,
            'attributes'  => array(),
            'button_text' => 'ФИЛТРИРАЙ',
            'clear_text'  => 'ИЗЧИСТИ',
            'ajax'        => 1,
        );
    }

    private static function settings() {
        return wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
    }

    public static function register_settings() {
        register_setting( 'lod_waf_group', self::OPTION, array( __CLASS__, 'sanitize_settings' ) );
    }

    public static function sanitize_settings( $input ) {
        $defaults = self::defaults();
        $output   = $defaults;

        $output['show_price']  = empty( $input['show_price'] ) ? 0 : 1;
        $output['show_counts'] = empty( $input['show_counts'] ) ? 0 : 1;
        $output['ajax']        = empty( $input['ajax'] ) ? 0 : 1;
        $output['columns']     = isset( $input['columns'] ) && 1 === absint( $input['columns'] ) ? 1 : 2;
        $output['button_text'] = isset( $input['button_text'] ) ? sanitize_text_field( $input['button_text'] ) : $defaults['button_text'];
        $output['clear_text']  = isset( $input['clear_text'] ) ? sanitize_text_field( $input['clear_text'] ) : $defaults['clear_text'];
        $output['attributes']  = array();

        if ( ! empty( $input['attributes'] ) && is_array( $input['attributes'] ) ) {
            foreach ( $input['attributes'] as $attribute_id ) {
                $attribute_id = absint( $attribute_id );
                if ( $attribute_id ) {
                    $output['attributes'][] = $attribute_id;
                }
            }
        }

        return $output;
    }

    public static function admin_menu() {
        add_submenu_page(
            'woocommerce',
            'LOD AJAX Filters',
            'LOD AJAX Filters',
            'manage_woocommerce',
            'lod-waf',
            array( __CLASS__, 'settings_page' )
        );
    }

    public static function settings_page() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            return;
        }

        $settings   = self::settings();
        $attributes = function_exists( 'wc_get_attribute_taxonomies' ) ? wc_get_attribute_taxonomies() : array();
        ?>
        <div class="wrap">
            <h1>LOD Woo AJAX Filters v<?php echo esc_html( self::VERSION ); ?></h1>
            <p><strong>Поправка:</strong> атрибутите се филтрират директно по WooCommerce taxonomy slug и цената не се изпраща, докато плъзгачът не бъде променен.</p>
            <form method="post" action="options.php">
                <?php settings_fields( 'lod_waf_group' ); ?>
                <table class="form-table" role="presentation">
                    <tr><th scope="row">AJAX</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[ajax]" value="1" <?php checked( ! empty( $settings['ajax'] ) ); ?>> Обновяване без презареждане</label></td></tr>
                    <tr><th scope="row">Цена</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_price]" value="1" <?php checked( ! empty( $settings['show_price'] ) ); ?>> Показвай филтър по цена</label></td></tr>
                    <tr><th scope="row">Броячи</th><td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_counts]" value="1" <?php checked( ! empty( $settings['show_counts'] ) ); ?>> Показвай брой продукти</label></td></tr>
                    <tr><th scope="row">Колони</th><td><select name="<?php echo esc_attr( self::OPTION ); ?>[columns]"><option value="1" <?php selected( 1, absint( $settings['columns'] ) ); ?>>1</option><option value="2" <?php selected( 2, absint( $settings['columns'] ) ); ?>>2</option></select></td></tr>
                    <tr>
                        <th scope="row">Атрибути</th>
                        <td>
                            <?php if ( empty( $attributes ) ) : ?>
                                <em>Няма създадени глобални WooCommerce атрибути.</em>
                            <?php else : ?>
                                <?php foreach ( $attributes as $attribute ) : ?>
                                    <label style="display:block;margin-bottom:8px">
                                        <input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[attributes][]" value="<?php echo esc_attr( absint( $attribute->attribute_id ) ); ?>" <?php checked( in_array( absint( $attribute->attribute_id ), array_map( 'absint', (array) $settings['attributes'] ), true ) ); ?>>
                                        <?php echo esc_html( $attribute->attribute_label ); ?> <code><?php echo esc_html( wc_attribute_taxonomy_name( $attribute->attribute_name ) ); ?></code>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr><th scope="row">Бутон</th><td><input class="regular-text" type="text" name="<?php echo esc_attr( self::OPTION ); ?>[button_text]" value="<?php echo esc_attr( $settings['button_text'] ); ?>"></td></tr>
                    <tr><th scope="row">Изчистване</th><td><input class="regular-text" type="text" name="<?php echo esc_attr( self::OPTION ); ?>[clear_text]" value="<?php echo esc_attr( $settings['clear_text'] ); ?>"></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private static function base_url() {
        if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
            $url = get_term_link( get_queried_object() );
            if ( ! is_wp_error( $url ) ) {
                return $url;
            }
        }

        $shop_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0;
        return $shop_id > 0 ? get_permalink( $shop_id ) : home_url( '/' );
    }

    private static function context() {
        $context = array(
            'taxonomy' => '',
            'term_id'  => 0,
            'search'   => '',
        );

        if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
            $object = get_queried_object();
            if ( $object instanceof WP_Term ) {
                $context['taxonomy'] = $object->taxonomy;
                $context['term_id']  = (int) $object->term_id;
            }
        }

        if ( is_search() ) {
            $context['search'] = get_search_query();
        }

        return $context;
    }

    private static function request_values( $key ) {
        if ( ! isset( $_GET[ $key ] ) ) {
            return array();
        }

        $raw = wp_unslash( $_GET[ $key ] );
        if ( is_array( $raw ) ) {
            $values = $raw;
        } else {
            $values = explode( ',', sanitize_text_field( $raw ) );
        }

        return array_values( array_unique( array_filter( array_map( 'sanitize_title', $values ) ) ) );
    }

    private static function get_price_bounds() {
        global $wpdb;

        $lookup = isset( $wpdb->wc_product_meta_lookup ) ? $wpdb->wc_product_meta_lookup : $wpdb->prefix . 'wc_product_meta_lookup';
        $row    = $wpdb->get_row(
            "SELECT FLOOR(MIN(min_price)) AS min_price, CEIL(MAX(max_price)) AS max_price FROM {$lookup}",
            ARRAY_A
        );

        return array(
            'min' => isset( $row['min_price'] ) ? (float) $row['min_price'] : 0,
            'max' => isset( $row['max_price'] ) ? (float) $row['max_price'] : 0,
        );
    }

    private static function format_money_plain( $amount ) {
        return wp_strip_all_tags( wc_price( $amount ) );
    }

    private static function render_styles() {
        return '<style id="lod-waf-css">
        .lod-waf{font:inherit;color:inherit}.lod-waf *{box-sizing:border-box}.lod-waf-section{padding:0 0 24px;margin:0 0 28px;border-bottom:1px solid #e5e5e5}.lod-waf-section h3{font-size:18px;line-height:1.25;margin:0 0 22px;font-weight:700}.lod-waf-options{display:grid;gap:10px 18px}.lod-waf-options.cols-2{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}.lod-waf-option{display:flex;align-items:center;gap:8px;min-width:0;cursor:pointer}.lod-waf-option input{position:absolute;opacity:0;pointer-events:none}.lod-waf-option .name{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.lod-waf-option .count{margin-left:auto;border:1px solid #ddd;border-radius:999px;padding:1px 8px;font-size:12px;line-height:18px;min-width:30px;text-align:center}.lod-waf-option input:checked + .name{font-weight:700;color:#e52b57}.lod-waf-actions{display:flex;gap:10px;flex-wrap:wrap}.lod-waf button{border:0;padding:11px 16px;cursor:pointer;font-weight:700}.lod-waf-submit{background:#222;color:#fff}.lod-waf-clear{background:#eee;color:#222}.lod-waf-price-line{display:flex;justify-content:space-between;gap:15px;align-items:center;margin-top:15px}.lod-waf-price-line strong{white-space:nowrap}.lod-waf-ranges{position:relative;height:28px}.lod-waf-ranges input[type=range]{position:absolute;left:0;top:0;width:100%;background:transparent;pointer-events:none;appearance:none;-webkit-appearance:none}.lod-waf-ranges input[type=range]::-webkit-slider-thumb{pointer-events:auto;-webkit-appearance:none;width:16px;height:16px;border-radius:50%;background:#e52b57;cursor:pointer}.lod-waf-ranges input[type=range]::-moz-range-thumb{pointer-events:auto;width:16px;height:16px;border:0;border-radius:50%;background:#e52b57;cursor:pointer}.lod-waf-loading{opacity:.45;pointer-events:none}.lod-waf-status{margin:0 0 16px;font-weight:600}.lod-waf-status:empty{display:none}@media(max-width:520px){.lod-waf-options.cols-2{grid-template-columns:1fr}}
        </style>';
    }

    public static function shortcode() {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return '';
        }

        $settings = self::settings();
        $context  = self::context();
        $bounds   = self::get_price_bounds();

        $price_requested = isset( $_GET['min_price'] ) || isset( $_GET['max_price'] );
        $current_min     = isset( $_GET['min_price'] ) && '' !== $_GET['min_price'] ? (float) wc_format_decimal( wp_unslash( $_GET['min_price'] ) ) : $bounds['min'];
        $current_max     = isset( $_GET['max_price'] ) && '' !== $_GET['max_price'] ? (float) wc_format_decimal( wp_unslash( $_GET['max_price'] ) ) : $bounds['max'];

        ob_start();
        echo self::render_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
        <form class="lod-waf" action="<?php echo esc_url( self::base_url() ); ?>" method="get"
            data-ajax="<?php echo empty( $settings['ajax'] ) ? '0' : '1'; ?>"
            data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
            data-nonce="<?php echo esc_attr( wp_create_nonce( 'lod_waf_nonce' ) ); ?>"
            data-context-taxonomy="<?php echo esc_attr( $context['taxonomy'] ); ?>"
            data-context-term="<?php echo esc_attr( $context['term_id'] ); ?>"
            data-context-search="<?php echo esc_attr( $context['search'] ); ?>">
            <div class="lod-waf-status" aria-live="polite"></div>

            <?php if ( ! empty( $settings['show_price'] ) && $bounds['max'] > $bounds['min'] ) : ?>
                <section class="lod-waf-section lod-waf-price" data-price-dirty="<?php echo $price_requested ? '1' : '0'; ?>">
                    <h3>ФИЛТРИРАНЕ ПО ЦЕНА</h3>
                    <div class="lod-waf-ranges">
                        <input class="lod-min-range" type="range" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>" step="1" value="<?php echo esc_attr( $current_min ); ?>">
                        <input class="lod-max-range" type="range" min="<?php echo esc_attr( $bounds['min'] ); ?>" max="<?php echo esc_attr( $bounds['max'] ); ?>" step="1" value="<?php echo esc_attr( $current_max ); ?>">
                    </div>
                    <div class="lod-waf-price-line"><span>Цена:</span><strong class="lod-price-label"><?php echo esc_html( self::format_money_plain( $current_min ) . ' – ' . self::format_money_plain( $current_max ) ); ?></strong></div>
                    <input class="lod-min-price" type="hidden" name="min_price" value="<?php echo esc_attr( $current_min ); ?>" <?php disabled( ! $price_requested ); ?>>
                    <input class="lod-max-price" type="hidden" name="max_price" value="<?php echo esc_attr( $current_max ); ?>" <?php disabled( ! $price_requested ); ?>>
                </section>
            <?php endif; ?>

            <?php foreach ( (array) $settings['attributes'] as $attribute_id ) :
                $attribute = wc_get_attribute( absint( $attribute_id ) );
                if ( ! $attribute || empty( $attribute->slug ) ) {
                    continue;
                }

                $taxonomy = wc_attribute_taxonomy_name( $attribute->slug );
                if ( ! taxonomy_exists( $taxonomy ) ) {
                    continue;
                }

                $terms = get_terms( array(
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => true,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                ) );
                if ( is_wp_error( $terms ) || empty( $terms ) ) {
                    continue;
                }

                $query_key = 'filter_' . wc_attribute_taxonomy_slug( $taxonomy );
                $selected  = self::request_values( $query_key );
                ?>
                <section class="lod-waf-section" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
                    <h3><?php echo esc_html( function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $attribute->name ) : strtoupper( $attribute->name ) ); ?></h3>
                    <div class="lod-waf-options cols-<?php echo esc_attr( absint( $settings['columns'] ) ); ?>">
                        <?php foreach ( $terms as $term ) : ?>
                            <label class="lod-waf-option">
                                <input type="checkbox" name="<?php echo esc_attr( $query_key ); ?>[]" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( in_array( $term->slug, $selected, true ) ); ?>>
                                <span class="name"><?php echo esc_html( $term->name ); ?></span>
                                <?php if ( ! empty( $settings['show_counts'] ) ) : ?><span class="count"><?php echo esc_html( (int) $term->count ); ?></span><?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <?php if ( $context['search'] ) : ?><input type="hidden" name="s" value="<?php echo esc_attr( $context['search'] ); ?>"><input type="hidden" name="post_type" value="product"><?php endif; ?>
            <div class="lod-waf-actions">
                <button class="lod-waf-submit" type="submit"><?php echo esc_html( $settings['button_text'] ); ?></button>
                <button class="lod-waf-clear" type="button"><?php echo esc_html( $settings['clear_text'] ); ?></button>
            </div>
        </form>
        <script id="lod-waf-js">
        (function(){
            'use strict';
            function qs(s,c){return (c||document).querySelector(s)}
            function qsa(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))}
            function productContainer(){return qs('.products')}
            function paginationContainer(){return qs('.woocommerce-pagination')}
            function formParams(form){
                var fd=new FormData(form), p=new URLSearchParams();
                fd.forEach(function(v,k){
                    if(k.indexOf('filter_')===0 && /\[\]$/.test(k)){k=k.slice(0,-2)}
                    if(p.has(k) && k.indexOf('filter_')===0){p.set(k,p.get(k)+','+v)}else{p.append(k,v)}
                });
                return p;
            }
            function updatePrice(form){
                var box=qs('.lod-waf-price',form); if(!box)return;
                var min=qs('.lod-min-range',box),max=qs('.lod-max-range',box),minH=qs('.lod-min-price',box),maxH=qs('.lod-max-price',box),label=qs('.lod-price-label',box);
                function sync(changed){
                    var a=parseFloat(min.value),b=parseFloat(max.value);
                    if(a>b){if(changed===min){max.value=min.value;b=a}else{min.value=max.value;a=b}}
                    minH.value=a;maxH.value=b;minH.disabled=false;maxH.disabled=false;box.dataset.priceDirty='1';
                    label.textContent=a.toFixed(0)+' – '+b.toFixed(0);
                }
                min.addEventListener('input',function(){sync(min)});max.addEventListener('input',function(){sync(max)});
            }
            function run(form,push){
                var params=formParams(form), url=form.action+(params.toString()?'?'+params.toString():'');
                if(form.dataset.ajax!=='1' || !productContainer()){window.location.href=url;return}
                var request=new URLSearchParams(params);
                request.set('action','lod_waf_products');request.set('nonce',form.dataset.nonce);request.set('context_taxonomy',form.dataset.contextTaxonomy||'');request.set('context_term',form.dataset.contextTerm||'');request.set('context_search',form.dataset.contextSearch||'');
                form.classList.add('lod-waf-loading');var status=qs('.lod-waf-status',form);status.textContent='Зареждане…';
                fetch(form.dataset.ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:request.toString(),credentials:'same-origin'})
                    .then(function(r){return r.json()}).then(function(json){
                        if(!json || !json.success)throw new Error('ajax');
                        var holder=document.createElement('div');holder.innerHTML=json.data.products;
                        var newProducts=qs('.products',holder);var oldProducts=productContainer();
                        if(newProducts){oldProducts.replaceWith(newProducts)}else{oldProducts.outerHTML=json.data.products}
                        var oldPag=paginationContainer();
                        if(oldPag){oldPag.outerHTML=json.data.pagination||''}else if(json.data.pagination){var pc=productContainer();pc.insertAdjacentHTML('afterend',json.data.pagination)}
                        status.textContent=json.data.found+' продукта';
                        if(push!==false)history.pushState({lodWaf:1},'',url);
                        document.dispatchEvent(new CustomEvent('lod_waf_updated',{detail:json.data}));
                        window.scrollTo({top:Math.max(0,productContainer().getBoundingClientRect().top+window.pageYOffset-100),behavior:'smooth'});
                    }).catch(function(){window.location.href=url}).finally(function(){form.classList.remove('lod-waf-loading')});
            }
            qsa('.lod-waf').forEach(function(form){
                updatePrice(form);
                form.addEventListener('submit',function(e){e.preventDefault();run(form,true)});
                var clear=qs('.lod-waf-clear',form);if(clear)clear.addEventListener('click',function(){qsa('input[type=checkbox]',form).forEach(function(i){i.checked=false});var minH=qs('.lod-min-price',form),maxH=qs('.lod-max-price',form);if(minH)minH.disabled=true;if(maxH)maxH.disabled=true;window.location.href=form.action});
            });
            document.addEventListener('click',function(e){var a=e.target.closest('.woocommerce-pagination a');var form=qs('.lod-waf');if(!a||!form||form.dataset.ajax!=='1')return;e.preventDefault();var u=new URL(a.href,location.href);var p=u.searchParams.get('product-page')||u.searchParams.get('paged')||((u.pathname.match(/\/page\/(\d+)\/?/)||[])[1]);var params=formParams(form);if(p)params.set('paged',p);var hidden=qs('input[name=paged]',form);if(!hidden){hidden=document.createElement('input');hidden.type='hidden';hidden.name='paged';form.appendChild(hidden)}hidden.value=p||1;run(form,true)});
            window.addEventListener('popstate',function(){location.reload()});
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    private static function collect_filters() {
        $filters = array();
        foreach ( $_REQUEST as $key => $raw ) {
            if ( 0 !== strpos( $key, 'filter_' ) ) {
                continue;
            }

            $attribute_slug = sanitize_title( substr( sanitize_key( $key ), 7 ) );
            if ( '' === $attribute_slug ) {
                continue;
            }

            $taxonomy = wc_attribute_taxonomy_name( $attribute_slug );
            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }

            $values = is_array( $raw ) ? $raw : explode( ',', sanitize_text_field( wp_unslash( $raw ) ) );
            $values = array_values( array_unique( array_filter( array_map( 'sanitize_title', $values ) ) ) );
            if ( $values ) {
                $filters[ $taxonomy ] = $values;
            }
        }
        return $filters;
    }

    private static function build_tax_query() {
        $tax_query = array( 'relation' => 'AND' );

        $context_taxonomy = isset( $_REQUEST['context_taxonomy'] ) ? sanitize_key( wp_unslash( $_REQUEST['context_taxonomy'] ) ) : '';
        $context_term     = isset( $_REQUEST['context_term'] ) ? absint( $_REQUEST['context_term'] ) : 0;
        if ( $context_taxonomy && $context_term && taxonomy_exists( $context_taxonomy ) ) {
            $tax_query[] = array(
                'taxonomy'         => $context_taxonomy,
                'field'            => 'term_id',
                'terms'            => array( $context_term ),
                'include_children' => true,
                'operator'         => 'IN',
            );
        }

        foreach ( self::collect_filters() as $taxonomy => $slugs ) {
            $tax_query[] = array(
                'taxonomy'         => $taxonomy,
                'field'            => 'slug',
                'terms'            => $slugs,
                'include_children' => false,
                'operator'         => 'IN',
            );
        }

        $visibility = wc_get_product_visibility_term_ids();
        $not_in     = array();
        if ( ! empty( $visibility['exclude-from-catalog'] ) ) {
            $not_in[] = $visibility['exclude-from-catalog'];
        }
        if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && ! empty( $visibility['outofstock'] ) ) {
            $not_in[] = $visibility['outofstock'];
        }
        if ( $not_in ) {
            $tax_query[] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'term_taxonomy_id',
                'terms'    => $not_in,
                'operator' => 'NOT IN',
            );
        }

        return $tax_query;
    }

    private static function build_meta_query() {
        $meta_query = WC()->query->get_meta_query( array(), false );
        $min_set    = isset( $_REQUEST['min_price'] ) && '' !== (string) $_REQUEST['min_price'];
        $max_set    = isset( $_REQUEST['max_price'] ) && '' !== (string) $_REQUEST['max_price'];

        if ( $min_set || $max_set ) {
            $min = $min_set ? (float) wc_format_decimal( wp_unslash( $_REQUEST['min_price'] ) ) : 0;
            $max = $max_set ? (float) wc_format_decimal( wp_unslash( $_REQUEST['max_price'] ) ) : PHP_INT_MAX;
            $meta_query[] = array(
                'key'     => '_price',
                'value'   => array( $min, $max ),
                'compare' => 'BETWEEN',
                'type'    => 'DECIMAL(20,6)',
            );
        }

        return $meta_query;
    }

    public static function ajax_products() {
        check_ajax_referer( 'lod_waf_nonce', 'nonce' );

        if ( ! class_exists( 'WooCommerce' ) ) {
            wp_send_json_error( array( 'message' => 'WooCommerce не е активен.' ), 400 );
        }

        $paged    = isset( $_REQUEST['paged'] ) ? max( 1, absint( $_REQUEST['paged'] ) ) : 1;
        $per_page = max( 1, (int) apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() ) );
        $ordering = WC()->query->get_catalog_ordering_args( isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : '' );

        $args = array(
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'posts_per_page'      => $per_page,
            'paged'               => $paged,
            'tax_query'           => self::build_tax_query(),
            'meta_query'          => self::build_meta_query(),
            'orderby'             => $ordering['orderby'],
            'order'               => $ordering['order'],
        );

        if ( isset( $ordering['meta_key'] ) ) {
            $args['meta_key'] = $ordering['meta_key'];
        }

        if ( ! empty( $_REQUEST['s'] ) ) {
            $args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
        } elseif ( ! empty( $_REQUEST['context_search'] ) ) {
            $args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['context_search'] ) );
        }

        $query = new WP_Query( $args );

        ob_start();
        if ( $query->have_posts() ) {
            woocommerce_product_loop_start();
            while ( $query->have_posts() ) {
                $query->the_post();
                wc_get_template_part( 'content', 'product' );
            }
            woocommerce_product_loop_end();
        } else {
            echo '<div class="woocommerce-info">Не бяха намерени продукти, отговарящи на критериите Ви.</div>';
        }
        $products = ob_get_clean();

        $old_query          = $GLOBALS['wp_query'];
        $GLOBALS['wp_query'] = $query;
        ob_start();
        woocommerce_pagination();
        $pagination = ob_get_clean();
        $GLOBALS['wp_query'] = $old_query;

        wp_reset_postdata();

        wp_send_json_success( array(
            'products'   => $products,
            'pagination' => $pagination,
            'found'      => (int) $query->found_posts,
            'pages'      => (int) $query->max_num_pages,
        ) );
    }
}

add_action( 'plugins_loaded', array( 'LOD_Woo_Ajax_Filters_103', 'init' ) );
