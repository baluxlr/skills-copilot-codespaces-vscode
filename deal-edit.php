<?php
/**
 * Deal edit template for admin
 */
defined('ABSPATH') || exit;

$post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
$post = $post_id ? get_post($post_id) : null;

// Get deal meta if editing
$organization_name = '';
$sector = '';
$funding_ask = '';
$funding_stage = '';
$equity_offered = '';
$minimum_investment = '';
$location = '';
$company_description = '';
$traction = '';
$video_url = '';
$team_members = array();
$milestones = array();

if ($post) {
    $organization_name = get_post_meta($post_id, 'organization_name', true);
    $sector = get_post_meta($post_id, 'sector', true);
    $funding_ask = get_post_meta($post_id, 'funding_ask', true);
    $funding_stage = get_post_meta($post_id, 'funding_stage', true);
    $equity_offered = get_post_meta($post_id, 'equity_offered', true);
    $minimum_investment = get_post_meta($post_id, 'minimum_investment', true);
    $location = get_post_meta($post_id, 'location', true);
    $company_description = get_post_meta($post_id, 'company_description', true);
    $traction = get_post_meta($post_id, 'traction', true);
    $video_url = get_post_meta($post_id, 'video_url', true);
    $team_members = get_post_meta($post_id, 'team_members', true) ?: array();
    $milestones = get_post_meta($post_id, 'milestones', true) ?: array();
}
?>

<div class="wrap">
    <h1><?php echo $post ? __('Edit Deal', 'dealroom') : __('Add New Deal', 'dealroom'); ?></h1>

    <form id="dealroom-admin-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('dealroom_save_deal', 'dealroom_nonce'); ?>
        <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <!-- Title -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Deal Title', 'dealroom'); ?></h2>
                        <div class="inside">
                            <input type="text" name="post_title" value="<?php echo $post ? esc_attr($post->post_title) : ''; ?>" class="large-text" required>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Deal Description', 'dealroom'); ?></h2>
                        <div class="inside">
                            <?php 
                            wp_editor(
                                $post ? $post->post_content : '',
                                'post_content',
                                array(
                                    'media_buttons' => true,
                                    'textarea_rows' => 10
                                )
                            );
                            ?>
                        </div>
                    </div>

                    <!-- Company Details -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Company Details', 'dealroom'); ?></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><label for="organization_name"><?php _e('Organization Name', 'dealroom'); ?></label></th>
                                    <td>
                                        <input type="text" id="organization_name" name="organization_name" value="<?php echo esc_attr($organization_name); ?>" class="regular-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="sector"><?php _e('Sector', 'dealroom'); ?></label></th>
                                    <td>
                                        <select id="sector" name="sector" required>
                                            <option value=""><?php _e('-- Select Sector --', 'dealroom'); ?></option>
                                            <?php foreach (DealRoom_Utilities::get_sectors() as $s): ?>
                                                <option value="<?php echo esc_attr($s); ?>" <?php selected($sector, $s); ?>><?php echo esc_html($s); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="location"><?php _e('Location', 'dealroom'); ?></label></th>
                                    <td>
                                        <input type="text" id="location" name="location" value="<?php echo esc_attr($location); ?>" class="regular-text">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="company_description"><?php _e('Company Description', 'dealroom'); ?></label></th>
                                    <td>
                                        <textarea id="company_description" name="company_description" rows="5" class="large-text"><?php echo esc_textarea($company_description); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Investment Details -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Investment Details', 'dealroom'); ?></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><label for="funding_ask"><?php _e('Funding Ask ($)', 'dealroom'); ?></label></th>
                                    <td>
                                        <input type="number" id="funding_ask" name="funding_ask" value="<?php echo esc_attr($funding_ask); ?>" class="regular-text" required>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="funding_stage"><?php _e('Funding Stage', 'dealroom'); ?></label></th>
                                    <td>
                                        <select id="funding_stage" name="funding_stage">
                                            <option value=""><?php _e('-- Select Stage --', 'dealroom'); ?></option>
                                            <?php foreach (DealRoom_Utilities::get_funding_stages() as $stage): ?>
                                                <option value="<?php echo esc_attr($stage); ?>" <?php selected($funding_stage, $stage); ?>><?php echo esc_html($stage); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="equity_offered"><?php _e('Equity Offered (%)', 'dealroom'); ?></label></th>
                                    <td>
                                        <input type="number" id="equity_offered" name="equity_offered" value="<?php echo esc_attr($equity_offered); ?>" class="regular-text" min="0" max="100" step="0.01">
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="minimum_investment"><?php _e('Minimum Investment ($)', 'dealroom'); ?></label></th>
                                    <td>
                                        <input type="number" id="minimum_investment" name="minimum_investment" value="<?php echo esc_attr($minimum_investment); ?>" class="regular-text">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Files and Media -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Files & Media', 'dealroom'); ?></h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th><label for="featured_image"><?php _e('Featured Image', 'dealroom'); ?></label></th>
                                    <td>
                                        <div id="featured-image-container">
                                            <?php if ($post && has_post_thumbnail($post->ID)): ?>
                                                <?php echo get_the_post_thumbnail($post->ID, 'medium'); ?>
                                                <button type="button" class="button remove-featured-image"><?php _e('Remove', 'dealroom'); ?></button>
                                            <?php endif; ?>
                                            <button type="button" class="button upload-featured-image"><?php _e('Set Featured Image', 'dealroom'); ?></button>
                                            <input type="hidden" name="featured_image_id" id="featured_image_id" value="<?php echo get_post_thumbnail_id($post_id); ?>">
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="video_url"><?php _e('Video URL', 'dealroom'); ?></label></th>
                                    <td>
                                        <input type="url" id="video_url" name="video_url" value="<?php echo esc_url($video_url); ?>" class="large-text">
                                        <p class="description"><?php _e('Enter YouTube or Vimeo video URL', 'dealroom'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="postbox-container-1" class="postbox-container">
                    <!-- Publishing Actions -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Publishing', 'dealroom'); ?></h2>
                        <div class="inside">
                            <div class="submitbox">
                                <div id="major-publishing-actions">
                                    <div id="publishing-action">
                                        <input type="submit" name="publish" id="publish" class="button button-primary button-large" value="<?php echo $post ? esc_attr__('Update', 'dealroom') : esc_attr__('Publish', 'dealroom'); ?>">
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deal Status -->
                    <div class="postbox">
                        <h2 class="hndle"><?php _e('Deal Status', 'dealroom'); ?></h2>
                        <div class="inside">
                            <select name="post_status" id="post_status">
                                <option value="draft" <?php selected($post ? $post->post_status : '', 'draft'); ?>><?php _e('Draft', 'dealroom'); ?></option>
                                <option value="pending" <?php selected($post ? $post->post_status : '', 'pending'); ?>><?php _e('Pending Review', 'dealroom'); ?></option>
                                <?php if (current_user_can('publish_posts')): ?>
                                    <option value="publish" <?php selected($post ? $post->post_status : '', 'publish'); ?>><?php _e('Published', 'dealroom'); ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.inside p {
    margin: 1em 0;
}

#featured-image-container img {
    max-width: 100%;
    height: auto;
    margin-bottom: 10px;
}

.form-table th {
    width: 200px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle featured image
    $('.upload-featured-image').on('click', function(e) {
        e.preventDefault();
        
        const frame = wp.media({
            title: '<?php _e('Select Featured Image', 'dealroom'); ?>',
            button: {
                text: '<?php _e('Set Featured Image', 'dealroom'); ?>'
            },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#featured-image-container').html(`
                <img src="${attachment.url}" alt="">
                <button type="button" class="button remove-featured-image"><?php _e('Remove', 'dealroom'); ?></button>
                <button type="button" class="button upload-featured-image"><?php _e('Change Image', 'dealroom'); ?></button>
                <input type="hidden" name="featured_image_id" value="${attachment.id}">
            `);
        });

        frame.open();
    });

    // Handle featured image removal
    $(document).on('click', '.remove-featured-image', function() {
        $('#featured-image-container').html(`
            <button type="button" class="button upload-featured-image"><?php _e('Set Featured Image', 'dealroom'); ?></button>
            <input type="hidden" name="featured_image_id" value="">
        `);
    });
});
</script>