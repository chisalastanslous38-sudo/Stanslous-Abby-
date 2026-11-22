<?php
/**
 * Plugin Name: Product Search Pro + Product Manager
 * Description: Product search by name or 8-digit ID with owner product management (Phase 2).
 * Version: 3.0.0
 * Author: Product Search Pro
 * Text Domain: product-search-pro
 */
if (!defined('ABSPATH')) exit;

const PSP_VERSION = '3.0.0';
const PSP_POST_TYPE = 'psp_product';

function psp_register_product_type() {
    register_post_type(PSP_POST_TYPE, [
        'labels' => [
            'name' => 'Products', 'singular_name' => 'Product', 'add_new' => 'Add Product',
            'add_new_item' => 'Add New Product', 'edit_item' => 'Edit Product',
            'new_item' => 'New Product', 'view_item' => 'View Product',
            'search_items' => 'Search Products', 'not_found' => 'No products found',
        ],
        'public' => false, 'show_ui' => true, 'show_in_menu' => true,
        'menu_icon' => 'dashicons-products', 'supports' => ['title','editor','thumbnail'],
        'capability_type' => 'post', 'map_meta_cap' => true,
    ]);
}
add_action('init', 'psp_register_product_type');

function psp_add_product_meta_box() {
    add_meta_box('psp_product_details', 'Product Details', 'psp_product_meta_box', PSP_POST_TYPE, 'normal', 'high');
}
add_action('add_meta_boxes', 'psp_add_product_meta_box');

function psp_product_meta_box($post) {
    wp_nonce_field('psp_save_product', 'psp_product_nonce');
    $fields = [
        'product_id' => get_post_meta($post->ID, '_psp_product_id', true),
        'sku' => get_post_meta($post->ID, '_psp_sku', true),
        'category' => get_post_meta($post->ID, '_psp_category', true),
        'price' => get_post_meta($post->ID, '_psp_price', true),
        'sale_price' => get_post_meta($post->ID, '_psp_sale_price', true),
        'stock' => get_post_meta($post->ID, '_psp_stock', true),
        'rating' => get_post_meta($post->ID, '_psp_rating', true),
        'reviews' => get_post_meta($post->ID, '_psp_reviews', true),
    ];
    if ($fields['product_id'] === '') $fields['product_id'] = psp_generate_product_id();
    ?>
    <style>.psp-admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.psp-admin-grid label{font-weight:600;display:block;margin-bottom:5px}.psp-admin-grid input{width:100%}.psp-admin-help{color:#646970;font-size:12px}@media(max-width:700px){.psp-admin-grid{grid-template-columns:1fr}}</style>
    <div class="psp-admin-grid">
      <div><label for="psp_product_id">8-digit Product ID</label><input id="psp_product_id" name="psp_product_id" value="<?php echo esc_attr($fields['product_id']); ?>" maxlength="8" pattern="[0-9]{8}" required><p class="psp-admin-help">Keep leading zeros. Must be unique.</p></div>
      <div><label for="psp_sku">SKU / Reference</label><input id="psp_sku" name="psp_sku" value="<?php echo esc_attr($fields['sku']); ?>"></div>
      <div><label for="psp_category">Category</label><input id="psp_category" name="psp_category" value="<?php echo esc_attr($fields['category']); ?>" placeholder="e.g. Electronics" required></div>
      <div><label for="psp_price">Price</label><input id="psp_price" name="psp_price" type="number" step="0.01" min="0" value="<?php echo esc_attr($fields['price']); ?>" required></div>
      <div><label for="psp_sale_price">Sale price (optional)</label><input id="psp_sale_price" name="psp_sale_price" type="number" step="0.01" min="0" value="<?php echo esc_attr($fields['sale_price']); ?>"></div>
      <div><label for="psp_stock">Stock quantity</label><input id="psp_stock" name="psp_stock" type="number" step="1" min="0" value="<?php echo esc_attr($fields['stock'] === '' ? '0' : $fields['stock']); ?>" required></div>
      <div><label for="psp_rating">Rating (optional, 0–5)</label><input id="psp_rating" name="psp_rating" type="number" step="0.1" min="0" max="5" value="<?php echo esc_attr($fields['rating']); ?>"></div>
      <div><label for="psp_reviews">Review count (optional)</label><input id="psp_reviews" name="psp_reviews" type="number" min="0" value="<?php echo esc_attr($fields['reviews']); ?>"></div>
    </div>
    <p style="margin-top:18px"><strong>Product image:</strong> Use the Featured Image box on the right to upload/select an image.</p>
    <p><strong>Description:</strong> Use the main editor above for the product description.</p>
    <?php
}

function psp_generate_product_id() {
    for ($i=0; $i<20; $i++) {
        $id = str_pad((string) wp_rand(0, 99999999), 8, '0', STR_PAD_LEFT);
        $existing = get_posts(['post_type'=>PSP_POST_TYPE,'post_status'=>['publish','draft','pending','private'],'meta_key'=>'_psp_product_id','meta_value'=>$id,'fields'=>'ids','numberposts'=>1]);
        if (!$existing) return $id;
    }
    return (string) time();
}
function psp_save_product_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['psp_product_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['psp_product_nonce'])), 'psp_save_product')) return;
    if (!current_user_can('edit_post', $post_id)) return;
    $id = isset($_POST['psp_product_id']) ? preg_replace('/\D/', '', wp_unslash($_POST['psp_product_id'])) : '';
    if (strlen($id) !== 8) $id = psp_generate_product_id();
    $duplicate = get_posts(['post_type'=>PSP_POST_TYPE,'post_status'=>['publish','draft','pending','private'],'meta_key'=>'_psp_product_id','meta_value'=>$id,'fields'=>'ids','numberposts'=>2,'exclude'=>[$post_id]]);
    if ($duplicate) $id = psp_generate_product_id();
    update_post_meta($post_id, '_psp_product_id', $id);
    update_post_meta($post_id, '_psp_sku', isset($_POST['psp_sku']) ? sanitize_text_field(wp_unslash($_POST['psp_sku'])) : '');
    update_post_meta($post_id, '_psp_category', isset($_POST['psp_category']) ? sanitize_text_field(wp_unslash($_POST['psp_category'])) : '');
    foreach (['price'=>'_psp_price','sale_price'=>'_psp_sale_price','stock'=>'_psp_stock','rating'=>'_psp_rating','reviews'=>'_psp_reviews'] as $key=>$meta) {
        $val = isset($_POST['psp_'.$key]) ? sanitize_text_field(wp_unslash($_POST['psp_'.$key])) : '';
        if ($val !== '' && is_numeric($val)) update_post_meta($post_id, $meta, max(0, (float)$val)); else delete_post_meta($post_id, $meta);
    }
}
add_action('save_post_'.PSP_POST_TYPE, 'psp_save_product_meta');

function psp_product_status($stock) { $stock=(int)$stock; return $stock<=0?'out_of_stock':($stock<=10?'low_stock':'in_stock'); }
function psp_get_live_products() {
    $posts = get_posts(['post_type'=>PSP_POST_TYPE,'post_status'=>'publish','numberposts'=>-1,'orderby'=>'date','order'=>'DESC']);
    $products=[];
    foreach ($posts as $p) {
        $stock=(int)get_post_meta($p->ID,'_psp_stock',true);
        $image=get_the_post_thumbnail_url($p->ID,'medium');
        $price=(float)get_post_meta($p->ID,'_psp_price',true);
        $sale=get_post_meta($p->ID,'_psp_sale_price',true);
        $products[]=[
            'id'=>(string)get_post_meta($p->ID,'_psp_product_id',true),
            'name'=>get_the_title($p->ID),'category'=>(string)get_post_meta($p->ID,'_psp_category',true),
            'price'=>($sale!=='' && (float)$sale>0 && (float)$sale<$price)?(float)$sale:$price,
            'regular_price'=>$price,'sale_price'=>($sale!==''?(float)$sale:null),'stock'=>$stock,
            'status'=>psp_product_status($stock),'image'=>$image?:'','description'=>wp_strip_all_tags($p->post_content),
            'sku'=>(string)get_post_meta($p->ID,'_psp_sku',true),'rating'=>(float)get_post_meta($p->ID,'_psp_rating',true),
            'reviews'=>(int)get_post_meta($p->ID,'_psp_reviews',true),
        ];
    }
    return $products;
}
// Demo products are shown only until the owner publishes their first product.
function psp_get_sample_products() {
    return [
      ['id'=>'00012345','name'=>'Aether Wireless Headphones','category'=>'Audio','price'=>249.00,'stock'=>42,'status'=>'in_stock','image'=>'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=400&fit=crop','description'=>'Premium noise-cancelling headphones with adaptive sound, 40-hour battery, and memory foam earcups.','sku'=>'ATH-WH-001','rating'=>4.8,'reviews'=>1284],
      ['id'=>'00012346','name'=>'Aether Headphones Pro Max','category'=>'Audio','price'=>399.00,'stock'=>11,'status'=>'low_stock','image'=>'https://images.unsplash.com/photo-1484704849700-f032a568e944?w=400&h=400&fit=crop','description'=>'Flagship model with spatial audio and premium carrying case.','sku'=>'ATH-WH-PRO','rating'=>4.9,'reviews'=>856],
      ['id'=>'00098765','name'=>'ErgoForm Executive Chair','category'=>'Furniture','price'=>589.00,'stock'=>7,'status'=>'low_stock','image'=>'https://images.unsplash.com/photo-1580480055273-228ff5388cf8?w=400&h=400&fit=crop','description'=>'Adjustable ergonomic chair with lumbar support and breathable mesh.','sku'=>'ERG-CH-900','rating'=>4.7,'reviews'=>432],
      ['id'=>'00112233','name'=>'Nova USB-C Hub Elite','category'=>'Accessories','price'=>79.00,'stock'=>156,'status'=>'in_stock','image'=>'https://images.unsplash.com/photo-1625948515291-69613efd103f?w=400&h=400&fit=crop','description'=>'7-in-1 hub with 4K HDMI, USB-C, card readers and fast data transfer.','sku'=>'NVA-HUB-7','rating'=>4.6,'reviews'=>2103],
      ['id'=>'00445566','name'=>'Keychron Mechanical Keyboard Q1','category'=>'Accessories','price'=>169.00,'stock'=>0,'status'=>'out_of_stock','image'=>'https://images.unsplash.com/photo-1618384887929-16ec70fab3b7?w=400&h=400&fit=crop','description'=>'Mechanical keyboard with aluminum body and customizable lighting.','sku'=>'KCN-Q1-RGB','rating'=>4.9,'reviews'=>1742],
      ['id'=>'00778899','name'=>'Clarity 4K Webcam','category'=>'Video','price'=>149.00,'stock'=>28,'status'=>'in_stock','image'=>'https://images.unsplash.com/photo-1587825140708-dfaf72ae4b04?w=400&h=400&fit=crop','description'=>'4K webcam with auto-framing, noise-reduction microphones and privacy shutter.','sku'=>'CLR-CAM-4K','rating'=>4.7,'reviews'=>967],
      ['id'=>'00000001','name'=>'Test Product – Leading Zeros','category'=>'Test','price'=>9.99,'stock'=>999,'status'=>'in_stock','image'=>'https://images.unsplash.com/photo-1560472355-536de3962603?w=400&h=400&fit=crop','description'=>'Demo item to test leading-zero IDs.','sku'=>'TEST-0001','rating'=>5.0,'reviews'=>12],
      ['id'=>'12345678','name'=>'Standard ID Reference Product','category'=>'Test','price'=>19.99,'stock'=>64,'status'=>'in_stock','image'=>'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=400&fit=crop','description'=>'Demo item with a standard 8-digit ID.','sku'=>'TEST-1234','rating'=>4.5,'reviews'=>88],
      ['id'=>'00055667','name'=>'Lumen Desk Lamp Pro','category'=>'Furniture','price'=>129.00,'stock'=>35,'status'=>'in_stock','image'=>'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=400&h=400&fit=crop','description'=>'Smart LED lamp with adjustable color temperature and touch controls.','sku'=>'LMN-DL-PRO','rating'=>4.8,'reviews'=>541],
      ['id'=>'00033445','name'=>'Pulse Fitness Tracker','category'=>'Wearables','price'=>199.00,'stock'=>19,'status'=>'in_stock','image'=>'https://images.unsplash.com/photo-1575311373937-040b8e1fd5b6?w=400&h=400&fit=crop','description'=>'Fitness tracker with activity features and a 7-day battery.','sku'=>'PLS-FT-X1','rating'=>4.6,'reviews'=>3201],
    ];
}
function psp_enqueue_assets() {
    global $post;
    if (is_a($post,'WP_Post') && has_shortcode($post->post_content,'product_search')) {
        wp_enqueue_style('psp-style',plugin_dir_url(__FILE__).'assets/style.css',[],PSP_VERSION);
        wp_enqueue_script('psp-script',plugin_dir_url(__FILE__).'assets/script.js',[],PSP_VERSION,true);
        $live=psp_get_live_products();
        wp_localize_script('psp-script','PSPData',['products'=>$live?:psp_get_sample_products(),'ajax_url'=>admin_url('admin-ajax.php')]);
    }
}
add_action('wp_enqueue_scripts','psp_enqueue_assets');
function psp_shortcode() {
    ob_start(); ?>
    <div class="psp-app" id="psp-app"><div class="psp-container">
      <div class="psp-header"><div class="psp-header-content"><h1 class="psp-title">Product Search</h1><p class="psp-subtitle">Search by name or 8-digit product ID</p></div></div>
      <div class="psp-search-section"><div class="psp-search-wrapper"><div class="psp-search-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg></div><input type="text" id="psp-search-input" class="psp-search-input" placeholder="Search products or enter 8-digit ID..." autocomplete="off" aria-label="Search products"><button type="button" id="psp-clear-btn" class="psp-clear-btn" aria-label="Clear search" style="display:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="18" x2="18" y2="6"></line></svg></button></div><div class="psp-filters" id="psp-filters"><button class="psp-filter-chip active" data-category="all">All</button></div></div>
      <div id="psp-results" class="psp-results"><div class="psp-initial"><div class="psp-initial-icon"><svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.35-4.35"></path></svg></div><h3>Start searching</h3><p>Enter a product name or an 8-digit ID to find what you need</p></div></div>
    </div></div><?php return ob_get_clean();
}
add_shortcode('product_search','psp_shortcode');

function psp_admin_product_columns($columns) { $columns['psp_id']='Product ID'; $columns['psp_category']='Category'; $columns['psp_price']='Price'; $columns['psp_stock']='Stock'; return $columns; }
add_filter('manage_'.PSP_POST_TYPE.'_posts_columns','psp_admin_product_columns');
function psp_admin_product_column_content($column,$post_id) {
    if($column==='psp_id') echo esc_html(get_post_meta($post_id,'_psp_product_id',true));
    if($column==='psp_category') echo esc_html(get_post_meta($post_id,'_psp_category',true));
    if($column==='psp_price') echo esc_html(get_post_meta($post_id,'_psp_price',true));
    if($column==='psp_stock') echo esc_html(get_post_meta($post_id,'_psp_stock',true));
}
add_action('manage_'.PSP_POST_TYPE.'_posts_custom_column','psp_admin_product_column_content',10,2);
function psp_admin_dashboard_notice() {
    $screen=get_current_screen();
    if($screen && $screen->post_type===PSP_POST_TYPE) echo '<div class="notice notice-info"><p><strong>Product Search Pro — Phase 2:</strong> Add or edit products here. Published products automatically appear on the public search page. Draft products stay hidden from visitors.</p></div>';
}
add_action('admin_notices','psp_admin_dashboard_notice');
